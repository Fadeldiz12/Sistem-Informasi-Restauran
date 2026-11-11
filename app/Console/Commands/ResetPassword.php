<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

#[Signature('resto:password {email? : Email akun (kosongkan untuk melihat daftar akun staf)} {--password= : Password baru (kosongkan untuk diketik tersembunyi)}')]
#[Description('Atur ulang password sebuah akun dari terminal, sekaligus membuka kuncinya')]
class ResetPassword extends Command
{
    public function handle(): int
    {
        $email = Str::lower(trim((string) $this->argument('email')));
        if ($email === '') {
            $this->table(['Role', 'Nama', 'Email', 'Terkunci'], User::where('role', '!=', User::ROLE_PELANGGAN)->orderBy('role')->get()
                ->map(fn (User $u) => [$u->roleLabel(), $u->nama, $u->email, $u->terkunci() ? 'ya' : '-'])->all());
            $this->line('Contoh: php artisan resto:password owner@simresto.test');

            return self::SUCCESS;
        }

        $user = User::where('email', $email)->first();
        if (! $user) {
            $this->error("Tidak ada akun dengan email {$email}. Jalankan \"php artisan resto:password\" untuk melihat daftar akun.");

            return self::FAILURE;
        }

        $password = $this->option('password') ?: $this->secret('Password baru (min. 8 karakter)');
        $cek = Validator::make(['password' => $password], ['password' => ['required', Password::defaults()]]);
        if ($cek->fails()) {
            $this->error($cek->errors()->first());

            return self::FAILURE;
        }

        $user->forceFill([
            'password' => $password, 'gagal_login' => 0, 'gagal_wajah' => 0, 'terkunci_at' => null,
            'token_sesi' => null, 'email_verified_at' => $user->email_verified_at ?? now(),
        ])->save();
        $this->info("Password {$user->nama} ({$user->roleLabel()}) diganti dan akunnya tidak terkunci. Silakan masuk dengan {$user->email}.");

        return self::SUCCESS;
    }
}
