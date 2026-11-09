<?php

namespace Tests\Feature;

use App\Models\Menu;
use App\Models\PengaturanSistem;
use App\Models\Pesanan;
use App\Models\User;
use App\Notifications\KodeOtpNotification;
use App\Support\PesananTamu;
use Database\Seeders\MasterDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * Pesan tanpa login: tamu memesan dengan nama + nomor HP, melacak lewat link / Cek Pesanan,
 * dan pesanannya masuk ke akun bila nanti mendaftar atau masuk.
 */
class PesanTanpaLoginTest extends TestCase
{
    use RefreshDatabase;

    private Menu $pizza;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(MasterDataSeeder::class);
        Carbon::setTestNow(today()->setTime(12, 0));
        $this->pizza = Menu::where('nama_menu', 'Pizza Beef Pepperoni')->first();
    }

    /**
     * @param  array<string, mixed>  $isian
     */
    private function pesanSebagaiTamu(array $isian = []): TestResponse
    {
        $this->postJson('/keranjang', ['id_menu' => $this->pizza->id])->assertOk();

        return $this->post('/checkout', $isian + [
            'jenis_layanan' => 'take_away', 'metode' => 'tunai',
            'nama_pemesan' => 'Dewi Lestari', 'no_hp_pemesan' => '0812 3456 7890', 'email_pemesan' => 'Dewi@Example.com',
        ]);
    }

    private function aktifkanMidtrans(): void
    {
        config(['services.midtrans.server_key' => 'SB-Mid-server-xyz', 'services.midtrans.is_production' => false]);
        Http::fake([
            'api.sandbox.midtrans.com/v2/charge' => Http::response(['status_code' => '201', 'transaction_status' => 'pending', 'expiry_time' => now()->addHour()->format('Y-m-d H:i:s'),
                'actions' => [['name' => 'generate-qr-code', 'method' => 'GET', 'url' => 'https://api.sandbox.midtrans.com/v2/qris/abc/qr-code']]]),
            'api.sandbox.midtrans.com/v2/qris/*' => Http::response('PNGDATA', 200, ['Content-Type' => 'image/png']),
        ]);
    }

    public function test_tamu_bisa_melihat_menu_dan_checkout_tanpa_login(): void
    {
        $this->get('/beranda')->assertOk()->assertSee('Masuk');
        $this->postJson('/keranjang', ['id_menu' => $this->pizza->id])->assertOk();
        $this->get('/checkout')->assertOk()->assertSee('Pesan tanpa akun')->assertSee('name="no_hp_pemesan"', false)->assertSee('Email (opsional)');

        // staf yang sedang login tidak memesan dari halaman pelanggan
        $this->actingAs(User::factory()->create(['role' => User::ROLE_KASIR]))->get('/beranda')->assertForbidden();
    }

    public function test_pesanan_tamu_tersimpan_dengan_kontak_dan_terlihat_kasir(): void
    {
        $this->post('/checkout', ['jenis_layanan' => 'take_away', 'metode' => 'tunai'])->assertSessionHasErrors(['nama_pemesan', 'no_hp_pemesan']);

        $res = $this->pesanSebagaiTamu();
        $p = Pesanan::sole();
        $res->assertRedirect(route('pelanggan.pembayaran', $p))->assertCookie(PesananTamu::COOKIE);
        $this->assertSame([Pesanan::SUMBER_ONLINE, null, 'Dewi Lestari', '081234567890', 'dewi@example.com'], [$p->sumber, $p->id_pelanggan, $p->nama_pemesan, $p->no_hp_pemesan, $p->email_pemesan]);
        $this->assertTrue($p->isTamu());

        $this->get(route('pelanggan.pembayaran', $p))->assertOk()->assertSee('Simpan link pesanan ini')->assertSee('Buat Akun')
            ->assertSee(route('pelanggan.tracking', ['pesanan' => $p]));

        $kasir = User::factory()->create(['role' => User::ROLE_KASIR]);
        $this->actingAs($kasir)->get('/kasir/online')->assertOk()->assertSee($p->kode())->assertSee('Dewi Lestari')->assertSee('081234567890')->assertSee('Tamu');
        $this->actingAs($kasir)->post("/kasir/online/{$p->getRouteKey()}/terima")->assertSessionHasNoErrors();
        $this->assertSame(Pesanan::STATUS_DIPROSES, $p->fresh()->status);
    }

    public function test_tamu_melacak_pesanan_lewat_cookie_link_dan_cek_pesanan(): void
    {
        $res = $this->pesanSebagaiTamu();
        $p = Pesanan::sole();
        $daftar = $res->getCookie(PesananTamu::COOKIE)->getValue();

        // browser yang sama: muncul di Lacak Pesanan
        $this->withCookie(PesananTamu::COOKIE, $daftar)->get('/tracking')->assertOk()->assertSee($p->kode())->assertSee('Pesan tanpa akun');
        $this->flushSession();
        $this->defaultCookies = [];

        // perangkat lain tanpa cookie: kosong, tapi link lacak tetap bisa dibuka
        $this->get('/tracking')->assertSee('Belum ada pesanan')->assertDontSee($p->kode());
        $this->get(route('pelanggan.tracking', ['pesanan' => $p]))->assertSee($p->kode())->assertCookie(PesananTamu::COOKIE);

        // Cek Pesanan: nomor + HP harus cocok
        $this->post('/cek-pesanan', ['nomor' => $p->kode(), 'no_hp' => '089999999999'])->assertSessionHasErrors('nomor');
        $this->post('/cek-pesanan', ['nomor' => 'ord '.$p->id, 'no_hp' => '0812-3456-7890'])->assertRedirect(route('pelanggan.tracking', ['pesanan' => $p]));
    }

    public function test_tamu_tidak_bisa_membuka_pesanan_akun_atau_kasir(): void
    {
        $pelanggan = User::factory()->create(['role' => User::ROLE_PELANGGAN]);
        $this->actingAs($pelanggan)->postJson('/keranjang', ['id_menu' => $this->pizza->id]);
        $this->actingAs($pelanggan)->post('/checkout', ['jenis_layanan' => 'take_away', 'metode' => 'tunai']);
        $milikAkun = Pesanan::sole();
        auth()->logout();

        $this->get(route('pelanggan.pembayaran', $milikAkun))->assertNotFound();
        $this->post('/cek-pesanan', ['nomor' => $milikAkun->kode(), 'no_hp' => $pelanggan->no_hp])->assertSessionHasErrors('nomor');

        $kasir = User::factory()->create(['role' => User::ROLE_KASIR]);
        $this->actingAs($kasir)->post('/kasir/input', ['jenis_layanan' => 'take_away', 'items' => [['id_menu' => $this->pizza->id, 'jumlah' => 1]]]);
        auth()->logout();
        $this->get(route('pelanggan.pembayaran', Pesanan::latest('id')->first()))->assertNotFound();
    }

    public function test_delivery_tamu_wajib_bayar_online(): void
    {
        $isiDelivery = ['jenis_layanan' => 'delivery', 'alamat' => 'Jl. Karya 8', 'lat' => PengaturanSistem::LAT_AWAL + 0.018, 'lng' => PengaturanSistem::LNG_AWAL];
        $this->pesanSebagaiTamu($isiDelivery + ['metode' => 'tunai'])->assertSessionHasErrors('metode');
        $this->assertSame(0, Pesanan::count());

        $this->aktifkanMidtrans();
        $this->post('/checkout', $isiDelivery + ['metode' => 'qris', 'nama_pemesan' => 'Dewi Lestari', 'no_hp_pemesan' => '081234567890'])->assertSessionHasNoErrors();
        $p = Pesanan::sole();
        $this->assertSame('qris', $p->pembayaran->metode);

        // ganti ke tunai di halaman pembayaran juga ditolak
        $this->post(route('pelanggan.pembayaran.store', $p), ['metode' => 'tunai'])->assertSessionHasErrors('metode');

        // QRIS bisa diunduh sebagai file
        $this->get(route('pelanggan.pembayaran', $p))->assertSee('Unduh QRIS');
        $this->get(route('pelanggan.pembayaran.qris', $p))->assertOk()
            ->assertHeader('Content-Disposition', 'attachment; filename="QRIS-'.$p->kode().'.png"')->assertContent('PNGDATA');
    }

    public function test_pesanan_tamu_masuk_ke_akun_saat_mendaftar_atau_masuk(): void
    {
        Notification::fake();
        $res = $this->pesanSebagaiTamu();
        $pertama = Pesanan::sole();
        $daftar = $res->getCookie(PesananTamu::COOKIE)->getValue();

        // form daftar terisi dari data pesanan
        $this->get('/registrasi')->assertSee('value="Dewi Lestari"', false)->assertSee('value="dewi@example.com"', false);

        $this->post('/registrasi', ['nama' => 'Dewi Lestari', 'hp' => '081234567890', 'email' => 'dewi@example.com', 'password' => 'Rahasia123', 'password_confirmation' => 'Rahasia123', 'setuju' => '1'])
            ->assertRedirect('/verifikasi-otp');
        $kode = null;
        Notification::assertSentTo(new AnonymousNotifiable, KodeOtpNotification::class, function ($n) use (&$kode) {
            $kode = $n->kode;

            return true;
        });
        $this->post('/verifikasi-otp', ['kode' => $kode])->assertSessionHas('status', fn ($s) => str_contains($s, '1 pesanan sebelumnya sudah masuk ke riwayatmu'));
        $dewi = User::where('email', 'dewi@example.com')->sole();
        $this->assertSame($dewi->id, $pertama->fresh()->id_pelanggan);
        $this->post('/logout');
        $this->defaultCookies = [];

        // pesanan tamu kedua dari browser ini masuk ke akun saat login
        $res = $this->withCookie(PesananTamu::COOKIE, $daftar)->pesanSebagaiTamu(['email_pemesan' => '']);
        $kedua = Pesanan::latest('id')->first();
        $this->withCookie(PesananTamu::COOKIE, $res->getCookie(PesananTamu::COOKIE)->getValue())
            ->post('/login', ['email' => 'dewi@example.com', 'password' => 'Rahasia123'])->assertSessionHas('status');
        $this->assertSame($dewi->id, $kedua->fresh()->id_pelanggan);
    }
}
