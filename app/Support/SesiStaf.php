<?php

namespace App\Support;

use App\Models\KodeDarurat;
use App\Models\LogAktivitas;
use App\Models\PengaturanSistem;
use App\Models\User;
use App\Services\AksesOutletService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

/**
 * Sesi login staf: satu akun hanya aktif di satu perangkat, dan otomatis keluar setelah lama diam.
 */
class SesiStaf
{
    public const ALASAN_DIAM = 'diam';

    public const ALASAN_PERANGKAT_LAIN = 'perangkat_lain';

    public const ALASAN_TERKUNCI = 'terkunci';

    public const ALASAN_LUAR_AREA = 'luar_area';

    public const ALASAN_PERANGKAT = 'perangkat';

    public const ALASAN_SHIFT = 'shift';

    /**
     * Dipanggil tepat setelah login berhasil. Sesi staf di perangkat lain otomatis berakhir.
     */
    public static function mulai(Request $request, User $user, string $via = AksesOutletService::VIA_BEBAS, ?KodeDarurat $kode = null): void
    {
        $data = ['gagal_login' => 0];
        if ($user->isStaf()) {
            $data['token_sesi'] = Str::random(64);
            $request->session()->put('token_sesi', $data['token_sesi']);
            LogAktivitas::catat('akun.login', self::kalimatMasuk($user, $via), $user, ['via' => $via], $user);
        }
        $user->forceFill($data)->saveQuietly();
        $request->session()->put([
            'terakhir_aktif' => now()->timestamp,
            'login_at' => now()->timestamp,
            'lokasi_via' => $via,
            'gps_at' => $via === AksesOutletService::VIA_GPS ? now()->timestamp : null,
            'kode_darurat' => $kode?->id,
        ]);
        app(AksesOutletService::class)->perangkat($request)?->forceFill(['terakhir_dipakai_at' => now()])->saveQuietly();
    }

    /**
     * Contoh: "Bima Saputra masuk sebagai Kasir · lewat WiFi outlet · shift 08:00–16:00 (terlambat 12 menit)".
     */
    private static function kalimatMasuk(User $user, string $via): string
    {
        $akses = app(AksesOutletService::class);
        $teks = "{$user->nama} masuk sebagai {$user->roleLabel()}".match ($via) {
            AksesOutletService::VIA_IP => ' · lewat WiFi outlet',
            AksesOutletService::VIA_GPS => ' · lokasi GPS di outlet',
            AksesOutletService::VIA_KODE => ' · memakai KODE DARURAT',
            default => '',
        };

        if ($user->hasRole(User::ROLE_OWNER) && $akses->daftarIp() && ! $akses->ipDiOutlet(request())) {
            $teks .= ' · dari luar outlet';
        }
        if (in_array($user->role, AksesOutletService::ROLE_SHIFT, true) && ($shift = $akses->shiftBerjalan($user))) {
            [$mulai] = $shift->rentangPada(now()->isoWeekday() === $shift->hari ? today() : today()->subDay());
            $telat = (int) $mulai->diffInMinutes(now(), false);
            $teks .= ' · shift '.$shift->label().($telat > 5 ? " (terlambat {$telat} menit)" : '');
        }

        return $teks;
    }

    /**
     * Alasan sesi staf ini harus diakhiri, atau null bila masih sah.
     */
    public static function alasanBerakhir(Request $request, User $user): ?string
    {
        if (! $user->isStaf()) {
            return null;
        }
        if ($user->terkunci()) {
            return self::ALASAN_TERKUNCI;
        }
        // token kosong = belum pernah login sejak fitur ini ada: belum ada perangkat yang "memiliki" akun
        if ($user->token_sesi && ! hash_equals($user->token_sesi, (string) $request->session()->get('token_sesi'))) {
            return self::ALASAN_PERANGKAT_LAIN;
        }
        if ($alasan = self::alasanAksesOutlet($request, $user)) {
            return $alasan;
        }
        $terakhir = $request->session()->get('terakhir_aktif');
        if ($terakhir && now()->timestamp - $terakhir >= self::batasDiamMenit() * 60) {
            return self::ALASAN_DIAM;
        }

        return null;
    }

