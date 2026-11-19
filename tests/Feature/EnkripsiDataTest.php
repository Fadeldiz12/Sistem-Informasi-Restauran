<?php

namespace Tests\Feature;

use App\Models\LogAktivitas;
use App\Models\Pengiriman;
use App\Models\Pesanan;
use App\Models\User;
use App\Support\BackupTerenkripsi;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class EnkripsiDataTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        File::deleteDirectory(BackupTerenkripsi::folder());
        parent::tearDown();
    }

    public function test_nomor_hp_dan_alamat_tersimpan_terenkripsi(): void
    {
        $u = User::factory()->create(['nama' => 'Rina Amalia', 'no_hp' => '081234567890', 'alamat' => 'Jl. Melati No. 7, Medan']);

        $mentah = DB::table('users')->where('id', $u->id)->first();
        $this->assertStringNotContainsString('081234567890', $mentah->no_hp);
        $this->assertStringNotContainsString('Melati', $mentah->alamat);
        $this->assertSame('081234567890', $u->fresh()->no_hp);
        $this->assertSame('Jl. Melati No. 7, Medan', $u->fresh()->alamat);
        $this->assertSame(User::hashHp('0812-3456-7890'), $mentah->no_hp_hash);

        foreach ([[new Pesanan, 'no_hp_pemesan'], [new Pengiriman, 'alamat'], [new Pengiriman, 'no_hp_penerima'], [new Pengiriman, 'patokan']] as [$model, $kolom]) {
            $this->assertSame('encrypted', $model->getCasts()[$kolom], class_basename($model).".{$kolom} harus terenkripsi");
        }
    }

    public function test_admin_tetap_bisa_mencari_dengan_nomor_hp_utuh_dan_log_tidak_memuat_nomor(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $kurir = User::factory()->create(['role' => User::ROLE_KURIR, 'nama' => 'Andi Pratama', 'no_hp' => '081298765432']);
        User::factory()->create(['nama' => 'Orang Lain', 'no_hp' => '085200000000']);

        $this->actingAs($admin)->get('/admin/pengguna?q=0812-9876-5432')->assertSee('Andi Pratama')->assertDontSee('Orang Lain');
        $this->actingAs($admin)->get('/admin/pengguna?q=8765')->assertOk()->assertDontSee('Andi Pratama');

        $this->actingAs($admin)->put("/admin/pengguna/{$kurir->getRouteKey()}", [
            'nama' => 'Andi Pratama', 'email' => $kurir->email, 'no_hp' => '081311112222', 'role' => User::ROLE_KURIR,
        ])->assertSessionHasNoErrors();
        $log = LogAktivitas::where('aksi', 'pengguna.ubah')->sole();
        $this->assertStringContainsString('no hp diganti', $log->keterangan);
        $this->assertStringNotContainsString('081311112222', $log->keterangan.json_encode($log->data));
        $this->assertStringNotContainsString('081298765432', $log->keterangan.json_encode($log->data));
    }

    public function test_backup_terenkripsi_bisa_dipulihkan(): void
    {
        config(['resto.backup.kunci' => 'kunci-backup-terpisah-123456']);
        $u = User::factory()->create(['nama' => 'Rina Amalia', 'no_hp' => '081234567890']);

        $this->artisan('resto:backup')->assertSuccessful();
        $file = File::glob(BackupTerenkripsi::folder().'/*.backup')[0];
        $isi = File::get($file);
        $this->assertStringNotContainsString('Rina Amalia', $isi);
        $this->assertStringNotContainsString($u->email, $isi);

        $u->delete();
        User::factory()->create(['nama' => 'Data Baru']);
        $this->artisan('resto:pulihkan', ['file' => $file, '--force' => true])->assertSuccessful();

        $this->assertSame('081234567890', User::firstWhere('nama', 'Rina Amalia')?->no_hp);
        $this->assertNull(User::firstWhere('nama', 'Data Baru'));

        // backup dengan BACKUP_KEY tidak bisa dibuka dengan kunci lain, termasuk APP_KEY
        config(['resto.backup.kunci' => 'kunci-lain-yang-salah-123']);
        $this->artisan('resto:pulihkan', ['file' => $file, '--force' => true])->assertFailed()
            ->expectsOutputToContain('kuncinya berbeda');
    }

    public function test_backup_lama_dari_app_key_tetap_terbuka_setelah_backup_key_diisi(): void
    {
        User::factory()->create(['nama' => 'Rina Amalia']);
        $this->artisan('resto:backup')->assertSuccessful(); // tanpa BACKUP_KEY: memakai APP_KEY
        $file = File::glob(BackupTerenkripsi::folder().'/*.backup')[0];

        config(['resto.backup.kunci' => 'kunci-backup-terpisah-123456']);
        $this->assertSame('Rina Amalia', collect(BackupTerenkripsi::baca($file)['tabel']['users'])->firstWhere('nama', 'Rina Amalia')['nama']);

        config(['resto.backup.kunci' => 'pendek']);
        $this->artisan('resto:backup')->assertFailed();
    }

    public function test_rotasi_app_key_tidak_merusak_data_lama(): void
    {
        $u = User::factory()->create(['nama' => 'Rina Amalia', 'no_hp' => '081234567890', 'alamat' => 'Jl. Melati 7']);
        $kodeLama = $u->getRouteKey();
        $kunciLama = config('app.key');

        $this->gantiAppKey('base64:'.base64_encode(random_bytes(32)), [$kunciLama]);

        // sebelum rotasi: data, pencarian HP, dan tautan lama tetap jalan memakai kunci lama
        $this->assertSame('081234567890', User::find($u->id)->no_hp);
        $this->assertContains(DB::table('users')->where('id', $u->id)->value('no_hp_hash'), User::hashHpSemuaKunci('081234567890'));
        $this->assertTrue(User::first()->resolveRouteBinding($kodeLama)?->is($u));

        $this->artisan('resto:rotasi-kunci', ['--force' => true])->assertSuccessful();

        // sesudah rotasi: data bisa dibaca tanpa kunci lama
        $this->gantiAppKey(config('app.key'), []);
        $baru = User::find($u->id);
        $this->assertSame('081234567890', $baru->no_hp);
        $this->assertSame('Jl. Melati 7', $baru->alamat);
        $this->assertSame(User::hashHp('081234567890'), DB::table('users')->where('id', $u->id)->value('no_hp_hash'));
    }

    public function test_backup_disalin_ke_lokasi_lain_dan_uji_pemulihan_tidak_menyentuh_database_utama(): void
    {
        $salinan = storage_path('framework/testing/salinan-backup');
        config(['resto.backup.salinan_folder' => $salinan]);
        User::factory()->create(['nama' => 'Rina Amalia', 'no_hp' => '081234567890']);

        $this->artisan('resto:backup')->assertSuccessful()->expectsOutputToContain('Salinan disimpan');
        $this->assertCount(1, File::glob($salinan.'/resto-*.backup'));

        User::factory()->create(['nama' => 'Data Sesudah Backup']);
        $this->artisan('resto:uji-backup')->assertSuccessful()->expectsOutputToContain('berhasil dipulihkan');
        $this->assertTrue(BackupTerenkripsi::hasilUjiTerakhir()['ok']);
        $this->assertNotNull(User::firstWhere('nama', 'Data Sesudah Backup')); // database utama tetap utuh
        $this->assertSame(2, User::count());

        // file rusak → uji gagal dan tercatat
        File::put(BackupTerenkripsi::terbaru(), "RESTOFLOW-BACKUP-1\nrusak");
        $this->artisan('resto:uji-backup')->assertFailed();
        $this->assertFalse(BackupTerenkripsi::hasilUjiTerakhir()['ok']);

        File::deleteDirectory($salinan);
    }

    public function test_data_contoh_dari_seeder_tersimpan_terenkripsi_dan_bisa_dicari(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertNotEmpty(Pengiriman::pluck('alamat')->filter()); // terbaca tanpa DecryptException
        $this->assertStringNotContainsString('Jl. Contoh', (string) DB::table('pengiriman')->value('alamat'));
        $this->assertSame(0, User::whereNotNull('no_hp')->whereNull('no_hp_hash')->count());
        $admin = User::firstWhere('role', User::ROLE_ADMIN);
        $this->actingAs($admin)->get('/admin/pengguna?q=085266123344')->assertSee('Bima Saputra');
    }

    public function test_migrasi_perbaikan_mengenkripsi_data_lama_yang_masih_teks_biasa(): void
    {
        $u = User::factory()->create(['no_hp' => '081234567890']);
        DB::table('users')->where('id', $u->id)->update(['alamat' => 'Jl. Melati 7', 'no_hp_hash' => null]);

        (require database_path('migrations/2026_11_19_150227_perbaiki_data_pribadi_belum_terenkripsi.php'))->up();
        (require database_path('migrations/2026_11_19_150227_perbaiki_data_pribadi_belum_terenkripsi.php'))->up(); // aman dijalankan ulang

        $this->assertSame('Jl. Melati 7', $u->fresh()->alamat);
        $this->assertStringNotContainsString('Melati', DB::table('users')->where('id', $u->id)->value('alamat'));
        $this->assertSame(User::hashHp('081234567890'), DB::table('users')->where('id', $u->id)->value('no_hp_hash'));
    }

    /**
     * @param  list<string>  $lama
     */
    private function gantiAppKey(string $baru, array $lama): void
    {
        config(['app.key' => $baru, 'app.previous_keys' => $lama]);
        $this->app->forgetInstance('encrypter');
        Crypt::clearResolvedInstance('encrypter');
    }
}
