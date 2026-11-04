<?php

namespace Tests\Feature;

use App\Models\Menu;
use App\Models\Pembayaran;
use App\Models\PengaturanSistem;
use App\Models\Pesanan;
use App\Models\User;
use App\Models\ZonaPengiriman;
use Database\Seeders\MasterDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PesananPelangganTest extends TestCase
{
    use RefreshDatabase;

    private User $pelanggan;

    private Menu $pizza;   // Rp 109.000

    private Menu $tea;     // Rp 18.000

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        $this->seed(MasterDataSeeder::class);                   // pajak 10%, layanan 5%
        Carbon::setTestNow(today()->setTime(12, 0));            // di dalam jam buka 10:00 - 22:00
        $this->pelanggan = User::factory()->create(['role' => User::ROLE_PELANGGAN, 'alamat' => 'Jl. Merdeka 1']);
        $this->pizza = Menu::where('nama_menu', 'Pizza Beef Pepperoni')->first();
        $this->tea = Menu::where('nama_menu', 'Lemon Iced Tea')->first();
    }

    private function isiKeranjang(): void
    {
        $this->actingAs($this->pelanggan)->postJson('/keranjang', ['id_menu' => $this->pizza->id])->assertOk();
        $this->actingAs($this->pelanggan)->postJson('/keranjang', ['id_menu' => $this->tea->id, 'jumlah' => 2])->assertOk();
    }

    // ---------- keranjang

    public function test_keranjang_tambah_ubah_hapus(): void
    {
        $this->actingAs($this->pelanggan)->postJson('/keranjang', ['id_menu' => $this->pizza->id])
            ->assertOk()->assertJson(['jumlah' => 1, 'subtotal' => 109000, 'menu' => 1]);
        $this->actingAs($this->pelanggan)->postJson('/keranjang', ['id_menu' => $this->pizza->id])->assertJson(['jumlah' => 2]);

        $this->actingAs($this->pelanggan)->patchJson("/keranjang/{$this->pizza->id}", ['jumlah' => 5, 'catatan' => 'potong 12'])
            ->assertOk()->assertJson(['jumlah' => 5, 'subtotal' => 545000]);
        $this->actingAs($this->pelanggan)->get('/keranjang')->assertOk()->assertSee('value="potong 12"', false)->assertSee('Rp 545.000');

        $this->actingAs($this->pelanggan)->deleteJson("/keranjang/{$this->pizza->id}")->assertJson(['jumlah' => 0, 'menu' => 0]);

        $this->isiKeranjang();
        $this->actingAs($this->pelanggan)->deleteJson('/keranjang')->assertJson(['menu' => 0]);
    }

    public function test_menu_habis_tidak_bisa_masuk_keranjang_dan_dibuang_dari_keranjang(): void
    {
        $habis = Menu::where('tersedia', false)->first();
        $this->actingAs($this->pelanggan)->postJson('/keranjang', ['id_menu' => $habis->id])->assertStatus(422);

        $this->isiKeranjang();
        $this->tea->update(['tersedia' => false]);
        $this->actingAs($this->pelanggan)->get('/keranjang')->assertOk()->assertDontSee('Lemon Iced Tea</h3>', false);
    }

    public function test_header_menampilkan_isi_keranjang(): void
    {
        $this->isiKeranjang();
        $this->actingAs($this->pelanggan)->get('/beranda')->assertSee('data-cart-total>Rp 145.000', false)->assertSee('data-cart-count>3', false);
        $this->actingAs($this->pelanggan)->get('/tracking')->assertSee('data-cart-count>3', false);
    }

    // ---------- checkout

    public function test_checkout_delivery_menghitung_total_dan_membuat_pengiriman(): void
    {
        $this->isiKeranjang();
        $zonaB = ZonaPengiriman::where('nama_zona', 'Zona B')->first(); // 6 km x Rp 2.500 = Rp 15.000

        $this->actingAs($this->pelanggan)->get('/checkout')->assertOk()->assertSee('Rp 145.000')->assertSee('Rp 14.500')->assertSee('Rp 7.250');

        $res = $this->actingAs($this->pelanggan)->post('/checkout', ['jenis_layanan' => 'delivery', 'alamat' => 'Jl. Sei Batang Hari 27', 'id_zona' => $zonaB->id]);

        $pesanan = Pesanan::firstOrFail();
        $res->assertRedirect("/pesanan/{$pesanan->id}/pembayaran");
        // 145.000 + pajak 14.500 + layanan 7.250 + ongkir 15.000
        $this->assertEquals([145000, 15000, 181750], [(float) $pesanan->subtotal, (float) $pesanan->ongkir, (float) $pesanan->total]);
        $this->assertSame([Pesanan::STATUS_MENUNGGU, $this->pelanggan->id, null], [$pesanan->status, $pesanan->id_pelanggan, $pesanan->id_kasir]);
        $this->assertSame(2, $pesanan->detail()->count());
        $this->assertSame(2, $pesanan->detail()->where('id_menu', $this->tea->id)->value('jumlah'));
        $this->assertEquals([6, 'Jl. Sei Batang Hari 27'], [(float) $pesanan->pengiriman->jarak_km, $pesanan->pengiriman->alamat]);
        $this->assertNull($pesanan->tiketDapur); // tiket baru dibuat setelah dikonfirmasi kasir

        $this->actingAs($this->pelanggan)->get("/pesanan/{$pesanan->id}/pembayaran")->assertOk()->assertSee('Pesanan Dibuat!')->assertSee($pesanan->kode());
        $this->actingAs($this->pelanggan)->get('/keranjang')->assertSee('Keranjang masih kosong');
    }

    public function test_validasi_checkout(): void
    {
        $this->actingAs($this->pelanggan)->get('/checkout')->assertRedirect('/keranjang');

        $this->isiKeranjang();
        $this->actingAs($this->pelanggan)->post('/checkout', ['jenis_layanan' => 'dine_in'])->assertSessionHasErrors('nomor_meja');
        $this->actingAs($this->pelanggan)->post('/checkout', ['jenis_layanan' => 'delivery'])->assertSessionHasErrors(['alamat', 'id_zona']);
        $this->actingAs($this->pelanggan)->post('/checkout', ['jenis_layanan' => 'antar_jemput'])->assertSessionHasErrors('jenis_layanan');

        $this->actingAs($this->pelanggan)->post('/checkout', ['jenis_layanan' => 'dine_in', 'nomor_meja' => '07'])->assertSessionHasNoErrors();
        $this->assertSame(['dine_in', '07', 0.0], [Pesanan::first()->jenis_layanan, Pesanan::first()->nomor_meja, (float) Pesanan::first()->ongkir]);
    }

    public function test_checkout_ditolak_di_luar_jam_operasional(): void
    {
        $this->isiKeranjang();
        Carbon::setTestNow(today()->setTime(23, 0));

        $this->actingAs($this->pelanggan)->get('/checkout')->assertSee('Restoran sedang tutup');
        $this->actingAs($this->pelanggan)->post('/checkout', ['jenis_layanan' => 'take_away'])->assertSessionHas('error');
        $this->assertSame(0, Pesanan::count());

        PengaturanSistem::aktif()->update(['jam_tutup' => '23:30']);
        $this->actingAs($this->pelanggan)->post('/checkout', ['jenis_layanan' => 'take_away'])->assertSessionHasNoErrors();
        $this->assertSame(1, Pesanan::count());
    }

    // ---------- pembayaran

    private function buatPesanan(string $layanan = 'take_away'): Pesanan
    {
        $this->isiKeranjang();
        $this->actingAs($this->pelanggan)->post('/checkout', ['jenis_layanan' => $layanan]);

        return Pesanan::latest('id')->firstOrFail();
    }

    public function test_transfer_dan_qris_wajib_bukti_tunai_tidak(): void
    {
        $p = $this->buatPesanan();

        $this->actingAs($this->pelanggan)->post("/pesanan/{$p->id}/pembayaran", ['metode' => 'transfer'])->assertSessionHasErrors('bukti_bayar');
        $this->actingAs($this->pelanggan)->post("/pesanan/{$p->id}/pembayaran", ['metode' => 'qris', 'bukti_bayar' => UploadedFile::fake()->create('x.pdf', 10)])
            ->assertSessionHasErrors('bukti_bayar');

        $this->actingAs($this->pelanggan)->post("/pesanan/{$p->id}/pembayaran", ['metode' => 'qris', 'bukti_bayar' => UploadedFile::fake()->image('qris.png')])
            ->assertSessionHasNoErrors()->assertSessionHas('pembayaran_terkirim', 'qris');
        $bayar = $p->fresh()->pembayaran;
        $this->assertSame(['qris', Pembayaran::STATUS_MENUNGGU], [$bayar->metode, $bayar->status_bayar]);
        $this->assertEquals($p->total, $bayar->jumlah);
        Storage::disk('local')->assertExists($bayar->bukti_bayar);

        // ganti ke tunai: bukti lama dihapus
        $this->actingAs($this->pelanggan)->post("/pesanan/{$p->id}/pembayaran", ['metode' => 'tunai'])->assertSessionHasNoErrors();
        Storage::disk('local')->assertMissing($bayar->bukti_bayar);
        $this->assertNull($p->fresh()->pembayaran->bukti_bayar);
    }

    public function test_pesanan_dan_bukti_orang_lain_tidak_bisa_dibuka(): void
    {
        $p = $this->buatPesanan();
        $this->actingAs($this->pelanggan)->post("/pesanan/{$p->id}/pembayaran", ['metode' => 'transfer', 'bukti_bayar' => UploadedFile::fake()->image('b.jpg')]);
        $bayar = $p->fresh()->pembayaran;

        $lain = User::factory()->create(['role' => User::ROLE_PELANGGAN]);
        $this->actingAs($lain)->get("/pesanan/{$p->id}/pembayaran")->assertNotFound();
        $this->actingAs($lain)->post("/pesanan/{$p->id}/pembayaran", ['metode' => 'tunai'])->assertNotFound();
        $this->actingAs($lain)->get("/bukti-bayar/{$bayar->id}")->assertNotFound();
        $this->actingAs($lain)->get('/tracking')->assertDontSee($p->kode());

        $this->actingAs($this->pelanggan)->get("/bukti-bayar/{$bayar->id}")->assertOk();
        $this->actingAs(User::factory()->create(['role' => User::ROLE_KASIR]))->get("/bukti-bayar/{$bayar->id}")->assertOk();
        $this->actingAs(User::factory()->create(['role' => User::ROLE_DAPUR]))->get("/bukti-bayar/{$bayar->id}")->assertNotFound();
    }

    // ---------- lacak pesanan

    public function test_tracking_menampilkan_timeline_sesuai_layanan(): void
    {
        $this->isiKeranjang();
        $this->actingAs($this->pelanggan)->post('/checkout', ['jenis_layanan' => 'delivery', 'alamat' => 'Jl. A', 'id_zona' => ZonaPengiriman::first()->id]);
        $p = Pesanan::firstOrFail();

        $html = $this->actingAs($this->pelanggan)->get('/tracking')->assertOk()
            ->assertSeeInOrder(['Pesanan Dibuat', 'Dikonfirmasi', 'Dimasak', 'Siap Diantar', 'Dalam Perjalanan', 'Diterima'])
            ->assertSee('Bayar Sekarang')->getContent();
        $this->assertSame(1, substr_count($html, 'class="st now"'));

        $this->isiKeranjang();
        $this->actingAs($this->pelanggan)->post('/checkout', ['jenis_layanan' => 'take_away']);
        $q = Pesanan::latest('id')->first();
        $this->actingAs($this->pelanggan)->get("/tracking?pesanan={$q->id}")
            ->assertSeeInOrder(['Pesanan Dibuat', 'Dikonfirmasi', 'Dimasak', 'Siap Diambil', 'Selesai'])
            ->assertDontSee('Dalam Perjalanan</b>', false);
    }
}
