<?php

namespace Tests\Feature;

use App\Models\KodeOtp;
use App\Models\User;
use App\Notifications\KodeOtpNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Notifications\ChannelManager;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AkunTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
    }

    /**
     * Kode OTP terakhir yang dikirim ke email.
     */
    private function kodeTerkirim(string $email): string
    {
        $kode = null;
        Notification::assertSentTo(new AnonymousNotifiable, KodeOtpNotification::class, function ($n, $ch, $notifiable) use ($email, &$kode) {
            if (($notifiable->routes['mail'] ?? null) === $email) {
                $kode = $n->kode;
            }

            return true;
        });

        return $kode;
    }

    private function daftar(string $email = 'sari@gmail.com'): void
    {
        $this->post('/registrasi', ['nama' => 'Sari Wulandari', 'hp' => '081234567890', 'email' => $email, 'password' => 'rahasia123',
            'password_confirmation' => 'rahasia123', 'setuju' => 1])->assertRedirect('/verifikasi-otp');
    }

    public function test_halaman_login_tanpa_google_dan_facebook(): void
    {
        $this->get('/login')->assertOk()->assertSee('Masuk ke')->assertSee('Daftar &amp; Pesan Sekarang', false)->assertDontSee('Google')->assertDontSee('Facebook')
            ->assertSee(route('password.request'), false)->assertSee('PESAN · LACAK · NIKMATI');
        $this->get('/registrasi')->assertOk()->assertSee('Buat Akun Baru')->assertDontSee('Google');
        $this->get('/auth/google')->assertNotFound();
    }

    // ---------- OTP registrasi

    public function test_registrasi_lalu_otp_mengaktifkan_akun(): void
    {
        $this->daftar();
        $kode = $this->kodeTerkirim('sari@gmail.com');
        $this->assertMatchesRegularExpression('/^\d{6}$/', $kode);

        $this->get('/verifikasi-otp')->assertOk()->assertSee('Verifikasi Email')->assertSee('sa');
        $this->post('/verifikasi-otp', ['kode' => $kode])->assertRedirect(route('pelanggan.beranda'));

        $user = User::firstWhere('email', 'sari@gmail.com');
        $this->assertNotNull($user->email_verified_at);
        $this->assertAuthenticatedAs($user);
        $this->assertSame(0, KodeOtp::count()); // sekali pakai
    }

    public function test_registrasi_wajib_setuju_syarat(): void
    {
        $this->post('/registrasi', ['nama' => 'Sari', 'hp' => '081234567890', 'email' => 'a@gmail.com', 'password' => 'rahasia123', 'password_confirmation' => 'rahasia123'])
            ->assertSessionHasErrors('setuju');
    }

    public function test_otp_salah_dibatasi_dan_kedaluwarsa(): void
    {
        $this->daftar();
        $kode = $this->kodeTerkirim('sari@gmail.com');
        $salah = $kode === '000000' ? '111111' : '000000';

        $this->post('/verifikasi-otp', ['kode' => '12'])->assertSessionHasErrors('kode');
        $this->post('/verifikasi-otp', ['kode' => $salah])->assertSessionHasErrors(['kode' => 'Kode salah. Sisa 4 kali percobaan.']);
        $this->assertGuest();

        foreach (range(1, 4) as $_) {
            $this->post('/verifikasi-otp', ['kode' => $salah]);
        }
        // percobaan habis: kode benar pun ditolak
        $this->post('/verifikasi-otp', ['kode' => $kode])->assertSessionHasErrors('kode');
        $this->assertGuest();

        // percobaan habis: kode baru boleh langsung diminta, lalu jeda 60 detik berlaku lagi
        $this->post('/verifikasi-otp/kirim-ulang')->assertSessionHas('status');
        $baru = $this->kodeTerkirim('sari@gmail.com');
        $this->post('/verifikasi-otp/kirim-ulang')->assertSessionHasErrors('kode'); // masih jeda

        $this->travel(11)->minutes();
        $this->post('/verifikasi-otp', ['kode' => $baru])->assertSessionHasErrors('kode'); // kedaluwarsa
        $this->assertGuest();
    }

    public function test_login_akun_belum_verifikasi_diarahkan_ke_otp(): void
    {
        $user = User::factory()->belumVerifikasi()->create(['email' => 'baru@gmail.com']);

        $this->post('/login', ['email' => 'baru@gmail.com', 'password' => 'password'])->assertRedirect('/verifikasi-otp');
        $this->assertGuest();
        $kode = $this->kodeTerkirim('baru@gmail.com');

        $this->post('/verifikasi-otp', ['kode' => $kode])->assertRedirect();
        $this->assertAuthenticatedAs($user);
    }

    public function test_halaman_otp_tanpa_sesi_kembali_ke_login(): void
    {
        $this->get('/verifikasi-otp')->assertRedirect('/login');
        $this->post('/verifikasi-otp', ['kode' => '123456'])->assertRedirect('/login');
    }

    public function test_akun_buatan_admin_langsung_aktif(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $this->actingAs($admin)->post('/admin/pengguna', ['nama' => 'Kasir Baru', 'email' => 'kasirbaru@resto.test', 'no_hp' => '081234567890',
            'role' => 'kasir', 'password' => 'rahasia123', 'password_confirmation' => 'rahasia123'])->assertSessionHasNoErrors();
        $this->assertNotNull(User::firstWhere('email', 'kasirbaru@resto.test')->email_verified_at);
    }

    // ---------- lupa password dengan OTP

    public function test_lupa_password_otp_lalu_password_baru(): void
    {
        $user = User::factory()->create(['email' => 'rina@gmail.com', 'nama' => 'Rina Amalia']);

        $this->get('/lupa-password')->assertOk()->assertSee('Lupa Password?');
        $this->post('/lupa-password', ['email' => 'Rina@gmail.com'])->assertRedirect('/verifikasi-otp');
        $kode = $this->kodeTerkirim('rina@gmail.com');

        // belum memasukkan OTP: halaman password baru tertutup
        $this->get('/reset-password')->assertRedirect('/lupa-password');

        $this->get('/verifikasi-otp')->assertOk()->assertSee('Masukkan Kode OTP');
        $this->post('/verifikasi-otp', ['kode' => $kode])->assertRedirect('/reset-password');
        $this->assertGuest();

        $this->get('/reset-password')->assertOk()->assertSee('Buat Password Baru')->assertSee('ri');
        $this->post('/reset-password', ['password' => 'passwordBaru1', 'password_confirmation' => 'beda'])->assertSessionHasErrors('password');
        $this->post('/reset-password', ['password' => 'passwordBaru1', 'password_confirmation' => 'passwordBaru1'])
            ->assertRedirect('/login')->assertSessionHas('status');
        $this->assertTrue(Hash::check('passwordBaru1', $user->fresh()->password));

        // izin reset sekali pakai
        $this->get('/reset-password')->assertRedirect('/lupa-password');
        $this->post('/login', ['email' => 'rina@gmail.com', 'password' => 'passwordBaru1'])->assertRedirect();
        $this->assertAuthenticatedAs($user);
    }

    public function test_lupa_password_email_tidak_terdaftar_tidak_bocor(): void
    {
        $this->post('/lupa-password', ['email' => 'tidakada@gmail.com'])->assertRedirect('/verifikasi-otp')->assertSessionHas('status');
        Notification::assertNothingSent();
        $this->post('/verifikasi-otp', ['kode' => '123456'])->assertSessionHasErrors('kode');
        $this->get('/reset-password')->assertRedirect('/lupa-password');
    }

    public function test_izin_reset_kedaluwarsa_15_menit(): void
    {
        User::factory()->create(['email' => 'a@gmail.com']);
        $this->post('/lupa-password', ['email' => 'a@gmail.com']);
        $this->post('/verifikasi-otp', ['kode' => $this->kodeTerkirim('a@gmail.com')])->assertRedirect('/reset-password');

        $this->travel(16)->minutes();
        $this->post('/reset-password', ['password' => 'passwordBaru1', 'password_confirmation' => 'passwordBaru1'])->assertRedirect('/lupa-password');
    }

    public function test_isi_email_otp(): void
    {
        $user = User::factory()->belumVerifikasi()->create(['nama' => 'Sari Wulandari']);
        $mail = (new KodeOtpNotification('482913', KodeOtp::TUJUAN_REGISTRASI, 'Sari'))->toMail($user);
        $this->assertStringContainsString('Kode Verifikasi Akun', $mail->subject);
        $this->assertStringContainsString('4 8 2 9 1 3', implode(' ', $mail->introLines));
    }

    // ---------- QA fase 3: email gagal, batas percobaan, halaman tujuan, ingat saya

    public function test_email_otp_gagal_terkirim_akun_tidak_tersimpan_dan_bisa_daftar_ulang(): void
    {
        // server email tidak bisa dihubungi
        Notification::swap(new ChannelManager(app()));
        config(['mail.default' => 'smtp', 'mail.mailers.smtp.host' => '127.0.0.1', 'mail.mailers.smtp.port' => 1]);

        $this->post('/registrasi', ['nama' => 'Uji SMTP', 'hp' => '081234567890', 'email' => 'ujismtp@contoh.com', 'password' => 'rahasia123',
            'password_confirmation' => 'rahasia123', 'setuju' => 1])
            ->assertRedirect()->assertSessionHasErrors('email');
        $this->assertStringContainsString('belum terkirim', session('errors')->first('email'));
        $this->assertNull(User::firstWhere('email', 'ujismtp@contoh.com'));
        $this->assertSame(0, KodeOtp::count());
    }

    public function test_email_belum_terverifikasi_boleh_didaftarkan_ulang(): void
    {
        User::factory()->belumVerifikasi()->create(['email' => 'lama@gmail.com', 'nama' => 'Data Lama']);

        $this->daftar('Lama@Gmail.com');
        $this->assertSame(1, User::where('email', 'lama@gmail.com')->count());
        $this->assertSame('Sari Wulandari', User::firstWhere('email', 'lama@gmail.com')->nama);
        $this->kodeTerkirim('lama@gmail.com');

        // email terverifikasi tetap ditolak (beda huruf besar pun)
        User::factory()->create(['email' => 'aktif@gmail.com']);
        $this->post('/registrasi', ['nama' => 'X', 'hp' => '081234567890', 'email' => 'AKTIF@gmail.com', 'password' => 'rahasia123',
            'password_confirmation' => 'rahasia123', 'setuju' => 1])->assertSessionHasErrors(['email' => 'Email sudah terdaftar. Silakan masuk.']);
    }

    public function test_batas_login_dihitung_per_akun_dan_hanya_yang_gagal(): void
    {
        $akun = User::factory()->count(6)->create();
        foreach ($akun as $u) { // 6 staf login benar berurutan dari IP yang sama
            $this->post('/login', ['email' => $u->email, 'password' => 'password'])->assertRedirect();
            $this->assertAuthenticatedAs($u);
            $this->post('/logout');
        }

        $u = $akun->first();
        foreach (range(1, 5) as $_) {
            $this->post('/login', ['email' => strtoupper($u->email), 'password' => 'salah'])->assertSessionHasErrors(['email' => 'Email atau password salah.']);
        }
        $this->post('/login', ['email' => $u->email, 'password' => 'password'])->assertSessionHasErrors('email');
        $this->assertStringContainsString('Coba lagi dalam', session('errors')->first('email'));
        $this->assertGuest();
        // akun lain tidak ikut terkunci
        $this->post('/login', ['email' => $akun->last()->email, 'password' => 'password'])->assertRedirect();
        $this->assertAuthenticatedAs($akun->last());
    }

    public function test_batas_percobaan_tetap_di_form_dengan_pesan_indonesia(): void
    {
        $this->daftar();
        foreach (range(1, 10) as $_) {
            $this->post('/verifikasi-otp', ['kode' => '000000']);
        }
        $this->from('/verifikasi-otp')->post('/verifikasi-otp', ['kode' => '000000'])
            ->assertRedirect('/verifikasi-otp')->assertSessionHasErrors('kode');
        $this->assertStringContainsString('Terlalu banyak percobaan. Coba lagi dalam', session('errors')->first('kode'));
    }

    public function test_setelah_login_kembali_ke_halaman_tujuan_sesuai_role(): void
    {
        $pelanggan = User::factory()->create(['role' => User::ROLE_PELANGGAN]);
        $this->get('/profil')->assertRedirect('/login');
        $this->get('/login')->assertSee('Silakan masuk dulu untuk membuka halaman itu.');
        $this->post('/login', ['email' => $pelanggan->email, 'password' => 'password'])->assertRedirect('/profil');
        $this->post('/logout');

        // halaman staf yang tidak sesuai role: ke halaman utama role
        $this->get('/admin/dashboard');
        $this->post('/login', ['email' => $pelanggan->email, 'password' => 'password'])->assertRedirect('/beranda');
    }

    public function test_ingat_saya_memasang_cookie_remember(): void
    {
        $u = User::factory()->create();
        $this->get('/login')->assertSee('Ingat saya');
        $res = $this->post('/login', ['email' => $u->email, 'password' => 'password', 'remember' => 1]);
        $this->assertNotNull(collect($res->headers->getCookies())->first(fn ($c) => str_starts_with($c->getName(), 'remember_web_')));
    }

    public function test_halaman_error_berbahasa_indonesia(): void
    {
        $this->get('/halaman-tidak-ada')->assertNotFound()->assertSee('Halaman Tidak Ditemukan')->assertSee('Ke halaman masuk');
        $this->getJson('/profil')->assertUnauthorized()->assertJson(['message' => 'Sesi kamu sudah berakhir. Silakan masuk lagi.']);
    }

    public function test_buka_reset_password_langsung_pesan_netral(): void
    {
        $this->get('/reset-password')->assertRedirect('/lupa-password')->assertSessionHas('status')->assertSessionMissing('errors');
    }

    public function test_halaman_berlogin_tidak_disimpan_cache_browser(): void
    {
        $u = User::factory()->create(['role' => User::ROLE_PELANGGAN]);
        $res = $this->actingAs($u)->get('/beranda')->assertOk();
        $this->assertStringContainsString('no-store', $res->headers->get('Cache-Control'));
        $this->assertSame('no-cache', $res->headers->get('Pragma'));
    }
}
