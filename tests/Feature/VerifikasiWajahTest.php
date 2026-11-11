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

    /** Sampel kamera dari orang yang sama: sedikit berbeda tiap frame, lolos cek kualitas. */
    private function sampel(array $orang, int $n = 4): array
    {
        return array_map(fn () => [
            'descriptor' => array_map(fn ($v) => round($v + mt_rand(-10, 10) / 1000, 6), $orang),
            'metrics' => ['score' => 0.97, 'yaw' => 2.1, 'pitch' => 4.0, 'roll' => -1.2, 'blink' => 0.08, 'jaw' => 0.02, 'width' => 0.41, 'cover' => 6.3, 'in_frame' => true],
        ], range(1, $n));
    }

    /**
     * Hasil FaceGuard (face-guard.js) yang lolos untuk tantangan di sesi; $ubah menimpa bagian tertentu.
     *
     * @param  array<string, mixed>  $ubah
     */
    private function bukti(string $untuk, array $orang, array $ubah = []): string
    {
        $t = session("tantangan_wajah.{$untuk}");
        $nilai = ['blink' => 0.82, 'turn_left' => 27.5, 'turn_right' => 26.1, 'open_mouth' => 0.63];

        return json_encode(array_merge([
            'nonce' => $t['nonce'],
            'samples' => $this->sampel($orang, $t['samples']['before'] + $t['samples']['after']),
            'eye_baseline' => 0.07,
            'steps' => array_map(fn ($s) => ['type' => $s, 'value' => $nilai[$s], 'onset_ms' => 640, 'ms' => 1310], $t['steps']),
            'flash' => $t['flash'] ? ['colors' => $t['flash'], 'corr' => 0.86, 'amp' => 0.0042, 'frames' => 95] : null,
            'camera' => 'Integrated Webcam (0bda:5634)',
            'duration_ms' => 1800,
        ], $ubah));
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

        $this->post("/owner/wajah/{$this->kasir->getRouteKey()}", ['bukti' => $this->bukti('daftar', $bima)])
            ->assertSessionHasErrors('setuju');

        $this->get("/owner/wajah/{$this->kasir->getRouteKey()}");
        $this->post("/owner/wajah/{$this->kasir->getRouteKey()}", ['setuju' => 1, 'bukti' => $this->bukti('daftar', $bima)])
            ->assertRedirect('/owner/keamanan')->assertSessionHas('status');

        $w = $this->kasir->fresh()->wajah;
        $this->assertSame(6, $w->jumlah_sampel); // 3 sampel sebelum + 3 sesudah gerakan
        $this->assertLessThan(0.05, app(WajahService::class)->jarak($bima, $w->ciri));
        $mentah = DB::table('wajah_staf')->value('ciri');
        $this->assertStringNotContainsString((string) $w->ciri[0], $mentah); // tersimpan terenkripsi
        $this->assertDatabaseHas('log_aktivitas', ['aksi' => 'wajah.daftar', 'id_user' => $this->owner->id]);

        // satu wajah hanya untuk satu akun: wajah Bima tidak bisa didaftarkan ke akun lain (role apa pun)
        $akunLain = User::factory()->create(['role' => User::ROLE_ADMIN, 'nama' => 'Bima Admin']);
        $this->get("/owner/wajah/{$akunLain->getRouteKey()}");
        $this->post("/owner/wajah/{$akunLain->getRouteKey()}", ['setuju' => 1, 'bukti' => $this->bukti('daftar', $bima)])
            ->assertSessionHasErrors('wajah');
        $this->assertStringContainsString('sudah terdaftar untuk akun Bima Kasir (Kasir)', session('errors')->first('wajah'));
        $this->assertNull($akunLain->fresh()->wajah);
        $this->assertDatabaseHas('log_aktivitas', ['aksi' => 'wajah.ditolak']);

        // daftar ulang wajah sendiri tetap boleh; wajah orang lain ke akun lain juga boleh
        $this->get("/owner/wajah/{$this->kasir->getRouteKey()}");
        $this->post("/owner/wajah/{$this->kasir->getRouteKey()}", ['setuju' => 1, 'bukti' => $this->bukti('daftar', $bima)])->assertRedirect('/owner/keamanan');
        $this->get("/owner/wajah/{$akunLain->getRouteKey()}");
        $this->post("/owner/wajah/{$akunLain->getRouteKey()}", ['setuju' => 1, 'bukti' => $this->bukti('daftar', $this->orang())])->assertRedirect('/owner/keamanan');

        // setelah wajah di akun lama dihapus, orang itu bisa didaftarkan di akun barunya
        $this->delete("/owner/wajah/{$akunLain->getRouteKey()}");
        $this->delete("/owner/wajah/{$this->kasir->getRouteKey()}");
        $this->get("/owner/wajah/{$akunLain->getRouteKey()}");
        $this->post("/owner/wajah/{$akunLain->getRouteKey()}", ['setuju' => 1, 'bukti' => $this->bukti('daftar', $bima)])->assertRedirect('/owner/keamanan');
        $this->delete("/owner/wajah/{$akunLain->getRouteKey()}");
        $this->get("/owner/wajah/{$this->kasir->getRouteKey()}");
        $this->post("/owner/wajah/{$this->kasir->getRouteKey()}", ['setuju' => 1, 'bukti' => $this->bukti('daftar', $bima)]);

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
        $this->post('/verifikasi-wajah', ['bukti' => $this->bukti('login', $bima)])->assertRedirect('/kasir/dashboard');
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
            $this->from('/verifikasi-wajah')->post('/verifikasi-wajah', ['bukti' => $this->bukti('login', $orangLain)])
                ->assertSessionHasErrors('wajah');
            $this->get('/verifikasi-wajah');
        }
        $this->post('/verifikasi-wajah', ['bukti' => $this->bukti('login', $orangLain)])->assertRedirect('/login');

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
        $langkah = session('tantangan_wajah.login.steps');
        $terbalik = array_map(fn ($a) => ['type' => $a, 'value' => 30, 'onset_ms' => 700, 'ms' => 1200], array_reverse($langkah));
        $this->from('/verifikasi-wajah')->post('/verifikasi-wajah', ['bukti' => $this->bukti('login', $bima, ['steps' => $terbalik])])->assertSessionHasErrors('wajah');
        $this->assertDatabaseHas('log_aktivitas', ['aksi' => 'akun.spoofing', 'id_user' => $this->kasir->id]);

        // tantangan yang sama dipakai ulang
        $this->get('/verifikasi-wajah');
        $bukti = $this->bukti('login', $bima);
        $this->post('/verifikasi-wajah', ['bukti' => $bukti])->assertRedirect('/kasir/dashboard');
        $this->post('/logout');
        $this->sampaiHalamanWajah($this->kasir);
        $this->from('/verifikasi-wajah')->post('/verifikasi-wajah', ['bukti' => $bukti])
            ->assertSessionHasErrors(['wajah' => 'Tantangan sudah tidak berlaku. Ulangi dari awal. Sisa 2 kali percobaan.']);

        // frame yang persis sama (foto diputar ulang)
        $this->get('/verifikasi-wajah');
        $this->from('/verifikasi-wajah')->post('/verifikasi-wajah', ['bukti' => $this->bukti('login', $bima, ['samples' => array_map(fn ($x) => ['descriptor' => $bima] + $x, $this->sampel($bima))])])->assertSessionHasErrors('wajah');
        $this->assertStringContainsString('foto atau rekaman', session('errors')->first('wajah'));

        // waktu habis
        $this->get('/verifikasi-wajah');
        $bukti = $this->bukti('login', $bima);
        $this->travel(121)->seconds(); // tantangan berlaku 120 detik
        $this->post('/verifikasi-wajah', ['bukti' => $bukti])->assertRedirect('/login'); // dikunci (3 kali gagal)
        $this->assertTrue($this->kasir->fresh()->terkunci());
    }

    public function test_anti_spoofing_kamera_virtual_layar_reaksi_dan_masker(): void
    {
        $bima = $this->orang();
        $this->daftarkan($this->kasir, $bima);
        $this->wajibWajah();
        $this->sampaiHalamanWajah($this->kasir);

        $kasus = [
            'kamera virtual' => [['camera' => 'OBS Virtual Camera'], 'Kamera virtual terdeteksi'],
            'wajah dari layar HP' => [fn ($t) => ['flash' => ['colors' => $t['flash'], 'corr' => 0.12, 'amp' => 0.0004, 'frames' => 90]], 'Pantulan cahaya layar'],
            'warna kedipan lain' => [fn ($t) => ['flash' => ['colors' => array_reverse($t['flash']), 'corr' => 0.9, 'amp' => 0.004, 'frames' => 90]], 'pantulan layar tidak lengkap'],
            'reaksi terlalu cepat' => [fn ($t) => ['steps' => array_map(fn ($x) => ['type' => $x, 'value' => 30, 'onset_ms' => 40, 'ms' => 200], $t['steps'])], 'tidak wajar'],
            'durasi mustahil' => [['duration_ms' => 400], 'Durasi verifikasi tidak wajar'],
            'wajah bermasker' => [fn ($t) => ['samples' => array_map(fn ($x) => ['metrics' => ['cover' => 22.4] + $x['metrics']] + $x, $this->sampel($bima, 4))], 'tertutup'],
            'ganti orang di tengah' => [fn ($t) => ['samples' => array_merge($this->sampel($bima, 2), $this->sampel($this->orang(), 2))], 'Wajah berubah di tengah verifikasi'],
        ];
        foreach ($kasus as $nama => [$ubah, $pesan]) {
            User::whereKey($this->kasir->id)->update(['gagal_wajah' => 0]);
            $this->get('/verifikasi-wajah');
            $ubah = is_callable($ubah) ? $ubah(session('tantangan_wajah.login')) : $ubah;
            $this->from('/verifikasi-wajah')->post('/verifikasi-wajah', ['bukti' => $this->bukti('login', $bima, $ubah)])->assertSessionHasErrors('wajah');
            $this->assertStringContainsString($pesan, session('errors')->first('wajah'), $nama);
            $this->assertGuest();
        }
        $this->assertGreaterThanOrEqual(5, LogAktivitas::where('aksi', 'akun.spoofing')->count());

        // tantangan berisi kedip + 2 gerakan acak dan 6 warna (minimal 3 warna berbeda, tidak berurutan sama)
        $this->postJson('/wajah/tantangan')->assertOk()->assertJson(fn ($j) => $j->hasAll(['nonce', 'steps', 'flash', 'samples'])->etc());
        $t = session('tantangan_wajah.login');
        $this->assertCount(3, $t['steps']);
        $this->assertContains('blink', $t['steps']);
        $this->assertCount(6, $t['flash']);
        $this->assertCount(3, array_unique($t['flash']));
        for ($i = 1; $i < 6; $i++) {
            $this->assertNotSame($t['flash'][$i - 1], $t['flash'][$i]);
        }
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

        $this->from('/konfirmasi-identitas')->post('/konfirmasi-identitas', ['password' => 'password', 'bukti' => $this->bukti('konfirmasi', $this->orang())])
            ->assertSessionHasErrors('password');
        $this->get('/konfirmasi-identitas');
        $this->from('/konfirmasi-identitas')->post('/konfirmasi-identitas', ['password' => 'salah', 'bukti' => $this->bukti('konfirmasi', $owner)])
            ->assertSessionHasErrors(['password' => 'Password salah.']);
        $this->get('/konfirmasi-identitas');
        $this->post('/konfirmasi-identitas', ['password' => 'password', 'bukti' => $this->bukti('konfirmasi', $owner)])
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
