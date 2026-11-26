<?php

namespace App\Support;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Rantai hash log aktivitas (tamper-evident log). Hash tiap baris = HMAC-SHA256 (kunci turunan APP_KEY) atas
 * hash baris sebelumnya + isi baris. Mengubah, menyisipkan, atau menghapus baris langsung di database
 * (mis. lewat phpMyAdmin) memutus rantai, dan tanpa APP_KEY pelaku tidak bisa menghitung ulang hash-nya.
 */
class RantaiLog
{
    /** Kunci cache hasil pemeriksaan terakhir (ditampilkan di Log Aktivitas & Pemeriksaan Server). */
    public const CACHE = 'rantai_log_hasil';

    /**
     * Hasil pemeriksaan yang disimpan sebentar supaya halaman tidak memeriksa ulang seluruh log setiap dibuka.
     *
     * @return array{utuh: bool, jumlah: int, masalah: list<string>, waktu: string}
     */
    public static function hasil(): array
    {
        return Cache::remember(self::CACHE, now()->addMinutes(10), fn () => self::periksa() + ['waktu' => now()->toIso8601String()]);
    }

    /** Kolom yang ikut di-hash. id_user tidak ikut: kolom itu dikosongkan database saat akunnya dihapus. */
    private const KOLOM = ['created_at', 'nama_user', 'role_user', 'aksi', 'keterangan', 'subjek_tipe', 'subjek_id', 'data', 'ip', 'perangkat'];

    /**
     * @param  array<string, mixed>  $baris  nilai kolom seperti tersimpan di database
     */
    public static function hitung(array $baris, string $sebelum, ?string $appKey = null): string
    {
        $isi = [$sebelum];
        foreach (self::KOLOM as $k) {
            $isi[] = match ($k) {
                'created_at' => self::waktu($baris[$k] ?? null),
                'data' => self::jsonBaku($baris[$k] ?? null),
                default => (string) ($baris[$k] ?? ''),
            };
        }

        return hash_hmac('sha256', implode("\x1F", $isi), self::kunci($appKey ?? KunciAplikasi::sekarang()));
    }

    /**
     * Simpan baris log baru di ujung rantai. Ujung rantai dikunci selama penyimpanan supaya dua permintaan
     * yang bersamaan tidak membuat cabang.
     *
     * @param  callable(string): object  $simpan  menerima hash baris baru, mengembalikan model yang tersimpan
     */
    public static function tambah(array $baris, callable $simpan): object
    {
        return DB::transaction(function () use ($baris, $simpan) {
            $ujung = DB::table('log_rantai')->where('id', 1)->lockForUpdate()->first();
            $hash = self::hitung($baris, (string) ($ujung->hash_terakhir ?? ''));
            $log = $simpan($hash);
            DB::table('log_rantai')->updateOrInsert(['id' => 1], [
                'id_terakhir' => $log->getKey(), 'hash_terakhir' => $hash, 'jumlah' => (int) ($ujung->jumlah ?? 0) + 1,
            ]);

            return $log;
        });
    }

    /**
     * Periksa seluruh rantai dari awal.
     *
     * @return array{utuh: bool, jumlah: int, masalah: list<string>}
     */
    public static function periksa(): array
    {
        $masalah = [];
        $sebelum = '';
        $jumlah = 0;
        $idTerakhir = null;
        $kunci = KunciAplikasi::semua();

        DB::table('log_aktivitas')->orderBy('id')->chunkById(1000, function ($baris) use (&$masalah, &$sebelum, &$jumlah, &$idTerakhir, $kunci) {
            foreach ($baris as $b) {
                $jumlah++;
                $idTerakhir = $b->id;
                $data = (array) $b;
                $cocok = $b->hash !== null && collect($kunci)->contains(fn ($k) => hash_equals(self::hitung($data, $sebelum, $k), $b->hash));
                if (! $cocok && count($masalah) < 10) {
                    $masalah[] = $b->hash === null
                        ? "Catatan #{$b->id} tidak punya hash (disisipkan langsung ke database)."
                        : "Catatan #{$b->id} tidak cocok: isinya diubah, atau catatan sebelumnya dihapus.";
                }
                $sebelum = (string) $b->hash;
            }
        });

        $ujung = DB::table('log_rantai')->where('id', 1)->first();
        if ($ujung && ((int) $ujung->jumlah !== $jumlah || (string) $ujung->hash_terakhir !== $sebelum || $ujung->id_terakhir != $idTerakhir)) {
            $masalah[] = "Jumlah atau ujung rantai berbeda: tercatat {$ujung->jumlah} catatan, ditemukan {$jumlah}. Ada catatan yang dihapus.";
        }

        return ['utuh' => $masalah === [], 'jumlah' => $jumlah, 'masalah' => $masalah];
    }

    private static function kunci(string $appKey): string
    {
        return hash_hmac('sha256', 'rantai-log-aktivitas', KunciAplikasi::biner($appKey), true);
    }

    private static function waktu(mixed $nilai): string
    {
        if ($nilai === null || $nilai === '') {
            return '';
        }

        return ($nilai instanceof \DateTimeInterface ? Carbon::instance($nilai) : Carbon::parse((string) $nilai))->format('Y-m-d H:i:s');
    }

    /**
     * Bentuk JSON baku (kunci diurutkan) supaya hash sama walau MySQL 8 menyimpan ulang JSON dengan urutan/spasi berbeda.
     */
    private static function jsonBaku(mixed $nilai): string
    {
        if ($nilai === null || $nilai === '') {
            return '';
        }
        $data = is_string($nilai) ? json_decode($nilai, true) : $nilai;
        if (! is_array($data)) {
            return (string) json_encode($data);
        }
        $urut = function (array $a) use (&$urut): array {
            ksort($a);

            return array_map(fn ($v) => is_array($v) ? $urut($v) : $v, $a);
        };

        return (string) json_encode($urut($data), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
