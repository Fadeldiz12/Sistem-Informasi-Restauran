<?php

namespace Tests\Feature;

use App\Models\BahanBaku;
use App\Models\Menu;
use App\Models\MutasiBahanBaku;
use App\Models\Pembayaran;
use App\Models\Pesanan;
use App\Models\TiketDapur;
use App\Models\User;
use App\Models\ZonaPengiriman;
use App\Services\PesananService;
use Database\Seeders\BahanBakuSeeder;
use Database\Seeders\MasterDataSeeder;
use Database\Seeders\ResepSeeder;
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
        $data = ['jenis_layanan' => $layanan] + ($layanan === 'delivery' ? ['alamat' => 'Jl. Karya 8', 'id_zona' => ZonaPengiriman::first()->id] : []);
        if (! $metode) {
            // pesanan lama (sebelum metode bayar wajib dipilih di checkout)
            return app(PesananService::class)->buat([['id_menu' => $this->pizza->id, 'jumlah' => 1]], $layanan, $this->pelanggan, data: $data);
        }

        $this->actingAs($this->pelanggan)->postJson('/keranjang', ['id_menu' => $this->pizza->id]);
        $this->actingAs($this->pelanggan)->post('/checkout', $data + ['metode' => $metode])->assertSessionHasNoErrors();

        return Pesanan::latest('id')->firstOrFail();
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
        $this->actingAs($this->kasir)->post("/kasir/online/{$p->id}/tolak")->assertSessionHasErrors('alasan');
        $this->actingAs($this->kasir)->post("/kasir/online/{$p->id}/tolak", ['alasan' => 'Lainnya'])->assertSessionHasErrors('alasan_lain');
        $this->actingAs($this->kasir)->post("/kasir/online/{$p->id}/tolak", ['alasan' => 'Menu habis'])->assertSessionHasNoErrors();

        $this->assertSame([Pesanan::STATUS_DIBATALKAN, 'Menu habis'], [$p->fresh()->status, $p->fresh()->alasan_batal]);
        $this->assertNull($p->fresh()->tiketDapur);
        $this->actingAs($this->pelanggan)->get("/tracking?pesanan={$p->id}")->assertSee('dibatalkan oleh kasir. Alasan: Menu habis');
        // dikonfirmasi dari tab lama: pesannya jelas
        $this->actingAs($this->kasir)->post("/kasir/online/{$p->id}/terima")->assertSessionHasErrors(['pesanan' => "{$p->kode()} sudah ditolak/dibatalkan."]);
        $this->actingAs($this->kasir)->get('/kasir/pembayaran')->assertDontSee($p->kode());
    }

    // ---------- pembayaran

    public function test_pesanan_online_tunai_dibayar_di_kasir(): void
    {
        $p = $this->pesananOnline();
        $this->actingAs($this->kasir)->get('/kasir/pembayaran')->assertDontSee($p->kode()); // belum dikonfirmasi
        $this->actingAs($this->kasir)->post("/kasir/pembayaran/{$p->id}/lunas", ['metode' => 'tunai', 'uang_diterima' => '200000'])->assertSessionHasErrors('pesanan');
        $this->assertFalse($p->fresh()->isLunas());

        $this->actingAs($this->kasir)->post("/kasir/online/{$p->id}/terima");
        $this->actingAs($this->kasir)->get("/kasir/pembayaran?pesanan={$p->id}")->assertSee($p->kode())->assertSee('Sari Wulandari');

        $this->actingAs($this->kasir)->post("/kasir/pembayaran/{$p->id}/lunas", ['metode' => 'tunai', 'uang_diterima' => '200000'])->assertSessionHasNoErrors();
        $bayar = $p->fresh()->pembayaran;
        $this->assertSame([Pembayaran::STATUS_LUNAS, $this->kasir->id], [$bayar->status_bayar, $bayar->id_kasir]);
        $this->assertNotNull($bayar->waktu_bayar);
        $this->actingAs($this->kasir)->get('/kasir/pembayaran')->assertDontSee(route('kasir.pembayaran', ['pesanan' => $p->id]));

        // pelanggan tidak bisa mengubah pembayaran yang sudah lunas
        $this->actingAs($this->pelanggan)->post("/pesanan/{$p->id}/pembayaran", ['metode' => 'tunai'])->assertSessionHasErrors('metode');
    }

    public function test_pesanan_delivery_tunai_tidak_bisa_dilunasi_kasir(): void
    {
        $p = $this->pesananOnline('delivery');
        $this->actingAs($this->kasir)->post("/kasir/online/{$p->id}/terima");

        $this->actingAs($this->kasir)->get('/kasir/pembayaran')->assertDontSee(route('kasir.pembayaran', ['pesanan' => $p->id]));
        $this->actingAs($this->kasir)->post("/kasir/pembayaran/{$p->id}/lunas", ['metode' => 'tunai', 'uang_diterima' => '500000'])
            ->assertSessionHasErrors(['pesanan' => 'Pesanan Delivery dibayar ke kurir saat diantar, tidak dilunasi di kasir.']);
        $this->assertFalse($p->fresh()->isLunas());
    }

    public function test_total_pesanan_terlalu_besar_ditolak_dengan_pesan(): void
    {
        $this->pizza->update(['harga' => Menu::HARGA_MAKS]);

        $this->actingAs($this->kasir)->post('/kasir/input', ['jenis_layanan' => 'take_away', 'items' => [['id_menu' => $this->pizza->id, 'jumlah' => 99]]])
            ->assertSessionHasErrors(['items' => 'Total pesanan terlalu besar. Kurangi jumlah porsi atau periksa harga menu.']);
        $this->assertSame(0, Pesanan::count());
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

        $this->assertEquals(150000, (float) $p->fresh()->pembayaran->uang_diterima);

        $this->actingAs($this->kasir)->get('/kasir/pembayaran')->assertSee('Cetak Struk')->assertSee('Kembalian <b>Rp 24.650</b>', false);
        $this->actingAs($this->kasir)->get("/kasir/pesanan/{$p->id}/struk")->assertOk()
            ->assertSee('Meja 12')->assertSee('Rp 125.350')->assertSee('Rp 150.000')->assertSee('Rp 24.650')->assertSee('LUNAS');

        // angka di struk diambil dari database, tidak bisa diubah lewat URL
        $this->actingAs($this->kasir)->get("/kasir/pesanan/{$p->id}/struk?tunai=5000000")->assertOk()
            ->assertDontSee('Rp 5.000.000')->assertSee('Rp 24.650');

        // cetak ulang dari tab Sudah Lunas dan dari dashboard
        $this->actingAs($this->kasir)->get('/kasir/pembayaran?tab=lunas')->assertSee($p->kode())->assertSee('Cetak ulang struk')
            ->assertSee(route('kasir.struk', $p));
        $this->actingAs($this->kasir)->get('/kasir/dashboard')->assertSee(route('kasir.struk', $p));

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

    // ---------- batal & ubah pesanan

    private function inputKasir(string $meja = '05', int $jumlah = 1): Pesanan
    {
        $this->actingAs($this->kasir)->post('/kasir/input', ['jenis_layanan' => 'dine_in', 'nomor_meja' => $meja, 'items' => [['id_menu' => $this->pizza->id, 'jumlah' => $jumlah]]])
            ->assertSessionHasNoErrors();

        return Pesanan::latest('id')->firstOrFail();
    }

    public function test_kasir_membatalkan_pesanan_dan_tiket_hilang_dari_antrean(): void
    {
        $p = $this->inputKasir('05');
        $this->assertNotNull($p->tiketDapur);

        $this->actingAs($this->kasir)->post("/kasir/pesanan/{$p->id}/batal", ['alasan' => ''])->assertSessionHasErrors('alasan');
        $this->actingAs($this->kasir)->post("/kasir/pesanan/{$p->id}/batal", ['alasan' => 'Salah input kasir'])->assertSessionHas('status');

        $p->refresh();
        $this->assertSame([Pesanan::STATUS_DIBATALKAN, 'Salah input kasir'], [$p->status, $p->alasan_batal]);
        $this->assertNull($p->tiketDapur);
        $this->inputKasir('05'); // meja kosong lagi

        $this->actingAs($this->kasir)->post("/kasir/pesanan/{$p->id}/batal", ['alasan' => 'lagi'])->assertSessionHasErrors('pesanan');
    }

    public function test_batal_setelah_mulai_masak_bisa_mengembalikan_stok(): void
    {
        $this->seed([BahanBakuSeeder::class, ResepSeeder::class]);
        $keju = BahanBaku::firstWhere('nama_bahan', 'Keju Mozzarella');
        $awal = (float) $keju->stok;
        $p = $this->inputKasir('06', 2);
        $dapur = User::factory()->create(['role' => User::ROLE_DAPUR]);
        $this->actingAs($dapur)->post("/dapur/tiket/{$p->tiketDapur->id}/mulai");
        $this->assertLessThan($awal, (float) $keju->fresh()->stok);

        $this->actingAs($this->kasir)->get('/kasir/dashboard')->assertSee('data-dimasak="1"', false);
        $this->actingAs($this->kasir)->post("/kasir/pesanan/{$p->id}/batal", ['alasan' => 'Pelanggan batal', 'kembalikan_stok' => 1])
            ->assertSessionHas('status', fn ($m) => str_contains($m, 'stok'));

        $this->assertEqualsWithDelta($awal, (float) $keju->fresh()->stok, 0.001);
        $this->assertSame(0, MutasiBahanBaku::where('id_pesanan', $p->id)->count());
    }

    public function test_pesanan_lunas_atau_online_menunggu_tidak_bisa_dibatalkan_kasir(): void
    {
        $p = $this->inputKasir('07');
        $this->actingAs($this->kasir)->post("/kasir/pembayaran/{$p->id}/lunas", ['metode' => 'qris']);
        $this->actingAs($this->kasir)->post("/kasir/pesanan/{$p->id}/batal", ['alasan' => 'x'])->assertSessionHasErrors('pesanan');

        $q = $this->pesananOnline();
        $this->actingAs($this->kasir)->post("/kasir/pesanan/{$q->id}/batal", ['alasan' => 'x'])->assertSessionHasErrors('pesanan');
        $this->assertSame(Pesanan::STATUS_MENUNGGU, $q->fresh()->status);
    }

    public function test_kasir_mengubah_pesanan_sebelum_dapur_mulai_memasak(): void
    {
        $p = $this->inputKasir('03');
        $teh = Menu::where('nama_menu', 'Lemon Iced Tea')->firstOrFail();

        $this->actingAs($this->kasir)->get('/kasir/dashboard')->assertSee(route('kasir.pesanan.ubah', $p));
        $this->actingAs($this->kasir)->get("/kasir/pesanan/{$p->id}/ubah")->assertOk()->assertSee('Ubah Pesanan '.$p->kode())->assertSee('Simpan Perubahan');

        $this->actingAs($this->kasir)->put("/kasir/pesanan/{$p->id}", ['jenis_layanan' => 'dine_in', 'nomor_meja' => '04', 'items' => [
            ['id_menu' => $this->pizza->id, 'jumlah' => 2], ['id_menu' => $teh->id, 'jumlah' => 1, 'catatan' => 'es sedikit'],
        ]])->assertSessionHasNoErrors()->assertRedirect('/kasir/dashboard');

        $p->refresh();
        $subtotal = 2 * (float) $this->pizza->harga + (float) $teh->harga;
        $this->assertSame('04', $p->nomor_meja);
        $this->assertEquals($subtotal, (float) $p->subtotal);
        $this->assertEquals(app(PesananService::class)->hitung($subtotal)['total'], (float) $p->total);
        $this->assertSame(2, $p->detail()->count());
        $this->assertSame(1, Pesanan::count());

        // setelah dapur mulai memasak, pesanan tidak bisa diubah lagi
        $p->tiketDapur->update(['status_masak' => TiketDapur::STATUS_DIMASAK]);
        $this->actingAs($this->kasir)->get("/kasir/pesanan/{$p->id}/ubah")->assertRedirect('/kasir/dashboard')->assertSessionHas('error');
        $this->actingAs($this->kasir)->put("/kasir/pesanan/{$p->id}", ['jenis_layanan' => 'take_away', 'items' => [['id_menu' => $teh->id, 'jumlah' => 1]]])
            ->assertSessionHasErrors('pesanan');
        $this->assertSame(2, $p->detail()->count());

        // pesanan online tidak bisa diubah kasir
        $q = $this->pesananOnline();
        $this->actingAs($this->kasir)->get("/kasir/pesanan/{$q->id}/ubah")->assertRedirect('/kasir/dashboard');
    }

    public function test_nomor_meja_divalidasi_di_server(): void
    {
        $item = ['items' => [['id_menu' => $this->pizza->id, 'jumlah' => 1]]];
        $this->actingAs($this->kasir)->post('/kasir/input', ['jenis_layanan' => 'dine_in', 'nomor_meja' => '99'] + $item)->assertSessionHasErrors('nomor_meja');

        $p = $this->inputKasir('07');
        $this->actingAs($this->kasir)->post('/kasir/input', ['jenis_layanan' => 'dine_in', 'nomor_meja' => '07'] + $item)
            ->assertSessionHasErrors(['nomor_meja' => "Meja 07 sedang dipakai pesanan {$p->kode()}. Pilih meja lain, atau ubah pesanan {$p->kode()} untuk menambah menu."]);
        $this->assertSame(1, Pesanan::count());

        // pelanggan: nomor meja harus ada di restoran
        $this->actingAs($this->pelanggan)->postJson('/keranjang', ['id_menu' => $this->pizza->id]);
        $this->actingAs($this->pelanggan)->post('/checkout', ['jenis_layanan' => 'dine_in', 'nomor_meja' => 'meja mana?', 'metode' => 'tunai'])
            ->assertSessionHasErrors('nomor_meja');
        $this->actingAs($this->pelanggan)->post('/checkout', ['jenis_layanan' => 'dine_in', 'nomor_meja' => '3', 'metode' => 'tunai'])->assertSessionHasNoErrors();
        $this->assertSame('03', Pesanan::latest('id')->first()->nomor_meja);
    }

    public function test_pesan_error_input_pesanan_mudah_dipahami(): void
    {
        $this->actingAs($this->kasir)->post('/kasir/input', ['jenis_layanan' => 'take_away', 'items' => [['id_menu' => $this->pizza->id, 'jumlah' => 0]]])
            ->assertSessionHasErrors(['items.0.jumlah' => 'Jumlah porsi minimal 1.']);
        $this->actingAs($this->kasir)->post('/kasir/input', ['jenis_layanan' => 'take_away', 'items' => [['id_menu' => 9999, 'jumlah' => 1]]])
            ->assertSessionHasErrors(['items.0.id_menu' => 'Salah satu menu sudah tidak tersedia. Muat ulang halaman lalu pilih lagi.']);
    }

    public function test_notifikasi_pesanan_siap_untuk_kasir(): void
    {
        $p = $this->inputKasir('09');
        $this->actingAs($this->kasir)->getJson('/kasir/notifikasi')->assertOk()->assertJson(['siap' => 0]);

        $p->update(['status' => Pesanan::STATUS_SIAP]);
        $this->actingAs($this->kasir)->getJson('/kasir/notifikasi')->assertJson(['siap' => 1, 'pesanan' => [['id' => $p->id, 'kode' => $p->kode()]]]);
        $this->actingAs($this->kasir)->get('/kasir/input')->assertSee('data-badge-siap', false);
        $this->actingAs($this->pelanggan)->getJson('/kasir/notifikasi')->assertForbidden();
    }
}
