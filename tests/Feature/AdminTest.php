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
            ->assertSee('ORD-0001')->assertSee('Pesanan di tempat')->assertSee('2 menu berstatus Habis');
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
            'nama' => 'Lia H.', 'email' => 'lia@simresto.test', 'no_hp' => '0857', 'role' => 'dapur', 'password' => '',
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
            ->post('/admin/pengguna', ['_modal' => 'mUser', 'nama' => 'X', 'email' => $this->admin->email, 'no_hp' => '08', 'role' => 'kasir', 'password' => 'rahasia123', 'password_confirmation' => 'rahasia123'])
            ->assertSee('data-reopen="mUser"', false)
            ->assertSee('Email sudah digunakan.');
    }

    public function test_admin_tidak_bisa_hapus_atau_ganti_peran_diri_sendiri(): void
    {
        $this->actingAs($this->admin)->delete("/admin/pengguna/{$this->admin->id}")->assertSessionHas('error');
        $this->assertModelExists($this->admin);

        $this->actingAs($this->admin)->put("/admin/pengguna/{$this->admin->id}", [
            'nama' => 'Dewi', 'email' => $this->admin->email, 'no_hp' => '08', 'role' => 'kasir',
        ])->assertSessionHasErrors('role');
        $this->assertSame('admin', $this->admin->fresh()->role);
    }

    public function test_pengguna_yang_punya_pengeluaran_tidak_bisa_dihapus(): void
    {
        $owner = User::factory()->create(['role' => User::ROLE_OWNER]);
        Pengeluaran::create(['id_owner' => $owner->id, 'kategori' => 'Listrik', 'nominal' => 100000, 'tanggal' => today()]);

        $this->actingAs($this->admin)->delete("/admin/pengguna/{$owner->id}")->assertSessionHas('error');
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
            'pajak' => '11', 'biaya_layanan' => '3.500', 'jam_buka' => '09:00', 'jam_tutup' => '21:30',
        ])->assertRedirect('/admin/pengaturan')->assertSessionHas('status');

        $set = PengaturanSistem::aktif();
        $this->assertSame(1, PengaturanSistem::count());
        $this->assertEquals([11, 3500], [(float) $set->pajak, (float) $set->biaya_layanan]);
        $this->assertSame('09:00 – 21:30', $set->jamLabel());
        $this->assertEquals(11000, $set->hitungPajak(100000));

        $this->actingAs($this->admin)->get('/admin/dashboard')->assertSee('09:00 – 21:30')->assertSee('11%');
    }

    public function test_validasi_pengaturan(): void
    {
        $this->actingAs($this->admin)->put('/admin/pengaturan', [
            'pajak' => 150, 'biaya_layanan' => 'x', 'jam_buka' => '22:00', 'jam_tutup' => '10:00',
        ])->assertSessionHasErrors(['pajak', 'biaya_layanan', 'jam_tutup']);
    }
}
