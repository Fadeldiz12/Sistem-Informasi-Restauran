<?php

namespace App\Support;

use Illuminate\Encryption\Encrypter;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

/**
 * Backup seluruh isi database ke satu file terenkripsi AES-256-GCM (data dikompres lalu dienkripsi),
 * dan pemulihannya. Struktur tabel dibuat lewat migrasi; backup hanya berisi data.
 */
class BackupTerenkripsi
{
    /** Tabel sementara yang tidak perlu dibackup. */
    private const LEWATI = ['sessions', 'cache', 'cache_locks', 'jobs', 'job_batches', 'failed_jobs', 'password_reset_tokens', 'migrations'];

    private const AWALAN = 'RESTOFLOW-BACKUP-1'.PHP_EOL;

    public static function folder(): string
    {
        return storage_path('app/private/backup');
    }

    private static function enkripsi(): Encrypter
    {
        $kunci = config('resto.backup.kunci') ?: config('app.key');
        if (str_starts_with((string) $kunci, 'base64:')) {
            $kunci = base64_decode(substr($kunci, 7));
        }
        if (! $kunci) {
            throw new RuntimeException('Kunci backup belum ada: isi APP_KEY atau BACKUP_KEY di file .env.');
        }

        return new Encrypter(hash('sha256', 'resto-backup|'.$kunci, true), 'aes-256-gcm');
    }

    /**
     * @return array{path: string, tabel: int, baris: int}
     */
    public static function buat(): array
    {
        $data = ['dibuat' => now()->toIso8601String(), 'tabel' => []];
        $baris = 0;
        foreach (self::daftarTabel() as $tabel) {
            $isi = DB::table($tabel)->get()->map(fn ($r) => (array) $r)->all();
            $data['tabel'][$tabel] = $isi;
            $baris += count($isi);
        }

        File::ensureDirectoryExists(self::folder(), 0700);
        $path = self::folder().'/resto-'.now()->format('Ymd-His').'.backup';
        File::put($path, self::AWALAN.self::enkripsi()->encryptString(gzcompress(json_encode($data, JSON_THROW_ON_ERROR | JSON_INVALID_UTF8_SUBSTITUTE), 6)));
        @chmod($path, 0600);

        return ['path' => $path, 'tabel' => count($data['tabel']), 'baris' => $baris];
    }

    /**
     * Ganti seluruh isi database dengan isi backup.
     *
     * @return array{dibuat: string, tabel: int, baris: int}
     */
    public static function pulihkan(string $path): array
    {
        $isi = File::get($path);
        if (! str_starts_with($isi, self::AWALAN)) {
            throw new RuntimeException('File ini bukan backup Resto Flow.');
        }
        try {
            $data = json_decode(gzuncompress(self::enkripsi()->decryptString(substr($isi, strlen(self::AWALAN)))), true, 512, JSON_THROW_ON_ERROR);
        } catch (\Throwable) {
            throw new RuntimeException('Backup tidak bisa dibuka: kuncinya berbeda (APP_KEY / BACKUP_KEY) atau file rusak.');
        }

        $ada = self::daftarTabel();
        $baris = 0;
        Schema::withoutForeignKeyConstraints(function () use ($data, $ada, &$baris) {
            DB::transaction(function () use ($data, $ada, &$baris) {
                foreach ($data['tabel'] as $tabel => $isi) {
                    if (! in_array($tabel, $ada, true)) {
                        continue;
                    }
                    DB::table($tabel)->delete();
                    foreach (array_chunk($isi, 200) as $potong) {
                        DB::table($tabel)->insert($potong);
                    }
                    $baris += count($isi);
                }
            });
        });

        return ['dibuat' => $data['dibuat'], 'tabel' => count($data['tabel']), 'baris' => $baris];
    }

    /**
     * Hapus backup yang lebih tua dari batas hari. Mengembalikan jumlah file yang dihapus.
     */
    public static function bersihkan(int $hari): int
    {
        $hapus = collect(File::glob(self::folder().'/resto-*.backup'))->filter(fn ($f) => File::lastModified($f) < now()->subDays($hari)->getTimestamp());
        $hapus->each(fn ($f) => File::delete($f));

        return $hapus->count();
    }

    /**
     * @return array<int, string>
     */
    private static function daftarTabel(): array
    {
        return array_values(array_diff(array_column(Schema::getTables(), 'name'), self::LEWATI));
    }
}
