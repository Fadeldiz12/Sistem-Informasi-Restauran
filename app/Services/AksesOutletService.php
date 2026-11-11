<?php

namespace App\Services;

use App\Models\JadwalShift;
use App\Models\KodeDarurat;
use App\Models\PengaturanSistem;
use App\Models\PerangkatOutlet;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\IpUtils;

/**
 * Aturan akses outlet untuk staf: lokasi (IP WiFi outlet atau GPS), perangkat terdaftar, dan jadwal shift.
 * Owner bebas dari aturan lokasi & perangkat (tercatat bila masuk dari luar outlet); kurir bebas dari aturan lokasi & perangkat.
 */
class AksesOutletService
{
    /** Role yang wajib berada di outlet & memakai perangkat terdaftar. */
    public const ROLE_OUTLET = [User::ROLE_ADMIN, User::ROLE_KASIR, User::ROLE_DAPUR];

    /** Role yang dibatasi jadwal shift. */
    public const ROLE_SHIFT = [User::ROLE_ADMIN, User::ROLE_KASIR, User::ROLE_DAPUR, User::ROLE_KURIR];

    public const VIA_IP = 'ip';

    public const VIA_GPS = 'gps';

    public const VIA_KODE = 'kode';

    public const VIA_BEBAS = 'bebas';

    /** GPS harus dikirim ulang paling lambat tiap sekian menit selama sesi yang masuk lewat GPS. */
    public const GPS_KADALUARSA_MENIT = 20;

    private ?PengaturanSistem $pengaturan = null;

    public function pengaturan(): PengaturanSistem
    {
        return $this->pengaturan ??= PengaturanSistem::aktif();
    }

    public function terikatOutlet(User $user): bool
    {
        return in_array($user->role, self::ROLE_OUTLET, true);
    }

    /**
     * Daftar IP / rentang CIDR outlet, satu per baris.
     *
     * @return array<int, string>
     */
    public function daftarIp(): array
    {
        return array_values(array_filter(array_map('trim', preg_split('/[\r\n,]+/', (string) $this->pengaturan()->ip_outlet) ?: [])));
    }

    public function ipDiOutlet(Request $request): bool
    {
        $ip = $this->daftarIp();

        return $ip && IpUtils::checkIp((string) $request->ip(), $ip);
    }

    public function aturanLokasiAktif(): bool
    {
        return $this->daftarIp() !== [] || (bool) $this->pengaturan()->cek_gps;
    }

    public function perangkat(Request $request): ?PerangkatOutlet
    {
        return PerangkatOutlet::dariToken($request->cookie(PerangkatOutlet::COOKIE));
    }

    /**
     * Pemeriksaan saat login (password sudah benar).
     *
     * @return array{status: 'ok'|'verifikasi'|'ditolak', pesan: ?string, via: string, masalah: array<int, string>, bisa_gps: bool}
     */
    public function periksaLogin(Request $request, User $user): array
    {
        $hasil = ['status' => 'ok', 'pesan' => null, 'via' => self::VIA_BEBAS, 'masalah' => [], 'bisa_gps' => false];

        if ($pesan = $this->pesanLuarShift($user)) {
            return ['status' => 'ditolak', 'pesan' => $pesan] + $hasil;
        }
        if (! $this->terikatOutlet($user)) {
            return $hasil;
        }

        if ($this->pengaturan()->wajib_perangkat && ! $this->perangkat($request)) {
            $hasil['masalah'][] = 'perangkat';
        }
        if ($this->aturanLokasiAktif()) {
            if ($this->ipDiOutlet($request)) {
                $hasil['via'] = self::VIA_IP;
            } else {
                $hasil['masalah'][] = 'lokasi';
            }
        }

        if ($hasil['masalah']) {
            $hasil['status'] = 'verifikasi';
            $hasil['bisa_gps'] = $hasil['masalah'] === ['lokasi'] && $this->pengaturan()->cek_gps;
        }

        return $hasil;
    }

    /**
     * Jarak (meter) titik GPS dari restoran dan apakah masih di area outlet.
     *
     * @return array{jarak: int, di_outlet: bool}
     */
    public function cekGps(float $lat, float $lng, float $akurasi): array
    {
        $radius = (int) $this->pengaturan()->radius_outlet_m;
        $jarak = (int) round($this->pengaturan()->jarakKm($lat, $lng) * 1000);

        // akurasi GPS di dalam gedung sering puluhan meter: diberi kelonggaran sebesar akurasinya, paling banyak sebesar radius
        return ['jarak' => $jarak, 'di_outlet' => $jarak <= $radius + min(max($akurasi, 0), $radius)];
    }

    public function kodeDaruratCocok(string $kode): ?KodeDarurat
    {
        $kode = preg_replace('/\D/', '', $kode);
        if (strlen($kode) !== 8) {
            return null;
        }

        return KodeDarurat::aktif()->get()->first(fn (KodeDarurat $k) => Hash::check($kode, $k->kode_hash));
    }

    /**
     * Shift yang sedang berjalan (termasuk 30 menit sebelum & 60 menit sesudah), atau null.
     */
    public function shiftBerjalan(User $user, ?Carbon $waktu = null): ?JadwalShift
    {
        $waktu ??= now();
        $kemarin = $waktu->copy()->subDay()->startOfDay();

        foreach ($user->jadwalShift as $shift) {
            foreach ([$kemarin, $waktu->copy()->startOfDay()] as $hari) {
                if ($shift->hari !== $hari->isoWeekday()) {
                    continue;
                }
                [$mulai, $selesai] = $shift->rentangPada($hari);
                if ($waktu->between($mulai->subMinutes(JadwalShift::MASUK_LEBIH_AWAL_MENIT), $selesai->addMinutes(JadwalShift::TOLERANSI_PULANG_MENIT))) {
                    return $shift;
                }
            }
        }

        return null;
    }

    /**
     * Pesan penolakan bila staf di luar jadwal shift; null bila boleh. Staf tanpa jadwal tidak dibatasi.
     */
    public function pesanLuarShift(User $user): ?string
    {
        if (! $this->pengaturan()->wajib_shift || ! in_array($user->role, self::ROLE_SHIFT, true) || $user->jadwalShift->isEmpty()) {
            return null;
        }
        if ($this->shiftBerjalan($user)) {
            return null;
        }

        $hariIni = $user->jadwalShift->firstWhere('hari', now()->isoWeekday());

        return 'Di luar jadwal shift kamu'.($hariIni ? ' (hari ini '.$hariIni->label().')' : ' (hari ini kamu libur)')
            .'. Akun hanya bisa dipakai mulai '.JadwalShift::MASUK_LEBIH_AWAL_MENIT.' menit sebelum shift sampai '.JadwalShift::TOLERANSI_PULANG_MENIT.' menit sesudahnya. Hubungi Owner bila jadwalmu berubah.';
    }

    /**
     * Ringkasan jadwal untuk ditampilkan, contoh "Sen, Sel, Rab 08:00–16:00 · Sab 10:00–18:00".
     */
    public static function ringkasJadwal(User $user): string
    {
        return $user->jadwalShift->sortBy('hari')->groupBy(fn (JadwalShift $s) => $s->label())
            ->map(fn ($grup, $label) => $grup->map(fn ($s) => mb_substr(JadwalShift::HARI[$s->hari], 0, 3))->implode(', ').' '.$label)
            ->implode(' · ');
    }
}
