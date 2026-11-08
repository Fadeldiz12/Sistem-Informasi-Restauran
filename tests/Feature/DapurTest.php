<?php

namespace Tests\Feature;

use App\Models\BahanBaku;
use App\Models\Menu;
use App\Models\MutasiBahanBaku;
use App\Models\Pesanan;
use App\Models\TiketDapur;
use App\Models\User;
use App\Models\ZonaPengiriman;
use App\Services\BahanBakuService;
use App\Services\PesananService;
use Database\Seeders\BahanBakuSeeder;
use Database\Seeders\MasterDataSeeder;
use Database\Seeders\ResepSeeder;
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
            ->assertSeeInOrder([$a->kode(), $b->kode()])->assertSee($a->tiketDapur->kode())
            ->assertSee('potong 12')->assertSee('Meja 07');

        $this->actingAs($this->kasir)->get('/dapur/antrean')->assertForbidden();
        $this->actingAs($this->kasir)->post("/dapur/tiket/{$a->tiketDapur->getRouteKey()}/mulai")->assertForbidden();
    }

    public function test_ubah_status_masakan_sampai_siap(): void
    {
        $p = $this->pesananKasir();
        $t = $p->tiketDapur;

        // tidak bisa langsung selesai sebelum dimasak
        $this->actingAs($this->dapur)->post("/dapur/tiket/{$t->getRouteKey()}/selesai")->assertSessionHasErrors('tiket');

        $this->actingAs($this->dapur)->post("/dapur/tiket/{$t->getRouteKey()}/mulai")->assertSessionHasNoErrors();
        $this->assertSame(TiketDapur::STATUS_DIMASAK, $t->fresh()->status_masak);
        $this->assertSame(Pesanan::STATUS_DIPROSES, $p->fresh()->status);
        $this->actingAs($this->dapur)->post("/dapur/tiket/{$t->getRouteKey()}/mulai")->assertSessionHasErrors('tiket');

        $this->actingAs($this->dapur)->post("/dapur/tiket/{$t->getRouteKey()}/selesai")->assertSessionHasNoErrors();
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

        $this->actingAs($this->dapur)->post("/dapur/tiket/{$t->getRouteKey()}/mulai");
        $this->actingAs($this->dapur)->post("/dapur/tiket/{$t->getRouteKey()}/selesai");

        $p->refresh();
        $this->assertSame(Pesanan::STATUS_SIAP, $p->status);
        $this->assertNotNull($p->pengiriman->waktu_siap);
        $this->actingAs($pelanggan)->get("/tracking?pesanan={$p->getRouteKey()}")->assertSee('class="st done"', false)->assertSee('Siap Diantar');
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
        app(BahanBakuService::class)->catatMutasi($b->fresh(), 'pemakaian', 9, today()->subDays(40)->toDateString());
        $this->assertEquals(2, (float) $b->fresh()->rata_pemakaian);
        $this->actingAs($this->dapur)->post('/dapur/mutasi', ['id_bahan' => $b->id, 'jenis' => 'masuk', 'jumlah' => 100, 'tanggal' => today()->toDateString()]);
        $this->assertFalse($b->fresh()->isStokKritis()); // 101 > 12

        // ubah lead time -> ROP ikut berubah
        $this->actingAs($this->dapur)->put("/dapur/bahan-baku/{$b->getRouteKey()}", ['nama_bahan' => 'Tepung', 'satuan' => 'kg', 'lead_time' => 5, 'safety_stock' => 6]);
        $this->assertEquals(16, (float) $b->fresh()->rop);
        $this->actingAs($this->dapur)->put("/dapur/bahan-baku/{$b->getRouteKey()}", ['nama_bahan' => 'Tepung', 'satuan' => 'kg', 'stok' => 999, 'lead_time' => 5, 'safety_stock' => 6])
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
        $this->actingAs($this->dapur)->post('/dapur/mutasi', ['id_bahan' => $b->id, 'jenis' => 'masuk', 'jumlah' => 1, 'tanggal' => '2001-01-01'])
            ->assertSessionHasErrors(['tanggal' => 'Tanggal mutasi paling lama 30 hari ke belakang.']);
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
        $riwayat = MutasiBahanBaku::where('id_bahan', $b->id)->count();
        $this->actingAs($this->dapur)->delete("/dapur/bahan-baku/{$b->getRouteKey()}")->assertSessionHas('status');
        $this->assertSoftDeleted($b);
        $this->assertSame($riwayat, MutasiBahanBaku::where('id_bahan', $b->id)->count()); // riwayat tetap ada
        $this->actingAs($this->dapur)->get('/dapur/bahan-baku')->assertDontSee('data-name="Lemon"', false);
    }

    public function test_bahan_yang_dipakai_resep_tidak_bisa_dihapus(): void
    {
        $this->seed([BahanBakuSeeder::class, ResepSeeder::class]);
        $lemon = BahanBaku::firstWhere('nama_bahan', 'Lemon');

        $this->actingAs($this->dapur)->delete("/dapur/bahan-baku/{$lemon->getRouteKey()}")
            ->assertSessionHas('error', fn ($m) => str_contains($m, 'Lemon Iced Tea'));
        $this->assertNotSoftDeleted($lemon);
        $this->assertSame(1, $lemon->resep()->count());
    }

    public function test_nama_bahan_unik_tanpa_peduli_huruf_besar_dan_satuan_dari_daftar(): void
    {
        $isi = ['satuan' => 'kg', 'stok' => 1, 'lead_time' => 1, 'safety_stock' => 0];
        $this->actingAs($this->dapur)->post('/dapur/bahan-baku', ['nama_bahan' => 'Lemon'] + $isi)->assertSessionHasNoErrors();

        $this->actingAs($this->dapur)->post('/dapur/bahan-baku', ['nama_bahan' => 'lemon'] + $isi)->assertSessionHasErrors('nama_bahan');
        $this->actingAs($this->dapur)->post('/dapur/bahan-baku', ['nama_bahan' => 'Jeruk', 'satuan' => 'ton<b>'] + $isi)->assertSessionHasErrors('satuan');
        $this->assertSame(1, BahanBaku::count());
    }

    public function test_ganti_satuan_kg_ke_gram_mengonversi_stok_resep_dan_riwayat(): void
    {
        $this->seed([BahanBakuSeeder::class, ResepSeeder::class]);
        $selada = BahanBaku::firstWhere('nama_bahan', 'Selada Romaine');
        $stok = (float) $selada->stok;
        $ss = (float) $selada->safety_stock;
        $pemakaian = (float) $selada->mutasi()->sum('jumlah');
        $isi = ['nama_bahan' => 'Selada Romaine', 'lead_time' => $selada->lead_time, 'safety_stock' => $ss];

        $this->actingAs($this->dapur)->put("/dapur/bahan-baku/{$selada->getRouteKey()}", ['satuan' => 'gram'] + $isi)
            ->assertSessionHasNoErrors()->assertSessionHas('status', fn ($m) => str_contains($m, 'dikonversi'));

        $selada->refresh();
        $this->assertSame('gram', $selada->satuan);
        $this->assertEqualsWithDelta($stok * 1000, (float) $selada->stok, 0.001);
        $this->assertEqualsWithDelta($ss * 1000, (float) $selada->safety_stock, 0.001);
        $this->assertEqualsWithDelta($pemakaian * 1000, (float) $selada->mutasi()->sum('jumlah'), 0.01);
        $this->assertEqualsWithDelta(150, (float) $selada->resep()->whereHas('menu', fn ($q) => $q->where('nama_menu', 'Caesar Salad'))->value('jumlah'), 0.001);

        // satuan yang tidak bisa dikonversi ditolak
        $this->actingAs($this->dapur)->put("/dapur/bahan-baku/{$selada->getRouteKey()}", ['satuan' => 'pcs'] + $isi)->assertSessionHasErrors('satuan');
        $this->assertSame('gram', $selada->fresh()->satuan);
    }

    // ---------- resep: stok berkurang otomatis saat mulai masak

    public function test_mulai_masak_mengurangi_stok_sesuai_resep(): void
    {
        $this->seed([BahanBakuSeeder::class, ResepSeeder::class]);
        $menu = Menu::firstWhere('nama_menu', 'Pizza Beef Pepperoni');
        $keju = BahanBaku::firstWhere('nama_bahan', 'Keju Mozzarella');
        $tepung = BahanBaku::firstWhere('nama_bahan', 'Tepung Terigu');
        $dAwal = (float) $tepung->rata_pemakaian;

        $p = app(PesananService::class)->buat([['id_menu' => $menu->id, 'jumlah' => 2]], 'dine_in', kasir: $this->kasir, data: ['nomor_meja' => '01']);

        $this->actingAs($this->dapur)->post("/dapur/tiket/{$p->tiketDapur->getRouteKey()}/mulai")
            ->assertSessionHasNoErrors()->assertSessionMissing('error');

        // 2 porsi × 0,15 kg keju, 2 × 0,25 kg tepung
        $this->assertSame(6.2, (float) $keju->fresh()->stok);
        $this->assertSame(41.5, (float) $tepung->fresh()->stok);
        $this->assertGreaterThan($dAwal, (float) $tepung->fresh()->rata_pemakaian);
        $this->assertDatabaseHas('mutasi_bahan_baku', ['id_bahan' => $keju->id, 'jenis' => MutasiBahanBaku::JENIS_PEMAKAIAN, 'jumlah' => 0.3, 'tanggal' => today()->toDateString().' 00:00:00']);
    }

    public function test_stok_tidak_cukup_tetap_boleh_masak_dan_habis(): void
    {
        $this->seed([BahanBakuSeeder::class, ResepSeeder::class]);
        $menu = Menu::firstWhere('nama_menu', 'Pizza Beef Pepperoni');
        $keju = BahanBaku::firstWhere('nama_bahan', 'Keju Mozzarella');
        $keju->update(['stok' => 0.2]);

        $p = app(PesananService::class)->buat([['id_menu' => $menu->id, 'jumlah' => 2]], 'dine_in', kasir: $this->kasir, data: ['nomor_meja' => '01']);

        // sebelum mulai: kebutuhan bahan & peringatan stok kurang terlihat
        $this->actingAs($this->dapur)->get("/dapur/antrean?tiket={$p->tiketDapur->getRouteKey()}")
            ->assertSee('Bahan yang akan dipakai')->assertSee('⚠ Keju Mozzarella')->assertSee('butuh 0,3 kg · sisa 0,2');

        // sesudah mulai: hanya satu pesan peringatan, tanpa toast sukses
        $this->actingAs($this->dapur)->post("/dapur/tiket/{$p->tiketDapur->getRouteKey()}/mulai")
            ->assertSessionHasNoErrors()->assertSessionHas('error', fn ($m) => str_contains($m, 'Keju Mozzarella'))->assertSessionMissing('status');

        $this->assertSame(TiketDapur::STATUS_DIMASAK, $p->tiketDapur->fresh()->status_masak);
        $this->assertSame(0.0, (float) $keju->fresh()->stok);
        $this->assertTrue($keju->fresh()->isStokKritis());
    }

    public function test_admin_mengatur_resep_menu(): void
    {
        $this->seed(BahanBakuSeeder::class);
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $menu = Menu::firstWhere('nama_menu', 'Kentang Goreng');
        $kentang = BahanBaku::firstWhere('nama_bahan', 'Kentang Beku');
        $lemon = BahanBaku::firstWhere('nama_bahan', 'Lemon');
        $isi = ['nama_menu' => $menu->nama_menu, 'id_kategori' => $menu->id_kategori, 'harga' => '28.000', 'tersedia' => 1];

        $this->actingAs($admin)->put("/admin/menu/{$menu->getRouteKey()}", $isi + ['resep' => [
            ['id_bahan' => $kentang->id, 'jumlah' => '0,2'],
            ['id_bahan' => '', 'jumlah' => ''], // baris kosong diabaikan
            ['id_bahan' => $lemon->id, 'jumlah' => '0.01'],
        ]])->assertSessionHasNoErrors();
        $this->assertSame(['0.20', '0.01'], $menu->resep()->orderBy('id')->pluck('jumlah')->all());

        $this->actingAs($admin)->get('/admin/menu')->assertSee('Resep: 2 bahan baku');

        // hapus satu bahan, takaran tidak valid ditolak
        $this->actingAs($admin)->put("/admin/menu/{$menu->getRouteKey()}", $isi + ['resep' => [['id_bahan' => $kentang->id, 'jumlah' => '0.25']]]);
        $this->assertSame(1, $menu->resep()->count());
        $this->actingAs($admin)->put("/admin/menu/{$menu->getRouteKey()}", $isi + ['resep' => [['id_bahan' => $kentang->id, 'jumlah' => '0']]])
            ->assertSessionHasErrors('resep.0.jumlah');
        $this->assertSame('0.25', $menu->resep()->first()->jumlah);
    }
}