    /**
     * Pemeriksaan berkala selama sesi: masih di outlet, perangkat masih terdaftar, masih dalam jam shift.
     */
    private static function alasanAksesOutlet(Request $request, User $user): ?string
    {
        $akses = app(AksesOutletService::class);
        if ($akses->pesanLuarShift($user)) {
            return self::ALASAN_SHIFT;
        }
        if (! $akses->terikatOutlet($user)) {
            return null;
        }

        $via = $request->session()->get('lokasi_via');
        if ($via === AksesOutletService::VIA_KODE) {
            $kode = KodeDarurat::aktif()->find($request->session()->get('kode_darurat'));

            return $kode ? null : self::ALASAN_LUAR_AREA;
        }
        if ($akses->pengaturan()->wajib_perangkat && ! $akses->perangkat($request)) {
            return self::ALASAN_PERANGKAT;
        }
        if (! $akses->aturanLokasiAktif() || $akses->ipDiOutlet($request)) {
            return null;
        }
        // tidak di WiFi outlet: sah bila masuk lewat GPS dan posisi GPS masih dikirim berkala
        $gpsAt = (int) $request->session()->get('gps_at');
        $gpsMasihBaru = $via === AksesOutletService::VIA_GPS && now()->timestamp - $gpsAt < AksesOutletService::GPS_KADALUARSA_MENIT * 60;

        return $gpsMasihBaru ? null : self::ALASAN_LUAR_AREA;
    }

    public static function batasDiamMenit(): int
    {
        return max(5, (int) (PengaturanSistem::aktif()->batas_diam_menit ?: 30));
    }

    /**
     * Akhiri sesi, catat di log, dan kembalikan pesan untuk halaman login.
     */
    public static function akhiri(Request $request, User $user, string $alasan): string
    {
        [$aksi, $log, $pesan] = match ($alasan) {
            self::ALASAN_DIAM => ['akun.keluar_otomatis', "{$user->nama} otomatis keluar setelah ".self::batasDiamMenit().' menit tanpa aktivitas',
                'Kamu otomatis keluar karena tidak ada aktivitas selama '.self::batasDiamMenit().' menit. Silakan masuk lagi.'],
            self::ALASAN_PERANGKAT_LAIN => ['akun.diambil_alih', "Sesi {$user->nama} di perangkat ini diakhiri karena akunnya masuk di perangkat lain",
                'Akunmu baru saja dipakai masuk di perangkat lain, jadi sesi di perangkat ini diakhiri. Jika itu bukan kamu, segera ganti password dan hubungi Owner.'],
            self::ALASAN_LUAR_AREA => ['akun.keluar_area', "Sesi {$user->nama} diakhiri karena tidak lagi terdeteksi di outlet (IP {$request->ip()})",
                'Kamu keluar otomatis karena tidak lagi terdeteksi di area outlet. Masuk lagi dari WiFi outlet, atau minta kode darurat ke Owner.'],
            self::ALASAN_PERANGKAT => ['akun.keluar_area', "Sesi {$user->nama} diakhiri karena perangkatnya tidak (lagi) terdaftar",
                'Perangkat ini tidak terdaftar sebagai perangkat outlet. Masuk dari komputer/tablet outlet.'],
            self::ALASAN_SHIFT => ['akun.keluar_otomatis', "Sesi {$user->nama} diakhiri karena jam shift sudah lewat",
                'Jam shift kamu sudah selesai, jadi sesi diakhiri. Sampai jumpa di shift berikutnya!'],
            default => ['akun.keluar_otomatis', "Sesi {$user->nama} diakhiri karena akunnya terkunci",
                'Akunmu terkunci. Hubungi Owner atau Admin untuk membukanya.'],
        };
        LogAktivitas::catat($aksi, $log, $user, pelaku: $user);

        self::keluar($request, $user);

        return $pesan;
    }

    /**
     * Logout biasa. Token perangkat dihapus hanya bila sesi ini pemiliknya (bukan sesi yang sudah diambil alih).
     */
    public static function keluar(Request $request, User $user): void
    {
        if ($user->token_sesi && hash_equals($user->token_sesi, (string) $request->session()->get('token_sesi'))) {
            $user->forceFill(['token_sesi' => null])->saveQuietly();
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
    }
}
