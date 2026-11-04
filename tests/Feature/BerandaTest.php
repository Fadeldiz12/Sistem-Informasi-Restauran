<?php

namespace Tests\Feature;

use App\Models\Kategori;
use App\Models\Menu;
use App\Models\User;
use Database\Seeders\MasterDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BerandaTest extends TestCase
{
    use RefreshDatabase;

    private User $pelanggan;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(MasterDataSeeder::class);
        $this->pelanggan = User::factory()->create(['role' => User::ROLE_PELANGGAN]);
    }

    private function navbarHtml(string $url = '/beranda'): string
    {
        $html = $this->actingAs($this->pelanggan)->get($url)->assertOk()->getContent();
        preg_match('/<nav class="mainnav">(.*?)<\/nav>/s', $html, $m);

        return $m[1] ?? '';
    }

    public function test_navbar_berisi_promo_dan_kategori_pilihan_admin_sesuai_urutan(): void
    {
        $nav = $this->navbarHtml();

        preg_match_all('/>([^<]+)<\/a>/', $nav, $teks);
        $this->assertSame(['Promo', 'Pizza', 'Pasta', 'Burger', 'Camilan', 'Minuman', 'Dessert'], array_map('trim', $teks[1]));
        $this->assertStringNotContainsString('Salad', $nav);

        // urutan diubah admin -> navbar ikut berubah, di semua halaman pelanggan
        Kategori::where('nama_kategori', 'Dessert')->update(['urutan_navbar' => 0]);
        foreach (['/beranda', '/keranjang', '/tracking'] as $url) {
            $this->assertMatchesRegularExpression('/Promo.*Dessert.*Pizza/s', $this->navbarHtml($url), $url);
        }
    }

    public function test_menu_dari_database_hanya_yang_tersedia(): void
    {
        $this->actingAs($this->pelanggan)->get('/beranda')
            ->assertSee('Pizza Margherita')->assertSee('Rp 89.000')
            ->assertSee('data-c="pizza"', false)
            ->assertDontSee('Spaghetti Seafood')   // habis
            ->assertDontSee('Chicken Wings BBQ');  // habis

        Menu::where('nama_menu', 'Pizza Margherita')->update(['tersedia' => false]);
        $this->actingAs($this->pelanggan)->get('/beranda')->assertDontSee('Pizza Margherita');
    }

    public function test_filter_kategori_dari_url(): void
    {
        $html = $this->actingAs($this->pelanggan)->get('/beranda?kategori=pasta')->assertOk()->getContent();

        // navbar & chips Pasta aktif, menu kategori lain disembunyikan
        $this->assertMatchesRegularExpression('/data-kat="pasta" class="on"/', $html);
        $this->assertMatchesRegularExpression('/<article class="item" data-c="pasta"\s*>/', $html);
        $this->assertMatchesRegularExpression('/<article class="item" data-c="pizza"\s+style="display:none"/', $html);

        // kategori tidak dikenal = semua menu
        $this->actingAs($this->pelanggan)->get('/beranda?kategori=tidak-ada')->assertOk()
            ->assertDontSee('style="display:none"', false);
    }

    public function test_kategori_navbar_tanpa_menu_tersedia_menampilkan_pesan_kosong(): void
    {
        Menu::whereHas('kategori', fn ($q) => $q->where('nama_kategori', 'Dessert'))->update(['tersedia' => false]);

        $html = $this->actingAs($this->pelanggan)->get('/beranda?kategori=dessert')->assertOk()->getContent();
        $this->assertStringContainsString('data-kat="dessert"', $html);
        $this->assertMatchesRegularExpression('/<p class="grid-empty" id="gridEmpty"\s*>/', $html);
    }
}
