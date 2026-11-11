<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Verifikasi wajah 1:1 staf. Yang disimpan hanya angka ciri wajah (128 angka, terenkripsi), bukan foto.
     */
    public function up(): void
    {
        Schema::create('wajah_staf', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_user')->unique()->constrained('users')->cascadeOnDelete();
            $table->text('ciri'); // JSON 128 angka, terenkripsi
            $table->unsignedTinyInteger('jumlah_sampel');
            $table->foreignId('id_pendaftar')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('persetujuan_at'); // persetujuan tertulis staf dikonfirmasi Owner
            $table->string('catatan_mirip', 200)->nullable(); // mis. mirip akun lain (kembar) dan tetap disetujui Owner
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->unsignedSmallInteger('gagal_wajah')->default(0)->after('gagal_login');
        });

        Schema::table('pengaturan_sistem', function (Blueprint $table) {
            $table->boolean('wajib_wajah')->default(false)->after('wajib_shift');
        });
    }

    public function down(): void
    {
        Schema::table('pengaturan_sistem', fn (Blueprint $table) => $table->dropColumn('wajib_wajah'));
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn('gagal_wajah'));
        Schema::dropIfExists('wajah_staf');
    }
};
