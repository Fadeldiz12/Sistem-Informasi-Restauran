<?php

namespace Tests\Feature;

use App\Models\LogAktivitas;
use App\Models\User;
use App\Support\RantaiLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use LogicException;
use Tests\TestCase;

class RantaiLogTest extends TestCase
{
    use RefreshDatabase;

    private function isiLog(): void
    {
        $owner = User::factory()->create(['role' => User::ROLE_OWNER, 'nama' => 'Reza']);
        LogAktivitas::catat('akun.login', 'Reza masuk sebagai Owner', $owner, ['via' => 'ip', 'jarak' => 1.0, 'b' => ['z' => 1, 'a' => 2]], $owner);
        LogAktivitas::catat('menu.ubah', 'Harga Pizza Margherita diubah dari Rp 85.000 jadi Rp 95.000', pelaku: $owner);
        LogAktivitas::catat('akun.logout', 'Reza keluar', pelaku: $owner);
    }

    public function test_rantai_utuh_dan_tampil_di_log_aktivitas(): void
    {
        $this->isiLog();
        $hasil = RantaiLog::periksa();
        $this->assertTrue($hasil['utuh'], implode(' ', $hasil['masalah']));
        $this->assertGreaterThanOrEqual(3, $hasil['jumlah']);
        $this->artisan('resto:cek-log')->assertSuccessful()->expectsOutputToContain('Rantai log utuh');

        $owner = User::firstWhere('role', User::ROLE_OWNER);
        $this->actingAs($owner)->get('/owner/aktivitas')->assertSee('Rantai hash utuh');
    }

    public function test_log_tidak_bisa_diubah_atau_dihapus_lewat_aplikasi(): void
    {
        $this->isiLog();
        $log = LogAktivitas::firstWhere('aksi', 'menu.ubah');

        $this->assertThrows(fn () => $log->update(['keterangan' => 'tidak ada perubahan']), LogicException::class);
        $this->assertThrows(fn () => $log->delete(), LogicException::class);
        $this->assertModelExists($log);
    }

    public function test_perubahan_langsung_di_database_ketahuan(): void
    {
        $this->isiLog();
        $id = LogAktivitas::firstWhere('aksi', 'menu.ubah')->id;

        // isi diubah lewat phpMyAdmin
        DB::table('log_aktivitas')->where('id', $id)->update(['keterangan' => 'Harga Pizza Margherita tidak diubah']);
        $hasil = RantaiLog::periksa();
        $this->assertFalse($hasil['utuh']);
        $this->assertStringContainsString("#{$id} tidak cocok", $hasil['masalah'][0]);

        Cache::forget(RantaiLog::CACHE);
        $owner = User::firstWhere('role', User::ROLE_OWNER);
        $this->actingAs($owner)->get('/owner/aktivitas')->assertSee('Rantai hash RUSAK');
        $this->artisan('resto:cek-log')->assertFailed();
    }

    public function test_catatan_tengah_dihapus_ketahuan(): void
    {
        $this->isiLog();
        DB::table('log_aktivitas')->where('id', LogAktivitas::firstWhere('aksi', 'menu.ubah')->id)->delete();

        $this->assertFalse(RantaiLog::periksa()['utuh']);
    }

    public function test_catatan_terakhir_dihapus_ketahuan(): void
    {
        $this->isiLog();
        DB::table('log_aktivitas')->where('id', LogAktivitas::max('id'))->delete();

        $this->assertStringContainsString('dihapus', implode(' ', RantaiLog::periksa()['masalah']));
    }

    public function test_catatan_disisipkan_langsung_ketahuan(): void
    {
        $this->isiLog();
        DB::table('log_aktivitas')->insert(['aksi' => 'pengguna.hapus', 'keterangan' => 'palsu', 'created_at' => now()]);

        $this->assertStringContainsString('tidak punya hash', implode(' ', RantaiLog::periksa()['masalah']));
    }

    public function test_rantai_tetap_utuh_setelah_akun_pelaku_dihapus_dan_app_key_diganti(): void
    {
        $this->isiLog();
        $kasir = User::factory()->create(['role' => User::ROLE_KASIR]);
        LogAktivitas::catat('akun.login', 'Kasir masuk', pelaku: $kasir);
        $kasir->delete(); // id_user di log dikosongkan database

        $lama = config('app.key');
        config(['app.key' => 'base64:'.base64_encode(random_bytes(32)), 'app.previous_keys' => [$lama]]);
        LogAktivitas::catat('akun.login', 'Catatan setelah APP_KEY diganti');

        $hasil = RantaiLog::periksa();
        $this->assertTrue($hasil['utuh'], implode(' ', $hasil['masalah']));
    }

    public function test_json_yang_disusun_ulang_mysql_8_tetap_dianggap_utuh(): void
    {
        $this->isiLog();
        $log = LogAktivitas::firstWhere('aksi', 'akun.login');
        // MySQL 8 menyimpan kolom JSON dengan urutan kunci & spasi sendiri; isinya sama
        DB::table('log_aktivitas')->where('id', $log->id)->update(['data' => '{"b": {"a": 2, "z": 1}, "jarak": 1, "via": "ip"}']);

        $this->assertTrue(RantaiLog::periksa()['utuh']);

        DB::table('log_aktivitas')->where('id', $log->id)->update(['data' => '{"b": {"a": 2, "z": 1}, "jarak": 1, "via": "gps"}']);
        $this->assertFalse(RantaiLog::periksa()['utuh']); // isi yang benar-benar diubah tetap ketahuan
    }
}
