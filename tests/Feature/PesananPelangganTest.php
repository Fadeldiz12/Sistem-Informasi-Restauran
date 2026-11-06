<?php

namespace Tests\Feature;

use App\Models\Menu;
use App\Models\Pembayaran;
use App\Models\PengaturanSistem;
use App\Models\Pesanan;
use App\Models\User;
use App\Models\ZonaPengiriman;
use App\Services\DapurService;
use App\Services\PesananService;
use App\Support\Keranjang;
use App\Support\TimelinePesanan;
use Database\Seeders\MasterDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

        // titik ±4 km dari restoran → Zona B (3-6 km), dihitung server dari peta
        $res = $this->actingAs($this->pelanggan)->post('/checkout', ['metode' => 'tunai', 'jenis_layanan' => 'delivery', 'alamat' => 'Jl. Sei Batang Hari 27',
            'lat' => PengaturanSistem::LAT_AWAL + 0.036, 'lng' => PengaturanSistem::LNG_AWAL, 'patokan' => 'pagar hijau']);

        $pesanan = Pesanan::firstOrFail();
        $res->assertRedirect("/pesanan/{$pesanan->id}/pembayaran");
        // 145.000 + pajak 14.500 + layanan 7.250 + ongkir 15.000
        $this->assertEquals([145000, 15000, 181750], [(float) $pesanan->subtotal, (float) $pesanan->ongkir, (float) $pesanan->total]);
        $this->assertSame([Pesanan::STATUS_MENUNGGU, $this->pelanggan->id, null], [$pesanan->status, $pesanan->id_pelanggan, $pesanan->id_kasir]);
        $this->assertSame(2, $pesanan->detail()->count());
        $this->assertSame(2, $pesanan->detail()->where('id_menu', $this->tea->id)->value('jumlah'));
        $this->assertSame([$zonaB->id, 'Jl. Sei Batang Hari 27', 'pagar hijau'], [$pesanan->pengiriman->id_zona, $pesanan->pengiriman->alamat, $pesanan->pengiriman->patokan]);
        $this->assertEqualsWithDelta(4.0, (float) $pesanan->pengiriman->jarak_km, 0.05);
        $this->assertEqualsWithDelta(PengaturanSistem::LAT_AWAL + 0.036, (float) $pesanan->pengiriman->lat, 0.00001);
        $this->assertNull($pesanan->tiketDapur); // tiket baru dibuat setelah dikonfirmasi kasir

        $this->actingAs($this->pelanggan)->get("/pesanan/{$pesanan->id}/pembayaran")->assertOk()->assertSee('Pesanan Dibuat!')->assertSee($pesanan->kode());
        $this->actingAs($this->pelanggan)->get('/keranjang')->assertSee('Keranjang masih kosong');
    }

    public function test_validasi_checkout(): void
    {
        $this->actingAs($this->pelanggan)->get('/checkout')->assertRedirect('/keranjang');

        $this->isiKeranjang();
        $this->actingAs($this->pelanggan)->post('/checkout', ['metode' => 'tunai', 'jenis_layanan' => 'dine_in'])->assertSessionHasErrors('nomor_meja');
        $this->actingAs($this->pelanggan)->post('/checkout', ['metode' => 'tunai', 'jenis_layanan' => 'delivery'])->assertSessionHasErrors(['alamat', 'lat']);
        // titik ±20 km: di luar zona terjauh (10 km) → ditolak
        $this->actingAs($this->pelanggan)->post('/checkout', ['metode' => 'tunai', 'jenis_layanan' => 'delivery', 'alamat' => 'Jl. Jauh',
            'lat' => PengaturanSistem::LAT_AWAL + 0.18, 'lng' => PengaturanSistem::LNG_AWAL])->assertSessionHasErrors('lat');
        $this->assertSame(0, Pesanan::count());
        $this->actingAs($this->pelanggan)->post('/checkout', ['metode' => 'tunai', 'jenis_layanan' => 'antar_jemput'])->assertSessionHasErrors('jenis_layanan');

        $this->actingAs($this->pelanggan)->post('/checkout', ['metode' => 'tunai', 'jenis_layanan' => 'dine_in', 'nomor_meja' => '07'])->assertSessionHasNoErrors();
        $this->assertSame(['dine_in', '07', 0.0], [Pesanan::first()->jenis_layanan, Pesanan::first()->nomor_meja, (float) Pesanan::first()->ongkir]);
    }

    public function test_checkout_ditolak_di_luar_jam_operasional(): void
    {
        $this->isiKeranjang();
        Carbon::setTestNow(today()->setTime(23, 0));

        $this->actingAs($this->pelanggan)->get('/checkout')->assertSee('Restoran sedang tutup');
        // ditolak; banner tutup sudah tampil di halaman, jadi tidak ada pesan error kedua (QA A2-#23)
        $this->actingAs($this->pelanggan)->from('/checkout')->post('/checkout', ['metode' => 'tunai', 'jenis_layanan' => 'take_away'])
            ->assertRedirect('/checkout')->assertSessionMissing('error');
        $this->assertSame(1, substr_count($this->actingAs($this->pelanggan)->get('/checkout')->getContent(), 'role="alert">Restoran sedang tutup'));
        $this->assertSame(0, Pesanan::count());

        PengaturanSistem::aktif()->update(['jam_tutup' => '23:30']);
        $this->actingAs($this->pelanggan)->post('/checkout', ['metode' => 'tunai', 'jenis_layanan' => 'take_away'])->assertSessionHasNoErrors();
        $this->assertSame(1, Pesanan::count());
    }

    // ---------- pembayaran

    private function buatPesanan(string $layanan = 'take_away'): Pesanan
    {
        $this->isiKeranjang();
        $this->actingAs($this->pelanggan)->post('/checkout', ['metode' => 'tunai', 'jenis_layanan' => $layanan]);

        return Pesanan::latest('id')->firstOrFail();
    }

    public function test_pilih_tunai_dan_pesanan_orang_lain_tidak_bisa_dibuka(): void
    {
        $p = $this->buatPesanan();

        $this->actingAs($this->pelanggan)->post("/pesanan/{$p->id}/pembayaran", ['metode' => 'tunai'])->assertSessionHasNoErrors();
        $bayar = $p->fresh()->pembayaran;
        $this->assertSame(['tunai', Pembayaran::STATUS_MENUNGGU, null], [$bayar->metode, $bayar->status_bayar, $bayar->kode_transaksi]);
        $this->assertEquals($p->total, $bayar->jumlah);
        $this->actingAs($this->pelanggan)->get("/pesanan/{$p->id}/pembayaran")->assertSee('bayar tunai');

        $lain = User::factory()->create(['role' => User::ROLE_PELANGGAN]);
        $this->actingAs($lain)->get("/pesanan/{$p->id}/pembayaran")->assertNotFound();
        $this->actingAs($lain)->post("/pesanan/{$p->id}/pembayaran", ['metode' => 'tunai'])->assertNotFound();
        $this->actingAs($lain)->getJson("/pesanan/{$p->id}/pembayaran/status")->assertNotFound();
        $this->actingAs($lain)->get('/tracking')->assertDontSee($p->kode());
    }

    public function test_va_dan_qris_tidak_bisa_dipilih_jika_midtrans_belum_diatur(): void
    {
        config(['services.midtrans.server_key' => null]);
        $p = $this->buatPesanan();

        $this->actingAs($this->pelanggan)->get("/pesanan/{$p->id}/pembayaran")->assertSee('belum diaktifkan');
        $this->actingAs($this->pelanggan)->post("/pesanan/{$p->id}/pembayaran", ['metode' => 'transfer', 'bank' => 'bca'])
            ->assertSessionHasErrors('metode');
        $this->assertSame('tunai', $p->fresh()->pembayaran->metode);
    }

    public function test_checkout_wajib_memilih_metode_pembayaran(): void
    {
        config(['services.midtrans.server_key' => null]);
        $this->isiKeranjang();

        $this->actingAs($this->pelanggan)->get('/checkout')->assertSee('Pilih metode pembayaran')->assertSee('belum diaktifkan');
        $this->actingAs($this->pelanggan)->post('/checkout', ['jenis_layanan' => 'take_away'])->assertSessionHasErrors('metode');
        // VA gagal dibuat: pesanan tidak ikut tersimpan dan keranjang tetap ada
        $this->actingAs($this->pelanggan)->post('/checkout', ['jenis_layanan' => 'take_away', 'metode' => 'transfer', 'bank' => 'bca'])->assertSessionHasErrors('metode');
        $this->assertSame(0, Pesanan::count());

        $this->actingAs($this->pelanggan)->post('/checkout', ['jenis_layanan' => 'take_away', 'metode' => 'tunai'])->assertSessionHasNoErrors();
        $p = Pesanan::firstOrFail();
        $this->assertSame([Pembayaran::METODE_TUNAI, Pembayaran::STATUS_MENUNGGU], [$p->pembayaran->metode, $p->pembayaran->status_bayar]);
        // langsung terlihat kasir untuk dikonfirmasi
        $this->assertTrue(Pesanan::menungguKonfirmasi()->whereKey($p->id)->exists());
    }

    // ---------- lacak pesanan

    public function test_tracking_menampilkan_timeline_sesuai_layanan(): void
    {
        $this->isiKeranjang();
        $this->actingAs($this->pelanggan)->post('/checkout', ['metode' => 'tunai', 'jenis_layanan' => 'delivery', 'alamat' => 'Jl. A', 'lat' => PengaturanSistem::LAT_AWAL + 0.018, 'lng' => PengaturanSistem::LNG_AWAL]);
        $p = Pesanan::firstOrFail();

        $html = $this->actingAs($this->pelanggan)->get('/tracking')->assertOk()
            ->assertSeeInOrder(['Pesanan Dibuat', 'Dikonfirmasi', 'Dimasak', 'Siap Diantar', 'Dalam Perjalanan', 'Diterima'])
            ->assertSee('Ganti metode')->getContent();
        $this->assertSame(1, substr_count($html, 'class="st now"'));

        $this->isiKeranjang();
        $this->actingAs($this->pelanggan)->post('/checkout', ['metode' => 'tunai', 'jenis_layanan' => 'take_away']);
        $q = Pesanan::latest('id')->first();
        $this->actingAs($this->pelanggan)->get("/tracking?pesanan={$q->id}")
            ->assertSeeInOrder(['Pesanan Dibuat', 'Dikonfirmasi', 'Dimasak', 'Siap Diambil', 'Selesai'])
            ->assertDontSee('Dalam Perjalanan</b>', false);
    }

    // ---------- QA fase 4: keranjang & checkout

    public function test_batas_50_porsi_dan_menu_habis_diberi_tahu(): void
    {
        $this->actingAs($this->pelanggan)->postJson('/keranjang', ['id_menu' => $this->tea->id, 'jumlah' => 50])->assertOk();
        $this->actingAs($this->pelanggan)->postJson('/keranjang', ['id_menu' => $this->tea->id])
            ->assertStatus(422)->assertJson(['message' => $this->tea->nama_menu.' sudah 50 porsi, batas maksimal per menu.']);

        $this->tea->update(['tersedia' => false]);
        $this->actingAs($this->pelanggan)->get('/keranjang')->assertSee($this->tea->nama_menu.' sedang habis dan sudah dihapus dari keranjang.');
    }

    public function test_hapus_item_bisa_dibatalkan_dengan_catatan_kembali(): void
    {
        $this->actingAs($this->pelanggan)->postJson('/keranjang', ['id_menu' => $this->tea->id, 'jumlah' => 3, 'catatan' => 'es sedikit'])->assertOk();
        $raw = session(Keranjang::KEY);
        $this->assertSame(['jumlah' => 3, 'catatan' => 'es sedikit'], $raw[$this->tea->id]);
    }

    public function test_back_setelah_checkout_ke_pesanan_yang_baru_dibuat(): void
    {
        $this->isiKeranjang();
        $this->actingAs($this->pelanggan)->post('/checkout', ['metode' => 'tunai', 'jenis_layanan' => 'take_away']);
        $p = Pesanan::firstOrFail();

        $this->actingAs($this->pelanggan)->get('/checkout')->assertRedirect("/pesanan/{$p->id}/pembayaran")->assertSessionHas('status');
        $this->actingAs($this->pelanggan)->get('/keranjang')->assertSee("Pesanan {$p->kode()} sudah dibuat.");
    }

    public function test_checkout_delivery_memakai_peta(): void
    {
        $this->isiKeranjang();
        $this->actingAs($this->pelanggan)->get('/checkout')->assertSee(config('resto.alamat'))
            ->assertSee('Pakai lokasi saya')->assertSee('id="peta"', false)->assertSee('name="lat"', false);
    }

    public function test_metode_bayar_terkunci_setelah_dikonfirmasi_dan_teks_selesai(): void
    {
        $kasir = User::factory()->create(['role' => User::ROLE_KASIR]);
        $p = $this->buatPesanan();
        app(PesananService::class)->konfirmasi($p->fresh(), $kasir);

        $this->actingAs($this->pelanggan)->get("/tracking?pesanan={$p->id}")->assertDontSee('Ganti metode')->assertSee('Bayar tunai');
        $this->actingAs($this->pelanggan)->get("/pesanan/{$p->id}/pembayaran")->assertDontSee('Simpan Metode Pembayaran');
        $this->actingAs($this->pelanggan)->post("/pesanan/{$p->id}/pembayaran", ['metode' => 'tunai'])->assertSessionHasErrors('metode');

        // dimasak, siap, lalu diserahkan: jam setiap langkah tercatat dan teks pembayaran sesuai status
        $tiket = $p->fresh()->tiketDapur;
        app(DapurService::class)->mulaiMasak($tiket);
        app(DapurService::class)->selesaiMasak($tiket->fresh());
        app(PesananService::class)->lunasi($p->fresh(), $kasir, 'tunai', 1000000);
        app(PesananService::class)->selesaikan($p->fresh());

        $langkah = collect(TimelinePesanan::untuk($p->fresh()))->pluck('waktu', 'label');
        $this->assertNotNull($langkah['Dimasak']);
        $this->assertNotNull($langkah['Siap Diambil']);
        $this->assertNotNull($langkah['Selesai']);
        $this->actingAs($this->pelanggan)->get("/pesanan/{$p->id}/pembayaran")->assertSee('Pesanan sudah selesai')->assertDontSee('diteruskan ke dapur');
    }
}
