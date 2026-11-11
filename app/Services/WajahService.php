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
 * Kamera & pembacaan wajah berjalan di browser (public/js/face-guard.js: MediaPipe Face Landmarker + face-api.js).
 * Server membuat tantangan acak sekali pakai (gerakan + kedipan warna layar), lalu memeriksa ulang semuanya dengan
 * batas di config/face.php dan mencocokkan ciri wajah hanya dengan akun yang login (1:1).
 */
class WajahService
{
    /** Jarak ciri wajah maksimal agar dianggap orang yang sama. */
    public const AMBANG_COCOK = 0.5;

    /** Saat pendaftaran: lebih dekat dari ini ke wajah akun lain = dianggap wajah yang sama, ditolak (satu wajah satu akun). */
    public const AMBANG_MIRIP = 0.55;

    public const MAKS_GAGAL = 3;

    public const LANGKAH = [
        'blink' => 'Kedipkan mata',
        'turn_left' => 'Toleh ke kiri',
        'turn_right' => 'Toleh ke kanan',
        'open_mouth' => 'Buka mulut',
    ];

    private const WARNA = ['red', 'green', 'blue'];

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
     * Konfigurasi yang dikirim ke browser (face-guard.js membaca window.AppConfig).
     *
     * @return array<string, mixed>
     */
    public static function configBrowser(): array
    {
        return [
            'modelsUrl' => asset('vendor/face-api/model'),
            'mediapipeWasm' => asset('vendor/mediapipe/wasm'),
            'face' => collect(config('face'))->except('server')->all(),
        ];
    }

    /**
     * Tantangan acak sekali pakai: kedip + gerakan acak (urutan diacak), urutan warna kedipan layar, jumlah sampel.
     *
     * @return array{nonce: string, steps: array<int, string>, flash: array<int, string>|null, samples: array{before: int, after: int}}
     */
    public function buatTantangan(Request $request, string $untuk): array
    {
        $l = config('face.liveness');
        $steps = collect($l['extra_pool'])->shuffle()->take($l['extra_steps'])->push('blink')->shuffle()->values()->all();

        $flash = null;
        if (config('face.flash.enabled')) {
            do {
                $flash = [];
                for ($i = 0; $i < config('face.flash.count'); $i++) {
                    $pilihan = array_values(array_diff(self::WARNA, [end($flash) ?: null]));
                    $flash[] = $pilihan[random_int(0, count($pilihan) - 1)];
                }
            } while (count(array_unique($flash)) < 3);
        }

        $samples = config($untuk === 'daftar' ? 'face.server.samples_daftar' : 'face.server.samples_login');
        $tantangan = ['nonce' => Str::random(40), 'steps' => $steps, 'flash' => $flash, 'samples' => $samples];
        $request->session()->put("tantangan_wajah.{$untuk}", $tantangan + ['dibuat' => now()->getTimestampMs()]);

        return $tantangan;
    }

