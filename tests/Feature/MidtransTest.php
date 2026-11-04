<?php

namespace Tests\Feature;

use App\Models\Menu;
use App\Models\Pembayaran;
use App\Models\Pesanan;
use App\Models\TiketDapur;
use App\Models\User;
use Database\Seeders\MasterDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Pembayaran otomatis Virtual Account & QRIS lewat Midtrans (respons Midtrans dipalsukan dengan Http::fake).
 */
class MidtransTest extends TestCase
{
    use RefreshDatabase;

    private const SERVER_KEY = 'SB-Mid-server-UJICOBA';

    private User $pelanggan;

    private Pesanan $pesanan; // Pizza Beef Pepperoni: 109.000 + 10.900 + 5.450 = 125.350

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.midtrans.server_key' => self::SERVER_KEY, 'services.midtrans.is_production' => false]);
        $this->seed(MasterDataSeeder::class);
        Carbon::setTestNow(today()->setTime(12, 0));

        $this->pelanggan = User::factory()->create(['role' => User::ROLE_PELANGGAN, 'nama' => 'Rina Amalia', 'email' => 'rina@example.com']);
        $this->actingAs($this->pelanggan)->postJson('/keranjang', ['id_menu' => Menu::where('nama_menu', 'Pizza Beef Pepperoni')->value('id')]);
        $this->actingAs($this->pelanggan)->post('/checkout', ['jenis_layanan' => 'take_away']);
        $this->pesanan = Pesanan::firstOrFail();
    }

    /**
     * Palsukan respons charge Midtrans; beberapa respons dipakai berurutan untuk beberapa charge.
     */
    private function fakeCharge(array ...$respons): void
    {
        $urutan = Http::sequence();
        foreach ($respons as $r) {
            $urutan->push($r + ['status_code' => '201', 'transaction_status' => 'pending', 'expiry_time' => now()->addHour()->format('Y-m-d H:i:s')]);
        }

        Http::fake([
            'api.sandbox.midtrans.com/v2/charge' => $urutan,
            'api.sandbox.midtrans.com/v2/*/cancel' => Http::response(['status_code' => '200']),
        ]);
    }

    private function notifikasi(string $status, ?string $gross = null, ?string $signature = null): array
    {
        $bayar = $this->pesanan->fresh()->pembayaran;
        $gross ??= number_format((float) $bayar->jumlah, 2, '.', '');
        $kode = $status === 'settlement' ? '200' : ($status === 'expire' ? '407' : '201');

        return [
            'order_id' => $bayar->kode_transaksi, 'status_code' => $kode, 'gross_amount' => $gross,
            'transaction_status' => $status, 'fraud_status' => 'accept', 'payment_type' => 'bank_transfer',
            'signature_key' => $signature ?? hash('sha512', $bayar->kode_transaksi.$kode.$gross.self::SERVER_KEY),
        ];
    }

    public function test_virtual_account_dibuat_lewat_midtrans(): void
    {
        $this->fakeCharge(['va_numbers' => [['bank' => 'bca', 'va_number' => '12345678901']]]);

        $this->actingAs($this->pelanggan)->post("/pesanan/{$this->pesanan->id}/pembayaran", ['metode' => 'transfer', 'bank' => 'bca'])
            ->assertSessionHasNoErrors();

        Http::assertSent(fn (Request $r) => $r->url() === 'https://api.sandbox.midtrans.com/v2/charge'
            && $r['payment_type'] === 'bank_transfer' && $r['bank_transfer']['bank'] === 'bca'
            && $r['transaction_details']['gross_amount'] === 125350
            && $r->hasHeader('Authorization', 'Basic '.base64_encode(self::SERVER_KEY.':')));

        $bayar = $this->pesanan->fresh()->pembayaran;
        $this->assertSame(['transfer', 'bca', '12345678901', Pembayaran::STATUS_MENUNGGU], [$bayar->metode, $bayar->bank, $bayar->nomor_va, $bayar->status_bayar]);
        $this->assertStringStartsWith("SR-{$this->pesanan->id}-", $bayar->kode_transaksi);
        $this->assertNotNull($bayar->kedaluwarsa_pada);
        $this->assertSame(Pesanan::STATUS_MENUNGGU, $this->pesanan->fresh()->status);

        $this->actingAs($this->pelanggan)->get("/pesanan/{$this->pesanan->id}/pembayaran")
            ->assertSee('12345678901')->assertSee('VA BCA')->assertSee('Menunggu pembayaran')->assertSee('simulator Midtrans');

        // memilih VA yang sama lagi tidak membuat transaksi baru
        $this->actingAs($this->pelanggan)->post("/pesanan/{$this->pesanan->id}/pembayaran", ['metode' => 'transfer', 'bank' => 'bca']);
        Http::assertSentCount(1);

        // pesanan VA tidak perlu dikonfirmasi / diproses kasir
        $kasir = User::factory()->create(['role' => User::ROLE_KASIR]);
        $this->actingAs($kasir)->get('/kasir/online')->assertDontSee($this->pesanan->kode());
        $this->actingAs($kasir)->get('/kasir/pembayaran')->assertDontSee($this->pesanan->kode());
        $this->actingAs($kasir)->post("/kasir/online/{$this->pesanan->id}/terima")->assertSessionHasErrors('pesanan');
    }

    public function test_va_permata_dan_qris(): void
    {
        $this->fakeCharge(
            ['permata_va_number' => '8778001122334455'],
            ['actions' => [['name' => 'generate-qr-code', 'method' => 'GET', 'url' => 'https://api.sandbox.midtrans.com/v2/qris/abc/qr-code']]],
        );
        $this->actingAs($this->pelanggan)->post("/pesanan/{$this->pesanan->id}/pembayaran", ['metode' => 'transfer', 'bank' => 'permata']);
        $this->assertSame('8778001122334455', $this->pesanan->fresh()->pembayaran->nomor_va);

        $this->actingAs($this->pelanggan)->post("/pesanan/{$this->pesanan->id}/pembayaran", ['metode' => 'qris'])->assertSessionHasNoErrors();

        Http::assertSent(fn (Request $r) => str_ends_with($r->url(), '/v2/charge') && $r['payment_type'] === 'qris');
        Http::assertSent(fn (Request $r) => str_ends_with($r->url(), '/cancel')); // VA Permata lama dibatalkan
        $bayar = $this->pesanan->fresh()->pembayaran;
        $this->assertSame(['qris', null, 'https://api.sandbox.midtrans.com/v2/qris/abc/qr-code'], [$bayar->metode, $bayar->bank, $bayar->qris_url]);
        $this->actingAs($this->pelanggan)->get("/pesanan/{$this->pesanan->id}/pembayaran")->assertSee('https://api.sandbox.midtrans.com/v2/qris/abc/qr-code');
    }

    public function test_validasi_dan_error_midtrans(): void
    {
        $this->actingAs($this->pelanggan)->post("/pesanan/{$this->pesanan->id}/pembayaran", ['metode' => 'transfer'])->assertSessionHasErrors('bank');
        $this->actingAs($this->pelanggan)->post("/pesanan/{$this->pesanan->id}/pembayaran", ['metode' => 'transfer', 'bank' => 'jago'])->assertSessionHasErrors('bank');

        // Midtrans bisa membalas HTTP 200 dengan status_code error
        Http::fake(['api.sandbox.midtrans.com/*' => Http::response(['status_code' => '401', 'status_message' => 'Access denied due to unauthorized transaction'])]);
        $this->actingAs($this->pelanggan)->post("/pesanan/{$this->pesanan->id}/pembayaran", ['metode' => 'transfer', 'bank' => 'bni'])
            ->assertSessionHasErrors(['metode' => 'Midtrans: Access denied due to unauthorized transaction']);
        $this->assertNull($this->pesanan->fresh()->pembayaran);
    }

    public function test_cek_status_lunas_langsung_ke_dapur(): void
    {
        $this->fakeCharge(['va_numbers' => [['bank' => 'bri', 'va_number' => '88810000001']]]);
        $this->actingAs($this->pelanggan)->post("/pesanan/{$this->pesanan->id}/pembayaran", ['metode' => 'transfer', 'bank' => 'bri']);
        $kode = $this->pesanan->fresh()->pembayaran->kode_transaksi;

        Http::fake(["api.sandbox.midtrans.com/v2/{$kode}/status" => Http::sequence()
            ->push(['status_code' => '201', 'transaction_status' => 'pending'])
            ->push(['status_code' => '200', 'transaction_status' => 'settlement', 'fraud_status' => 'accept'])]);

        $this->actingAs($this->pelanggan)->getJson("/pesanan/{$this->pesanan->id}/pembayaran/status")
            ->assertOk()->assertJson(['lunas' => false, 'status_pesanan' => Pesanan::STATUS_MENUNGGU]);

        $this->travel(5)->seconds(); // dibatasi satu cek per 4 detik
        $this->actingAs($this->pelanggan)->getJson("/pesanan/{$this->pesanan->id}/pembayaran/status")
            ->assertOk()->assertJson(['lunas' => true, 'status_pesanan' => Pesanan::STATUS_DIPROSES]);

        $p = $this->pesanan->fresh();
        $this->assertSame(Pembayaran::STATUS_LUNAS, $p->pembayaran->status_bayar);
        $this->assertNotNull($p->pembayaran->waktu_bayar);
        $this->assertSame(TiketDapur::STATUS_ANTRE, $p->tiketDapur->status_masak);
        $this->actingAs($this->pelanggan)->get("/pesanan/{$this->pesanan->id}/pembayaran")->assertSee('berhasil');
    }

    public function test_notifikasi_webhook(): void
    {
        $this->fakeCharge(['va_numbers' => [['bank' => 'bni', 'va_number' => '9881234']]]);
        $this->actingAs($this->pelanggan)->post("/pesanan/{$this->pesanan->id}/pembayaran", ['metode' => 'transfer', 'bank' => 'bni']);

        // tanpa login & tanpa CSRF; signature salah ditolak
        $this->postJson('/midtrans/notifikasi', $this->notifikasi('settlement', signature: 'palsu'))->assertForbidden();
        // nominal tidak cocok ditolak walaupun signature cocok
        $this->postJson('/midtrans/notifikasi', $this->notifikasi('settlement', gross: '1000.00'))->assertStatus(422);
        $this->assertSame(Pembayaran::STATUS_MENUNGGU, $this->pesanan->fresh()->pembayaran->status_bayar);

        $this->postJson('/midtrans/notifikasi', $this->notifikasi('settlement'))->assertOk();
        $p = $this->pesanan->fresh();
        $this->assertSame([Pembayaran::STATUS_LUNAS, Pesanan::STATUS_DIPROSES], [$p->pembayaran->status_bayar, $p->status]);
        $this->assertSame(1, TiketDapur::count());

        // notifikasi ganda tidak membuat tiket kedua
        $this->postJson('/midtrans/notifikasi', $this->notifikasi('settlement'))->assertOk();
        $this->assertSame(1, TiketDapur::count());
    }

    public function test_kedaluwarsa_bisa_pilih_metode_lagi(): void
    {
        $this->fakeCharge(['va_numbers' => [['bank' => 'bca', 'va_number' => '111']]], ['va_numbers' => [['bank' => 'bca', 'va_number' => '222']]]);
        $this->actingAs($this->pelanggan)->post("/pesanan/{$this->pesanan->id}/pembayaran", ['metode' => 'transfer', 'bank' => 'bca']);

        $this->postJson('/midtrans/notifikasi', $this->notifikasi('expire'))->assertOk();
        $this->assertSame(Pembayaran::STATUS_DITOLAK, $this->pesanan->fresh()->pembayaran->status_bayar);
        $this->actingAs($this->pelanggan)->get("/pesanan/{$this->pesanan->id}/pembayaran")->assertSee('kedaluwarsa')->assertSee('Pilih metode pembayaran');

        // VA sama dipilih lagi -> transaksi baru
        $this->actingAs($this->pelanggan)->post("/pesanan/{$this->pesanan->id}/pembayaran", ['metode' => 'transfer', 'bank' => 'bca']);
        $bayar = $this->pesanan->fresh()->pembayaran;
        $this->assertSame(['222', Pembayaran::STATUS_MENUNGGU], [$bayar->nomor_va, $bayar->status_bayar]);
    }
}
