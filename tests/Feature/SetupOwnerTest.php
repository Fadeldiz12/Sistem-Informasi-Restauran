<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SetupOwnerTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_pertama_dibuat_dari_komputer_server_lalu_halaman_setup_hilang(): void
    {
        $this->get('/login')->assertSee('Buat akun Owner pertama');
        $this->get('/setup')->assertOk()->assertSee('Owner Pertama');

        $this->post('/setup', ['nama' => 'Reza Mahendra', 'email' => 'Reza@Resto.id', 'no_hp' => '0812-3456-7890', 'password' => 'rahasia123', 'password_confirmation' => 'beda'])
            ->assertSessionHasErrors('password');
        $this->post('/setup', ['nama' => 'Reza Mahendra', 'email' => 'Reza@Resto.id', 'no_hp' => '0812-3456-7890', 'password' => 'rahasia123', 'password_confirmation' => 'rahasia123'])
            ->assertRedirect('/owner/dashboard');

        $owner = User::sole();
        $this->assertSame(['owner', 'reza@resto.id'], [$owner->role, $owner->email]);
        $this->assertNotNull($owner->email_verified_at);
        $this->assertAuthenticatedAs($owner);

        $this->post('/logout');
        $this->get('/setup')->assertNotFound();
        $this->post('/setup', ['nama' => 'Penyusup', 'email' => 'x@x.id', 'no_hp' => '081200000000', 'password' => 'rahasia123', 'password_confirmation' => 'rahasia123'])->assertNotFound();
        $this->get('/login')->assertDontSee('Buat akun Owner pertama');
        $this->assertSame(1, User::count());
    }

    public function test_setup_tidak_bisa_dibuka_dari_komputer_lain(): void
    {
        $this->withServerVariables(['REMOTE_ADDR' => '36.80.1.2'])->get('/setup')->assertNotFound();
        $this->withServerVariables(['REMOTE_ADDR' => '36.80.1.2'])->get('/login')->assertDontSee('Buat akun Owner pertama');
    }

    public function test_perintah_artisan_membuat_owner(): void
    {
        $this->artisan('resto:owner', ['--nama' => 'Reza Mahendra', '--email' => 'reza@resto.id', '--hp' => '081234567890', '--password' => 'rahasia123'])
            ->assertSuccessful();
        $this->assertTrue(User::where('email', 'reza@resto.id')->where('role', User::ROLE_OWNER)->exists());

        $this->artisan('resto:owner', ['--nama' => 'Lain', '--email' => 'reza@resto.id', '--hp' => '081234567890', '--password' => 'rahasia123'])
            ->expectsOutputToContain('Email sudah dipakai')->assertFailed();
        $this->post('/login', ['email' => 'reza@resto.id', 'password' => 'rahasia123'])->assertRedirect('/owner/dashboard');
    }

    public function test_perintah_reset_password_membuka_kunci(): void
    {
        $kasir = User::factory()->create(['role' => User::ROLE_KASIR, 'email' => 'bima@resto.id', 'terkunci_at' => now(), 'gagal_login' => 10]);

        $this->artisan('resto:password')->expectsOutputToContain('bima@resto.id')->assertSuccessful();
        $this->artisan('resto:password', ['email' => 'tidak@ada.id', '--password' => 'rahasia123'])->assertFailed();
        $this->artisan('resto:password', ['email' => 'Bima@Resto.id', '--password' => 'rahasia123'])->assertSuccessful();

        $this->assertFalse($kasir->fresh()->terkunci());
        $this->post('/login', ['email' => 'bima@resto.id', 'password' => 'rahasia123'])->assertRedirect('/kasir/dashboard');
    }
}
