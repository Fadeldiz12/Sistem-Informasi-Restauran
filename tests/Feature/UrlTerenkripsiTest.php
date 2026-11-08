<?php

namespace Tests\Feature;

use App\Models\Menu;
use App\Models\Pesanan;
use App\Models\User;
use Database\Seeders\MasterDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * ID di URL diganti kode terenkripsi; angka ID tidak bisa dipakai untuk membuka data.
 */
class UrlTerenkripsiTest extends TestCase
{
    use RefreshDatabase;

    private User $kasir;

    private Pesanan $pesanan;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(MasterDataSeeder::class);
        Carbon::setTestNow(today()->setTime(12, 0));
        $this->kasir = User::factory()->create(['role' => User::ROLE_KASIR]);
        $menu = Menu::where('tersedia', true)->firstOrFail();
        $this->actingAs($this->kasir)->post('/kasir/input', ['jenis_layanan' => 'take_away', 'items' => [['id_menu' => $menu->id, 'jumlah' => 1]]]);
        $this->pesanan = Pesanan::sole();
    }

    public function test_url_memakai_kode_bukan_angka_id(): void
    {
        $kode = $this->pesanan->getRouteKey();
        $this->assertMatchesRegularExpression('/^[A-Za-z0-9_-]{22}$/', $kode);
        $this->assertStringNotContainsString('/pesanan/'.$this->pesanan->id.'/', route('kasir.pesanan.ubah', $this->pesanan));
        $this->assertStringEndsWith('?pesanan='.$kode, route('kasir.pembayaran', ['pesanan' => $this->pesanan]));

        $this->actingAs($this->kasir)->get("/kasir/pesanan/{$kode}/ubah")->assertOk();
        $this->actingAs($this->kasir)->get('/kasir/dashboard')->assertSee(route('kasir.pesanan.ubah', $this->pesanan));
        $this->actingAs($this->kasir)->get('/kasir/pembayaran?pesanan='.$kode)->assertSee($this->pesanan->kode());
    }

    public function test_angka_id_atau_kode_palsu_tidak_bisa_membuka_data(): void
    {
        $this->actingAs($this->kasir)->get("/kasir/pesanan/{$this->pesanan->id}/ubah")->assertNotFound();
        $this->actingAs($this->kasir)->get('/kasir/pesanan/AAAAAAAAAAAAAAAAAAAAAA/ubah')->assertNotFound();

        // kode pesanan tidak berlaku untuk jenis data lain
        $menu = Menu::firstOrFail();
        $this->assertNull(Menu::idDariKode($this->pesanan->getRouteKey()));
        $this->assertSame($menu->id, Menu::idDariKode($menu->getRouteKey()));
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $this->actingAs($admin)->put('/admin/menu/'.$this->pesanan->getRouteKey(), [])->assertNotFound();
    }
}
