<?php

namespace Tests\Feature;

use App\Models\BahanBaku;
use App\Models\Menu;
use App\Models\MutasiBahanBaku;
use App\Models\Pesanan;
use App\Models\TiketDapur;
use App\Models\User;
use App\Models\ZonaPengiriman;
use App\Services\PesananService;
use Database\Seeders\BahanBakuSeeder;
use Database\Seeders\MasterDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class DapurTest extends TestCase
{
    use RefreshDatabase;

    private User $dapur;

    private User $kasir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(MasterDataSeeder::class);
        Carbon::setTestNow(today()->setTime(12, 0));
        $this->dapur = User::factory()->create(['role' => User::ROLE_DAPUR, 'nama' => 'Hendra Wijaya']);
        $this->kasir = User::factory()->create(['role' => User::ROLE_KASIR]);
    }

    private function pesananKasir(string $layanan = 'dine_in', array $data = ['nomor_meja' => '07']): Pesanan
    {
        $menu = Menu::where('tersedia', true)->first();

        return app(PesananService::class)->buat([['id_menu' => $menu->id, 'jumlah' => 2, 'catatan' => 'potong 12']], $layanan, kasir: $this->kasir, data: $data);
    }

    // ---------- antrean tiket

    public function test_antrean_fifo_dan_role_lain_ditolak(): void
    {
        $a = $this->pesananKasir();
        $this->travel(2)->minutes();
        $b = $this->pesananKasir('take_away', ['nama_pemesan' => 'Fajar']);

        $this->actingAs($this->dapur)->get('/dapur/antrean')->assertOk()
            ->assertSee('Hendra Wijaya')
            ->assertSeeInOrder([$a->tiketDapur->kode(), $b->tiketDapur->kode()])
            ->assertSee('potong 12')->assertSee('Meja 07');

        $this->actingAs($this->kasir)->get('/dapur/antrean')->assertForbidden();
        $this->actingAs($this->kasir)->post("/dapur/tiket/{$a->tiketDapur->id}/mulai")->assertForbidden();
    }

    public function test_ubah_status_masakan_sampai_siap(): void
    {
        $p = $this->pesananKasir();
        $t = $p->tiketDapur;

        // tidak bisa langsung selesai sebelum dimasak
        $this->actingAs($this->dapur)->post("/dapur/tiket/{$t->id}/selesai")->assertSessionHasErrors('tiket');

        $this->actingAs($this->dapur)->post("/dapur/tiket/{$t->id}/mulai")->assertSessionHasNoErrors();
        $this->assertSame(TiketDapur::STATUS_DIMASAK, $t->fresh()->status_masak);
        $this->assertSame(Pesanan::STATUS_DIPROSES, $p->fresh()->status);
        $this->actingAs($this->dapur)->post("/dapur/tiket/{$t->id}/mulai")->assertSessionHasErrors('tiket');

        $this->actingAs($this->dapur)->post("/dapur/tiket/{$t->id}/selesai")->assertSessionHasNoErrors();
        $this->assertSame(TiketDapur::STATUS_SELESAI, $t->fresh()->status_masak);
        $this->assertSame(Pesanan::STATUS_SIAP, $p->fresh()->status);

        // tiket selesai keluar dari antrean
        $this->actingAs($this->dapur)->get('/dapur/antrean')->assertDontSee($t->kode().'</b>', false)->assertSee('Antrean kosong');
    }

    public function test_delivery_selesai_masuk_antrean_kurir(): void
    {
        $pelanggan = User::factory()->create(['role' => User::ROLE_PELANGGAN]);
        $menu = Menu::where('tersedia', true)->first();
        $svc = app(PesananService::class);
        $p = $svc->buat([['id_menu' => $menu->id, 'jumlah' => 1]], 'delivery', $pelanggan, data: ['alamat' => 'Jl. A', 'id_zona' => ZonaPengiriman::first()->id]);
        $p->pembayaran()->create(['metode' => 'tunai', 'jumlah' => $p->total, 'status_bayar' => 'menunggu']);
        $svc->konfirmasi($p, $this->kasir);
        $t = $p->fresh()->tiketDapur;
        $this->assertNull($p->pengiriman->waktu_siap);

        $this->actingAs($this->dapur)->post("/dapur/tiket/{$t->id}/mulai");
        $this->actingAs($this->dapur)->post("/dapur/tiket/{$t->id}/selesai");

        $p->refresh();
        $this->assertSame(Pesanan::STATUS_SIAP, $p->status);
        $this->assertNotNull($p->pengiriman->waktu_siap);
        $this->actingAs($pelanggan)->get("/tracking?pesanan={$p->id}")->assertSee('class="st done"', false)->assertSee('Siap Diantar');
    }

    // ---------- bahan baku & ROP

    public function test_tambah_bahan_langsung_menghitung_rop(): void
    {
        $this->actingAs($this->dapur)->post('/dapur/bahan-baku', [
            'nama_bahan' => 'Keju Cheddar', 'satuan' => 'kg', 'stok' => '10', 'lead_time' => 2, 'safety_stock' => '1,5',
        ])->assertSessionHasNoErrors();

        $b = BahanBaku::where('nama_bahan', 'Keju Cheddar')->firstOrFail();
        // belum ada pemakaian: d = 0, ROP = SS
        $this->assertEquals([10, 0, 1.5, 1.5], [(float) $b->stok, (float) $b->rata_pemakaian, (float) $b->safety_stock, (float) $b->rop]);
        $this->assertSame(1, $b->mutasi()->where('jenis', 'masuk')->count()); // stok awal tercatat

        $this->actingAs($this->dapur)->post('/dapur/bahan-baku', ['nama_bahan' => 'Keju Cheddar', 'satuan' => 'kg', 'stok' => -1, 'lead_time' => -1, 'safety_stock' => 'x'])
            ->assertSessionHasErrors(['nama_bahan', 'stok', 'lead_time', 'safety_stock']);
    }

    public function test_mutasi_memperbarui_stok_dan_menghitung_ulang_rop(): void
    {
        $this->actingAs($this->dapur)->post('/dapur/bahan-baku', ['nama_bahan' => 'Tepung', 'satuan' => 'kg', 'stok' => 50, 'lead_time' => 3, 'safety_stock' => 6]);
        $b = BahanBaku::firstOrFail();

        // pemakaian 60 kg dalam 30 hari terakhir -> d = 2, ROP = 2 x 3 + 6 = 12
        $this->actingAs($this->dapur)->post('/dapur/mutasi', ['id_bahan' => $b->id, 'jenis' => 'masuk', 'jumlah' => 20, 'tanggal' => today()->toDateString()])->assertSessionHasNoErrors();
        $this->actingAs($this->dapur)->post('/dapur/mutasi', ['id_bahan' => $b->id, 'jenis' => 'pemakaian', 'jumlah' => 40, 'tanggal' => today()->subDays(5)->toDateString()]);
        $this->actingAs($this->dapur)->post('/dapur/mutasi', ['id_bahan' => $b->id, 'jenis' => 'pemakaian', 'jumlah' => 20, 'tanggal' => today()->toDateString()]);
        $b->refresh();
        $this->assertEquals([10, 2, 12], [(float) $b->stok, (float) $b->rata_pemakaian, (float) $b->rop]);
        $this->assertTrue($b->isStokKritis()); // 10 <= 12

        // pemakaian lebih dari 30 hari lalu tidak dihitung
        $this->actingAs($this->dapur)->post('/dapur/mutasi', ['id_bahan' => $b->id, 'jenis' => 'masuk', 'jumlah' => 100, 'tanggal' => today()->subDays(40)->toDateString()]);
        $this->assertEquals(2, (float) $b->fresh()->rata_pemakaian);
        $this->assertFalse($b->fresh()->isStokKritis()); // 110 > 12

        // ubah lead time -> ROP ikut berubah
        $this->actingAs($this->dapur)->put("/dapur/bahan-baku/{$b->id}", ['nama_bahan' => 'Tepung', 'satuan' => 'kg', 'lead_time' => 5, 'safety_stock' => 6]);
        $this->assertEquals(16, (float) $b->fresh()->rop);
        $this->actingAs($this->dapur)->put("/dapur/bahan-baku/{$b->id}", ['nama_bahan' => 'Tepung', 'satuan' => 'kg', 'stok' => 999, 'lead_time' => 5, 'safety_stock' => 6])
            ->assertSessionHasErrors('stok');
    }

    public function test_validasi_mutasi(): void
    {
        $this->actingAs($this->dapur)->post('/dapur/bahan-baku', ['nama_bahan' => 'Lemon', 'satuan' => 'kg', 'stok' => 3, 'lead_time' => 1, 'safety_stock' => 0.5]);
        $b = BahanBaku::firstOrFail();

        $this->actingAs($this->dapur)->post('/dapur/mutasi', ['id_bahan' => $b->id, 'jenis' => 'pemakaian', 'jumlah' => 5, 'tanggal' => today()->toDateString()])
            ->assertSessionHasErrors('jumlah');
        $this->actingAs($this->dapur)->post('/dapur/mutasi', ['id_bahan' => 999, 'jenis' => 'rusak', 'jumlah' => 0, 'tanggal' => today()->addDay()->toDateString()])
            ->assertSessionHasErrors(['id_bahan', 'jenis', 'jumlah', 'tanggal']);
        $this->assertEquals(3, (float) $b->fresh()->stok);
        $this->assertSame(1, MutasiBahanBaku::count());
    }

    public function test_notifikasi_stok_kritis_dan_hapus_bahan(): void
    {
        $this->seed(BahanBakuSeeder::class);

        $this->actingAs($this->dapur)->get('/dapur/bahan-baku')->assertOk()
            ->assertSee('Notifikasi stok kritis: Daging Beef Pepperoni, Keju Mozzarella dan Saus Tomat')
            ->assertSee('Kritis · pesan ulang');
        $this->actingAs($this->dapur)->get('/dapur/antrean')->assertSee('Notifikasi stok kritis');

        $b = BahanBaku::where('nama_bahan', 'Lemon')->first();
        $this->actingAs($this->dapur)->delete("/dapur/bahan-baku/{$b->id}")->assertSessionHas('status');
        $this->assertModelMissing($b);
        $this->assertSame(0, MutasiBahanBaku::where('id_bahan', $b->id)->count());
    }
}
