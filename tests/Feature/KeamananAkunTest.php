<?php

namespace Tests\Feature;

use App\Models\LogAktivitas;
use App\Models\Menu;
use App\Models\Pesanan;
use App\Models\User;
use App\Notifications\AkunTerkunciNotification;
use App\Notifications\KodeOtpNotification;
use Database\Seeders\MasterDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class KeamananAkunTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private User $admin;

    private User $kasir;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
        $this->owner = User::factory()->create(['role' => User::ROLE_OWNER, 'nama' => 'Owner Resto']);
        $this->admin = User::factory()->create(['role' => User::ROLE_ADMIN, 'nama' => 'Admin Resto']);
        $this->kasir = User::factory()->create(['role' => User::ROLE_KASIR, 'nama' => 'Bima Kasir', 'email' => 'bima@resto.id']);
    }

    /**
     * Password salah $kali kali; jeda per 5 percobaan supaya batas sementara (per menit) tidak menghalangi.
     */
    private function salahPassword(string $email, int $kali): void
    {
        for ($i = 1; $i <= $kali; $i++) {
            $this->post('/login', ['email' => $email, 'password' => 'salah-'.$i]);
            if ($i % 5 === 0) {
                $this->travel(61)->seconds();
            }
        }
    }

    private function login(User $user): void
    {
        $this->post('/login', ['email' => $user->email, 'password' => 'password'])->assertRedirect();
        $this->assertAuthenticatedAs($user);
    }

    public function test_akun_staf_terkunci_setelah_sepuluh_kali_salah_dan_owner_diberi_tahu(): void
    {
        $this->salahPassword('bima@resto.id', 8);
        $this->post('/login', ['email' => 'bima@resto.id', 'password' => 'salah'])
            ->assertSessionHasErrors(['email' => 'Email atau password salah. Sisa 1 kali percobaan sebelum akun dikunci.']);
        $this->post('/login', ['email' => 'bima@resto.id', 'password' => 'salah'])
            ->assertSessionHasErrors(['email' => 'Password salah 10 kali, akun ini sekarang terkunci. Hubungi Owner atau Admin untuk membukanya.']);

        $this->assertTrue($this->kasir->fresh()->terkunci());
        Notification::assertSentTo($this->owner, AkunTerkunciNotification::class);
        Notification::assertNotSentTo($this->admin, AkunTerkunciNotification::class);
        $this->assertSame(10, LogAktivitas::where('aksi', 'akun.login_gagal')->count());
        $this->assertDatabaseHas('log_aktivitas', ['aksi' => 'akun.terkunci', 'id_user' => $this->kasir->id]);

        // password benar pun ditolak selama terkunci
        $this->travel(2)->minutes();
        $this->post('/login', ['email' => 'bima@resto.id', 'password' => 'password'])
            ->assertSessionHasErrors(['email' => 'Akun ini terkunci karena terlalu banyak percobaan masuk yang salah. Hubungi Owner atau Admin untuk membukanya.']);
        $this->assertGuest();

        // admin melihat & membuka kunci, staf bisa masuk lagi
        $this->actingAs($this->admin)->get('/admin/pengguna')->assertSee('Akun Terkunci')->assertSee('Bima Kasir');
        $this->actingAs($this->admin)->post("/admin/pengguna/{$this->kasir->getRouteKey()}/buka-kunci")->assertSessionHas('status');
        $this->assertFalse($this->kasir->fresh()->terkunci());
        $this->assertSame(0, $this->kasir->fresh()->gagal_login);
        $this->assertDatabaseHas('log_aktivitas', ['aksi' => 'akun.buka_kunci', 'id_user' => $this->admin->id]);

        $this->post('/logout');
        $this->login($this->kasir);
    }

    public function test_admin_tidak_bisa_membuka_kunci_owner(): void
    {
        $owner2 = User::factory()->create(['role' => User::ROLE_OWNER, 'terkunci_at' => now()]);

        $this->actingAs($this->admin)->post("/admin/pengguna/{$owner2->getRouteKey()}/buka-kunci")->assertForbidden();
        $this->actingAs($this->owner)->post("/owner/pengguna/{$owner2->getRouteKey()}/buka-kunci")->assertSessionHas('status');
        $this->assertFalse($owner2->fresh()->terkunci());
    }

    public function test_pelanggan_terkunci_bisa_membuka_lewat_lupa_password(): void
    {
        $rina = User::factory()->create(['role' => User::ROLE_PELANGGAN, 'email' => 'rina@gmail.com']);
        $this->salahPassword('rina@gmail.com', 10);
        $this->assertTrue($rina->fresh()->terkunci());
        $this->assertSame(0, LogAktivitas::where('aksi', 'akun.login_gagal')->count()); // pelanggan tidak memenuhi log staf

        $this->post('/lupa-password', ['email' => 'rina@gmail.com']);
        $kode = null;
        Notification::assertSentTo(new AnonymousNotifiable, KodeOtpNotification::class, function ($n) use (&$kode) {
            $kode = $n->kode;

            return true;
        });
        $this->post('/verifikasi-otp', ['kode' => $kode]);
        $this->post('/reset-password', ['password' => 'passwordBaru1', 'password_confirmation' => 'passwordBaru1'])
            ->assertSessionHas('status', 'Password berhasil diubah. Kunci akun sudah dibuka. Silakan masuk dengan password baru.');

        $this->assertFalse($rina->fresh()->terkunci());
        $this->post('/login', ['email' => 'rina@gmail.com', 'password' => 'passwordBaru1'])->assertRedirect();
        $this->assertAuthenticatedAs($rina);
    }

    public function test_satu_akun_staf_hanya_aktif_di_satu_perangkat(): void
    {
        $this->login($this->kasir);
        $token = $this->kasir->fresh()->token_sesi;
        $this->assertSame(64, strlen($token));
        $this->assertSame($token, session('token_sesi'));
        $this->get('/kasir/dashboard')->assertOk();

        // akun yang sama masuk dari perangkat lain → token berganti
        $this->kasir->forceFill(['token_sesi' => str_repeat('b', 64)])->saveQuietly();
        $this->app['auth']->forgetGuards(); // permintaan berikutnya membaca akun dari database, seperti request sungguhan

        $this->get('/kasir/dashboard')->assertRedirect('/login')
            ->assertSessionHas('status', fn ($s) => str_contains($s, 'dipakai masuk di perangkat lain'));
        $this->assertGuest();
        $this->assertDatabaseHas('log_aktivitas', ['aksi' => 'akun.diambil_alih', 'id_user' => $this->kasir->id]);
        $this->assertSame(str_repeat('b', 64), $this->kasir->fresh()->token_sesi); // perangkat baru tetap masuk
    }

    public function test_staf_otomatis_keluar_setelah_lama_diam(): void
    {
        $this->login($this->kasir);

        // cek notifikasi otomatis (JSON) tidak dihitung aktivitas
        $this->travel(20)->minutes();
        $this->getJson('/kasir/notifikasi')->assertOk();
        $this->travel(11)->minutes();

        $this->get('/kasir/dashboard')->assertRedirect('/login')
            ->assertSessionHas('status', 'Kamu otomatis keluar karena tidak ada aktivitas selama 30 menit. Silakan masuk lagi.');
        $this->assertGuest();
        $this->assertDatabaseHas('log_aktivitas', ['aksi' => 'akun.keluar_otomatis', 'id_user' => $this->kasir->id]);
        $this->assertNull($this->kasir->fresh()->token_sesi);
    }

    public function test_aktivitas_memperpanjang_sesi_dan_script_peringatan_hanya_untuk_staf(): void
    {
        $this->login($this->kasir);
        $this->travel(20)->minutes();
        $this->get('/kasir/dashboard')->assertOk()->assertSee('js/sesi-staf.js')->assertSee('data-batas="30"', false);
        $this->travel(20)->minutes();
        $this->get('/kasir/dashboard')->assertOk();

        // script di browser mengirim logout dengan alasan diam
        $this->post('/logout', ['alasan' => 'diam'])->assertRedirect('/login')
            ->assertSessionHas('status', fn ($s) => str_contains($s, 'otomatis keluar'));

        $pelanggan = User::factory()->create(['role' => User::ROLE_PELANGGAN]);
        $this->login($pelanggan);
        $this->travel(3)->hours();
        $this->get('/beranda')->assertOk()->assertDontSee('js/sesi-staf.js');
        $this->assertAuthenticatedAs($pelanggan);
    }

    public function test_ingat_saya_tidak_berlaku_untuk_staf(): void
    {
        $r = $this->post('/login', ['email' => 'bima@resto.id', 'password' => 'password', 'remember' => '1']);
        $this->assertEmpty(array_filter($r->headers->getCookies(), fn ($c) => str_starts_with($c->getName(), 'remember_web_')));
    }

    public function test_perubahan_harga_dan_aksi_kasir_tercatat_di_log_aktivitas_owner(): void
    {
        $this->seed(MasterDataSeeder::class);
        $pizza = Menu::firstWhere('nama_menu', 'Pizza Margherita');
        $hargaLama = 'Rp '.number_format((float) $pizza->harga, 0, ',', '.');

        $this->actingAs($this->admin)->put("/admin/menu/{$pizza->getRouteKey()}", [
            'nama_menu' => 'Pizza Margherita', 'id_kategori' => $pizza->id_kategori, 'harga' => '99.000', 'tersedia' => 1,
        ])->assertSessionHasNoErrors();

        $log = LogAktivitas::where('aksi', 'menu.ubah')->sole();
        $this->assertSame("Menu \"Pizza Margherita\" diubah: harga {$hargaLama} → Rp 99.000", $log->keterangan);
        $this->assertSame([$this->admin->id, 'admin'], [$log->id_user, $log->role_user]);

        $this->actingAs($this->kasir)->post('/kasir/input', ['jenis_layanan' => 'take_away', 'nama_pemesan' => 'Budi', 'items' => [['id_menu' => $pizza->id, 'jumlah' => 1]]])
            ->assertSessionHasNoErrors();
        $p = Pesanan::latest('id')->firstOrFail();
        $this->actingAs($this->kasir)->post("/kasir/pembayaran/{$p->getRouteKey()}/lunas", ['metode' => 'tunai', 'uang_diterima' => '200000'])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('log_aktivitas', ['aksi' => 'pembayaran.lunas', 'id_user' => $this->kasir->id, 'subjek_id' => $p->id]);

        // hanya owner yang bisa membaca log
        $this->actingAs($this->admin)->get('/owner/aktivitas')->assertForbidden();
        $this->actingAs($this->kasir)->get('/owner/aktivitas')->assertForbidden();
        $this->actingAs($this->owner)->get('/owner/aktivitas')->assertOk()
            ->assertSee('Pizza Margherita')->assertSee('Rp 99.000')->assertSee("{$p->kode()} ditandai lunas");
        $this->actingAs($this->owner)->get('/owner/aktivitas?kategori=menu')->assertSee('Rp 99.000')->assertDontSee('ditandai lunas');
        $this->actingAs($this->owner)->get('/owner/aktivitas?q=Bima')->assertSee('ditandai lunas')->assertDontSee('Rp 99.000');
    }

    public function test_seeder_dan_perubahan_tanpa_login_tidak_dicatat(): void
    {
        $this->seed(MasterDataSeeder::class);
        Menu::first()->update(['harga' => 12345]);

        $this->assertSame(0, LogAktivitas::count());
    }
}
