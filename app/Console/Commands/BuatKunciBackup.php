<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('resto:kunci-backup')]
#[Description('Buat kunci acak untuk BACKUP_KEY (kunci backup yang terpisah dari APP_KEY)')]
class BuatKunciBackup extends Command
{
    public function handle(): int
    {
        $kunci = rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');

        $this->info('Kunci backup baru (256 bit):');
        $this->line("BACKUP_KEY={$kunci}");
        $this->newLine();
        $this->line('1. Tempel baris di atas ke file .env, lalu jalankan "php artisan config:clear".');
        $this->line('2. Simpan salinan kunci ini DI LUAR server (password manager / kertas di brankas).');
        $this->line('   Tanpa kunci ini, backup yang dibuat sesudahnya tidak bisa dibuka.');
        $this->line('3. Backup lama (dibuat dengan APP_KEY) tetap bisa dipulihkan selama APP_KEY-nya masih ada.');

        return self::SUCCESS;
    }
}
