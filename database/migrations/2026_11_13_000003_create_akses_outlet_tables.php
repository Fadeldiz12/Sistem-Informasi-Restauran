<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Akses outlet: staf hanya bisa masuk dari outlet (IP WiFi / GPS), dari perangkat terdaftar, dan sesuai jadwal shift.
     * Semua aturan mati secara bawaan dan dinyalakan Owner di menu Keamanan Outlet.
     */
    public function up(): void
    {
        Schema::table('pengaturan_sistem', function (Blueprint $table) {
            $table->text('ip_outlet')->nullable()->after('batas_diam_menit'); // satu IP per baris
            $table->boolean('cek_gps')->default(false)->after('ip_outlet');
            $table->unsignedSmallInteger('radius_outlet_m')->default(100)->after('cek_gps');
            $table->boolean('wajib_perangkat')->default(false)->after('radius_outlet_m');
            $table->boolean('wajib_shift')->default(false)->after('wajib_perangkat');
        });

        Schema::create('perangkat_outlet', function (Blueprint $table) {
            $table->id();
            $table->string('nama', 60);
            $table->char('token_hash', 64)->unique(); // sha256 dari token di cookie perangkat
            $table->foreignId('id_pendaftar')->nullable()->constrained('users')->nullOnDelete();
            $table->string('keterangan_perangkat', 120)->nullable();
            $table->timestamp('terakhir_dipakai_at')->nullable();
            $table->timestamps();
        });

        Schema::create('jadwal_shift', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_user')->constrained('users')->cascadeOnDelete();
            $table->unsignedTinyInteger('hari'); // 1 = Senin … 7 = Minggu (ISO)
            $table->time('jam_mulai');
            $table->time('jam_selesai'); // lebih awal dari jam mulai = selesai keesokan harinya
            $table->timestamps();

            $table->unique(['id_user', 'hari']);
        });

        Schema::create('kode_darurat', function (Blueprint $table) {
            $table->id();
            $table->string('kode_hash');
            $table->foreignId('id_owner')->nullable()->constrained('users')->nullOnDelete();
            $table->string('alasan', 200);
            $table->timestamp('berlaku_sampai');
            $table->timestamp('dicabut_at')->nullable();
            $table->unsignedInteger('dipakai')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kode_darurat');
        Schema::dropIfExists('jadwal_shift');
        Schema::dropIfExists('perangkat_outlet');
        Schema::table('pengaturan_sistem', function (Blueprint $table) {
            $table->dropColumn(['ip_outlet', 'cek_gps', 'radius_outlet_m', 'wajib_perangkat', 'wajib_shift']);
        });
    }
};
