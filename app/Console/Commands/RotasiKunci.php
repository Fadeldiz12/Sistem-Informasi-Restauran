<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Support\KunciAplikasi;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

#[Signature('resto:rotasi-kunci {--force : Tanpa konfirmasi}')]
#[Description('Setelah APP_KEY diganti: enkripsi ulang data pribadi & data wajah dengan APP_KEY baru')]
class RotasiKunci extends Command
{
    /**
     * Kolom yang disimpan terenkripsi dengan APP_KEY (cast "encrypted" / "encrypted:array").
     *
     * @var array<string, list<string>>
     */
    public const KOLOM = [
        'users' => ['no_hp', 'alamat', 'dua_faktor_rahasia', 'dua_faktor_cadangan'],
        'pesanan' => ['no_hp_pemesan'],
        'pengiriman' => ['alamat', 'no_hp_penerima', 'patokan'],
        'wajah_staf' => ['ciri'],
    ];

    public function handle(): int
    {
        if (! KunciAplikasi::lama()) {
            $this->warn('APP_PREVIOUS_KEYS masih kosong. Langkah mengganti APP_KEY:');
            $this->line('  1. Salin nilai APP_KEY sekarang ke APP_PREVIOUS_KEYS di .env');
            $this->line('  2. Jalankan "php artisan key:generate" untuk membuat APP_KEY baru');
            $this->line('  3. Jalankan "php artisan config:clear" lalu perintah ini lagi');
            if (! $this->option('force') && ! $this->confirm('Tetap enkripsi ulang semua data dengan APP_KEY sekarang?')) {
                return self::SUCCESS;
            }
        } elseif (! $this->option('force') && ! $this->confirm('Semua data terenkripsi akan dienkripsi ulang dengan APP_KEY baru. Buat backup dulu ("php artisan resto:backup"). Lanjutkan?')) {
            return self::FAILURE;
        }

        $gagal = 0;
        foreach (self::KOLOM as $tabel => $kolom) {
            if (! Schema::hasTable($tabel)) {
                continue;
            }
            [$diubah, $gagalTabel] = $this->ulangTabel($tabel, $kolom);
            $gagal += $gagalTabel;
            $this->line("{$tabel}: {$diubah} baris dienkripsi ulang".($gagalTabel ? ", {$gagalTabel} nilai GAGAL dibuka" : ''));
        }

        if ($gagal) {
            $this->error("{$gagal} nilai tidak bisa dibuka dengan APP_KEY maupun APP_PREVIOUS_KEYS dan dibiarkan apa adanya. Jangan hapus kunci lama dulu.");

            return self::FAILURE;
        }
        $this->info('Selesai. Semua data kini memakai APP_KEY baru.');
        $this->line('Biarkan APP_PREVIOUS_KEYS terisi selama struk/laporan cetakan lama masih perlu dicek keasliannya,');
        $this->line('dan selama backup lama (tanpa BACKUP_KEY) masih mungkin dipulihkan.');

        return self::SUCCESS;
    }

    /**
     * @param  list<string>  $kolom
     * @return array{0: int, 1: int}
     */
    private function ulangTabel(string $tabel, array $kolom): array
    {
        $diubah = 0;
        $gagal = 0;
        DB::table($tabel)->select(array_merge(['id'], $kolom))->orderBy('id')->chunkById(200, function ($baris) use ($tabel, $kolom, &$diubah, &$gagal) {
            DB::transaction(function () use ($baris, $tabel, $kolom, &$diubah, &$gagal) {
                foreach ($baris as $b) {
                    $data = [];
                    foreach ($kolom as $k) {
                        if ($b->{$k} === null || $b->{$k} === '') {
                            continue;
                        }
                        try {
                            $asli = Crypt::decryptString($b->{$k}); // mencoba APP_KEY lalu APP_PREVIOUS_KEYS
                        } catch (\Throwable) {
                            $gagal++;

                            continue;
                        }
                        $data[$k] = Crypt::encryptString($asli);
                        if ($tabel === 'users' && $k === 'no_hp') {
                            $data['no_hp_hash'] = User::hashHp($asli);
                        }
                    }
                    if ($data) {
                        DB::table($tabel)->where('id', $b->id)->update($data);
                        $diubah++;
                    }
                }
            });
        });

        return [$diubah, $gagal];
    }
}
