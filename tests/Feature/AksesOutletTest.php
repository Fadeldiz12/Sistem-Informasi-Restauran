<?php

namespace Tests\Feature;

use App\Models\KodeDarurat;
use App\Models\LogAktivitas;
use App\Models\PengaturanSistem;
use App\Models\PerangkatOutlet;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AksesOutletTest extends TestCase
{
    use RefreshDatabase;

    private const IP_OUTLET = '103.25.10.7';

    private const IP_LUAR = '36.80.1.2';

    private User $owner;

    private User $kasir;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
        $this->owner = User::factory()->create(['role' => User::ROLE_OWNER, 'nama' => 'Owner Resto', 'email' => 'owner@resto.id']);
        $this->kasir = User::factory()->create(['role' => User::ROLE_KASIR, 'nama' => 'Bima Kasir', 'email' => 'bima@resto.id']);
        PengaturanSistem::aktif()->update(['lat_resto' => 3.5905, 'lng_resto' => 98.6437]);
    }

    private function atur(array $data): void
    {
        PengaturanSistem::aktif()->update($data);
    }

    private function dari(string $ip): static
    {
        return $this->withServerVariables(['REMOTE_ADDR' => $ip]);
    }

    private function postLogin(User $user, string $ip = '127.0.0.1')
    {
        return $this->dari($ip)->post('/login', ['email' => $user->email, 'password' => 'password']);
    }

    public function test_tanpa_aturan_staf_bebas_masuk_dari_mana_saja(): void
    {
        $this->postLogin($this->kasir, self::IP_LUAR)->assertRedirect('/kasir/dashboard');
        $this->assertAuthenticatedAs($this->kasir);
    }

    public function test_ip_outlet_wajib_untuk_kasir_tapi_tidak_untuk_kurir_dan_owner(): void
    {
        $this->atur(['ip_outlet' => self::IP_OUTLET."\n10.0.0.0/24"]);

        $this->postLogin($this->kasir, self::IP_LUAR)->assertRedirect('/verifikasi-outlet');
        $this->assertGuest();
        $this->dari(self::IP_LUAR)->get('/verifikasi-outlet')->assertOk()->assertSee('Verifikasi')->assertSee('kode darurat')->assertDontSee('Periksa lokasi saya');
        $this->assertDatabaseHas('log_aktivitas', ['aksi' => 'akun.ditolak', 'id_user' => $this->kasir->id]);

        $this->postLogin($this->kasir, '10.0.0.44')->assertRedirect('/kasir/dashboard');
        $this->assertAuthenticatedAs($this->kasir);
        $this->assertStringContainsString('lewat WiFi outlet', LogAktivitas::where('aksi', 'akun.login')->latest('id')->first()->keterangan);

        // selama sesi: pindah ke jaringan lain = keluar otomatis
        $this->dari(self::IP_LUAR)->get('/kasir/dashboard')->assertRedirect('/login')
            ->assertSessionHas('status', fn ($s) => str_contains($s, 'tidak lagi terdeteksi di area outlet'));
        $this->assertGuest();

        $kurir = User::factory()->create(['role' => User::ROLE_KURIR]);
        $this->postLogin($kurir, self::IP_LUAR)->assertRedirect('/kurir/tugas');
        $this->post('/logout');

        $this->postLogin($this->owner, self::IP_LUAR)->assertRedirect('/owner/dashboard');
        $this->assertStringContainsString('dari luar outlet', LogAktivitas::where('aksi', 'akun.login')->latest('id')->first()->keterangan);
    }

    public function test_cek_gps_sebagai_cadangan_wifi_outlet(): void
    {
        $this->atur(['ip_outlet' => self::IP_OUTLET, 'cek_gps' => true, 'radius_outlet_m' => 100]);
        $this->postLogin($this->kasir, self::IP_LUAR)->assertRedirect('/verifikasi-outlet');
        $this->get('/verifikasi-outlet')->assertSee('Periksa lokasi saya');

        // ±1,1 km dari restoran: ditolak
        $this->from('/verifikasi-outlet')->post('/verifikasi-outlet', ['lat' => 3.6005, 'lng' => 98.6437, 'akurasi' => 20])
            ->assertSessionHasErrors('lat');
        $this->assertGuest();

        // ±30 m: masuk
        $this->dari(self::IP_LUAR)->post('/verifikasi-outlet', ['lat' => 3.5907, 'lng' => 98.6438, 'akurasi' => 25])->assertRedirect('/kasir/dashboard');
        $this->assertAuthenticatedAs($this->kasir);
        $this->dari(self::IP_LUAR)->get('/kasir/dashboard')->assertOk()->assertSee('data-gps=', false);

        // posisi berkala masih di outlet, lalu menjauh → sesi berakhir
        $this->dari(self::IP_LUAR)->postJson('/sesi/lokasi', ['lat' => 3.5906, 'lng' => 98.6437, 'akurasi' => 15])->assertOk();
        $this->dari(self::IP_LUAR)->postJson('/sesi/lokasi', ['lat' => 3.6300, 'lng' => 98.6437, 'akurasi' => 15])->assertUnauthorized();
        $this->assertGuest();
    }

    public function test_sesi_gps_berakhir_bila_posisi_tidak_terkirim_lagi(): void
    {
        $this->atur(['ip_outlet' => self::IP_OUTLET, 'cek_gps' => true]);
        $this->postLogin($this->kasir, self::IP_LUAR);
        $this->dari(self::IP_LUAR)->post('/verifikasi-outlet', ['lat' => 3.5905, 'lng' => 98.6437, 'akurasi' => 10]);
        $this->assertAuthenticatedAs($this->kasir);

        $this->travel(21)->minutes();
        $this->dari(self::IP_LUAR)->get('/kasir/dashboard')->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_perangkat_terdaftar(): void
    {
        // belum ada perangkat: aturan tidak bisa dinyalakan
        $this->actingAs($this->owner)->put('/owner/keamanan', ['radius_outlet_m' => 100, 'wajib_perangkat' => 1])
            ->assertSessionHasErrors('wajib_perangkat');

        $r = $this->actingAs($this->owner)->post('/owner/keamanan/perangkat', ['nama' => 'PC Kasir 1'])->assertSessionHas('status');
        $token = $r->getCookie(PerangkatOutlet::COOKIE)->getValue();
        $this->assertNotNull(PerangkatOutlet::dariToken($token));
        $this->actingAs($this->owner)->put('/owner/keamanan', ['radius_outlet_m' => 100, 'wajib_perangkat' => 1])->assertSessionHasNoErrors();
        $this->assertTrue(PengaturanSistem::aktif()->wajib_perangkat);
        $this->post('/logout');

        // perangkat lain: hanya bisa dengan kode darurat (tanpa pilihan GPS)
        $this->postLogin($this->kasir)->assertRedirect('/verifikasi-outlet');
        $this->get('/verifikasi-outlet')->assertSee('perangkat outlet yang terdaftar')->assertDontSee('Periksa lokasi saya');

        $this->withCookie(PerangkatOutlet::COOKIE, $token)->post('/login', ['email' => $this->kasir->email, 'password' => 'password'])->assertRedirect('/kasir/dashboard');
        $this->assertAuthenticatedAs($this->kasir);
        $this->assertNotNull(PerangkatOutlet::first()->terakhir_dipakai_at);

        // perangkat dihapus owner → sesi kasir di perangkat itu berakhir
        PerangkatOutlet::first()->delete();
        $this->app['auth']->forgetGuards();
        $this->withCookie(PerangkatOutlet::COOKIE, $token)->get('/kasir/dashboard')->assertRedirect('/login');
    }

    public function test_kode_darurat_dari_owner(): void
    {
        $this->atur(['ip_outlet' => self::IP_OUTLET]);
        $this->dari(self::IP_LUAR)->actingAs($this->owner)->post('/owner/keamanan/kode', ['alasan' => 'WiFi outlet mati', 'durasi' => 4])
            ->assertSessionHas('kode_baru');
        $kode = session('kode_baru');
        $this->assertMatchesRegularExpression('/^\d{4}-\d{4}$/', $kode);
        $this->post('/logout');

        $this->postLogin($this->kasir, self::IP_LUAR)->assertRedirect('/verifikasi-outlet');
        $this->from('/verifikasi-outlet')->post('/verifikasi-outlet', ['kode' => '0000-0000'])->assertSessionHasErrors('kode');
        $this->dari(self::IP_LUAR)->post('/verifikasi-outlet', ['kode' => $kode])->assertRedirect('/kasir/dashboard');
        $this->assertAuthenticatedAs($this->kasir);
        $this->assertSame(1, KodeDarurat::first()->dipakai);
        $this->assertDatabaseHas('log_aktivitas', ['aksi' => 'akun.kode_darurat', 'id_user' => $this->kasir->id]);

        // tetap bisa bekerja dari jaringan lain selama kode berlaku; dicabut → keluar
        $this->dari(self::IP_LUAR)->get('/kasir/dashboard')->assertOk();
        KodeDarurat::first()->update(['dicabut_at' => now()]);
        $this->dari(self::IP_LUAR)->get('/kasir/dashboard')->assertRedirect('/login');

        // kode kedaluwarsa tidak bisa dipakai
        KodeDarurat::first()->update(['dicabut_at' => null]);
        $this->travel(5)->hours();
        $this->postLogin($this->kasir, self::IP_LUAR);
        $this->from('/verifikasi-outlet')->post('/verifikasi-outlet', ['kode' => $kode])->assertSessionHasErrors('kode');
    }

    public function test_verifikasi_outlet_kedaluwarsa_setelah_lima_menit(): void
    {
        $this->atur(['ip_outlet' => self::IP_OUTLET]);
        $this->postLogin($this->kasir, self::IP_LUAR)->assertRedirect('/verifikasi-outlet');
        $this->travel(6)->minutes();
        $this->get('/verifikasi-outlet')->assertRedirect('/login');
        $this->post('/verifikasi-outlet', ['kode' => '1234-5678'])->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_jadwal_shift_membatasi_jam_masuk_dan_mengakhiri_sesi(): void
    {
        $this->atur(['wajib_shift' => true]);
        $this->actingAs($this->owner)->put("/owner/keamanan/shift/{$this->kasir->getRouteKey()}", ['hari' => [
            1 => ['aktif' => 1, 'mulai' => '08:00', 'selesai' => '16:00'],
            2 => ['aktif' => 1, 'mulai' => '22:00', 'selesai' => '06:00'],
            3 => ['mulai' => '08:00', 'selesai' => '16:00'],
        ]])->assertSessionHasNoErrors();
        $this->assertSame(2, $this->kasir->jadwalShift()->count());
        $this->post('/logout');

        // Senin 9 Nov 2026 07.20: terlalu awal (masuk paling cepat 07.30)
        Carbon::setTestNow('2026-11-09 07:20');
        $this->postLogin($this->kasir)->assertSessionHasErrors('email');
        $this->assertStringContainsString('Di luar jadwal shift kamu (hari ini 08:00–16:00)', session('errors')->first('email'));
        $this->assertGuest();

        Carbon::setTestNow('2026-11-09 08:12');
        $this->postLogin($this->kasir)->assertRedirect('/kasir/dashboard');
        $this->assertStringContainsString('shift 08:00–16:00 (terlambat 12 menit)', LogAktivitas::where('aksi', 'akun.login')->latest('id')->first()->keterangan);

        // 60 menit setelah shift selesai: sesi berakhir
        Carbon::setTestNow('2026-11-09 16:50');
        $this->withSession(['terakhir_aktif' => now()->timestamp])->get('/kasir/dashboard')->assertOk(); // anggap kasir aktif sepanjang hari
        Carbon::setTestNow('2026-11-09 17:05');
        $this->get('/kasir/dashboard')->assertRedirect('/login')->assertSessionHas('status', fn ($s) => str_contains($s, 'Jam shift kamu sudah selesai'));

        // shift malam Selasa 22.00–06.00 berlanjut ke Rabu dini hari; Rabu siang libur
        Carbon::setTestNow('2026-11-11 03:00');
        $this->postLogin($this->kasir)->assertRedirect('/kasir/dashboard');
        $this->post('/logout');
        Carbon::setTestNow('2026-11-11 12:00');
        $this->postLogin($this->kasir)->assertSessionHasErrors('email');
        $this->assertStringContainsString('hari ini kamu libur', session('errors')->first('email'));

        // staf tanpa jadwal tidak dibatasi
        $dapur = User::factory()->create(['role' => User::ROLE_DAPUR]);
        $this->postLogin($dapur)->assertRedirect('/dapur/antrean');
    }

    public function test_halaman_keamanan_hanya_owner(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $this->actingAs($admin)->get('/owner/keamanan')->assertForbidden();
        $this->actingAs($this->owner)->get('/owner/keamanan')->assertOk()
            ->assertSee('Lokasi Outlet')->assertSee('Perangkat Terdaftar')->assertSee('Kode Darurat')->assertSee('Bima Kasir');

        $this->actingAs($this->owner)->put('/owner/keamanan', ['ip_outlet' => "103.25.10.7\nbukan-ip", 'radius_outlet_m' => 100])
            ->assertSessionHasErrors('ip_outlet');
        $this->actingAs($this->owner)->put('/owner/keamanan', ['ip_outlet' => " 103.25.10.7 \n\n10.0.0.0/24", 'radius_outlet_m' => 80, 'cek_gps' => 1])
            ->assertSessionHasNoErrors();
        $this->assertSame("103.25.10.7\n10.0.0.0/24", PengaturanSistem::aktif()->ip_outlet);
        $this->assertDatabaseHas('log_aktivitas', ['aksi' => 'pengaturan.ubah', 'id_user' => $this->owner->id]);
    }
}
