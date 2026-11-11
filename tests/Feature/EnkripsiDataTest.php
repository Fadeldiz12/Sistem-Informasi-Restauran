<?php

namespace Tests\Feature;

use App\Models\LogAktivitas;
use App\Models\Pengiriman;
use App\Models\Pesanan;
use App\Models\User;
use App\Support\BackupTerenkripsi;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

        // kunci lain tidak bisa membuka
        config(['resto.backup.kunci' => 'kunci-lain-yang-salah-123']);
        $this->artisan('resto:pulihkan', ['file' => $file, '--force' => true])->assertFailed()
            ->expectsOutputToContain('kuncinya berbeda');
    }
}
