<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Akun contoh untuk tiap role. Password semua akun: "password".
 */
class UserSeeder extends Seeder
{
    public function run(): void
    {
        $akun = [
            ['nama' => 'Dewi Lestari', 'email' => 'admin@simresto.test', 'role' => User::ROLE_ADMIN, 'no_hp' => '081322114455'],
            ['nama' => 'Reza Mahendra', 'email' => 'owner@simresto.test', 'role' => User::ROLE_OWNER, 'no_hp' => '081277889900'],
            ['nama' => 'Bima Saputra', 'email' => 'kasir@simresto.test', 'role' => User::ROLE_KASIR, 'no_hp' => '085266123344'],
            ['nama' => 'Hendra Wijaya', 'email' => 'dapur@simresto.test', 'role' => User::ROLE_DAPUR, 'no_hp' => '082144556677'],
            ['nama' => 'Andi Pratama', 'email' => 'kurir@simresto.test', 'role' => User::ROLE_KURIR, 'no_hp' => '082299112233'],
            ['nama' => 'Rina Amalia', 'email' => 'pelanggan@simresto.test', 'role' => User::ROLE_PELANGGAN, 'no_hp' => '081265432109',
                'alamat' => 'Jl. Gatot Subroto No. 12, Medan'],
        ];

        foreach ($akun as $data) {
            User::updateOrCreate(
                ['email' => $data['email']],
                $data + ['password' => 'password', 'alamat' => null],
            );
        }
    }
}
