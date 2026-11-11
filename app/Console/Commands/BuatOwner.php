<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Support\AturanAkun;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

#[Signature('resto:owner {--nama= : Nama lengkap} {--email= : Email} {--hp= : Nomor HP} {--password= : Password (kosongkan untuk diketik tersembunyi)}')]
#[Description('Buat akun Owner (dipakai saat pemasangan pertama, atau bila semua Owner lupa password / terkunci)')]
class BuatOwner extends Command
{
    public function handle(): int
    {
        $data = [
            'nama' => $this->option('nama') ?: $this->ask('Nama lengkap Owner'),
            'email' => Str::lower(trim((string) ($this->option('email') ?: $this->ask('Email')))),
            'no_hp' => AturanAkun::rapikanHp($this->option('hp') ?: $this->ask('Nomor HP')),
        ];
        $data['password'] = $this->option('password') ?: $this->secret('Password (min. 8 karakter)');

        $cek = Validator::make($data, [
            'nama' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:100', 'unique:users,email'],
            'no_hp' => ['required', 'regex:'.AturanAkun::REGEX_HP],
            'password' => ['required', Password::defaults()],
        ], ['email.unique' => 'Email sudah dipakai akun lain.', 'no_hp.regex' => AturanAkun::PESAN_HP]);
        if ($cek->fails()) {
            foreach ($cek->errors()->all() as $e) {
                $this->error($e);
            }

            return self::FAILURE;
        }

        $owner = User::create($data + ['role' => User::ROLE_OWNER]);
        $owner->forceFill(['email_verified_at' => now()])->save();
        $this->info("Akun Owner {$owner->nama} ({$owner->email}) dibuat. Silakan masuk di halaman login.");

        return self::SUCCESS;
    }
}