    /**
     * Periksa ulang hasil dari browser terhadap tantangan di sesi (sekali pakai) dan batas di config/face.php.
     *
     * @param  array<string, mixed>  $bukti  {nonce, samples: [{descriptor, metrics}], eye_baseline, steps: [{type, value, onset_ms, ms}], flash, camera, duration_ms}
     * @return array{ok: bool, alasan: ?string, liveness: bool, ciri: array<int, array<int, float>>}
     */
    public function periksaBukti(Request $request, string $untuk, array $bukti, ?int $minSampel = null): array
    {
        $t = $request->session()->pull("tantangan_wajah.{$untuk}");
        $gagal = fn (string $alasan, bool $liveness = false) => ['ok' => false, 'alasan' => $alasan, 'liveness' => $liveness, 'ciri' => []];
        $q = config('face.quality');
        $l = config('face.liveness');
        $f = config('face.flash');

        if (! $t || ! hash_equals($t['nonce'], (string) ($bukti['nonce'] ?? ''))) {
            return $gagal('Tantangan sudah tidak berlaku. Ulangi dari awal.');
        }
        $berlalu = now()->getTimestampMs() - $t['dibuat'];
        if ($berlalu > $l['challenge_ttl'] * 1000) {
            return $gagal('Waktu habis. Ulangi verifikasi.');
        }

        // kamera virtual (OBS, ManyCam, DroidCam, ...) bisa memutar video sebagai kamera
        $kamera = (string) ($bukti['camera'] ?? '');
        if ($kamera !== '' && preg_match('/'.config('face.blocked_cameras').'/i', $kamera)) {
            return $gagal("Kamera virtual terdeteksi (\"{$kamera}\"). Gunakan kamera asli perangkat.", true);
        }

        $durasi = (int) ($bukti['duration_ms'] ?? 0);
        if ($durasi < $l['min_duration_ms'] || $durasi > $l['total_timeout'] * 1000 || $durasi > $berlalu + 2000) {
            return $gagal('Durasi verifikasi tidak wajar (diduga rekaman / otomatis).', true);
        }

        // gerakan: urutan sama, reaksi manusia (tidak terlalu cepat / lambat), gerakannya cukup besar
        $langkah = array_values(array_filter((array) ($bukti['steps'] ?? []), 'is_array'));
        if (array_column($langkah, 'type') !== $t['steps']) {
            return $gagal('Gerakan tidak sesuai urutan yang diminta.', true);
        }
        foreach ($langkah as $s) {
            $onset = (int) ($s['onset_ms'] ?? -1);
            $ms = (int) ($s['ms'] ?? -1);
            $nilai = (float) ($s['value'] ?? 0);
            $cukup = match ($s['type']) {
                'blink' => $nilai >= $l['blink_closed'],
                'turn_left', 'turn_right' => $nilai >= $l['turn_deg'],
                'open_mouth' => $nilai >= $l['mouth_open'],
                default => false,
            };
            if ($onset < $l['react_min_ms'] || $onset > $l['react_max_ms'] || $ms < $onset || $ms > $l['step_timeout'] * 1000 || ! $cukup) {
                return $gagal('Gerakan "'.(self::LANGKAH[$s['type']] ?? $s['type']).'" tidak wajar (terlalu cepat, terlambat, atau kurang jelas).', true);
            }
        }

        // pantulan warna layar di kulit wajah: layar HP / video tidak memantulkan pola warna yang sama
        if ($t['flash']) {
            $fl = (array) ($bukti['flash'] ?? []);
            if (($fl['colors'] ?? null) !== $t['flash'] || (int) ($fl['frames'] ?? 0) < count($t['flash']) * 2) {
                return $gagal('Cek pantulan layar tidak lengkap. Ulangi verifikasi.', true);
            }
            if ((float) ($fl['corr'] ?? 0) < $f['min_corr'] || (float) ($fl['amp'] ?? 0) < $f['min_amp']) {
                return $gagal('Pantulan cahaya layar pada wajah tidak terdeteksi (diduga wajah dari layar/video).', true);
            }
        }

        // sampel: jumlah sesuai, tiap sampel lolos cek kualitas (lurus, utuh, tidak tertutup, mata terbuka)
        $perlu = $t['samples']['before'] + $t['samples']['after'];
        $sampel = array_values(array_filter((array) ($bukti['samples'] ?? []), 'is_array'));
        if (count($sampel) !== $perlu || count($sampel) < ($minSampel ?? 0)) {
            return $gagal('Jumlah sampel wajah tidak sesuai. Ulangi verifikasi.');
        }
        $ciri = [];
        foreach ($sampel as $smp) {
            $m = (array) ($smp['metrics'] ?? []);
            $layak = (float) ($m['score'] ?? 0) >= $q['min_score'] && ($m['in_frame'] ?? false) === true
                && abs((float) ($m['yaw'] ?? 99)) <= $q['max_yaw'] && abs((float) ($m['roll'] ?? 99)) <= $q['max_roll']
                && (float) ($m['pitch'] ?? 99) >= $q['pitch_min'] && (float) ($m['pitch'] ?? 99) <= $q['pitch_max']
                && (float) ($m['cover'] ?? 99) < $q['cover_max'] && (float) ($m['blink'] ?? 1) <= $q['eye_open_max'];
            $c = $this->rapikanCiri($smp['descriptor'] ?? null);
            if (! $layak || ! $c) {
                return $gagal('Wajah kurang jelas, tertutup, atau tidak menghadap lurus. Ulangi verifikasi.');
            }
            $ciri[] = $c;
        }

        // sampel sebelum & sesudah gerakan harus orang yang sama (anti ganti orang/foto di tengah jalan)
        $rata = $this->rataRata($ciri);
        foreach ($ciri as $c) {
            if ($this->jarak($rata, $c) > self::ambangCocok() * 0.9) {
                return $gagal('Wajah berubah di tengah verifikasi (diduga ganti orang/foto).', true);
            }
        }
        for ($i = 1; $i < count($ciri); $i++) {
            if ($this->jarak($ciri[0], $ciri[$i]) < 1e-6) {
                return $gagal('Wajah terdeteksi tidak bergerak (diduga foto atau rekaman).', true);
            }
        }

        return ['ok' => true, 'alasan' => null, 'liveness' => true, 'ciri' => $ciri];
    }

    public static function ambangCocok(): float
    {
        return (float) config('face.server.match_threshold', self::AMBANG_COCOK);
    }

    public static function ambangMirip(): float
    {
        return (float) config('face.server.duplicate_threshold', self::AMBANG_MIRIP);
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
            if ($this->jarak($simpan, $c) > self::ambangCocok()) {
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
