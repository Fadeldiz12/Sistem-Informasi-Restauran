<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public static function akunRole(): array
    {
        return [
            'admin' => ['admin@simresto.test', '/admin/dashboard', 'Dewi Lestari'],
            'owner' => ['owner@simresto.test', '/owner/dashboard', 'Reza Mahendra'],
            'kasir' => ['kasir@simresto.test', '/kasir/dashboard', 'Bima Saputra'],
            'dapur' => ['dapur@simresto.test', '/dapur/antrean', 'Hendra Wijaya'],
            'kurir' => ['kurir@simresto.test', '/kurir/tugas', 'Andi Pratama'],
            'pelanggan' => ['pelanggan@simresto.test', '/beranda', 'Rina'],
        ];
    }

    #[DataProvider('akunRole')]
    public function test_login_diarahkan_ke_dashboard_sesuai_role(string $email, string $home, string $nama): void
    {
        $this->seed(UserSeeder::class);

        $this->post('/login', ['email' => $email, 'password' => 'password'])
            ->assertRedirect($home);

        $this->assertAuthenticated();
        $this->get($home)->assertOk()->assertSee($nama);
    }

    public function test_login_gagal_dengan_password_salah(): void
    {
        $this->seed(UserSeeder::class);

        $this->from('/login')
            ->post('/login', ['email' => 'admin@simresto.test', 'password' => 'salah'])
            ->assertRedirect('/login')
            ->assertSessionHasErrors(['email' => 'Email atau password salah.']);

        $this->assertGuest();

        // Pesan error dan email lama tampil di halaman login
        $this->followingRedirects()->from('/login')
            ->post('/login', ['email' => 'admin@simresto.test', 'password' => 'salah'])
            ->assertSee('Email atau password salah.')
            ->assertSee('value="admin@simresto.test"', false);
    }

    public function test_tamu_diarahkan_ke_login(): void
    {
        $this->get('/')->assertRedirect('/login');
        $this->get('/admin/dashboard')->assertRedirect('/login');
        $this->get('/beranda')->assertRedirect('/login');
    }

    public function test_role_lain_tidak_bisa_membuka_halaman_admin(): void
    {
        $kasir = User::factory()->create(['role' => User::ROLE_KASIR]);

        $this->actingAs($kasir)->get('/admin/dashboard')->assertForbidden();
        $this->actingAs($kasir)->get('/owner/laporan')->assertForbidden();
        $this->actingAs($kasir)->get('/kasir/dashboard')->assertOk();
    }

    public function test_user_yang_sudah_login_tidak_bisa_membuka_login(): void
    {
        $owner = User::factory()->create(['role' => User::ROLE_OWNER]);

        $this->actingAs($owner)->get('/login')->assertRedirect('/owner/dashboard');
        $this->actingAs($owner)->get('/')->assertRedirect('/owner/dashboard');
    }

    public function test_logout_kembali_ke_login(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->actingAs($admin)->get('/admin/dashboard')->assertSee('data-logout', false)->assertSee('id="logout-form"', false);

        $this->actingAs($admin)->post('/logout')->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_semua_halaman_pelanggan_punya_menu_profil_dan_logout(): void
    {
        $pelanggan = User::factory()->create([
            'role' => User::ROLE_PELANGGAN, 'nama' => 'Rina Amalia', 'email' => 'rina@example.com',
            'no_hp' => '081265432109', 'alamat' => 'Jl. Gatot Subroto No. 12',
        ]);

        foreach (['/beranda', '/keranjang', '/tracking'] as $url) {
            $this->actingAs($pelanggan)->get($url)->assertOk()
                ->assertSee('data-pm', false)
                ->assertSeeInOrder(['Rina Amalia', 'rina@example.com', '081265432109', 'Jl. Gatot Subroto No. 12'])
                ->assertSee('class="pm-out" data-logout', false)
                ->assertSee('id="logout-form"', false);
        }

        $this->actingAs($pelanggan)->post('/logout')->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_registrasi_membuat_akun_pelanggan(): void
    {
        $this->post('/registrasi', [
            'nama' => 'Sari Wulandari',
            'hp' => '0812 3456 7890',
            'email' => 'sari@example.com',
            'alamat' => 'Jl. Merdeka 1',
            'password' => 'rahasia123',
            'password_confirmation' => 'rahasia123',
            'setuju' => 1,
            'role' => 'admin', // harus diabaikan
        ])->assertRedirect('/verifikasi-otp');

        $user = User::where('email', 'sari@example.com')->firstOrFail();
        $this->assertSame(User::ROLE_PELANGGAN, $user->role);
        $this->assertSame('0812 3456 7890', $user->no_hp);
        $this->assertNull($user->email_verified_at);
        $this->assertGuest(); // aktif setelah kode OTP dimasukkan
    }

    public function test_registrasi_validasi(): void
    {
        User::factory()->create(['email' => 'ada@example.com']);

        $this->from('/registrasi')->post('/registrasi', [
            'nama' => '',
            'hp' => 'abc',
            'email' => 'ada@example.com',
            'password' => 'pendek',
            'password_confirmation' => 'beda',
        ])->assertRedirect('/registrasi')
            ->assertSessionHasErrors(['nama', 'hp', 'email', 'password']);

        $this->assertGuest();

        $this->followingRedirects()->from('/registrasi')
            ->post('/registrasi', ['nama' => 'Sari', 'hp' => '0812', 'email' => 'ada@example.com', 'password' => 'rahasia123', 'password_confirmation' => 'rahasia123', 'setuju' => 1])
            ->assertSee('Email sudah terdaftar.')
            ->assertSee('value="Sari"', false);
    }
}
