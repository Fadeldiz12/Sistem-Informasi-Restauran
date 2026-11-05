<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\ResetPasswordNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as GoogleUser;
use Tests\TestCase;

class AkunTest extends TestCase
{
    use RefreshDatabase;

    private function googleAktif(): void
    {
        config(['services.google.client_id' => 'id-uji', 'services.google.client_secret' => 'rahasia-uji']);
    }

    private function akunGoogle(string $email, bool $verified = true, string $id = 'g-123'): void
    {
        $g = (new GoogleUser)->setRaw(['email_verified' => $verified])
            ->map(['id' => $id, 'name' => 'Sinta Maharani', 'email' => $email]);
        Socialite::shouldReceive('driver->user')->andReturn($g);
    }

    // ---------- login Google

    public function test_halaman_login_hanya_ada_google(): void
    {
        $this->get('/login')->assertOk()->assertSee('Masuk dengan Google')->assertDontSee('Facebook')
            ->assertSee(route('password.request'), false);
        $this->get('/registrasi')->assertOk()->assertSee('Daftar dengan Google')->assertDontSee('Facebook');
    }

    public function test_google_belum_dikonfigurasi(): void
    {
        config(['services.google.client_id' => null]);
        $this->followingRedirects()->get('/auth/google')->assertSee('Login dengan Google belum diaktifkan');
    }

    public function test_google_akun_baru_jadi_pelanggan_lalu_lengkapi_hp(): void
    {
        $this->googleAktif();
        $this->akunGoogle('sinta@gmail.com');

        $this->get('/auth/google/callback')->assertRedirect(route('profil.lengkapi'));
        $user = User::firstWhere('email', 'sinta@gmail.com');
        $this->assertAuthenticatedAs($user);
        $this->assertSame(User::ROLE_PELANGGAN, $user->role);
        $this->assertSame('g-123', $user->google_id);
        $this->assertNull($user->no_hp);

        // belum isi HP: halaman pelanggan dialihkan
        $this->get('/beranda')->assertRedirect(route('profil.lengkapi'));
        $this->get('/lengkapi-profil')->assertOk()->assertSee('Satu Langkah Lagi')->assertSee('Sinta');
        $this->post('/lengkapi-profil', ['hp' => 'abc'])->assertSessionHasErrors('hp');
        $this->post('/lengkapi-profil', ['hp' => '0812 3456 7890', 'alamat' => 'Jl. Melati 3'])->assertRedirect(route('pelanggan.beranda'));
        $this->assertSame('0812 3456 7890', $user->fresh()->no_hp);
        $this->get('/beranda')->assertOk();
    }

    public function test_google_menghubungkan_akun_lama_dengan_email_sama(): void
    {
        $this->googleAktif();
        $kasir = User::factory()->create(['role' => User::ROLE_KASIR, 'email' => 'bima@gmail.com']);
        $this->akunGoogle('Bima@gmail.com', id: 'g-999');

        $this->get('/auth/google/callback')->assertRedirect(route('kasir.dashboard'));
        $this->assertAuthenticatedAs($kasir);
        $this->assertSame('g-999', $kasir->fresh()->google_id);
        $this->assertSame(1, User::count());
    }

    public function test_google_email_belum_terverifikasi_ditolak(): void
    {
        $this->googleAktif();
        $this->akunGoogle('palsu@gmail.com', verified: false);

        $this->get('/auth/google/callback')->assertRedirect(route('login'));
        $this->assertGuest();
        $this->assertDatabaseMissing('users', ['email' => 'palsu@gmail.com']);
    }

    // ---------- lupa password

    public function test_lupa_password_kirim_email_dan_reset(): void
    {
        Notification::fake();
        $user = User::factory()->create(['email' => 'rina@gmail.com', 'nama' => 'Rina Amalia']);

        $this->get('/lupa-password')->assertOk()->assertSee('Lupa Password?');
        $this->post('/lupa-password', ['email' => 'rina@gmail.com'])->assertSessionHas('status');

        $token = null;
        Notification::assertSentTo($user, ResetPasswordNotification::class, function ($n) use (&$token, $user) {
            $token = $n->token;
            $mail = $n->toMail($user);

            return str_contains($mail->subject, 'Atur Ulang Password') && str_contains($mail->actionUrl, '/reset-password/');
        });

        $this->get("/reset-password/{$token}?email=rina@gmail.com")->assertOk()->assertSee('rina@gmail.com');
        $this->post('/reset-password', ['token' => $token, 'email' => 'rina@gmail.com', 'password' => 'passwordBaru1', 'password_confirmation' => 'beda'])
            ->assertSessionHasErrors('password');
        $this->post('/reset-password', ['token' => $token, 'email' => 'rina@gmail.com', 'password' => 'passwordBaru1', 'password_confirmation' => 'passwordBaru1'])
            ->assertRedirect(route('login'))->assertSessionHas('status');
        $this->assertTrue(Hash::check('passwordBaru1', $user->fresh()->password));

        // token sekali pakai
        $this->post('/reset-password', ['token' => $token, 'email' => 'rina@gmail.com', 'password' => 'lainLagi12', 'password_confirmation' => 'lainLagi12'])
            ->assertSessionHasErrors('email');
    }

    public function test_lupa_password_email_tidak_terdaftar_pesan_sama(): void
    {
        Notification::fake();
        $this->post('/lupa-password', ['email' => 'tidakada@gmail.com'])->assertSessionHas('status')->assertSessionHasNoErrors();
        Notification::assertNothingSent();
    }

    public function test_token_salah_ditolak(): void
    {
        $user = User::factory()->create(['email' => 'a@b.test']);
        $this->post('/reset-password', ['token' => 'salah', 'email' => 'a@b.test', 'password' => 'passwordBaru1', 'password_confirmation' => 'passwordBaru1'])
            ->assertSessionHasErrors('email');
        $this->assertFalse(Hash::check('passwordBaru1', $user->fresh()->password));
    }
}
