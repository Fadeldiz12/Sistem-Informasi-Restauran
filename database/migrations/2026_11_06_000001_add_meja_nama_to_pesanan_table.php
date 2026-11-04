<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Nomor meja (Dine In) dan nama pemesan untuk pembeli langsung yang tidak punya akun.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pesanan', function (Blueprint $table) {
            $table->string('nomor_meja', 10)->nullable()->after('jenis_layanan');
            $table->string('nama_pemesan', 100)->nullable()->after('nomor_meja');
        });
    }

    public function down(): void
    {
        Schema::table('pesanan', function (Blueprint $table) {
            $table->dropColumn(['nomor_meja', 'nama_pemesan']);
        });
    }
};
