<?php

namespace App\Services;

use App\Models\LogAktivitas;
use App\Models\PengaturanSistem;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Verifikasi wajah 1:1 dengan tantangan liveness acak.
 *
 * Kamera & pembacaan wajah berjalan di browser (face-api.js). Server membuat tantangan acak sekali pakai,
 * memeriksa urutan & waktunya, kewajaran gerakan, lalu mencocokkan angka ciri wajah dengan data akun yang login saja.
 */
class WajahService
{
    /** Jarak ciri wajah maksimal agar dianggap orang yang sama (face-api: 0,6 = longgar; dipakai 0,5). */
    public const AMBANG_COCOK = 0.5;

    /** Saat pendaftaran: lebih dekat dari ini ke wajah akun lain = dianggap wajah yang sama, ditolak (satu wajah satu akun). */
    public const AMBANG_MIRIP = 0.55;

    public const MAKS_GAGAL = 3;

    public const BERLAKU_DETIK = 60;

    /** Gerakan liveness yang bisa diminta, beserta kalimat petunjuknya. */
    public const AKSI = [
        'kedip' => 'Kedipkan mata',
        'buka_mulut' => 'Buka mulut lebar-lebar',
        'noleh_kiri' => 'Tolehkan kepala ke kiri',
        'noleh_kanan' => 'Tolehkan kepala ke kanan',
    ];

    public function wajib(): bool
    {
        return (bool) PengaturanSistem::aktif()->wajib_wajah;
    }

    /**
     * Staf ini harus lolos verifikasi wajah? (aturan aktif dan wajahnya sudah didaftarkan)
     */
    public function perluVerifikasi(User $user): bool
    {
        return $user->isStaf() && $this->wajib() && $user->wajah()->exists();
    }

    /**
     * Tantangan acak sekali pakai: 2 gerakan berbeda, disimpan di sesi.
     *
     * @return array{nonce: string, langkah: array<int, array{aksi: string, teks: string}>}
     */
    public function buatTantangan(Request $request, string $untuk): array
    {
        $aksi = collect(array_keys(self::AKSI))->shuffle()->take(2)->values()->all();
        $request->session()->put("tantangan_wajah.{$untuk}", ['nonce' => $nonce = Str::random(32), 'aksi' => $aksi, 'dibuat' => now()->getTimestampMs()]);

        return ['nonce' => $nonce, 'langkah' => array_map(fn ($a) => ['aksi' => $a, 'teks' => self::AKSI[$a]], $aksi)];
    }

    /**
     * Periksa bukti dari browser terhadap tantangan di sesi (sekali pakai).
     *
     * @param  array<string, mixed>  $bukti  {nonce, langkah: [{aksi, durasi_ms, nilai}], ciri: [[128 angka], ...]}
     * @return array{ok: bool, alasan: ?string, liveness: bool, ciri: array<int, array<int, float>>}
     */
    public function periksaBukti(Request $request, string $untuk, array $bukti, int $minSampel = 3): array
    {
        $tantangan = $request->session()->pull("tantangan_wajah.{$untuk}");
        $gagal = fn (string $alasan, bool $liveness = false) => ['ok' => false, 'alasan' => $alasan, 'liveness' => $liveness, 'ciri' => []];

        if (! $tantangan || ! hash_equals($tantangan['nonce'], (string) ($bukti['nonce'] ?? ''))) {
            return $gagal('Tantangan sudah tidak berlaku. Ulangi dari awal.');
        }
        if (now()->getTimestampMs() - $tantangan['dibuat'] > self::BERLAKU_DETIK * 1000) {
            return $gagal('Waktu habis. Ulangi dan ikuti petunjuk lebih cepat.');
        }

        $langkah = array_values(array_filter((array) ($bukti['langkah'] ?? []), 'is_array'));
        if (array_column($langkah, 'aksi') !== $tantangan['aksi']) {
            return $gagal('Gerakan tidak sesuai urutan yang diminta.', true);
        }
        foreach ($langkah as $l) {
            $durasi = (int) ($l['durasi_ms'] ?? 0);
            $nilai = (float) ($l['nilai'] ?? 0);
            $wajar = $durasi >= 150 && $durasi <= 20000 && match ($l['aksi']) {
                'kedip' => $nilai > 0 && $nilai <= 0.75,          // mata menutup: rasio mata turun ≤ 75% dari biasanya
                'buka_mulut' => $nilai >= 0.25,                   // bukaan mulut bertambah
                'noleh_kiri' => $nilai >= 0.15,                   // hidung bergeser dari tengah mata
                'noleh_kanan' => $nilai <= -0.15,
                default => false,
            };
            if (! $wajar) {
                return $gagal('Gerakan "'.self::AKSI[$l['aksi']].'" tidak terdeteksi dengan jelas.', true);
            }
        }

        $ciri = array_values(array_filter(array_map([$this, 'rapikanCiri'], (array) ($bukti['ciri'] ?? []))));
        if (count($ciri) < $minSampel) {
            return $gagal('Wajah tidak terbaca dengan jelas. Pastikan wajah terang dan menghadap kamera.');
        }
        // foto/frame yang sama diputar ulang menghasilkan angka yang persis sama
        for ($i = 1; $i < count($ciri); $i++) {
            if ($this->jarak($ciri[0], $ciri[$i]) < 1e-6) {
                return $gagal('Wajah terdeteksi tidak bergerak (diduga foto atau rekaman).', true);
            }
        }

        return ['ok' => true, 'alasan' => null, 'liveness' => true, 'ciri' => $ciri];
    }

