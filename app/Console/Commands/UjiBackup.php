<?php

namespace App\Console\Commands;

use App\Support\BackupTerenkripsi;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('resto:uji-backup {file? : Path file .backup (bawaan: backup terbaru)}')]
#[Description('Uji pemulihan backup ke database sementara (database utama tidak disentuh)')]
class UjiBackup extends Command
{
    public function handle(): int
    {
        $hasil = BackupTerenkripsi::uji($this->argument('file'));

        if (! $hasil['ok']) {
            $this->error('Uji pemulihan GAGAL: '.$hasil['pesan']);

            return self::FAILURE;
        }
        $this->info($hasil['pesan']);

        return self::SUCCESS;
    }
}
