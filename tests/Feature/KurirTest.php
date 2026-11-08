<?php

namespace Tests\Feature;

use App\Models\Menu;
use App\Models\Pembayaran;
use App\Models\Pengiriman;
use App\Models\Pesanan;
use App\Models\Setoran;
use App\Models\User;
use App\Models\ZonaPengiriman;
use App\Services\DapurService;
use App\Services\KurirService;
use App\Services\PesananService;
use Database\Seeders\MasterDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class KurirTest extends TestCase
{
    use RefreshDatabase;

    private User $kurir;

    private User $kasir;

    private User $pelanggan;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake(KurirService::DISK);
        $this->seed(MasterDataSeeder::class);
        Carbon::setTestNow(today()->setTime(12, 0));
        $this->kurir = User::factory()->create(['role' => User::ROLE_KURIR, 'nama' => 'Bayu Saputra']);
        $this->kasir = User::factory()->create(['role' => User::ROLE_KASIR]);
        $this->pelanggan = User::factory()->create(['role' => User::ROLE_PELANGGAN, 'nama' => 'Rina Amalia', 'no_hp' => '081265432109']);
    }

    /**
     * Pesanan Delivery yang sudah selesai dimasak (siap diantar).
     */
    private function siapDiantar(string $alamat = 'Jl. Sei Batang Hari No. 27', string $metode = 'tunai', ?User $pelanggan = null): Pesanan
    {
        $svc = app(PesananService::class);
        $p = $svc->buat([['id_menu' => Menu::where('tersedia', true)->first()->id, 'jumlah' => 2]], 'delivery', $pelanggan ?? $this->pelanggan,
            data: ['alamat' => $alamat, 'id_zona' => ZonaPengiriman::first()->id]);

        if ($metode === 'tunai') {
            $p->pembayaran()->create(['metode' => 'tunai', 'jumlah' => $p->total, 'status_bayar' => 'menunggu']);
            $svc->konfirmasi($p, $this->kasir);
        } else {
            $p->pembayaran()->create(['metode' => $metode, 'jumlah' => $p->total, 'status_bayar' => 'menunggu']);
            $svc->terapkanStatusGateway($p->pembayaran, 'settlement');
        }

        $tiket = $p->fresh()->tiketDapur;
        app(DapurService::class)->mulaiMasak($tiket);
        app(DapurService::class)->selesaiMasak($tiket->fresh());

        return $p->fresh();
    }

    public function test_daftar_tugas_fifo_dari_waktu_siap(): void
    {
        $a = $this->siapDiantar('Jl. Pertama');
        $this->travel(3)->minutes();
        $b = $this->siapDiantar('Jl. Kedua');

        $this->actingAs($this->kurir)->get('/kurir/tugas')->assertOk()
            ->assertSee('Bayu Saputra')
            ->assertSeeInOrder(['Antrean ke-1', $a->kode(), 'Jl. Pertama', 'Antrean ke-2', $b->kode(), 'Jl. Kedua'])
            ->assertSee('Menunggu tugas #1 diterima');

        $this->actingAs($this->kasir)->get('/kurir/tugas')->assertForbidden();
        $this->actingAs($this->pelanggan)->post("/kurir/tugas/{$a->pengiriman->getRouteKey()}/terima")->assertForbidden();
    }

    public function test_pesanan_belum_siap_tidak_masuk_antrean(): void
    {
        $p = app(PesananService::class)->buat([['id_menu' => Menu::first()->id, 'jumlah' => 1]], 'delivery', $this->pelanggan,
            data: ['alamat' => 'Jl. Belum Siap', 'id_zona' => ZonaPengiriman::first()->id]);

        $this->actingAs($this->kurir)->get('/kurir/tugas')->assertOk()->assertDontSee('Jl. Belum Siap')->assertSee('Belum ada pesanan yang siap diantar');
        $this->actingAs($this->kurir)->post("/kurir/tugas/{$p->pengiriman->getRouteKey()}/terima")->assertSessionHasErrors('tugas');
    }

    public function test_terima_tugas_harus_urut_fifo(): void
    {
        $a = $this->siapDiantar('Jl. Pertama');
        $this->travel(1)->minutes();
        $b = $this->siapDiantar('Jl. Kedua');

        $this->actingAs($this->kurir)->get("/kurir/tugas/{$a->pengiriman->getRouteKey()}")->assertOk()->assertSee('Terima Tugas')->assertSee('0812');
        $this->actingAs($this->kurir)->get("/kurir/tugas/{$b->pengiriman->getRouteKey()}")->assertOk()->assertDontSee('Terima Tugas</button>', false)->assertSee('terkunci');

        $this->actingAs($this->kurir)->post("/kurir/tugas/{$b->pengiriman->getRouteKey()}/terima")->assertSessionHasErrors('tugas');
        $this->assertNull($b->pengiriman->fresh()->id_kurir);

        $this->actingAs($this->kurir)->post("/kurir/tugas/{$a->pengiriman->getRouteKey()}/terima")
            ->assertRedirect(route('kurir.status', ['pengiriman' => $a->pengiriman]));

        $kirim = $a->pengiriman->fresh();
        $this->assertSame($this->kurir->id, $kirim->id_kurir);
        $this->assertSame(Pengiriman::STATUS_DALAM_PERJALANAN, $kirim->status_kirim);
        $this->assertSame(Pesanan::STATUS_DIANTAR, $a->fresh()->status);

        // kurir lain tidak bisa mengambil tugas yang sama
        $lain = User::factory()->create(['role' => User::ROLE_KURIR]);
        $this->actingAs($lain)->post("/kurir/tugas/{$a->pengiriman->getRouteKey()}/terima")->assertSessionHasErrors('tugas');
        $this->actingAs($lain)->get("/kurir/tugas/{$a->pengiriman->getRouteKey()}")->assertRedirect(route('kurir.tugas'));

        // tugas berikutnya sekarang menjadi FIFO #1
        $this->actingAs($lain)->get('/kurir/tugas')->assertSeeInOrder(['Antrean ke-1', $b->kode()]);

        // pelanggan melihat nama kurir di halaman lacak
        $this->actingAs($this->pelanggan)->get("/tracking?pesanan={$a->getRouteKey()}")->assertSee('Bayu Saputra')->assertSee('Dalam Perjalanan');
    }

    public function test_diterima_wajib_foto_lalu_pesanan_selesai(): void
    {
        $p = $this->siapDiantar(metode: 'transfer');
        $kirim = $p->pengiriman;
        $this->actingAs($this->kurir)->post("/kurir/tugas/{$kirim->getRouteKey()}/terima");

        $this->actingAs($this->kurir)->get('/kurir/status')->assertOk()->assertSee($p->kode())->assertSee('Foto bukti pengiriman')->assertDontSee('name="uang_diterima"', false);

        // tanpa foto / bukan gambar ditolak
        $this->actingAs($this->kurir)->post("/kurir/tugas/{$kirim->getRouteKey()}/diterima")->assertSessionHasErrors('bukti');
        $this->actingAs($this->kurir)->post("/kurir/tugas/{$kirim->getRouteKey()}/diterima", ['bukti' => UploadedFile::fake()->create('a.pdf', 10, 'application/pdf')])->assertSessionHasErrors('bukti');
        $this->assertSame(Pengiriman::STATUS_DALAM_PERJALANAN, $kirim->fresh()->status_kirim);

        $this->actingAs($this->kurir)->post("/kurir/tugas/{$kirim->getRouteKey()}/diterima", ['bukti' => UploadedFile::fake()->image('bukti.jpg', 800, 600)])
            ->assertRedirect(route('kurir.riwayat'));

        $kirim->refresh();
        $this->assertSame(Pengiriman::STATUS_DITERIMA, $kirim->status_kirim);
        $this->assertSame(Pesanan::STATUS_SELESAI, $p->fresh()->status);
        Storage::disk(KurirService::DISK)->assertExists($kirim->bukti_kirim);

        $this->actingAs($this->kurir)->get('/kurir/riwayat')->assertOk()->assertSee($p->kode());
        $this->actingAs($this->kurir)->post("/kurir/tugas/{$kirim->getRouteKey()}/diterima", ['bukti' => UploadedFile::fake()->image('lagi.jpg')])->assertSessionHasErrors('tugas');
    }

    public function test_bayar_tunai_dikonfirmasi_kurir_menjadi_lunas(): void
    {
        $p = $this->siapDiantar();
        $kirim = $p->pengiriman;
        $this->actingAs($this->kurir)->post("/kurir/tugas/{$kirim->getRouteKey()}/terima");
        $this->actingAs($this->kurir)->get('/kurir/status')->assertSee('name="uang_diterima"', false);

        $this->actingAs($this->kurir)->post("/kurir/tugas/{$kirim->getRouteKey()}/diterima", ['bukti' => UploadedFile::fake()->image('b.jpg')])
            ->assertSessionHasErrors('uang_diterima');
        $this->assertFalse($p->fresh()->isLunas());

        $this->actingAs($this->kurir)->post("/kurir/tugas/{$kirim->getRouteKey()}/diterima", ['bukti' => UploadedFile::fake()->image('b.jpg'), 'uang_diterima' => 1])
            ->assertSessionHasNoErrors();

        $p->refresh();
        $this->assertTrue($p->isLunas());
        $this->assertSame(Pembayaran::METODE_TUNAI, $p->pembayaran->metode);
        $this->assertNotNull($p->pembayaran->waktu_bayar);
        $this->assertSame(Pesanan::STATUS_SELESAI, $p->status);
    }

    public function test_hanya_kurir_pengantar_yang_bisa_menyelesaikan(): void
    {
        $p = $this->siapDiantar(metode: 'transfer');
        $this->actingAs($this->kurir)->post("/kurir/tugas/{$p->pengiriman->getRouteKey()}/terima");

        $lain = User::factory()->create(['role' => User::ROLE_KURIR]);
        $this->actingAs($lain)->post("/kurir/tugas/{$p->pengiriman->getRouteKey()}/diterima", ['bukti' => UploadedFile::fake()->image('b.jpg')])->assertForbidden();
        $this->actingAs($lain)->get('/kurir/status')->assertOk()->assertSee('Tidak ada pesanan yang sedang diantar');
    }

    public function test_foto_bukti_hanya_untuk_yang_berhak(): void
    {
        $p = $this->siapDiantar(metode: 'transfer');
        $kirim = $p->pengiriman;
        $this->actingAs($this->kurir)->post("/kurir/tugas/{$kirim->getRouteKey()}/terima");
        $this->actingAs($this->kurir)->post("/kurir/tugas/{$kirim->getRouteKey()}/diterima", ['bukti' => UploadedFile::fake()->image('b.jpg')]);
        $url = route('pengiriman.bukti', $kirim);

        $this->actingAs($this->pelanggan)->get("/tracking?pesanan={$p->getRouteKey()}")->assertSee($url, false)->assertSee('Bukti pengiriman');
        $this->actingAs($this->pelanggan)->get($url)->assertOk();
        $this->actingAs($this->kurir)->get($url)->assertOk();
        $this->actingAs($this->kasir)->get($url)->assertOk();

        $orangLain = User::factory()->create(['role' => User::ROLE_PELANGGAN]);
        $this->actingAs($orangLain)->get($url)->assertForbidden();
        $this->actingAs(User::factory()->create(['role' => User::ROLE_KURIR]))->get($url)->assertForbidden();
        auth()->logout();
        $this->get($url)->assertRedirect('/login');
    }

    // ---------- setoran tunai kurir

    public function test_setoran_tunai_kurir_diterima_kasir(): void
    {
        $a = $this->siapDiantar('Jl. A');
        $this->actingAs($this->kurir)->post("/kurir/tugas/{$a->pengiriman->getRouteKey()}/terima");
        $this->actingAs($this->kurir)->post("/kurir/tugas/{$a->pengiriman->getRouteKey()}/diterima", ['bukti' => UploadedFile::fake()->image('b.jpg'), 'uang_diterima' => 1]);
        $bayar = $a->fresh()->pembayaran;
        $this->assertSame($this->kurir->id, $bayar->id_kurir);
        $this->assertNull($bayar->disetor_pada);
        $rp = 'Rp '.number_format((float) $a->total, 0, ',', '.');

        // kurir melihat uang yang dibawa
        $this->actingAs($this->kurir)->get('/kurir/riwayat')->assertSee('Uang tunai yang kamu bawa')->assertSee($rp);

        // kasir melihat setoran per kurir
        $this->actingAs($this->kasir)->get('/kasir/setoran')->assertOk()->assertSee('Bayu Saputra')->assertSee($a->kode())->assertSee('Terima Setoran');
        $this->actingAs($this->kurir)->get('/kasir/setoran')->assertForbidden();

        $this->actingAs($this->kasir)->post("/kasir/setoran/{$this->kurir->getRouteKey()}", ['ids' => [$bayar->id]])->assertSessionHasErrors('uang_dihitung');
        $this->actingAs($this->kasir)->post("/kasir/setoran/{$this->kurir->getRouteKey()}", ['ids' => [$bayar->id], 'uang_dihitung' => (string) ((int) $a->total - 5000)])
            ->assertSessionHasErrors('catatan'); // kurang tanpa catatan ditolak
        $this->actingAs($this->kasir)->post("/kasir/setoran/{$this->kurir->getRouteKey()}", ['ids' => [$bayar->id], 'uang_dihitung' => (string) ((int) $a->total - 5000), 'catatan' => 'kurang kembalian'])
            ->assertSessionHas('status', fn ($m) => str_contains($m, 'selisih -Rp 5.000'));
        $setoran = Setoran::firstOrFail();
        $this->assertEquals(-5000, $setoran->selisih());
        $this->actingAs($this->kasir)->get('/kasir/setoran')->assertSee('Selisih Setoran Hari Ini')->assertSee('kurang kembalian');
        $bayar->refresh();
        $this->assertNotNull($bayar->disetor_pada);
        $this->assertSame($this->kasir->id, $bayar->id_kasir);

        $this->actingAs($this->kasir)->get('/kasir/setoran')->assertSee('Tidak ada uang tunai yang belum disetor')->assertSee('Setoran Hari Ini');
        $this->actingAs($this->kurir)->get('/kurir/riwayat')->assertDontSee('Uang tunai yang kamu bawa');

        // dikirim ulang: tidak tercatat dua kali
        $this->actingAs($this->kasir)->post("/kasir/setoran/{$this->kurir->getRouteKey()}", ['ids' => [$bayar->id], 'uang_dihitung' => '1000'])->assertSessionHas('error');
    }

    public function test_kurir_yang_membawa_uang_tunai_tidak_bisa_dihapus_atau_diganti_role(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $a = $this->siapDiantar();
        $this->actingAs($this->kurir)->post("/kurir/tugas/{$a->pengiriman->getRouteKey()}/terima");
        $this->actingAs($this->kurir)->post("/kurir/tugas/{$a->pengiriman->getRouteKey()}/diterima", ['bukti' => UploadedFile::fake()->image('b.jpg'), 'uang_diterima' => 1]);
        $bayar = $a->fresh()->pembayaran;

        $this->actingAs($admin)->delete("/admin/pengguna/{$this->kurir->getRouteKey()}")
            ->assertSessionHas('error', fn ($m) => str_contains($m, 'belum disetor'));
        $this->actingAs($admin)->put("/admin/pengguna/{$this->kurir->getRouteKey()}", [
            'nama' => $this->kurir->nama, 'email' => $this->kurir->email, 'no_hp' => '081234567890', 'role' => User::ROLE_KASIR,
        ])->assertSessionHasErrors('role');
        $this->assertSame(User::ROLE_KURIR, $this->kurir->fresh()->role);
        $this->assertSame($this->kurir->id, $bayar->fresh()->id_kurir);

        // setelah setoran diterima kasir, akun boleh dihapus
        $this->actingAs($this->kasir)->post("/kasir/setoran/{$this->kurir->getRouteKey()}", ['ids' => [$bayar->id], 'uang_dihitung' => (string) (int) $bayar->jumlah]);
        $this->actingAs($admin)->delete("/admin/pengguna/{$this->kurir->getRouteKey()}")->assertSessionHas('status');
        $this->assertNull($this->kurir->fresh());
    }

    public function test_pembayaran_online_tidak_masuk_setoran(): void
    {
        $p = $this->siapDiantar(metode: 'transfer');
        $this->actingAs($this->kurir)->post("/kurir/tugas/{$p->pengiriman->getRouteKey()}/terima");
        $this->actingAs($this->kurir)->post("/kurir/tugas/{$p->pengiriman->getRouteKey()}/diterima", ['bukti' => UploadedFile::fake()->image('b.jpg')]);

        $this->assertNull($p->fresh()->pembayaran->id_kurir);
        $this->actingAs($this->kasir)->get('/kasir/setoran')->assertSee('Tidak ada uang tunai yang belum disetor');
    }
}