    /**
     * @return array<int, float>|null
     */
    public function rapikanCiri(mixed $ciri): ?array
    {
        if (! is_array($ciri) || count($ciri) !== 128) {
            return null;
        }
        $hasil = [];
        foreach (array_values($ciri) as $v) {
            if (! is_numeric($v) || ! is_finite((float) $v) || abs((float) $v) > 1) {
                return null;
            }
            $hasil[] = round((float) $v, 6);
        }

        return $hasil;
    }

    /**
     * @param  array<int, float>  $a
     * @param  array<int, float>  $b
     */
    public function jarak(array $a, array $b): float
    {
        $jumlah = 0.0;
        foreach ($a as $i => $v) {
            $jumlah += ($v - $b[$i]) ** 2;
        }

        return sqrt($jumlah);
    }

    /**
     * @param  array<int, array<int, float>>  $daftar
     * @return array<int, float>
     */
    public function rataRata(array $daftar): array
    {
        $n = count($daftar);

        return array_map(fn ($i) => round(array_sum(array_column($daftar, $i)) / $n, 6), range(0, 127));
    }

    /**
     * Cocokkan sampel dengan wajah milik akun ini saja (1:1). Semua sampel harus cocok.
     *
     * @param  array<int, array<int, float>>  $ciri
     */
    public function cocok(User $user, array $ciri): bool
    {
        $simpan = $user->wajah?->ciri;
        if (! $simpan) {
            return false;
        }
        foreach ($ciri as $c) {
            if ($this->jarak($simpan, $c) > self::AMBANG_COCOK) {
                return false;
            }
        }

        return true;
    }

    /**
     * Catat kegagalan; akun dikunci (dan Owner diberi tahu) setelah gagal berulang atau diduga spoofing.
     */
    public function catatGagal(User $user, string $alasan, bool $dugaSpoofing): void
    {
        $user->forceFill(['gagal_wajah' => $user->gagal_wajah + 1])->saveQuietly();
        LogAktivitas::catat($dugaSpoofing ? 'akun.spoofing' : 'akun.wajah_gagal',
            "Verifikasi wajah {$user->nama} gagal ({$user->gagal_wajah}/".self::MAKS_GAGAL."): {$alasan}", $user, pelaku: $user);

        if ($user->gagal_wajah >= self::MAKS_GAGAL) {
            $user->kunci(self::MAKS_GAGAL.' kali verifikasi wajah gagal');
        }
    }

    public function catatBerhasil(User $user): void
    {
        $user->forceFill(['gagal_wajah' => 0])->saveQuietly();
    }
}
