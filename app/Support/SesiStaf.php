<?php

namespace App\Support;

use App\Models\LogAktivitas;
use App\Models\PengaturanSistem;
use App\Models\User;
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

    /**
     * Dipanggil tepat setelah login berhasil. Sesi staf di perangkat lain otomatis berakhir.
     */
    public static function mulai(Request $request, User $user): void
    {
        $data = ['gagal_login' => 0];
        if ($user->isStaf()) {
            $data['token_sesi'] = Str::random(64);
            $request->session()->put('token_sesi', $data['token_sesi']);
            LogAktivitas::catat('akun.login', "{$user->nama} masuk sebagai {$user->roleLabel()}", $user, pelaku: $user);
        }
        $user->forceFill($data)->saveQuietly();
        $request->session()->put(['terakhir_aktif' => now()->timestamp, 'login_at' => now()->timestamp]);
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
        $terakhir = $request->session()->get('terakhir_aktif');
        if ($terakhir && now()->timestamp - $terakhir >= self::batasDiamMenit() * 60) {
            return self::ALASAN_DIAM;
        }

        return null;
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
