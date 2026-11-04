<?php

namespace Tests\Feature;

use App\Models\Menu;
use App\Models\Pembayaran;
use App\Models\Pesanan;
use App\Models\TiketDapur;
use App\Models\User;
use App\Models\ZonaPengiriman;
use Database\Seeders\MasterDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class KasirTest extends TestCase
{
    use RefreshDatabase;

    private User $kasir;

    private User $pelanggan;

    private Menu $pizza; // Rp 109.000

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        $this->seed(MasterDataSeeder::class);
        Carbon::setTestNow(today()->setTime(12, 0));
        $this->kasir = User::factory()->create(['role' => User::ROLE_KASIR, 'nama' => 'Bima Saputra']);
        $this->pelanggan = User::factory()->create(['role' => User::ROLE_PELANGGAN, 'nama' => 'Sari Wulandari']);
        $this->pizza = Menu::where('nama_menu', 'Pizza Beef Pepperoni')->first();
    }

    private function pesananOnline(string $layanan = 'take_away', ?string $metode = 'tunai'): Pesanan
    {
        $this->actingAs($this->pelanggan)->postJson('/keranjang', ['id_menu' => $this->pizza->id]);
        $data = ['jenis_layanan' => $layanan] + ($layanan === 'delivery' ? ['alamat' => 'Jl. Karya 8', 'id_zona' => ZonaPengiriman::first()->id] : []);
        $this->actingAs($this->pelanggan)->post('/checkout', $data);
        $p = Pesanan::latest('id')->firstOrFail();
        if ($metode) {
            $this->actingAs($this->pelanggan)->post("/pesanan/{$p->id}/pembayaran", ['metode' => $metode]);
        }

        return $p->fresh();
    }

    public function test_halaman_kasir_tampil_dan_role_lain_ditolak(): void
    {
        $this->pesananOnline();
        foreach (['/kasir/dashboard', '/kasir/input', '/kasir/online', '/kasir/pembayaran'] as $url) {
            $this->actingAs($this->kasir)->get($url)->assertOk()->assertSee('Bima Saputra');
            $this->actingAs($this->pelanggan)->get($url)->assertForbidden();
        }
    }

    // ---------- konfirmasi pesanan online

    public function test_konfirmasi_pesanan_online_membuat_tiket_dapur(): void
    {
        $p = $this->pesananOnline();
        $this->actingAs($this->kasir)->get('/kasir/online')->assertSee($p->kode())->assertSee('Sari Wulandari')->assertSee('Bayar Tunai');

        $this->actingAs($this->kasir)->post("/kasir/online/{$p->id}/terima")->assertSessionHasNoErrors()->assertSessionHas('status');

        $p->refresh();
        $this->assertSame([Pesanan::STATUS_DIPROSES, $this->kasir->id], [$p->status, $p->id_kasir]);
        $this->assertSame(TiketDapur::STATUS_ANTRE, $p->tiketDapur->status_masak);
        $this->assertNotNull($p->tiketDapur->waktu_masuk);

        // tidak bisa dikonfirmasi dua kali, tiket tetap satu
        $this->actingAs($this->kasir)->post("/kasir/online/{$p->id}/terima")->assertSessionHasErrors('pesanan');
        $this->assertSame(1, TiketDapur::count());
        $this->actingAs($this->kasir)->get('/kasir/online')->assertDontSee($p->kode());
    }

    public function test_konfirmasi_butuh_metode_pembayaran(): void
    {
        $p = $this->pesananOnline(metode: null);
        $this->actingAs($this->kasir)->post("/kasir/online/{$p->id}/terima")->assertSessionHasErrors('pesanan');
        $this->assertSame(Pesanan::STATUS_MENUNGGU, $p->fresh()->status);

        $q = $this->pesananOnline(metode: 'tunai');
        $this->actingAs($this->kasir)->post("/kasir/online/{$q->id}/terima")->assertSessionHasNoErrors();
        $this->assertSame(Pesanan::STATUS_DIPROSES, $q->fresh()->status);
    }

    public function test_tolak_pesanan_online(): void
    {
        $p = $this->pesananOnline();
        $this->actingAs($this->kasir)->post("/kasir/online/{$p->id}/tolak")->assertSessionHasNoErrors();

        $this->assertSame(Pesanan::STATUS_DIBATALKAN, $p->fresh()->status);
        $this->assertNull($p->fresh()->tiketDapur);
        $this->actingAs($this->pelanggan)->get("/tracking?pesanan={$p->id}")->assertSee('dibatalkan oleh kasir');
        $this->actingAs($this->kasir)->get('/kasir/pembayaran')->assertDontSee($p->kode());
    }

    // ---------- pembayaran

    public function test_pesanan_online_tunai_dibayar_di_kasir(): void
    {
        $p = $this->pesananOnline();
        $this->actingAs($this->kasir)->get("/kasir/pembayaran?pesanan={$p->id}")->assertSee($p->kode())->assertSee('Sari Wulandari');

        $this->actingAs($this->kasir)->post("/kasir/pembayaran/{$p->id}/lunas", ['metode' => 'tunai', 'uang_diterima' => '200000'])->assertSessionHasNoErrors();
        $bayar = $p->fresh()->pembayaran;
        $this->assertSame([Pembayaran::STATUS_LUNAS, $this->kasir->id], [$bayar->status_bayar, $bayar->id_kasir]);
        $this->assertNotNull($bayar->waktu_bayar);
        $this->actingAs($this->kasir)->get('/kasir/pembayaran')->assertDontSee(route('kasir.pembayaran', ['pesanan' => $p->id]));

        // pelanggan tidak bisa mengubah pembayaran yang sudah lunas
        $this->actingAs($this->pelanggan)->post("/pesanan/{$p->id}/pembayaran", ['metode' => 'tunai'])->assertSessionHasErrors('metode');
    }

    public function test_pembayaran_tunai_dan_kembalian_di_struk(): void
    {
        $this->actingAs($this->kasir)->post('/kasir/input', ['jenis_layanan' => 'dine_in', 'nomor_meja' => '12', 'items' => [['id_menu' => $this->pizza->id, 'jumlah' => 1]]]);
        $p = Pesanan::firstOrFail(); // 109.000 + 10.900 + 5.450 = 125.350

        $this->actingAs($this->kasir)->post("/kasir/pembayaran/{$p->id}/lunas", ['metode' => 'tunai', 'uang_diterima' => '100.000'])
            ->assertSessionHasErrors('uang_diterima');
        $this->assertFalse($p->fresh()->isLunas());

        $this->actingAs($this->kasir)->post("/kasir/pembayaran/{$p->id}/lunas", ['metode' => 'tunai', 'uang_diterima' => '150.000', 'cetak' => '1'])
            ->assertSessionHasNoErrors()->assertSessionHas('struk', $p->id);
        $this->assertTrue($p->fresh()->isLunas());

        $this->actingAs($this->kasir)->get('/kasir/pembayaran')->assertSee('Cetak Struk')->assertSee('Kembalian <b>Rp 24.650</b>', false);
        $this->actingAs($this->kasir)->get("/kasir/pesanan/{$p->id}/struk?tunai=150000")->assertOk()
            ->assertSee('Meja 12')->assertSee('Rp 125.350')->assertSee('Rp 24.650')->assertSee('LUNAS');

        $this->actingAs($this->kasir)->post("/kasir/pembayaran/{$p->id}/lunas", ['metode' => 'tunai', 'uang_diterima' => '200000'])->assertSessionHasErrors('pesanan');
    }

    public function test_struk_hanya_untuk_pesanan_lunas(): void
    {
        $p = $this->pesananOnline();
        $this->actingAs($this->kasir)->get("/kasir/pesanan/{$p->id}/struk")->assertNotFound();
    }

    // ---------- input pesanan di tempat

    public function test_input_pesanan_di_tempat_langsung_ke_dapur(): void
    {
        $this->actingAs($this->kasir)->post('/kasir/input', ['jenis_layanan' => 'dine_in', 'items' => [['id_menu' => $this->pizza->id, 'jumlah' => 1]]])
            ->assertSessionHasErrors('nomor_meja');
        $this->actingAs($this->kasir)->post('/kasir/input', ['jenis_layanan' => 'take_away', 'items' => []])->assertSessionHasErrors('items');
        $habis = Menu::where('tersedia', false)->first();
        $this->actingAs($this->kasir)->post('/kasir/input', ['jenis_layanan' => 'take_away', 'items' => [['id_menu' => $habis->id, 'jumlah' => 1]]])
            ->assertSessionHasErrors('items');

        $this->actingAs($this->kasir)->post('/kasir/input', [
            'jenis_layanan' => 'take_away', 'nama_pemesan' => 'Fajar Nugraha',
            'items' => [['id_menu' => $this->pizza->id, 'jumlah' => 2, 'catatan' => 'potong 12']],
        ])->assertRedirect('/kasir/input')->assertSessionHas('pesanan_tersimpan');

        $p = Pesanan::firstOrFail();
        $this->assertNull($p->id_pelanggan);
        $this->assertSame([Pesanan::STATUS_DIPROSES, $this->kasir->id, 'Fajar Nugraha'], [$p->status, $p->id_kasir, $p->nama_pemesan]);
        $this->assertSame('potong 12', $p->detail->first()->catatan);
        $this->assertSame(TiketDapur::STATUS_ANTRE, $p->tiketDapur->status_masak);
        $this->actingAs($this->kasir)->get('/kasir/input')->assertSee('Pesanan tersimpan')->assertSee('urutan ke-1');
        $this->actingAs($this->kasir)->get('/kasir/pembayaran')->assertSee($p->kode())->assertSee('Fajar Nugraha');
    }

    public function test_meja_terpakai_ditandai(): void
    {
        $this->actingAs($this->kasir)->post('/kasir/input', ['jenis_layanan' => 'dine_in', 'nomor_meja' => '05', 'items' => [['id_menu' => $this->pizza->id, 'jumlah' => 1]]]);
        $this->actingAs($this->kasir)->get('/kasir/input')->assertSee('data-meja="05" class="busy"', false);
    }

    // ---------- selesaikan pesanan

    public function test_selesaikan_pesanan_siap_dan_lunas(): void
    {
        $this->actingAs($this->kasir)->post('/kasir/input', ['jenis_layanan' => 'dine_in', 'nomor_meja' => '03', 'items' => [['id_menu' => $this->pizza->id, 'jumlah' => 1]]]);
        $p = Pesanan::firstOrFail();

        $this->actingAs($this->kasir)->post("/kasir/pesanan/{$p->id}/selesai")->assertSessionHasErrors('pesanan'); // belum siap
        $p->update(['status' => Pesanan::STATUS_SIAP]);
        $this->actingAs($this->kasir)->post("/kasir/pesanan/{$p->id}/selesai")->assertSessionHasErrors('pesanan'); // belum lunas

        $this->actingAs($this->kasir)->post("/kasir/pembayaran/{$p->id}/lunas", ['metode' => 'qris']);
        $this->actingAs($this->kasir)->get('/kasir/dashboard')->assertSee(route('kasir.selesai', $p));
        $this->actingAs($this->kasir)->post("/kasir/pesanan/{$p->id}/selesai")->assertSessionHasNoErrors();
        $this->assertSame(Pesanan::STATUS_SELESAI, $p->fresh()->status);

        $d = $this->pesananOnline('delivery', 'tunai');
        $d->update(['status' => Pesanan::STATUS_SIAP]);
        $this->actingAs($this->kasir)->post("/kasir/pesanan/{$d->id}/selesai")->assertSessionHasErrors('pesanan'); // delivery oleh kurir
    }

    public function test_dashboard_kasir_menghitung_data_hari_ini(): void
    {
        $this->pesananOnline();
        $this->actingAs($this->kasir)->post('/kasir/input', ['jenis_layanan' => 'dine_in', 'nomor_meja' => '01', 'items' => [['id_menu' => $this->pizza->id, 'jumlah' => 1]]]);
        $q = Pesanan::latest('id')->first();
        $this->actingAs($this->kasir)->post("/kasir/pembayaran/{$q->id}/lunas", ['metode' => 'tunai', 'uang_diterima' => '200000']);

        $this->actingAs($this->kasir)->get('/kasir/dashboard')->assertOk()
            ->assertSee('Dine In 1 · Take Away 1 · Delivery 0')
            ->assertSee('1 pembayaran lunas')
            ->assertSee('Rp 125.350')
            ->assertSee('Pesanan online masuk');
    }
}
