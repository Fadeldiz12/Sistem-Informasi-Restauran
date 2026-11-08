<?php

namespace Tests\Feature;

use App\Models\Menu;
use App\Models\Pengeluaran;
use App\Models\Pesanan;
use App\Models\User;
use App\Services\LaporanService;
use App\Services\PesananService;
use Database\Seeders\MasterDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class OwnerTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private User $kasir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(MasterDataSeeder::class);
        Carbon::setTestNow(Carbon::parse('2026-11-04 12:00'));
        $this->owner = User::factory()->create(['role' => User::ROLE_OWNER, 'nama' => 'Owner Resto']);
        $this->kasir = User::factory()->create(['role' => User::ROLE_KASIR]);
    }

    /**
     * Pesanan di tempat yang dilunasi kasir pada waktu tertentu.
     */
    private function lunas(string $waktu, string $menu = 'Pizza Margherita', int $jumlah = 1, string $layanan = 'dine_in'): Pesanan
    {
        Carbon::setTestNow(Carbon::parse($waktu));
        $svc = app(PesananService::class);
        $p = $svc->buat([['id_menu' => Menu::firstWhere('nama_menu', $menu)->id, 'jumlah' => $jumlah]], $layanan, kasir: $this->kasir,
            data: $layanan === 'dine_in' ? ['nomor_meja' => '01'] : ['nama_pemesan' => 'Budi']);
        $svc->lunasi($p, $this->kasir, 'tunai', 10000000);
        $p->update(['status' => Pesanan::STATUS_SELESAI]); // meja kosong lagi untuk pesanan berikutnya
        Carbon::setTestNow(Carbon::parse('2026-11-04 12:00'));

        return $p->fresh();
    }

    public function test_hanya_owner(): void
    {
        foreach (['/owner/dashboard', '/owner/laporan', '/owner/pengeluaran', '/owner/laporan/pdf', '/owner/laporan/excel'] as $url) {
            $this->actingAs($this->kasir)->get($url)->assertForbidden();
        }
        $this->actingAs($this->owner)->get('/owner/dashboard')->assertOk()->assertSee('Owner Resto')->assertSee('Belum ada penjualan');
    }

    public function test_dashboard_penjualan_per_periode(): void
    {
        $a = $this->lunas('2026-11-04 11:15', 'Pizza Beef Pepperoni', 3);
        $b = $this->lunas('2026-11-04 18:40', 'Penne Bolognese', 1, 'take_away');
        $kemarin = $this->lunas('2026-11-03 19:00', 'Penne Bolognese', 5);
        $bulanLalu = $this->lunas('2026-10-10 13:00', 'Kentang Goreng', 2);

        // pesanan belum lunas & dibatalkan tidak dihitung
        app(PesananService::class)->buat([['id_menu' => Menu::first()->id, 'jumlah' => 9]], 'dine_in', kasir: $this->kasir, data: ['nomor_meja' => '02']);
        $batal = $this->lunas('2026-11-04 11:30', 'Kentang Goreng', 7);
        $batal->update(['status' => Pesanan::STATUS_DIBATALKAN]);

        $harian = $this->actingAs($this->owner)->get('/owner/dashboard')->assertOk();
        $harian->assertViewHas('jumlah', 2)
            ->assertViewHas('total', (float) $a->total + (float) $b->total)
            ->assertSeeInOrder(['Menu Terlaris', 'Pizza Beef Pepperoni', '3 porsi', 'Penne Bolognese', '1 porsi'])
            ->assertViewHas('layanan', fn ($l) => $l['dine_in']['persen'] === 50 && $l['take_away']['jumlah'] === 1);
        $grafik = $harian->viewData('grafik');
        $this->assertSame((float) $a->total, $grafik['nilai'][array_search('11', $grafik['label'])]);

        // minggu ini (Senin 2 Nov – Minggu 8 Nov) mencakup kemarin; Penne jadi terlaris
        $this->actingAs($this->owner)->get('/owner/dashboard?periode=mingguan')->assertOk()
            ->assertViewHas('jumlah', 3)->assertViewHas('grafik', fn ($g) => count($g['label']) === 7 && $g['label'][0] === 'Sen')
            ->assertViewHas('terlaris', fn ($t) => $t[0]['nama'] === 'Penne Bolognese' && $t[0]['porsi'] === 6);

        $this->actingAs($this->owner)->get('/owner/dashboard?periode=bulanan')->assertOk()
            ->assertViewHas('jumlah', 4)->assertViewHas('grafik', fn ($g) => $g['label'] === ['Jun', 'Jul', 'Agt', 'Sep', 'Okt', 'Nov'] && $g['nilai'][4] === (float) $bulanLalu->total);
    }

    public function test_laporan_keuangan_per_tanggal(): void
    {
        $a = $this->lunas('2026-11-02 12:00');
        $b = $this->lunas('2026-11-04 10:00', 'Pizza Beef Pepperoni', 2);
        Pengeluaran::create(['id_owner' => $this->owner->id, 'kategori' => 'Gas', 'nominal' => 950000, 'tanggal' => '2026-11-02']);
        Pengeluaran::create(['id_owner' => $this->owner->id, 'kategori' => 'Gaji', 'nominal' => 50000, 'tanggal' => '2026-10-01']);

        $r = $this->actingAs($this->owner)->get('/owner/laporan')->assertOk(); // bawaan 7 hari: 29 Okt – 4 Nov
        $r->assertViewHas('baris', fn ($b) => $b->count() === 7)
            ->assertViewHas('pendapatan', (float) $a->total + (float) $b->total)
            ->assertViewHas('pengeluaran', 950000.0)
            ->assertViewHas('laba', (float) $a->total + (float) $b->total - 950000)
            ->assertSee('29 Okt – 4 Nov 2026')->assertSee('Unduh PDF');

        $this->actingAs($this->owner)->get('/owner/laporan?dari=2026-10-01&sampai=2026-10-01')
            ->assertViewHas('pengeluaran', 50000.0)->assertViewHas('pendapatan', 0.0);
        $this->actingAs($this->owner)->get('/owner/laporan?cepat=hari')->assertViewHas('pendapatan', (float) $b->total);

        // tidak ada data → pesan, tanpa tombol unduh
        $this->actingAs($this->owner)->get('/owner/laporan?dari=2026-09-01&sampai=2026-09-05')->assertOk()
            ->assertSee('Tidak ada data pendapatan maupun pengeluaran')->assertDontSee('Unduh PDF');

        // validasi periode
        $this->actingAs($this->owner)->get('/owner/laporan?dari=2026-11-04&sampai=2026-11-01')->assertSessionHasErrors('sampai');
        $this->actingAs($this->owner)->get('/owner/laporan?dari=2024-01-01&sampai=2026-11-01')->assertSessionHasErrors('sampai');
    }

    public function test_unduh_pdf_dan_excel(): void
    {
        $this->lunas('2026-11-03 12:00');
        $q = '?dari=2026-11-01&sampai=2026-11-04';

        $pdf = $this->actingAs($this->owner)->get('/owner/laporan/pdf'.$q)->assertOk();
        $this->assertStringContainsString('application/pdf', $pdf->headers->get('content-type'));
        $this->assertStringContainsString('laporan-keuangan-20261101-20261104.pdf', $pdf->headers->get('content-disposition'));
        $this->assertStringStartsWith('%PDF', $pdf->getContent());

        $xls = $this->actingAs($this->owner)->get('/owner/laporan/excel'.$q)->assertOk();
        $this->assertStringContainsString('laporan-keuangan-20261101-20261104.xlsx', $xls->headers->get('content-disposition'));
        $this->assertStringStartsWith('PK', file_get_contents($xls->getFile()->getPathname()));
    }

    public function test_crud_pengeluaran(): void
    {
        $this->actingAs($this->owner)->post('/owner/pengeluaran', [
            'tanggal' => '2026-11-03', 'kategori' => 'Gas', 'nominal' => '950.000', 'keterangan' => 'Isi ulang 6 tabung',
        ])->assertSessionHasNoErrors();
        $p = Pengeluaran::first();
        $this->assertSame('950000.00', $p->nominal);
        $this->assertSame($this->owner->id, $p->id_owner);

        $this->actingAs($this->owner)->get('/owner/pengeluaran')->assertOk()->assertSee('Isi ulang 6 tabung')->assertSee('Rp 950.000');
        $this->actingAs($this->owner)->get('/owner/pengeluaran?q=tabung')->assertSee('Isi ulang 6 tabung');
        $this->actingAs($this->owner)->get('/owner/pengeluaran?kategori=Gaji')->assertDontSee('<td class="ket">Isi ulang 6 tabung', false);

        $this->actingAs($this->owner)->put("/owner/pengeluaran/{$p->getRouteKey()}", [
            'tanggal' => '2026-11-04', 'kategori' => 'Utilitas', 'nominal' => '1.200.000', 'keterangan' => 'Listrik',
        ])->assertSessionHasNoErrors();
        $this->assertSame('Utilitas', $p->fresh()->kategori);
        $this->assertSame('1200000.00', $p->fresh()->nominal);

        // validasi: masa depan, kategori tidak dikenal, nominal 0
        $this->actingAs($this->owner)->post('/owner/pengeluaran', ['tanggal' => '2026-11-05', 'kategori' => 'Gas', 'nominal' => '1000'])->assertSessionHasErrors('tanggal');
        $this->actingAs($this->owner)->post('/owner/pengeluaran', ['tanggal' => '2026-11-04', 'kategori' => 'Liburan', 'nominal' => '1000'])->assertSessionHasErrors('kategori');
        $this->actingAs($this->owner)->post('/owner/pengeluaran', ['tanggal' => '2026-11-04', 'kategori' => 'Gas', 'nominal' => '0'])->assertSessionHasErrors('nominal');

        $this->actingAs($this->owner)->delete("/owner/pengeluaran/{$p->getRouteKey()}")->assertSessionHasNoErrors();
        $this->assertSame(0, Pengeluaran::count());
    }

    public function test_pesan_format_tanggal_dan_singkatan_miliar(): void
    {
        $this->actingAs($this->owner)->from('/owner/laporan')->get('/owner/laporan?dari=06/11/2026&sampai=abc')
            ->assertSessionHasErrors(['dari' => 'Format tanggal tidak valid. Pilih tanggal dari kalender.']);
        $this->assertSame('Rp 1,5 miliar', LaporanService::ringkas(1_500_000_000));
        $this->assertSame('Rp 18,02 jt', LaporanService::ringkas(18_020_000));
    }
}
