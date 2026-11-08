<?php

namespace Tests\Feature;

use App\Models\Menu;
use App\Models\Pembayaran;
use App\Models\PengaturanSistem;
use App\Models\Pesanan;
use App\Models\User;
use App\Services\KurirService;
use App\Services\PesananService;
use Database\Seeders\MasterDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Pembeli datang ke restoran lalu minta pesanannya diantar (ke rumahnya atau rumah orang lain).
 */
class KasirDeliveryTest extends TestCase
{
    use RefreshDatabase;

    private User $kasir;

    private Menu $pizza;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(MasterDataSeeder::class);
        Carbon::setTestNow(today()->setTime(12, 0));
        $this->kasir = User::factory()->create(['role' => User::ROLE_KASIR]);
        $this->pizza = Menu::where('nama_menu', 'Pizza Beef Pepperoni')->first();
    }

    /**
     * @param  array<string, mixed>  $ubah
     * @return array<string, mixed>
     */
    private function isian(array $ubah = []): array
    {
        return $ubah + [
            'jenis_layanan' => 'delivery',
            'nama_penerima' => 'Rina Amalia',
            'no_hp_penerima' => '0812-3456-7890',
            'alamat' => 'Jl. Sei Batang Hari No. 12, Medan',
            'lat' => PengaturanSistem::LAT_AWAL + 0.036, // ± 4 km → Zona B
            'lng' => PengaturanSistem::LNG_AWAL,
            'patokan' => 'Pagar hijau',
            'bayar' => PesananService::BAYAR_DI_KASIR,
            'items' => [['id_menu' => $this->pizza->id, 'jumlah' => 1]],
        ];
    }

    private function siap(Pesanan $p): void
    {
        $p->update(['status' => Pesanan::STATUS_SIAP]);
        $p->pengiriman->update(['waktu_siap' => now()]);
    }

    public function test_halaman_input_menampilkan_pilihan_delivery_dengan_peta(): void
    {
        $this->actingAs($this->kasir)->get('/kasir/input')->assertOk()
            ->assertSee('data-s="delivery"', false)->assertSee('id="peta"', false)
            ->assertSee('Nomor HP penerima')->assertSee('Bayar tunai ke kurir (COD)');
    }

    public function test_delivery_bayar_di_kasir_dilunasi_dulu_baru_diantar(): void
    {
        $this->actingAs($this->kasir)->post('/kasir/input', $this->isian())->assertSessionHasNoErrors();

        $p = Pesanan::with('pengiriman.zona')->sole();
        $kirim = $p->pengiriman;
        $this->assertSame([Pesanan::LAYANAN_DELIVERY, 'Rina Amalia', null], [$p->jenis_layanan, $p->nama_pemesan, $p->id_pelanggan]);
        $this->assertSame(['Rina Amalia', '081234567890', 'Pagar hijau', 'Zona B'], [$kirim->nama_penerima, $kirim->no_hp_penerima, $kirim->patokan, $kirim->zona->nama_zona]);
        $this->assertEquals($kirim->zona->ongkir(), (float) $p->ongkir);
        $this->assertEquals(app(PesananService::class)->hitung((float) $this->pizza->harga, $kirim->zona->ongkir())['total'], (float) $p->total);
        $this->assertNull($p->pembayaran);
        $this->actingAs($this->kasir)->get('/kasir/input')->assertSee('Lunasi sekarang di Pembayaran');

        // muncul di Belum Lunas; selama belum lunas kurir tidak bisa mengambilnya
        $this->actingAs($this->kasir)->get('/kasir/pembayaran')->assertSee($p->kode());
        $this->siap($p);
        $this->assertFalse(KurirService::antrean()->exists());

        $this->actingAs($this->kasir)->post("/kasir/pembayaran/{$p->id}/lunas", ['metode' => 'qris'])->assertSessionHasNoErrors();
        $this->assertTrue($p->fresh()->isLunas());
        $this->assertTrue(KurirService::antrean()->whereKey($kirim->id)->exists());
        $this->assertFalse(app(KurirService::class)->perluTagihTunai($p->fresh()));
    }

    public function test_delivery_cod_ditagih_kurir_bukan_kasir(): void
    {
        $this->actingAs($this->kasir)->post('/kasir/input', $this->isian(['bayar' => PesananService::BAYAR_KE_KURIR]))->assertSessionHasNoErrors();

        $p = Pesanan::sole();
        $this->assertSame([Pembayaran::METODE_TUNAI, Pembayaran::STATUS_MENUNGGU], [$p->pembayaran->metode, $p->pembayaran->status_bayar]);
        $this->assertEquals((float) $p->total, (float) $p->pembayaran->jumlah);
        $this->actingAs($this->kasir)->get('/kasir/input')->assertSee('membayar tunai ke kurir');

        $this->actingAs($this->kasir)->get('/kasir/pembayaran')->assertDontSee($p->kode());
        $this->actingAs($this->kasir)->post("/kasir/pembayaran/{$p->id}/lunas", ['metode' => 'tunai', 'uang_diterima' => '999999'])
            ->assertSessionHasErrors('pesanan');

        $this->siap($p);
        $this->assertTrue(KurirService::antrean()->exists());
        $this->assertTrue(app(KurirService::class)->perluTagihTunai($p->fresh()));

        $kurir = User::factory()->create(['role' => User::ROLE_KURIR]);
        $this->actingAs($kurir)->get("/kurir/tugas/{$p->pengiriman->id}")->assertOk()->assertSee('Rina Amalia')->assertSee('tel:081234567890', false);
    }

    public function test_isian_delivery_kasir_divalidasi(): void
    {
        $this->actingAs($this->kasir)->post('/kasir/input', $this->isian(['nama_penerima' => '', 'no_hp_penerima' => '---', 'lat' => null, 'bayar' => null]))
            ->assertSessionHasErrors(['nama_penerima', 'no_hp_penerima', 'lat', 'bayar']);

        // titik di luar jangkauan ditolak
        $this->actingAs($this->kasir)->post('/kasir/input', $this->isian(['lat' => PengaturanSistem::LAT_AWAL + 0.18]))
            ->assertSessionHasErrors('lat');
        $this->assertSame(0, Pesanan::count());

        // isian Delivery yang tersisa diabaikan untuk Take Away
        $this->actingAs($this->kasir)->post('/kasir/input', $this->isian(['jenis_layanan' => 'take_away', 'no_hp_penerima' => 'abc']))->assertSessionHasNoErrors();
        $this->assertNull(Pesanan::sole()->pengiriman);
    }

    public function test_kasir_mengubah_delivery_menjadi_take_away_dan_sebaliknya(): void
    {
        $this->actingAs($this->kasir)->post('/kasir/input', $this->isian(['bayar' => PesananService::BAYAR_KE_KURIR]));
        $p = Pesanan::sole();
        $this->actingAs($this->kasir)->get("/kasir/pesanan/{$p->id}/ubah")->assertOk()->assertSee('Jl. Sei Batang Hari No. 12, Medan')->assertSee('081234567890');

        $this->actingAs($this->kasir)->put("/kasir/pesanan/{$p->id}", ['jenis_layanan' => 'take_away', 'items' => [['id_menu' => $this->pizza->id, 'jumlah' => 2]]])
            ->assertSessionHasNoErrors();
        $p->refresh();
        $this->assertSame([Pesanan::LAYANAN_TAKE_AWAY, 0.0], [$p->jenis_layanan, (float) $p->ongkir]);
        $this->assertNull($p->pengiriman);
        $this->assertNull($p->pembayaran);

        $this->actingAs($this->kasir)->put("/kasir/pesanan/{$p->id}", $this->isian(['bayar' => PesananService::BAYAR_KE_KURIR, 'lat' => PengaturanSistem::LAT_AWAL + 0.018]))
            ->assertSessionHasNoErrors();
        $p->refresh();
        $this->assertSame('Zona A', $p->pengiriman->zona->nama_zona);
        $this->assertEquals((float) $p->total, (float) $p->pembayaran->jumlah);
    }
}
