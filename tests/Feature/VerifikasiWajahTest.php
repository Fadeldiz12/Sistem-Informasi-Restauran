<?php

namespace Tests\Feature;

use App\Models\LogAktivitas;
use App\Models\PengaturanSistem;
use App\Models\User;
use App\Models\WajahStaf;
use App\Notifications\AkunTerkunciNotification;
use App\Services\WajahService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class VerifikasiWajahTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private User $admin;

    private User $kasir;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
        mt_srand(7);
        $this->owner = User::factory()->create(['role' => User::ROLE_OWNER, 'nama' => 'Owner Resto']);
        $this->admin = User::factory()->create(['role' => User::ROLE_ADMIN, 'nama' => 'Admin Resto', 'email' => 'admin@resto.id']);
        $this->kasir = User::factory()->create(['role' => User::ROLE_KASIR, 'nama' => 'Bima Kasir', 'email' => 'bima@resto.id']);
    }

    /** Ciri wajah acak (128 angka) sebagai "orang". */
    private function orang(): array
    {
        return array_map(fn () => mt_rand(-200, 200) / 1000, range(1, 128));
    }

    /** Sampel kamera dari orang yang sama: sedikit berbeda tiap frame. */
    private function sampel(array $orang, int $n = 3): array
    {
        return array_map(fn () => array_map(fn ($v) => round($v + mt_rand(-10, 10) / 1000, 6), $orang), range(1, $n));
    }

    /** Bukti liveness yang lolos untuk tantangan di sesi. */
    private function bukti(string $untuk, array $ciri, ?array $ubahLangkah = null): string
    {
        $t = session("tantangan_wajah.{$untuk}");
        $nilai = ['kedip' => 0.55, 'buka_mulut' => 0.4, 'noleh_kiri' => 0.3, 'noleh_kanan' => -0.3];
        $langkah = $ubahLangkah ?? array_map(fn ($a) => ['aksi' => $a, 'durasi_ms' => 1400, 'nilai' => $nilai[$a]], $t['aksi']);

        return json_encode(['nonce' => $t['nonce'], 'langkah' => $langkah, 'ciri' => $ciri]);
    }

    private function daftarkan(User $user, array $orang): void
    {
        WajahStaf::create(['id_user' => $user->id, 'ciri' => $orang, 'jumlah_sampel' => 5, 'id_pendaftar' => $this->owner->id, 'persetujuan_at' => now()]);
    }

    private function wajibWajah(): void
    {
        PengaturanSistem::aktif()->update(['wajib_wajah' => true]);
    }

    private function sampaiHalamanWajah(User $user): void
    {
        $this->post('/login', ['email' => $user->email, 'password' => 'password'])->assertRedirect('/verifikasi-wajah');
        $this->assertGuest();
        $this->get('/verifikasi-wajah')->assertOk()->assertSee('Verifikasi Wajah')->assertSee('face-api.js', false);
    }

    public function test_owner_mendaftarkan_wajah_staf_dengan_persetujuan(): void
    {
        $bima = $this->orang();
        $this->actingAs($this->owner)->get("/owner/wajah/{$this->kasir->getRouteKey()}")->assertOk()->assertSee('Daftarkan Wajah Bima Kasir');

        $this->post("/owner/wajah/{$this->kasir->getRouteKey()}", ['bukti' => $this->bukti('daftar', $this->sampel($bima, 5))])
            ->assertSessionHasErrors('setuju');

        $this->get("/owner/wajah/{$this->kasir->getRouteKey()}");
        $this->post("/owner/wajah/{$this->kasir->getRouteKey()}", ['setuju' => 1, 'bukti' => $this->bukti('daftar', $this->sampel($bima, 5))])
            ->assertRedirect('/owner/keamanan')->assertSessionHas('status');

        $w = $this->kasir->fresh()->wajah;
        $this->assertSame(5, $w->jumlah_sampel);
        $this->assertLessThan(0.05, app(WajahService::class)->jarak($bima, $w->ciri));
        $mentah = DB::table('wajah_staf')->value('ciri');
        $this->assertStringNotContainsString((string) $w->ciri[0], $mentah); // tersimpan terenkripsi
        $this->assertDatabaseHas('log_aktivitas', ['aksi' => 'wajah.daftar', 'id_user' => $this->owner->id]);

        // satu wajah hanya untuk satu akun: wajah Bima tidak bisa didaftarkan ke akun lain (role apa pun)
        $akunLain = User::factory()->create(['role' => User::ROLE_ADMIN, 'nama' => 'Bima Admin']);
        $this->get("/owner/wajah/{$akunLain->getRouteKey()}");
        $this->post("/owner/wajah/{$akunLain->getRouteKey()}", ['setuju' => 1, 'bukti' => $this->bukti('daftar', $this->sampel($bima, 5))])
            ->assertSessionHasErrors('wajah');
        $this->assertStringContainsString('sudah terdaftar untuk akun Bima Kasir (Kasir)', session('errors')->first('wajah'));
        $this->assertNull($akunLain->fresh()->wajah);
        $this->assertDatabaseHas('log_aktivitas', ['aksi' => 'wajah.ditolak']);

        // daftar ulang wajah sendiri tetap boleh; wajah orang lain ke akun lain juga boleh
        $this->get("/owner/wajah/{$this->kasir->getRouteKey()}");
        $this->post("/owner/wajah/{$this->kasir->getRouteKey()}", ['setuju' => 1, 'bukti' => $this->bukti('daftar', $this->sampel($bima, 5))])->assertRedirect('/owner/keamanan');
        $this->get("/owner/wajah/{$akunLain->getRouteKey()}");
        $this->post("/owner/wajah/{$akunLain->getRouteKey()}", ['setuju' => 1, 'bukti' => $this->bukti('daftar', $this->sampel($this->orang(), 5))])->assertRedirect('/owner/keamanan');

        // setelah wajah di akun lama dihapus, orang itu bisa didaftarkan di akun barunya
        $this->delete("/owner/wajah/{$akunLain->getRouteKey()}");
        $this->delete("/owner/wajah/{$this->kasir->getRouteKey()}");
        $this->get("/owner/wajah/{$akunLain->getRouteKey()}");
        $this->post("/owner/wajah/{$akunLain->getRouteKey()}", ['setuju' => 1, 'bukti' => $this->bukti('daftar', $this->sampel($bima, 5))])->assertRedirect('/owner/keamanan');
        $this->delete("/owner/wajah/{$akunLain->getRouteKey()}");
        $this->get("/owner/wajah/{$this->kasir->getRouteKey()}");
        $this->post("/owner/wajah/{$this->kasir->getRouteKey()}", ['setuju' => 1, 'bukti' => $this->bukti('daftar', $this->sampel($bima, 5))]);

        // hapus saat staf berhenti
        $this->delete("/owner/wajah/{$this->kasir->getRouteKey()}")->assertSessionHas('status');
        $this->assertNull($this->kasir->fresh()->wajah);
    }

    public function test_login_staf_lolos_dengan_wajah_yang_cocok(): void
    {
        $bima = $this->orang();
        $this->daftarkan($this->kasir, $bima);
        $this->wajibWajah();

        $this->sampaiHalamanWajah($this->kasir);
        $this->post('/verifikasi-wajah', ['bukti' => $this->bukti('login', $this->sampel($bima))])->assertRedirect('/kasir/dashboard');
        $this->assertAuthenticatedAs($this->kasir);
        $this->assertNotNull(session('wajah_at'));
    }

    public function test_wajah_lain_ditolak_dan_akun_dikunci_setelah_tiga_kali(): void
    {
        $this->daftarkan($this->kasir, $this->orang());
        $this->wajibWajah();
        $orangLain = $this->orang();

        $this->sampaiHalamanWajah($this->kasir);
        foreach ([1, 2] as $i) {
            $this->from('/verifikasi-wajah')->post('/verifikasi-wajah', ['bukti' => $this->bukti('login', $this->sampel($orangLain))])
                ->assertSessionHasErrors('wajah');
            $this->get('/verifikasi-wajah');
        }
        $this->post('/verifikasi-wajah', ['bukti' => $this->bukti('login', $this->sampel($orangLain))])->assertRedirect('/login');

        $this->assertGuest();
        $this->assertTrue($this->kasir->fresh()->terkunci());
        Notification::assertSentTo($this->owner, AkunTerkunciNotification::class, fn ($n) => str_contains($n->sebab, 'wajah'));
        $this->assertSame(3, LogAktivitas::where('aksi', 'akun.wajah_gagal')->count());
    }

    public function test_liveness_palsu_dan_tantangan_bekas_ditolak(): void
    {
        $bima = $this->orang();
        $this->daftarkan($this->kasir, $bima);
        $this->wajibWajah();
        $this->sampaiHalamanWajah($this->kasir);

        // gerakan tidak sesuai urutan tantangan
        $aksi = session('tantangan_wajah.login.aksi');
        $terbalik = array_map(fn ($a) => ['aksi' => $a, 'durasi_ms' => 900, 'nilai' => 0.5], array_reverse($aksi));
        $this->from('/verifikasi-wajah')->post('/verifikasi-wajah', ['bukti' => $this->bukti('login', $this->sampel($bima), $terbalik)])->assertSessionHasErrors('wajah');
        $this->assertDatabaseHas('log_aktivitas', ['aksi' => 'akun.spoofing', 'id_user' => $this->kasir->id]);

        // tantangan yang sama dipakai ulang
        $this->get('/verifikasi-wajah');
        $bukti = $this->bukti('login', $this->sampel($bima));
        $this->post('/verifikasi-wajah', ['bukti' => $bukti])->assertRedirect('/kasir/dashboard');
        $this->post('/logout');
        $this->sampaiHalamanWajah($this->kasir);
        $this->from('/verifikasi-wajah')->post('/verifikasi-wajah', ['bukti' => $bukti])
            ->assertSessionHasErrors(['wajah' => 'Tantangan sudah tidak berlaku. Ulangi dari awal. Sisa 2 kali percobaan.']);

        // frame yang persis sama (foto diputar ulang)
        $this->get('/verifikasi-wajah');
        $this->from('/verifikasi-wajah')->post('/verifikasi-wajah', ['bukti' => $this->bukti('login', array_fill(0, 3, $bima))])->assertSessionHasErrors('wajah');
        $this->assertStringContainsString('foto atau rekaman', session('errors')->first('wajah'));

        // waktu habis
        $this->get('/verifikasi-wajah');
        $bukti = $this->bukti('login', $this->sampel($bima));
        $this->travel(61)->seconds();
        $this->post('/verifikasi-wajah', ['bukti' => $bukti])->assertRedirect('/login'); // dikunci (3 kali gagal)
        $this->assertTrue($this->kasir->fresh()->terkunci());
    }

    public function test_cadangan_persetujuan_owner_atau_admin_yang_hadir(): void
    {
        $this->daftarkan($this->kasir, $this->orang());
        $this->wajibWajah();
        $kasirLain = User::factory()->create(['role' => User::ROLE_KASIR]);

        $this->sampaiHalamanWajah($this->kasir);
        $this->from('/verifikasi-wajah')->post('/verifikasi-wajah/persetujuan', ['email_penyetuju' => $kasirLain->email, 'password_penyetuju' => 'password'])
            ->assertSessionHasErrors('email_penyetuju');
        $this->from('/verifikasi-wajah')->post('/verifikasi-wajah/persetujuan', ['email_penyetuju' => 'admin@resto.id', 'password_penyetuju' => 'salah'])
            ->assertSessionHasErrors('email_penyetuju');
        $this->assertGuest();

        $this->post('/verifikasi-wajah/persetujuan', ['email_penyetuju' => 'admin@resto.id', 'password_penyetuju' => 'password'])->assertRedirect('/kasir/dashboard');
        $this->assertAuthenticatedAs($this->kasir);
        $this->assertDatabaseHas('log_aktivitas', ['aksi' => 'akun.wajah_disetujui', 'id_user' => $this->admin->id]);
        $this->post('/logout');

        // owner hanya bisa disetujui owner lain
        $this->daftarkan($this->owner, $this->orang());
        $this->sampaiHalamanWajah($this->owner);
        $this->from('/verifikasi-wajah')->post('/verifikasi-wajah/persetujuan', ['email_penyetuju' => 'admin@resto.id', 'password_penyetuju' => 'password'])
            ->assertSessionHasErrors('email_penyetuju');
    }

    public function test_staf_tanpa_data_wajah_dan_pelanggan_tidak_diminta_wajah(): void
    {
        $this->wajibWajah();
        $this->post('/login', ['email' => $this->kasir->email, 'password' => 'password'])->assertRedirect('/kasir/dashboard');
        $this->post('/logout');

        $pelanggan = User::factory()->create(['role' => User::ROLE_PELANGGAN]);
        $this->post('/login', ['email' => $pelanggan->email, 'password' => 'password'])->assertRedirect('/beranda');
    }

    public function test_aksi_sensitif_minta_konfirmasi_wajah_dan_password(): void
    {
        $owner = $this->orang();
        $this->daftarkan($this->owner, $owner);

        // aturan wajah mati: tanpa konfirmasi
        $this->actingAs($this->owner)->get('/owner/laporan/pdf?dari=2026-11-01&sampai=2026-11-04')->assertOk();

        $this->wajibWajah();
        $url = '/owner/laporan/pdf?dari=2026-11-01&sampai=2026-11-04';
        $this->actingAs($this->owner)->get($url)->assertRedirect('/konfirmasi-identitas');
        $this->get('/konfirmasi-identitas')->assertOk()->assertSee('Konfirmasi');

        $this->from('/konfirmasi-identitas')->post('/konfirmasi-identitas', ['password' => 'password', 'bukti' => $this->bukti('konfirmasi', $this->sampel($this->orang()))])
            ->assertSessionHasErrors('password');
        $this->get('/konfirmasi-identitas');
        $this->from('/konfirmasi-identitas')->post('/konfirmasi-identitas', ['password' => 'salah', 'bukti' => $this->bukti('konfirmasi', $this->sampel($owner))])
            ->assertSessionHasErrors(['password' => 'Password salah.']);
        $this->get('/konfirmasi-identitas');
        $this->post('/konfirmasi-identitas', ['password' => 'password', 'bukti' => $this->bukti('konfirmasi', $this->sampel($owner))])
            ->assertRedirect($url);
        $this->get($url)->assertOk();

        // admin tanpa data wajah (sesi lain): cukup password, lalu ulangi aksinya
        $this->flushSession();
        $korban = User::factory()->create(['role' => User::ROLE_DAPUR]);
        $this->actingAs($this->admin)->from('/admin/pengguna')->delete("/admin/pengguna/{$korban->getRouteKey()}")->assertRedirect('/konfirmasi-identitas');
        $this->assertNotNull($korban->fresh());
        $this->post('/konfirmasi-identitas', ['password' => 'password'])->assertRedirect('/admin/pengguna');
        $this->actingAs($this->admin)->delete("/admin/pengguna/{$korban->getRouteKey()}")->assertSessionHas('status');
        $this->assertNull($korban->fresh());
    }
}
