<?php

namespace App\Console\Commands;

use App\Support\RantaiLog;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

#[Signature('resto:cek-log')]
#[Description('Periksa rantai hash log aktivitas: apakah ada catatan yang diubah, disisipkan, atau dihapus langsung di database')]
class CekLog extends Command
{
    public function handle(): int
    {
        $hasil = RantaiLog::periksa();
        Cache::put(RantaiLog::CACHE, $hasil + ['waktu' => now()->toIso8601String()], now()->addDay());

        if (! $hasil['utuh']) {
            $this->error("Rantai log RUSAK ({$hasil['jumlah']} catatan diperiksa):");
            foreach ($hasil['masalah'] as $m) {
                $this->line(" - {$m}");
            }

            return self::FAILURE;
        }
        $this->info("Rantai log utuh: {$hasil['jumlah']} catatan, tidak ada yang diubah atau dihapus.");

        return self::SUCCESS;
    }
}
