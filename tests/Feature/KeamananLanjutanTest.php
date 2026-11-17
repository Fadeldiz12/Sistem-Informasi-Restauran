<?php

namespace Tests\Feature;

use App\Console\Commands\BersihkanDataPribadi;
use App\Models\KodeDarurat;
use App\Models\PengaturanSistem;
use App\Models\Pesanan;
use App\Models\User;
use App\Models\WajahStaf;
use App\Models\ZonaPengiriman;
use App\Notifications\RingkasanKeamananNotification;
use App\Services\KurirService;
use App\Support\DuaFaktor;
use App\Support\TandaTanganDigital;
use App\Support\Totp;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class KeamananLanjutanTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
    }

    private function login(User $user, string $ip = '127.0.0.1')
    {
        return $this->withServerVariables(['REMOTE_ADDR' => $ip])->post('/login', ['email' => $user->email, 'password' => 'password']);
    }

    /**
     * Aktifkan 2FA lewat halaman pengaturan seperti pengguna sungguhan. Mengembalikan [rahasia, kode cadangan].
     *
     * @return array{0: string, 1: list<string>}
     */
    private function aktifkan2fa(User $user): array
    {
        $this->actingAs($user)->get('/akun/verifikasi-2-langkah')->assertOk()->assertSee('Kunci penyiapan');
        $rahasia = session('dua_faktor_baru');
        $this->post('/akun/verifikasi-2-langkah', ['kode' => '000000'])->assertSessionHasErrors('kode');
        $this->post('/akun/verifikasi-2-langkah', ['kode' => Totp::kode($rahasia, Totp::langkahSekarang())])
            ->assertRedirect('/akun/verifikasi-2-langkah')->assertSessionHas('kode_cadangan');
        $cadangan = session('kode_cadangan');
        $this->post('/logout');

        return [$rahasia, $cadangan];
    }

    public function test_password_bcrypt_lama_diperbarui_ke_argon2id_saat_login(): void
    {
        $this->assertSame('argon2id', config('hashing.driver'));
        $u = User::factory()->create(['role' => User::ROLE_PELANGGAN]);
        DB::table('users')->where('id', $u->id)->update(['password' => password_hash('password', PASSWORD_BCRYPT)]);

        $this->login($u)->assertRedirect();
        $this->assertAuthenticatedAs($u);
        $hash = DB::table('users')->where('id', $u->id)->value('password');
        $this->assertStringStartsWith('$argon2id$', $hash);
        $this->assertTrue(password_verify('password', $hash));
    }

    public function test_owner_mengaktifkan_2fa_dan_login_butuh_kode(): void
    {
        $owner = User::factory()->create(['role' => User::ROLE_OWNER]);
        [$rahasia, $cadangan] = $this->aktifkan2fa($owner);
        $this->assertCount(DuaFaktor::JUMLAH_CADANGAN, $cadangan);
        $mentah = DB::table('users')->where('id', $owner->id)->first();
        $this->assertStringNotContainsString($rahasia, $mentah->dua_faktor_rahasia); // rahasia tersimpan terenkripsi
        $this->assertStringNotContainsString($cadangan[0], Crypt::decryptString($mentah->dua_faktor_cadangan)); // hanya sidiknya

        // password benar saja belum cukup
        $this->login($owner)->assertRedirect('/verifikasi-2-langkah');
        $this->assertGuest();
        $this->get('/owner/dashboard')->assertRedirect('/login');
        $this->from('/verifikasi-2-langkah')->post('/verifikasi-2-langkah', ['kode' => '123456'])->assertSessionHasErrors('kode');
        $this->assertGuest();

        $this->travel(31)->seconds(); // kode saat aktivasi sudah dipakai, tunggu langkah berikutnya
        $kode = Totp::kode($rahasia, Totp::langkahSekarang());
        $this->post('/verifikasi-2-langkah', ['kode' => $kode])->assertRedirect('/owner/dashboard');
        $this->assertAuthenticatedAs($owner);
        $this->assertSame(0, $owner->fresh()->gagal_login);
        $this->post('/logout');

        // kode yang sama tidak bisa dipakai ulang
        $this->login($owner);
        $this->post('/verifikasi-2-langkah', ['kode' => $kode])->assertSessionHasErrors('kode');
        $this->assertGuest();

        // kode cadangan hanya sekali pakai
        $this->post('/verifikasi-2-langkah', ['kode' => strtolower($cadangan[0])])->assertRedirect('/owner/dashboard');
        $this->assertAuthenticatedAs($owner);
        $this->assertCount(DuaFaktor::JUMLAH_CADANGAN - 1, $owner->fresh()->dua_faktor_cadangan);
        $this->post('/logout');
        $this->login($owner);
        $this->post('/verifikasi-2-langkah', ['kode' => $cadangan[0]])->assertSessionHasErrors('kode');
        $this->assertGuest();
        $this->assertDatabaseHas('log_aktivitas', ['aksi' => 'akun.2fa_gagal']);
    }

    public function test_2fa_salah_berulang_mengulang_login_dan_waktu_terbatas(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $this->aktifkan2fa($admin);

        $this->login($admin)->assertRedirect('/verifikasi-2-langkah');
        for ($i = 1; $i < DuaFaktor::MAKS_GAGAL; $i++) {
            $this->post('/verifikasi-2-langkah', ['kode' => '000000'])->assertSessionHasErrors('kode');
        }
        $this->post('/verifikasi-2-langkah', ['kode' => '000000'])->assertRedirect('/login')->assertSessionHasErrors('email');
        $this->get('/verifikasi-2-langkah')->assertRedirect('/login');

        $this->login($admin)->assertRedirect('/verifikasi-2-langkah');
        $this->travel(DuaFaktor::BERLAKU_MENIT + 1)->minutes();
        $this->get('/verifikasi-2-langkah')->assertRedirect('/login');
    }

    public function test_2fa_hanya_untuk_owner_admin_dan_owner_bisa_mereset(): void
    {
        $kasir = User::factory()->create(['role' => User::ROLE_KASIR]);
        $this->actingAs($kasir)->get('/akun/verifikasi-2-langkah')->assertForbidden();
        $this->post('/logout');

        $owner = User::factory()->create(['role' => User::ROLE_OWNER]);
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $this->actingAs($admin)->get('/admin/dashboard')->assertSee('belum memakai');
        $this->post('/logout');
        $this->aktifkan2fa($admin);
        $this->assertTrue(DuaFaktor::aktif($admin->fresh()));

        $this->actingAs($owner)->get('/owner/pengguna')->assertSee('Reset verifikasi 2 langkah');
        $this->post("/owner/pengguna/{$admin->getRouteKey()}/reset-2-langkah")->assertSessionHas('status');
        $this->assertFalse(DuaFaktor::aktif($admin->fresh()));
        $this->post('/logout');

        $this->login($admin)->assertRedirect('/admin/dashboard');
        $this->assertDatabaseHas('log_aktivitas', ['aksi' => 'akun.2fa_reset']);
    }

    public function test_mematikan_2fa_butuh_password(): void
    {
        $owner = User::factory()->create(['role' => User::ROLE_OWNER]);
        [$rahasia] = $this->aktifkan2fa($owner);
        $this->login($owner);
        $this->travel(31)->seconds();
        $this->post('/verifikasi-2-langkah', ['kode' => Totp::kode($rahasia, Totp::langkahSekarang())]);

        $this->delete('/akun/verifikasi-2-langkah', ['password' => 'salah123'])->assertSessionHasErrors('password');
        $this->assertTrue(DuaFaktor::aktif($owner->fresh()));
        $this->delete('/akun/verifikasi-2-langkah', ['password' => 'password'])->assertSessionHasNoErrors();
        $this->assertFalse(DuaFaktor::aktif($owner->fresh()));
    }

    public function test_kode_darurat_dibatasi_per_akun(): void
    {
        $owner = User::factory()->create(['role' => User::ROLE_OWNER]);
        $kasir = User::factory()->create(['role' => User::ROLE_KASIR]);
        PengaturanSistem::aktif()->update(['ip_outlet' => '103.25.10.7']);
        $this->actingAs($owner)->post('/owner/keamanan/kode', ['alasan' => 'WiFi mati', 'durasi' => 4]);
        $kode = session('kode_baru');
        $this->post('/logout');

        $this->login($kasir, '36.80.1.2')->assertRedirect('/verifikasi-outlet');
        for ($i = 1; $i < 5; $i++) {
            $this->from('/verifikasi-outlet')->post('/verifikasi-outlet', ['kode' => '0000-000'.$i])->assertSessionHasErrors('kode');
        }
        $this->post('/verifikasi-outlet', ['kode' => '0000-0009'])->assertRedirect('/login')->assertSessionHasErrors('email');

        // walau login ulang dan kodenya benar, tetap diblokir sementara
        $this->login($kasir, '36.80.1.2')->assertRedirect('/verifikasi-outlet');
        $this->post('/verifikasi-outlet', ['kode' => $kode])->assertRedirect('/login');
        $this->assertGuest();
        $this->assertSame(0, KodeDarurat::first()->dipakai);

        $this->travel(16)->minutes();
        $this->login($kasir, '36.80.1.2');
        $this->post('/verifikasi-outlet', ['kode' => $kode])->assertRedirect('/kasir/dashboard');
    }

    public function test_laporan_ditandatangani_ed25519_dan_bisa_diverifikasi_dengan_kunci_publik(): void
    {
        $ttd = TandaTanganDigital::laporan('2026-11-01', '2026-11-04', 1250000, 300000, 950000);
        parse_str((string) parse_url($ttd['url'], PHP_URL_QUERY), $q);
        $this->assertSame(SODIUM_CRYPTO_SIGN_BYTES, strlen(base64_decode(strtr($q['sig'], '-_', '+/'))));

        $this->get($ttd['url'])->assertOk()->assertSee('ASLI')->assertSee('Ed25519');

        // verifikasi mandiri oleh pihak luar: hanya dengan kunci publik
        $publik = $this->get('/cek-keaslian/kunci-publik')->assertOk()->assertJsonPath('algoritma', 'Ed25519 (RFC 8032)')->json('kunci_publik');
        $isi = TandaTanganDigital::isiLaporan($q);
        $this->assertTrue(sodium_crypto_sign_verify_detached(base64_decode(strtr($q['sig'], '-_', '+/')), $isi, base64_decode($publik)));

        // tanda tangan dirusak → tidak valid walau kode HMAC benar
        $rusak = str_replace('sig='.$q['sig'], 'sig='.strrev($q['sig']), $ttd['url']);
        $this->get($rusak)->assertSee('TIDAK VALID');

        // laporan lama (tanpa Ed25519) tetap bisa dicek
        $lama = preg_replace('/&sig=[^&]+/', '', $ttd['url']);
        $this->get($lama)->assertSee('ASLI');
    }

    public function test_dokumen_lama_tetap_valid_setelah_app_key_diganti(): void
    {
        $ttd = TandaTanganDigital::laporan('2026-11-01', '2026-11-04', 1250000, 300000, 950000);
        $lama = config('app.key');
        config(['app.key' => 'base64:'.base64_encode(random_bytes(32)), 'app.previous_keys' => [$lama]]);

        $this->get($ttd['url'])->assertSee('ASLI');
        $this->assertCount(1, $this->get('/cek-keaslian/kunci-publik')->json('kunci_publik_lama'));

        config(['app.previous_keys' => []]);
        $this->get($ttd['url'])->assertSee('TIDAK VALID');
    }

    public function test_ringkasan_keamanan_tampil_dan_dikirim_ke_owner(): void
    {
        $owner = User::factory()->create(['role' => User::ROLE_OWNER]);
        $kasir = User::factory()->create(['role' => User::ROLE_KASIR, 'nama' => 'Bima Kasir']);
        $this->artisan('resto:ringkasan-keamanan')->assertSuccessful()->expectsOutputToContain('Email tidak dikirim');
        Notification::assertNothingSent();

        for ($i = 0; $i < 5; $i++) {
            $this->withServerVariables(['REMOTE_ADDR' => '36.80.1.2'])->post('/login', ['email' => $kasir->email, 'password' => 'salah123']);
        }

        $this->actingAs($owner)->get('/owner/aktivitas')->assertOk()
            ->assertSee('Peringatan Keamanan 24 Jam Terakhir')->assertSee('Password salah: 5×')
            ->assertSee('Percobaan gagal beruntun')->assertSee('36.80.1.2');
        $this->get('/owner/aktivitas?peringatan=password')->assertOk()->assertSee('Password salah ✕');

        $this->artisan('resto:ringkasan-keamanan')->assertSuccessful();
        Notification::assertSentTo($owner, RingkasanKeamananNotification::class, fn ($n) => $n->ringkasan['total'] === 5
            && str_contains($n->toMail($owner)->render(), 'Bima Kasir'));
    }

    public function test_data_pribadi_lama_dihapus_tanpa_menghapus_angka_penjualan(): void
    {
        $zona = ZonaPengiriman::create(['nama_zona' => 'Dekat', 'radius_km' => 5, 'tarif_per_km' => 2000]);
        $lama = Pesanan::create(['jenis_layanan' => 'delivery', 'waktu_pesan' => now()->subDays(400), 'subtotal' => 50000, 'total' => 55000,
            'status' => Pesanan::STATUS_SELESAI, 'nama_pemesan' => 'Rina', 'no_hp_pemesan' => '081234567890', 'email_pemesan' => 'rina@contoh.id']);
        Storage::fake(KurirService::DISK);
        Storage::disk(KurirService::DISK)->put('bukti-kirim/lama.jpg', 'foto');
        $lama->pengiriman()->create(['id_zona' => $zona->id, 'alamat' => 'Jl. Melati 7', 'jarak_km' => 3, 'lat' => 3.59, 'lng' => 98.64,
            'nama_penerima' => 'Rina', 'no_hp_penerima' => '081234567890', 'patokan' => 'pagar hijau', 'bukti_kirim' => 'bukti-kirim/lama.jpg']);
        $baru = Pesanan::create(['jenis_layanan' => 'take_away', 'waktu_pesan' => now()->subDays(10), 'subtotal' => 1, 'total' => 1,
            'status' => Pesanan::STATUS_SELESAI, 'nama_pemesan' => 'Budi']);
        $belumSelesai = Pesanan::create(['jenis_layanan' => 'take_away', 'waktu_pesan' => now()->subDays(400), 'subtotal' => 1, 'total' => 1,
            'status' => Pesanan::STATUS_MENUNGGU, 'nama_pemesan' => 'Citra']);

        $staf = User::factory()->create(['role' => User::ROLE_KASIR]);
        $staf->wajah()->create(['ciri' => [0.1, 0.2], 'jumlah_sampel' => 6, 'persetujuan_at' => now()]);
        WajahStaf::query()->update(['created_at' => now()->subDays(200)]);
        $pelangganLama = User::factory()->create(['role' => User::ROLE_PELANGGAN]);
        $pelangganLama->forceFill(['created_at' => now()->subDays(800)])->saveQuietly();
        $pelangganAktif = User::factory()->create(['role' => User::ROLE_PELANGGAN]);
        $pelangganAktif->forceFill(['created_at' => now()->subDays(800)])->saveQuietly();
        $baru->update(['id_pelanggan' => $pelangganAktif->id]);

        $this->artisan('resto:bersihkan-data-pribadi', ['--coba' => true])->expectsOutputToContain('1 pesanan lama dianonimkan, 1 data wajah')->assertSuccessful();
        $this->assertSame('Rina', $lama->fresh()->nama_pemesan);

        $this->artisan('resto:bersihkan-data-pribadi')->assertSuccessful();

        $lama->refresh();
        $this->assertNull($lama->nama_pemesan);
        $this->assertNull($lama->no_hp_pemesan);
        $this->assertNull($lama->email_pemesan);
        $this->assertNotNull($lama->data_pribadi_dihapus_at);
        $this->assertSame(55000.0, (float) $lama->total); // angka penjualan tetap
        $kirim = $lama->pengiriman;
        $this->assertSame(BersihkanDataPribadi::ALAMAT_DIHAPUS, $kirim->alamat);
        $this->assertNull($kirim->no_hp_penerima);
        $this->assertNull($kirim->lat);
        $this->assertNull($kirim->bukti_kirim);
        Storage::disk(KurirService::DISK)->assertMissing('bukti-kirim/lama.jpg');

        $this->assertSame('Budi', $baru->fresh()->nama_pemesan);
        $this->assertSame('Citra', $belumSelesai->fresh()->nama_pemesan);
        $this->assertNull($staf->fresh()->wajah);
        $this->assertModelMissing($pelangganLama);
        $this->assertModelExists($pelangganAktif);
        $this->assertDatabaseHas('log_aktivitas', ['aksi' => 'data.retensi']);
    }

    public function test_wajah_langsung_dihapus_saat_akun_bukan_staf_lagi(): void
    {
        $staf = User::factory()->create(['role' => User::ROLE_KASIR]);
        $staf->wajah()->create(['ciri' => [0.1, 0.2], 'jumlah_sampel' => 6, 'persetujuan_at' => now()]);

        $staf->update(['role' => User::ROLE_PELANGGAN]);

        $this->assertNull($staf->fresh()->wajah);
        $this->assertDatabaseHas('log_aktivitas', ['aksi' => 'wajah.hapus']);
    }
}
