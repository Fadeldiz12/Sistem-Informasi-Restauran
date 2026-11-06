<?php

namespace Tests\Feature;

use App\Models\Kategori;
use App\Models\Menu;
use App\Models\PengaturanSistem;
use App\Models\User;
use App\Models\ZonaPengiriman;
use Database\Seeders\MasterDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
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
        // tautan Promo hanya muncul bila admin sudah membuat menu di kategori "Promo"
        $this->assertSame(['Pizza', 'Pasta', 'Burger', 'Camilan', 'Minuman', 'Dessert'], array_map('trim', $teks[1]));
        $this->assertStringNotContainsString('Salad', $nav);

        // urutan diubah admin -> navbar ikut berubah, di semua halaman pelanggan
        Kategori::where('nama_kategori', 'Dessert')->update(['urutan_navbar' => 0]);
        foreach (['/beranda', '/keranjang', '/tracking'] as $url) {
            $this->assertMatchesRegularExpression('/Dessert.*Pizza/s', $this->navbarHtml($url), $url);
        }
    }

    public function test_menu_habis_tetap_tampil_dengan_label_habis(): void
    {
        $html = $this->actingAs($this->pelanggan)->get('/beranda')
            ->assertSee('Pizza Margherita')->assertSee('Rp 89.000')
            ->assertSee('data-c="pizza"', false)
            ->assertSee('Spaghetti Seafood')->getContent();
        $this->assertMatchesRegularExpression('/<article class="item out" data-c="pasta" data-n="spaghetti seafood"/', $html);
        $this->assertStringContainsString('aria-label="Spaghetti Seafood sedang habis"', $html);

        Menu::where('nama_menu', 'Pizza Margherita')->update(['tersedia' => false]);
        $this->assertStringContainsString('aria-label="Pizza Margherita sedang habis"', $this->actingAs($this->pelanggan)->get('/beranda')->getContent());
    }

    public function test_promo_dari_menu_kategori_promo_bisa_dipesan(): void
    {
        $this->actingAs($this->pelanggan)->get('/beranda')->assertDontSee('id="promo"', false)->assertDontSee('Promo Spesial');

        $kat = Kategori::create(['nama_kategori' => 'Promo']);
        $paket = Menu::create(['nama_menu' => 'Paket Berdua', 'id_kategori' => $kat->id, 'harga' => 109000, 'tersedia' => true]);

        $html = $this->actingAs($this->pelanggan)->get('/beranda')->assertSee('Promo Spesial')->assertSee('Paket Berdua')->assertSee('Rp 109.000')->getContent();
        $this->assertStringContainsString('data-id="'.$paket->id.'"', $html);
        $this->assertStringContainsString('>Promo</a>', $this->navbarHtml());
        $this->actingAs($this->pelanggan)->postJson('/keranjang', ['id_menu' => $paket->id])->assertOk();
    }

    public function test_filter_kategori_dari_url(): void
    {
        $html = $this->actingAs($this->pelanggan)->get('/beranda?kategori=pasta')->assertOk()->getContent();

        // navbar & chips Pasta aktif, menu kategori lain disembunyikan
        $this->assertMatchesRegularExpression('/data-kat="pasta" class="on"/', $html);
        $this->assertMatchesRegularExpression('/<article class="item" data-c="pasta" data-n="[^"]*" data-q="[^"]*"\s*>/', $html);
        $this->assertMatchesRegularExpression('/<article class="item" data-c="pizza" data-n="[^"]*" data-q="[^"]*"\s+style="display:none"/', $html);

        // kategori tidak dikenal = semua menu
        $this->actingAs($this->pelanggan)->get('/beranda?kategori=tidak-ada')->assertOk()
            ->assertDontSee('style="display:none"', false);
    }

    public function test_kategori_navbar_tanpa_menu_tersedia_menampilkan_pesan_kosong(): void
    {
        Menu::whereHas('kategori', fn ($q) => $q->where('nama_kategori', 'Dessert'))->delete();

        $html = $this->actingAs($this->pelanggan)->get('/beranda?kategori=dessert')->assertOk()->getContent();
        $this->assertStringContainsString('data-kat="dessert"', $html);
        $this->assertMatchesRegularExpression('/<p class="grid-empty" id="gridEmpty"\s*>/', $html);
    }

    public function test_profil_pelanggan_bisa_diubah(): void
    {
        $this->actingAs($this->pelanggan)->get('/profil')->assertOk()->assertSee('Profil Saya')->assertSee($this->pelanggan->email);

        $this->actingAs($this->pelanggan)->put('/profil', ['nama' => 'Rina Baru', 'no_hp' => '0812-9988-7766', 'alamat' => 'Jl. Baru 1'])
            ->assertSessionHasNoErrors();
        $this->assertSame(['Rina Baru', '081299887766', 'Jl. Baru 1'], [$this->pelanggan->fresh()->nama, $this->pelanggan->fresh()->no_hp, $this->pelanggan->fresh()->alamat]);
        $this->actingAs($this->pelanggan)->put('/profil', ['nama' => 'X', 'no_hp' => '08', 'alamat' => ''])->assertSessionHasErrors('no_hp');

        $this->actingAs($this->pelanggan)->put('/profil/password', ['password_lama' => 'salah', 'password' => 'baru12345', 'password_confirmation' => 'baru12345'])
            ->assertSessionHasErrors('password_lama');
        $this->actingAs($this->pelanggan)->put('/profil/password', ['password_lama' => 'password', 'password' => 'baru12345', 'password_confirmation' => 'baru12345'])
            ->assertSessionHasNoErrors();
        $this->assertTrue(Hash::check('baru12345', $this->pelanggan->fresh()->password));
    }

    public function test_bantuan_dan_footer_tanpa_link_mati(): void
    {
        ZonaPengiriman::first()->update(['contoh_wilayah' => 'Medan Petisah, Medan Baru']);
        $this->actingAs($this->pelanggan)->get('/bantuan')->assertOk()->assertSee('Pertanyaan Umum')->assertSee('Cara Pembayaran')->assertSee('Medan Petisah, Medan Baru');

        $html = $this->actingAs($this->pelanggan)->get('/beranda')->getContent();
        preg_match('/<footer>(.*?)<\/footer>/s', $html, $f);
        $this->assertStringNotContainsString('href="#"', str_replace('href="#" data-pilih-layanan', '', $f[1]));
        $this->assertStringContainsString('href="tel:', $f[1]);
    }

    public function test_layanan_pilihan_beranda_terbawa_ke_checkout_dan_banner_tutup(): void
    {
        $this->actingAs($this->pelanggan)->postJson('/keranjang', ['id_menu' => Menu::where('tersedia', true)->value('id')]);
        $this->actingAs($this->pelanggan)->withUnencryptedCookie('layanan_pilihan', 'take_away')->get('/checkout')
            ->assertSee('id="jenisLayanan" value="take_away"', false);

        Carbon::setTestNow(today()->setTime(12, 0));
        PengaturanSistem::aktif()->update(['jam_buka' => '22:00', 'jam_tutup' => '23:00']);
        $this->actingAs($this->pelanggan)->get('/beranda')->assertSee('Restoran sedang tutup')->assertSee('22:00 – 23:00');
    }
}
