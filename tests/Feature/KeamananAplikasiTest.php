<?php

namespace Tests\Feature;

use App\Models\Menu;
use App\Models\Pesanan;
use App\Models\User;
use App\Services\LaporanService;
use App\Services\PesananService;
use App\Support\TandaTanganDigital;
use Database\Seeders\MasterDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KeamananAplikasiTest extends TestCase
{
    use RefreshDatabase;

    private function pesananLunas(): Pesanan
    {
        $this->seed(MasterDataSeeder::class);
        $kasir = User::factory()->create(['role' => User::ROLE_KASIR]);
        $svc = app(PesananService::class);
        $p = $svc->buat([['id_menu' => Menu::firstWhere('nama_menu', 'Pizza Margherita')->id, 'jumlah' => 2]], 'take_away', kasir: $kasir, data: ['nama_pemesan' => 'Budi']);
        $svc->lunasi($p, $kasir, 'tunai', 1000000);

        return $p->fresh(['detail', 'pembayaran']);
    }

    public function test_security_headers_terpasang(): void
    {
        $r = $this->get('/beranda')->assertOk();

        $r->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $this->assertStringContainsString("frame-ancestors 'self'", $r->headers->get('Content-Security-Policy'));
        $this->assertStringContainsString("object-src 'none'", $r->headers->get('Content-Security-Policy'));
        $this->assertStringContainsString('camera=(self)', $r->headers->get('Permissions-Policy'));
        $this->assertFalse($r->headers->has('X-Powered-By'));
        $this->assertFalse($r->headers->has('Strict-Transport-Security')); // hanya lewat HTTPS

        $this->get('https://localhost/beranda')->assertHeader('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
    }

    public function test_struk_bertanda_tangan_digital_dan_bisa_dicek_keasliannya(): void
    {
        $p = $this->pesananLunas();
        $kasir = User::find($p->pembayaran->id_kasir);
        $ttd = TandaTanganDigital::struk($p);
        $this->assertMatchesRegularExpression('/^[0-9A-Z]{4}(-[0-9A-Z]{4}){3}$/', $ttd['kode']);

        $this->actingAs($kasir)->get(route('kasir.struk', $p))->assertOk()->assertSee($ttd['kode'])->assertSee('Cek keaslian');
        $this->post('/logout');

        $this->get($ttd['url'])->assertOk()->assertSee('ASLI')->assertSee($p->kode())->assertSee('Rp '.number_format((float) $p->total, 0, ',', '.'));
        $this->get(route('keaslian.struk', ['pesanan' => $p, 's' => 'AAAA-BBBB-CCCC-DDDD']))->assertOk()->assertSee('TIDAK VALID')->assertDontSee($p->kode());

        // total diubah di database (mis. struk dipalsukan lalu dicocokkan) → kode lama tidak berlaku
        $p->update(['total' => 1]);
        $this->get($ttd['url'])->assertSee('TIDAK VALID');
    }

    public function test_laporan_bertanda_tangan_digital(): void
    {
        $ttd = TandaTanganDigital::laporan('2026-11-01', '2026-11-04', 1250000, 300000, 950000);

        $this->get($ttd['url'])->assertOk()->assertSee('ASLI')->assertSee('Rp 1.250.000')->assertSee('Rp 950.000');
        // angka laba diubah di tautan → tidak valid
        $this->get(str_replace('lb=950000', 'lb=9950000', $ttd['url']))->assertSee('TIDAK VALID')->assertDontSee('Rp 9.950.000');
    }

    public function test_pdf_laporan_memuat_kode_verifikasi(): void
    {
        [$dari, $sampai] = [now()->subDays(2)->toImmutable(), now()->toImmutable()];
        $html = view('owner.laporan-pdf', app(LaporanService::class)->keuangan($dari, $sampai)
            + ['dari' => $dari, 'sampai' => $sampai, 'judulPeriode' => 'uji', 'pakaiLogo' => false])->render();

        $this->assertStringContainsString('Tanda tangan digital', $html);
        $this->assertStringContainsString('/cek-keaslian/laporan?', $html);
    }

    public function test_pemeriksaan_server_tampil_untuk_owner(): void
    {
        $owner = User::factory()->create(['role' => User::ROLE_OWNER]);
        config(['app.debug' => true]);

        $this->actingAs($owner)->get('/owner/keamanan')->assertOk()
            ->assertSee('Pemeriksaan Server')->assertSee('Mode debug mati')->assertSee('APP_DEBUG=false');
    }
}
