<?php

namespace App\Console\Commands;

use App\Models\LogAktivitas;
use App\Models\User;
use App\Support\DuaFaktor;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('resto:matikan-2fa {email : Email akun Owner/Admin}')]
#[Description('Matikan verifikasi 2 langkah sebuah akun (HP hilang & kode cadangan tidak ada). Hanya dari komputer server.')]
class MatikanDuaFaktor extends Command
{
    public function handle(): int
    {
        $user = User::where('email', mb_strtolower(trim((string) $this->argument('email'))))->first();
        if (! $user || ! DuaFaktor::aktif($user)) {
            $this->error('Akun tidak ditemukan atau verifikasi 2 langkahnya tidak aktif.');

            return self::FAILURE;
        }

        DuaFaktor::matikan($user);
        LogAktivitas::catat('akun.2fa_reset', "Verifikasi 2 langkah {$user->nama} dimatikan lewat perintah server", $user, pelaku: $user);
        $this->info("Verifikasi 2 langkah {$user->nama} dimatikan. Minta ia mengaktifkannya lagi setelah masuk.");

        return self::SUCCESS;
    }
}
