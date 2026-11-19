<?php

namespace App\Support;

use Illuminate\Encryption\Encrypter;
use Illuminate\Support\Facades\Artisan;
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

    /** Panjang minimal BACKUP_KEY. */
    public const PANJANG_KUNCI_MIN = 16;

    /**
     * Kunci backup terpisah (BACKUP_KEY) sudah diisi dengan panjang yang cukup.
     */
    public static function kunciTerpisah(): bool
    {
        return mb_strlen((string) config('resto.backup.kunci')) >= self::PANJANG_KUNCI_MIN;
    }

    /**
     * Pengenkripsi untuk backup baru: BACKUP_KEY bila ada, selain itu APP_KEY.
     */
    private static function enkripsi(): Encrypter
    {
        $kunci = filled(config('resto.backup.kunci')) ? (string) config('resto.backup.kunci') : KunciAplikasi::biner(KunciAplikasi::sekarang());
        if ($kunci === '') {
            throw new RuntimeException('Kunci backup belum ada: isi APP_KEY atau BACKUP_KEY di file .env.');
        }
        if (filled(config('resto.backup.kunci')) && ! self::kunciTerpisah()) {
            throw new RuntimeException('BACKUP_KEY terlalu pendek: minimal '.self::PANJANG_KUNCI_MIN.' karakter. Buat yang baru dengan "php artisan resto:kunci-backup".');
        }

        return self::pengenkripsi($kunci);
    }

    /**
     * Semua kunci yang dicoba saat membuka backup: BACKUP_KEY, APP_KEY sekarang, lalu APP_KEY lama
     * (backup yang dibuat sebelum BACKUP_KEY diisi atau sebelum APP_KEY diganti tetap bisa dibuka).
     *
     * @return list<Encrypter>
     */
    private static function semuaPembuka(): array
    {
        $kunci = array_map(fn (string $k) => KunciAplikasi::biner($k), KunciAplikasi::semua());
        if (filled(config('resto.backup.kunci'))) {
            array_unshift($kunci, (string) config('resto.backup.kunci'));
        }

        return array_map(fn (string $k) => self::pengenkripsi($k), array_values(array_unique(array_filter($kunci))));
    }

    private static function pengenkripsi(string $kunci): Encrypter
    {
        return new Encrypter(hash('sha256', 'resto-backup|'.$kunci, true), 'aes-256-gcm');
    }

    /**
     * Buka & uraikan isi file backup tanpa menyentuh database.
     *
     * @return array{dibuat: string, tabel: array<string, array<int, array<string, mixed>>>}
     */
    public static function baca(string $path): array
    {
        $isi = File::get($path);
        if (! str_starts_with($isi, self::AWALAN)) {
            throw new RuntimeException('File ini bukan backup Resto Flow.');
        }
        $sandi = substr($isi, strlen(self::AWALAN));
        foreach (self::semuaPembuka() as $pembuka) {
            try {
                return json_decode(gzuncompress($pembuka->decryptString($sandi)), true, 512, JSON_THROW_ON_ERROR);
            } catch (\Throwable) {
                // coba kunci berikutnya
            }
        }

        throw new RuntimeException('Backup tidak bisa dibuka: kuncinya berbeda (BACKUP_KEY / APP_KEY / APP_PREVIOUS_KEYS) atau file rusak.');
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
    public static function pulihkan(string $path, ?string $koneksi = null): array
    {
        $data = self::baca($path);

        $ada = self::daftarTabel($koneksi);
        $baris = 0;
        Schema::connection($koneksi)->withoutForeignKeyConstraints(function () use ($data, $ada, $koneksi, &$baris) {
            DB::connection($koneksi)->transaction(function () use ($data, $ada, $koneksi, &$baris) {
                foreach ($data['tabel'] as $tabel => $isi) {
                    if (! in_array($tabel, $ada, true)) {
                        continue;
                    }
                    DB::connection($koneksi)->table($tabel)->delete();
                    foreach (array_chunk($isi, 200) as $potong) {
                        DB::connection($koneksi)->table($tabel)->insert($potong);
                    }
                    $baris += count($isi);
                }
            });
        });

        return ['dibuat' => $data['dibuat'], 'tabel' => count($data['tabel']), 'baris' => $baris];
    }

    /**
     * Salin file backup ke folder lain (BACKUP_SALINAN_FOLDER, mis. flashdisk, NAS, atau folder OneDrive/Google Drive)
     * supaya backup tidak ikut hilang bila komputer server rusak. Mengembalikan path salinan, atau null bila belum diatur.
     */
    public static function salin(string $path): ?string
    {
        $folder = self::folderSalinan();
        if (! $folder) {
            return null;
        }
        File::ensureDirectoryExists($folder, 0700);
        $tujuan = $folder.DIRECTORY_SEPARATOR.basename($path);
        if (! File::copy($path, $tujuan)) {
            throw new RuntimeException("Gagal menyalin backup ke {$folder}.");
        }

        return $tujuan;
    }

    public static function folderSalinan(): ?string
    {
        $folder = rtrim((string) config('resto.backup.salinan_folder'), '/\\');

        return $folder !== '' ? $folder : null;
    }

    /**
     * Uji pemulihan sungguhan tanpa menyentuh database utama: backup terbaru dipulihkan ke database SQLite sementara
     * yang dibuat dari migrasi, lalu jumlah baris tiap tabel dicocokkan. Hasilnya dicatat untuk Pemeriksaan Server.
     *
     * @return array{ok: bool, file: ?string, tabel: int, baris: int, pesan: string, waktu: string}
     */
    public static function uji(?string $path = null): array
    {
        $path ??= self::terbaru();
        $hasil = ['ok' => false, 'file' => $path ? basename($path) : null, 'tabel' => 0, 'baris' => 0, 'waktu' => now()->toIso8601String()];
        $dbUji = storage_path('app/private/uji-pemulihan-'.bin2hex(random_bytes(4)).'.sqlite');

        try {
            if (! $path) {
                throw new RuntimeException('Belum ada file backup. Jalankan "php artisan resto:backup" dulu.');
            }
            $data = self::baca($path);
            File::put($dbUji, '');
            config(['database.connections.uji_backup' => ['driver' => 'sqlite', 'database' => $dbUji, 'prefix' => '', 'foreign_key_constraints' => true]]);
            Artisan::call('migrate', ['--database' => 'uji_backup', '--force' => true]);
            $pulih = self::pulihkan($path, 'uji_backup');

            $selisih = collect($data['tabel'])->filter(fn ($isi, $tabel) => Schema::connection('uji_backup')->hasTable($tabel)
                && DB::connection('uji_backup')->table($tabel)->count() !== count($isi))->keys();
            if ($selisih->isNotEmpty()) {
                throw new RuntimeException('Jumlah baris berbeda setelah dipulihkan: '.$selisih->implode(', '));
            }
            $hasil = ['ok' => true, 'tabel' => $pulih['tabel'], 'baris' => $pulih['baris'],
                'pesan' => "Backup {$hasil['file']} berhasil dipulihkan ke database uji ({$pulih['tabel']} tabel, {$pulih['baris']} baris)."] + $hasil;
        } catch (\Throwable $e) {
            $hasil['pesan'] = $e->getMessage();
        } finally {
            DB::purge('uji_backup');
            File::delete($dbUji);
        }

        File::ensureDirectoryExists(self::folder(), 0700);
        File::put(self::folder().'/uji-terakhir.json', json_encode($hasil, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        return $hasil;
    }

    /**
     * Hasil uji pemulihan terakhir, atau null bila belum pernah diuji.
     *
     * @return array{ok: bool, file: ?string, tabel: int, baris: int, pesan: string, waktu: string}|null
     */
    public static function hasilUjiTerakhir(): ?array
    {
        $file = self::folder().'/uji-terakhir.json';

        return File::exists($file) ? json_decode(File::get($file), true) : null;
    }

    public static function terbaru(): ?string
    {
        return collect(File::glob(self::folder().'/resto-*.backup'))->sortBy(fn ($f) => [File::lastModified($f), $f])->last();
    }

    /**
     * Hapus backup yang lebih tua dari batas hari. Mengembalikan jumlah file yang dihapus.
     */
    public static function bersihkan(int $hari): int
    {
        $folder = array_filter([self::folder(), self::folderSalinan()]);
        $hapus = collect($folder)->flatMap(fn ($f) => File::glob($f.'/resto-*.backup'))
            ->filter(fn ($f) => File::lastModified($f) < now()->subDays($hari)->getTimestamp());
        $hapus->each(fn ($f) => File::delete($f));

        return $hapus->count();
    }

    /**
     * Daftar tabel milik database aplikasi saja (bukan semua database di server MySQL).
     *
     * @return array<int, string>
     */
    private static function daftarTabel(?string $koneksi = null): array
    {
        $db = DB::connection($koneksi);
        // MySQL/MariaDB: batasi ke database aplikasi (satu server bisa berisi banyak database).
        // SQLite: nama database berupa path file, bukan nama skema, jadi tidak dipakai sebagai filter.
        $skema = in_array($db->getDriverName(), ['mysql', 'mariadb'], true) ? $db->getDatabaseName() : null;
        $tabel = array_column(Schema::connection($koneksi)->getTables($skema), 'name');

        return array_values(array_diff(array_unique($tabel), self::LEWATI));
    }
}
