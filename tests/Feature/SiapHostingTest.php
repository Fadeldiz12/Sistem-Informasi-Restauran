<?php

namespace Tests\Feature;

use App\Models\PengaturanSistem;
use App\Models\User;
use App\Providers\AppServiceProvider;
use App\Support\PemeriksaanServer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Middleware\TrustProxies;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class SiapHostingTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        TrustProxies::flushState();
        parent::tearDown();
    }

    private function aktifkanProxy(string $nilai): void
    {
        config(['resto.proxy_tepercaya' => $nilai]);
        (new AppServiceProvider($this->app))->boot();
    }

    public function test_tanpa_proxy_tepercaya_header_ip_palsu_diabaikan(): void
    {
        Notification::fake();
        PengaturanSistem::aktif()->update(['ip_outlet' => '103.25.10.7']);
        $kasir = User::factory()->create(['role' => User::ROLE_KASIR]);

        // penyerang dari luar outlet memalsukan header X-Forwarded-For dengan IP outlet
        $this->withServerVariables(['REMOTE_ADDR' => '36.80.1.2'])->withHeader('X-Forwarded-For', '103.25.10.7')
            ->post('/login', ['email' => $kasir->email, 'password' => 'password'])->assertRedirect('/verifikasi-outlet');
        $this->assertGuest();
    }

    public function test_di_belakang_proxy_tepercaya_ip_asli_pengunjung_dipakai(): void
    {
        Notification::fake();
        $this->aktifkanProxy('10.0.0.5');
        PengaturanSistem::aktif()->update(['ip_outlet' => '103.25.10.7']);
        $kasir = User::factory()->create(['role' => User::ROLE_KASIR]);

        // permintaan datang dari proxy hosting 10.0.0.5 yang meneruskan IP asli kasir di outlet
        $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.5'])->withHeader('X-Forwarded-For', '103.25.10.7')
            ->post('/login', ['email' => $kasir->email, 'password' => 'password'])->assertRedirect('/kasir/dashboard');
        $this->assertAuthenticatedAs($kasir);
    }

    public function test_produksi_mengalihkan_http_ke_https(): void
    {
        $this->app['env'] = 'production';
        config(['app.url' => 'https://resto.contoh.id']);

        $this->get('http://resto.contoh.id/beranda?kategori=pizza')->assertStatus(301)
            ->assertRedirect('https://resto.contoh.id/beranda?kategori=pizza');
        $this->post('http://resto.contoh.id/login', [])->assertStatus(308);
        $this->get('http://resto.contoh.id/up')->assertOk(); // health check hosting tetap lewat
        $this->get('https://resto.contoh.id/beranda')->assertOk();

        // di belakang proxy yang meneruskan X-Forwarded-Proto: https, tidak dialihkan lagi (tidak berputar)
        $this->aktifkanProxy('*');
        $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.5'])->withHeader('X-Forwarded-Proto', 'https')
            ->get('http://resto.contoh.id/beranda')->assertOk()->assertHeader('Strict-Transport-Security');
    }

    public function test_lokal_tidak_dialihkan(): void
    {
        $this->get('http://localhost/beranda')->assertOk();
    }

    public function test_pemeriksaan_server_tahu_scheduler_berjalan_atau_mati(): void
    {
        File::delete(storage_path('framework/scheduler-terakhir'));
        $this->assertFalse(PemeriksaanServer::schedulerJalan());

        $this->artisan('schedule:run')->assertSuccessful(); // seperti cron hosting tiap menit
        $this->assertTrue(PemeriksaanServer::schedulerJalan());

        $this->travel(PemeriksaanServer::SCHEDULER_MATI_MENIT + 1)->minutes();
        touch(storage_path('framework/scheduler-terakhir'), now()->subMinutes(PemeriksaanServer::SCHEDULER_MATI_MENIT + 1)->getTimestamp());
        $this->assertFalse(PemeriksaanServer::schedulerJalan());
        File::delete(storage_path('framework/scheduler-terakhir'));
    }

    public function test_peta_memakai_leaflet_dari_server_sendiri(): void
    {
        $this->assertFileExists(public_path('vendor/leaflet/leaflet.js'));
        $this->assertFileExists(public_path('vendor/leaflet/images/marker-icon.png'));
        foreach (['partials/peta-antar', 'admin/pengaturan', 'kurir/detail'] as $view) {
            $isi = file_get_contents(resource_path("views/{$view}.blade.php"));
            $this->assertStringContainsString("asset('vendor/leaflet/leaflet.js')", $isi, $view);
            $this->assertStringNotContainsString('unpkg.com', $isi, $view);
        }
    }
}
