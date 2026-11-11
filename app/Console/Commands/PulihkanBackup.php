<?php

namespace App\Console\Commands;

use App\Support\BackupTerenkripsi;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('resto:pulihkan {file : Path file .backup} {--force : Tanpa konfirmasi}')]
#[Description('Pulihkan data dari backup terenkripsi (SEMUA data sekarang diganti isi backup)')]
class PulihkanBackup extends Command
{
    public function handle(): int
    {
        $file = $this->argument('file');
        if (! is_file($file)) {
            $this->error("File {$file} tidak ditemukan.");

            return self::FAILURE;
        }
        if (! $this->option('force') && ! $this->confirm('Semua data di database sekarang akan DIGANTI isi backup. Lanjutkan?')) {
            return self::FAILURE;
        }

        try {
            $hasil = BackupTerenkripsi::pulihkan($file);
        } catch (\RuntimeException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }
        $this->info("Data dipulihkan dari backup {$hasil['dibuat']} ({$hasil['tabel']} tabel, {$hasil['baris']} baris).");

        return self::SUCCESS;
    }
}
