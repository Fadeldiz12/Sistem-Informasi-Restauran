<?php

namespace Tests\Feature;

use App\Models\Menu;
use App\Models\Pesanan;
use App\Models\TutupKasir;
use App\Models\User;
use Database\Seeders\MasterDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class TutupHariTest extends TestCase
{
    use RefreshDatabase;

    private User $kasir;

    private Menu $pizza; // Rp 109.000 → total Rp 125.350

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(MasterDataSeeder::class);
        Carbon::setTestNow(today()->setTime(12, 0));
        $this->kasir = User::factory()->create(['role' => User::ROLE_KASIR, 'nama' => 'Bima Saputra']);
        $this->pizza = Menu::where('nama_menu', 'Pizza Beef Pepperoni')->first();
    }

    /**
     * Pesanan dine in yang dibayar tunai di kasir; selesai bila $selesai.
     */
    private function penjualan(bool $selesai = true): Pesanan
    {
        $this->actingAs($this->kasir)->post('/kasir/input', ['jenis_layanan' => 'dine_in', 'nomor_meja' => '02', 'items' => [['id_menu' => $this->pizza->id, 'jumlah' => 1]]])
            ->assertSessionHasNoErrors();
        $p = Pesanan::latest('id')->firstOrFail();
        $this->actingAs($this->kasir)->post("/kasir/pembayaran/{$p->getRouteKey()}/lunas", ['metode' => 'tunai', 'uang_diterima' => '200000'])->assertSessionHasNoErrors();
        if ($selesai) {
            $p->update(['status' => Pesanan::STATUS_SELESAI]);
        }

        return $p;
    }

    public function test_eod_ditolak_selama_ada_pesanan_berjalan_atau_setoran_kurir(): void
    {
        $p = $this->penjualan(selesai: false);

        $this->actingAs($this->kasir)->get('/kasir/tutup-hari')->assertOk()
            ->assertSee('Belum bisa tutup hari')->assertSee($p->kode())->assertDontSee('Tutup Hari &amp; Cetak Rekap', false);
        $this->actingAs($this->kasir)->post('/kasir/tutup-hari')->assertSessionHasErrors(['eod' => 'Masih ada 1 pesanan yang belum selesai. Bereskan dulu sebelum tutup hari.']);

        $p->update(['status' => Pesanan::STATUS_SELESAI]);
        $kurir = User::factory()->create(['role' => User::ROLE_KURIR, 'nama' => 'Bayu Saputra']);
        $p->pembayaran->update(['id_kurir' => $kurir->id]);
        $this->actingAs($this->kasir)->get('/kasir/tutup-hari')->assertSee('Bayu Saputra')->assertSee('Terima setoran kurir');
        $this->actingAs($this->kasir)->post('/kasir/tutup-hari')->assertSessionHasErrors('eod');

        $this->assertSame(0, TutupKasir::count());
    }

    public function test_setelah_eod_penjualan_kasir_mulai_dari_nol_tapi_owner_tetap_melihat(): void
    {
        $p = $this->penjualan();
        $this->actingAs($this->kasir)->get('/kasir/tutup-hari')->assertOk()->assertSee('Rp 125.350')->assertSee('Tutup Hari &amp; Cetak Rekap', false);

        Carbon::setTestNow(today()->setTime(22, 0));
        $tutup = $this->actingAs($this->kasir)->post('/kasir/tutup-hari');
        $eod = TutupKasir::sole();
        $tutup->assertRedirect(route('kasir.eod.cetak', $eod));
        $this->assertSame([1, '125350.00', '125350.00', $this->kasir->id], [$eod->jumlah_pesanan, $eod->total_pemasukan, $eod->total_tunai, $eod->id_kasir]);
        $this->actingAs($this->kasir)->get(route('kasir.eod.cetak', $eod))->assertOk()->assertSee('REKAP TUTUP HARI')->assertSee('Rp 125.350');

        // kasir tidak lagi melihat penjualan sebelum EOD
        $this->actingAs($this->kasir)->get('/kasir/dashboard')->assertSee('0 pembayaran lunas · sejak tutup hari 22:00')->assertDontSee('Rp 125.350');
        $this->actingAs($this->kasir)->get('/kasir/pembayaran?tab=lunas')->assertDontSee($p->kode());
        $this->actingAs($this->kasir)->get(route('kasir.struk', $p))->assertForbidden();
        $this->actingAs($this->kasir)->get('/kasir/tutup-hari')->assertSee('tutup hari terakhir pukul 22:00')->assertDontSee('Rp 125.350');

        // rekap hanya bisa dicetak sesaat setelah EOD oleh kasir yang menutup
        $lain = User::factory()->create(['role' => User::ROLE_KASIR]);
        $this->actingAs($lain)->get(route('kasir.eod.cetak', $eod))->assertForbidden();

        // penjualan baru setelah EOD terhitung lagi dari nol
        Carbon::setTestNow(today()->setTime(22, 30));
        $baru = $this->penjualan();
        $this->actingAs($this->kasir)->get('/kasir/pembayaran?tab=lunas')->assertSee($baru->kode())->assertDontSee($p->kode());
        $this->actingAs($this->kasir)->get('/kasir/dashboard')->assertSee('1 pembayaran lunas');

        // owner tetap melihat semua penjualan hari ini
        $owner = User::factory()->create(['role' => User::ROLE_OWNER]);
        $this->actingAs($owner)->get('/owner/laporan')->assertOk()->assertSee('250.700');
    }

    public function test_besok_otomatis_mulai_dari_nol_walau_lupa_eod(): void
    {
        $p = $this->penjualan();
        $this->actingAs($this->kasir)->get('/kasir/dashboard')->assertSee('1 pembayaran lunas');

        Carbon::setTestNow(today()->addDay()->setTime(9, 0));
        $this->actingAs($this->kasir)->get('/kasir/dashboard')->assertSee('0 pembayaran lunas')->assertDontSee('sejak tutup hari');
        $this->actingAs($this->kasir)->get('/kasir/pembayaran?tab=lunas')->assertDontSee($p->kode());
        $this->assertSame(today()->toDateTimeString(), TutupKasir::batas()->toDateTimeString());
    }

    public function test_hanya_kasir_yang_bisa_tutup_hari(): void
    {
        $pelanggan = User::factory()->create(['role' => User::ROLE_PELANGGAN]);
        $this->actingAs($pelanggan)->get('/kasir/tutup-hari')->assertForbidden();
        $this->actingAs($pelanggan)->post('/kasir/tutup-hari')->assertForbidden();
        $this->actingAs($this->kasir)->get('/kasir/dashboard')->assertSee(route('kasir.eod'));
    }
}
