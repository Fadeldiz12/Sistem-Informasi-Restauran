<?php

namespace Tests\Feature;

use App\Models\Kategori;
use App\Models\Menu;
use App\Models\PengaturanSistem;
use App\Models\Pengeluaran;
use App\Models\Pesanan;
use App\Models\User;
use App\Models\ZonaPengiriman;
use Database\Seeders\MasterDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['role' => User::ROLE_ADMIN, 'nama' => 'Dewi Lestari']);
        $this->seed(MasterDataSeeder::class);
    }

    public function test_semua_halaman_admin_tampil(): void
    {
        Pesanan::create(['jenis_layanan' => 'dine_in', 'waktu_pesan' => now(), 'subtotal' => 50000, 'total' => 57000]);

        foreach (['/admin/dashboard', '/admin/pengguna', '/admin/menu', '/admin/zona', '/admin/pengaturan'] as $url) {
            $this->actingAs($this->admin)->get($url)->assertOk()->assertSee('Dewi Lestari');
        }

        $this->actingAs($this->admin)->get('/admin/dashboard')
            ->assertSee('ORD-0001')->assertSee('Pembeli di tempat')->assertSee('2 menu berstatus Habis')
            ->assertSee('1 pesanan · 100%')->assertDontSee('1.00 pesanan');
        $this->actingAs($this->admin)->get('/admin/menu')->assertSee('Pizza Margherita')->assertSee('Rp 89.000');
        $this->actingAs($this->admin)->get('/admin/zona')->assertSee('Rp 15.000')->assertSee('3 – 6 km');
    }

    public function test_role_lain_tidak_bisa_kelola_data_admin(): void
    {
        $kasir = User::factory()->create(['role' => User::ROLE_KASIR]);

        $this->actingAs($kasir)->get('/admin/pengguna')->assertForbidden();
        $this->actingAs($kasir)->post('/admin/zona', ['nama_zona' => 'X', 'radius_km' => 1, 'tarif_per_km' => 1])->assertForbidden();
        $this->assertDatabaseMissing('zona_pengiriman', ['nama_zona' => 'X']);
    }

    // ---------- pengguna

    public function test_tambah_ubah_hapus_pengguna(): void
    {
        $this->actingAs($this->admin)->post('/admin/pengguna', [
            'nama' => 'Lia Hutapea', 'email' => 'lia@simresto.test', 'no_hp' => '0857 1200 3478', 'role' => 'kasir',
            'password' => 'rahasia123', 'password_confirmation' => 'rahasia123',
        ])->assertRedirect('/admin/pengguna')->assertSessionHas('status');

        $lia = User::where('email', 'lia@simresto.test')->firstOrFail();
        $this->assertSame('kasir', $lia->role);
        $hash = $lia->password;

        // ubah tanpa password: password lama tetap
        $this->actingAs($this->admin)->put("/admin/pengguna/{$lia->id}", [
            'nama' => 'Lia H.', 'email' => 'lia@simresto.test', 'no_hp' => '085712003478', 'role' => 'dapur', 'password' => '',
        ])->assertSessionHasNoErrors();
        $lia->refresh();
        $this->assertSame(['Lia H.', 'dapur', $hash], [$lia->nama, $lia->role, $lia->password]);

        $this->actingAs($this->admin)->delete("/admin/pengguna/{$lia->id}")->assertSessionHas('status');
        $this->assertModelMissing($lia);
    }

    public function test_validasi_pengguna_dan_dialog_dibuka_ulang(): void
    {
        $this->actingAs($this->admin)->from('/admin/pengguna')->post('/admin/pengguna', [
            '_modal' => 'mUser', 'nama' => '', 'email' => $this->admin->email, 'no_hp' => 'abc', 'role' => 'superadmin', 'password' => '123',
        ])->assertRedirect('/admin/pengguna')->assertSessionHasErrors(['nama', 'email', 'no_hp', 'role', 'password']);

        $this->actingAs($this->admin)->followingRedirects()->from('/admin/pengguna')
            ->post('/admin/pengguna', ['_modal' => 'mUser', 'nama' => 'X', 'email' => $this->admin->email, 'no_hp' => '081234567890', 'role' => 'kasir', 'password' => 'rahasia123', 'password_confirmation' => 'rahasia123'])
            ->assertSee('data-reopen="mUser"', false)
            ->assertSee('Email sudah digunakan.');
    }

    public function test_password_staf_wajib_huruf_dan_angka(): void
    {
        $isi = ['nama' => 'Kasir Baru', 'email' => 'kb@simresto.test', 'no_hp' => '081234567890', 'role' => 'kasir'];
        $this->actingAs($this->admin)->post('/admin/pengguna', $isi + ['password' => '11111111', 'password_confirmation' => '11111111'])
            ->assertSessionHasErrors('password');
        $this->actingAs($this->admin)->post('/admin/pengguna', $isi + ['password' => 'kasir2026', 'password_confirmation' => 'kasir2026'])
            ->assertSessionHasNoErrors();
    }

    public function test_admin_tidak_bisa_hapus_atau_ganti_peran_diri_sendiri(): void
    {
        $this->actingAs($this->admin)->delete("/admin/pengguna/{$this->admin->id}")->assertSessionHas('error');
        $this->assertModelExists($this->admin);

        $this->actingAs($this->admin)->put("/admin/pengguna/{$this->admin->id}", [
            'nama' => 'Dewi', 'email' => $this->admin->email, 'no_hp' => '081234567890', 'role' => 'kasir',
        ])->assertSessionHasErrors('role');
        $this->assertSame('admin', $this->admin->fresh()->role);
    }

    public function test_pengguna_yang_punya_pengeluaran_tidak_bisa_dihapus(): void
    {
        $owner = User::factory()->create(['role' => User::ROLE_OWNER]);
        Pengeluaran::create(['id_owner' => $owner->id, 'kategori' => 'Listrik', 'nominal' => 100000, 'tanggal' => today()]);

        $ownerLain = User::factory()->create(['role' => User::ROLE_OWNER]);
        $this->actingAs($ownerLain)->delete("/owner/pengguna/{$owner->id}")->assertSessionHas('error');
        $this->assertModelExists($owner);
    }

    public function test_filter_dan_cari_pengguna(): void
    {
        User::factory()->create(['role' => 'kurir', 'nama' => 'Andi Pratama']);
        User::factory()->create(['role' => 'kasir', 'nama' => 'Bima Saputra']);

        $this->actingAs($this->admin)->get('/admin/pengguna?role=kurir')->assertSee('Andi Pratama')->assertDontSee('Bima Saputra');
        $this->actingAs($this->admin)->get('/admin/pengguna?q=bima')->assertSee('Bima Saputra')->assertDontSee('Andi Pratama');
    }

    // ---------- kategori & menu

    public function test_kategori_crud_dan_tidak_bisa_hapus_jika_ada_menu(): void
    {
        $this->actingAs($this->admin)->post('/admin/kategori', ['nama_kategori' => 'Paket Hemat'])->assertSessionHas('status');
        $kat = Kategori::where('nama_kategori', 'Paket Hemat')->firstOrFail();

        $this->actingAs($this->admin)->post('/admin/kategori', ['nama_kategori' => 'Paket Hemat'])->assertSessionHasErrors('nama_kategori');

        $this->actingAs($this->admin)->put("/admin/kategori/{$kat->id}", ['nama_kategori' => 'Paket Keluarga']);
        $this->assertSame('Paket Keluarga', $kat->fresh()->nama_kategori);

        $pizza = Kategori::where('nama_kategori', 'Pizza')->first();
        $this->actingAs($this->admin)->delete("/admin/kategori/{$pizza->id}")->assertSessionHas('error');
        $this->assertModelExists($pizza);

        $this->actingAs($this->admin)->delete("/admin/kategori/{$kat->id}")->assertSessionHas('status');
        $this->assertModelMissing($kat);
    }

    public function test_navbar_maksimal_6_kategori(): void
    {
        // seeder: 6 kategori sudah di navbar, Salad belum
        $this->assertSame(6, Kategori::where('tampil_navbar', true)->count());
        $salad = Kategori::where('nama_kategori', 'Salad')->first();

        $this->actingAs($this->admin)->put("/admin/kategori/{$salad->id}", ['nama_kategori' => 'Salad', 'tampil_navbar' => '1', 'urutan_navbar' => 2])
            ->assertSessionHasErrors('tampil_navbar');
        $this->assertFalse($salad->fresh()->tampil_navbar);

        $this->actingAs($this->admin)->post('/admin/kategori', ['nama_kategori' => 'Paket', 'tampil_navbar' => '1', 'urutan_navbar' => 1])
            ->assertSessionHasErrors('tampil_navbar');

        // sembunyikan Dessert, lalu Salad bisa masuk
        $dessert = Kategori::where('nama_kategori', 'Dessert')->first();
        $this->actingAs($this->admin)->put("/admin/kategori/{$dessert->id}", ['nama_kategori' => 'Dessert'])->assertSessionHasNoErrors();
        $this->assertFalse($dessert->fresh()->tampil_navbar);
        $this->assertNull($dessert->fresh()->urutan_navbar);

        $this->actingAs($this->admin)->put("/admin/kategori/{$salad->id}", ['nama_kategori' => 'Salad', 'tampil_navbar' => '1', 'urutan_navbar' => 6])
            ->assertSessionHasNoErrors();
        $this->assertSame([true, 6], [$salad->fresh()->tampil_navbar, $salad->fresh()->urutan_navbar]);
    }

    public function test_urutan_navbar_bertukar_posisi(): void
    {
        $pizza = Kategori::where('nama_kategori', 'Pizza')->first();   // urutan 1
        $burger = Kategori::where('nama_kategori', 'Burger')->first(); // urutan 3

        $this->actingAs($this->admin)->put("/admin/kategori/{$burger->id}", ['nama_kategori' => 'Burger', 'tampil_navbar' => '1', 'urutan_navbar' => 1])
            ->assertSessionHasNoErrors();

        $this->assertSame(1, $burger->fresh()->urutan_navbar);
        $this->assertSame(3, $pizza->fresh()->urutan_navbar);
        $this->assertSame(['Burger', 'Pasta', 'Pizza', 'Camilan', 'Minuman', 'Dessert'], Kategori::navbar()->pluck('nama_kategori')->all());

        $this->actingAs($this->admin)->put("/admin/kategori/{$burger->id}", ['nama_kategori' => 'Burger', 'tampil_navbar' => '1'])
            ->assertSessionHasErrors('urutan_navbar');
    }

    public function test_tambah_dan_ubah_menu_dengan_foto(): void
    {
        Storage::fake('public');
        $kat = Kategori::first();

        $this->actingAs($this->admin)->post('/admin/menu', [
            'nama_menu' => 'Pizza Tuna', 'id_kategori' => $kat->id, 'harga' => '95.000', 'tersedia' => '1',
            'foto' => UploadedFile::fake()->image('tuna.jpg', 600, 600),
        ])->assertSessionHasNoErrors();

        $menu = Menu::where('nama_menu', 'Pizza Tuna')->firstOrFail();
        $this->assertEquals(95000, $menu->harga);
        $this->assertTrue($menu->tersedia);
        Storage::disk('public')->assertExists($menu->foto);
        $fotoLama = $menu->foto;

        // ganti foto + jadi habis (checkbox tidak dikirim)
        $this->actingAs($this->admin)->put("/admin/menu/{$menu->id}", [
            'nama_menu' => 'Pizza Tuna Pedas', 'id_kategori' => $kat->id, 'harga' => '97000',
            'foto' => UploadedFile::fake()->image('baru.png'),
        ])->assertSessionHasNoErrors();
        $menu->refresh();
        $this->assertSame('Pizza Tuna Pedas', $menu->nama_menu);
        $this->assertFalse($menu->tersedia);
        Storage::disk('public')->assertMissing($fotoLama);
        Storage::disk('public')->assertExists($menu->foto);

        $this->actingAs($this->admin)->delete("/admin/menu/{$menu->id}");
        Storage::disk('public')->assertMissing($menu->foto);
        $this->assertModelMissing($menu);
    }

    public function test_validasi_menu(): void
    {
        $this->actingAs($this->admin)->post('/admin/menu', [
            'nama_menu' => '', 'id_kategori' => 999, 'harga' => 'abc',
            'foto' => UploadedFile::fake()->create('virus.exe', 10),
        ])->assertSessionHasErrors(['nama_menu', 'id_kategori', 'harga', 'foto']);

        $kategori = Menu::firstWhere('nama_menu', 'Pizza Margherita')->id_kategori;
        $isi = ['nama_menu' => 'Menu Baru QA', 'id_kategori' => $kategori];
        // harga 0, desimal, dan terlalu besar ditolak
        $this->actingAs($this->admin)->post('/admin/menu', $isi + ['harga' => '0'])->assertSessionHasErrors(['harga' => 'Harga harus lebih dari Rp 0.']);
        $this->actingAs($this->admin)->post('/admin/menu', $isi + ['harga' => '12,5'])->assertSessionHasErrors('harga');
        $this->actingAs($this->admin)->post('/admin/menu', $isi + ['harga' => '9.999.999.999'])
            ->assertSessionHasErrors(['harga' => 'Harga maksimal Rp 99.999.999.']);
        // nama menu yang sudah ada (beda huruf besar/kecil) ditolak
        $this->actingAs($this->admin)->post('/admin/menu', ['nama_menu' => 'pizza margherita', 'id_kategori' => $kategori, 'harga' => '89.000'])
            ->assertSessionHasErrors('nama_menu');
        $this->assertSame(0, Menu::where('nama_menu', 'Menu Baru QA')->count());
        $this->assertSame(1, Menu::where('nama_menu', 'Pizza Margherita')->count());

        // mengubah menu tanpa mengganti namanya tetap boleh
        $pizza = Menu::firstWhere('nama_menu', 'Pizza Margherita');
        $this->actingAs($this->admin)->put("/admin/menu/{$pizza->id}", ['nama_menu' => 'Pizza Margherita', 'id_kategori' => $kategori, 'harga' => '95.000', 'tersedia' => 1])
            ->assertSessionHasNoErrors();
    }

    public function test_toggle_tersedia_dan_filter_menu(): void
    {
        $menu = Menu::where('nama_menu', 'Pizza Margherita')->first();

        $this->actingAs($this->admin)->patchJson("/admin/menu/{$menu->id}/tersedia", ['tersedia' => false])
            ->assertOk()->assertJson(['tersedia' => false, 'message' => 'Menu ditandai Habis']);
        $this->assertFalse($menu->fresh()->tersedia);

        $this->actingAs($this->admin)->get('/admin/menu?status=habis')->assertSee('Pizza Margherita')->assertDontSee('Penne Bolognese');
        $this->actingAs($this->admin)->get('/admin/menu?q=penne')->assertSee('Penne Bolognese')->assertDontSee('Pizza Margherita');
    }

    public function test_menu_yang_sudah_dipesan_tidak_bisa_dihapus(): void
    {
        $menu = Menu::first();
        $pesanan = Pesanan::create(['jenis_layanan' => 'take_away', 'waktu_pesan' => now(), 'subtotal' => 1, 'total' => 1]);
        $pesanan->detail()->create(['id_menu' => $menu->id, 'jumlah' => 1, 'subtotal' => 1]);

        $this->actingAs($this->admin)->delete("/admin/menu/{$menu->id}")->assertSessionHas('error');
        $this->assertModelExists($menu);
    }

    // ---------- zona

    public function test_zona_crud(): void
    {
        $this->actingAs($this->admin)->post('/admin/zona', ['nama_zona' => 'Zona D', 'radius_km' => '15', 'tarif_per_km' => '2.000'])
            ->assertSessionHasNoErrors();
        $zona = ZonaPengiriman::where('nama_zona', 'Zona D')->firstOrFail();
        $this->assertEquals(30000, $zona->ongkir());

        $this->actingAs($this->admin)->post('/admin/zona', ['nama_zona' => 'Zona D', 'radius_km' => 0, 'tarif_per_km' => -1])
            ->assertSessionHasErrors(['nama_zona', 'radius_km', 'tarif_per_km']);

        $this->actingAs($this->admin)->put("/admin/zona/{$zona->id}", ['nama_zona' => 'Zona D', 'radius_km' => 12, 'tarif_per_km' => 2000]);
        $this->assertEquals(24000, $zona->fresh()->ongkir());

        // radius sama dengan zona lain ditolak
        $lain = ZonaPengiriman::where('id', '!=', $zona->id)->first();
        $this->actingAs($this->admin)->post('/admin/zona', ['nama_zona' => 'Zona Kembar', 'radius_km' => (float) $lain->radius_km, 'tarif_per_km' => '2.000'])
            ->assertSessionHasErrors('radius_km');

        // tarif Rp 0 harus dikonfirmasi sebagai ongkir gratis
        $this->actingAs($this->admin)->post('/admin/zona', ['nama_zona' => 'Zona Gratis', 'radius_km' => 1, 'tarif_per_km' => '0'])
            ->assertSessionHasErrors('tarif_per_km');
        $this->actingAs($this->admin)->post('/admin/zona', ['nama_zona' => 'Zona Gratis', 'radius_km' => 1, 'tarif_per_km' => '0', 'konfirmasi_gratis' => 1])
            ->assertSessionHasNoErrors();
        $this->assertEquals(0, ZonaPengiriman::firstWhere('nama_zona', 'Zona Gratis')->ongkir());

        $this->actingAs($this->admin)->delete("/admin/zona/{$zona->id}")->assertSessionHas('status');
        $this->assertModelMissing($zona);
    }

    public function test_zona_yang_dipakai_pengiriman_tidak_bisa_dihapus(): void
    {
        $zona = ZonaPengiriman::first();
        $pesanan = Pesanan::create(['jenis_layanan' => 'delivery', 'waktu_pesan' => now(), 'subtotal' => 1, 'total' => 1]);
        $pesanan->pengiriman()->create(['id_zona' => $zona->id, 'alamat' => 'Jl. A', 'jarak_km' => 3]);

        $this->actingAs($this->admin)->delete("/admin/zona/{$zona->id}")->assertSessionHas('error');
        $this->assertModelExists($zona);
    }

    // ---------- pengaturan

    public function test_ubah_pengaturan_sistem(): void
    {
        $this->actingAs($this->admin)->put('/admin/pengaturan', [
            'pajak' => '11', 'biaya_layanan' => '2.5', 'jam_buka' => '09:00', 'jam_tutup' => '21:30', 'lat_resto' => '3.5911', 'lng_resto' => '98.6501',
        ])->assertRedirect('/admin/pengaturan')->assertSessionHas('status');
        $this->assertEqualsWithDelta(3.5911, (float) PengaturanSistem::aktif()->lat_resto, 0.00001);

        $set = PengaturanSistem::aktif();
        $this->assertSame(1, PengaturanSistem::count());
        $this->assertEquals([11, 2.5], [(float) $set->pajak, (float) $set->biaya_layanan]);
        $this->assertEquals(2500, $set->hitungBiayaLayanan(100000));
        $this->assertSame('09:00 – 21:30', $set->jamLabel());
        $this->assertEquals(11000, $set->hitungPajak(100000));

        $this->actingAs($this->admin)->get('/admin/dashboard')->assertSee('09:00 – 21:30')->assertSee('11%')->assertSee('2,5%');
    }

    public function test_jam_operasional_lewat_tengah_malam(): void
    {
        $this->actingAs($this->admin)->put('/admin/pengaturan', [
            'pajak' => '10', 'biaya_layanan' => '5', 'jam_buka' => '16:00', 'jam_tutup' => '02:00', 'lat_resto' => '3.59', 'lng_resto' => '98.64',
        ])->assertSessionHasNoErrors();

        $set = PengaturanSistem::aktif();
        $this->assertTrue($set->sedangBuka(Carbon::parse('2026-11-06 23:30')));
        $this->assertTrue($set->sedangBuka(Carbon::parse('2026-11-06 01:59')));
        $this->assertFalse($set->sedangBuka(Carbon::parse('2026-11-06 02:00')));
        $this->assertFalse($set->sedangBuka(Carbon::parse('2026-11-06 15:59')));
        $this->assertTrue($set->sedangBuka(Carbon::parse('2026-11-06 16:00')));
    }

    public function test_validasi_pengaturan(): void
    {
        $this->actingAs($this->admin)->put('/admin/pengaturan', [
            'pajak' => 150, 'biaya_layanan' => 101, 'jam_buka' => '22:00', 'jam_tutup' => '22:00',
        ])->assertSessionHasErrors(['pajak', 'biaya_layanan', 'jam_tutup']);
    }
}
