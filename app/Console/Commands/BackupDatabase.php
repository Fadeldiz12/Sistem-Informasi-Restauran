<?php

namespace App\Console\Commands;

use App\Support\BackupTerenkripsi;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('resto:backup')]
#[Description('Backup seluruh data ke file terenkripsi di storage/app/private/backup')]
class BackupDatabase extends Command
{
    public function handle(): int
    {
        $hasil = BackupTerenkripsi::buat();
        $dihapus = BackupTerenkripsi::bersihkan((int) config('resto.backup.simpan_hari', 14));

        $this->info("Backup terenkripsi dibuat: {$hasil['path']} ({$hasil['tabel']} tabel, {$hasil['baris']} baris).");
        if ($dihapus) {
            $this->line("{$dihapus} backup lama dihapus.");
        }
        $this->warn('Simpan salinan file backup dan kuncinya (APP_KEY / BACKUP_KEY di .env) di tempat terpisah.');

        return self::SUCCESS;
    }
}
