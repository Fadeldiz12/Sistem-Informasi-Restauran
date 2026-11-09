<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Pesan tanpa login: sumber pesanan (online / kasir) dan kontak pemesan tamu.
 * Sebelumnya "online" ditentukan dari id_pelanggan; pesanan tamu juga tanpa id_pelanggan.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pesanan', function (Blueprint $table) {
            $table->string('sumber', 10)->default('online')->after('id_kasir');
            $table->string('no_hp_pemesan', 20)->nullable()->after('nama_pemesan');
            $table->string('email_pemesan', 100)->nullable()->after('no_hp_pemesan');
            $table->index(['sumber', 'status']);
        });

        // semua pesanan lama tanpa akun pelanggan berasal dari kasir
        DB::table('pesanan')->whereNull('id_pelanggan')->update(['sumber' => 'kasir']);
    }

    public function down(): void
    {
        Schema::table('pesanan', function (Blueprint $table) {
            $table->dropIndex(['sumber', 'status']);
            $table->dropColumn(['sumber', 'no_hp_pemesan', 'email_pemesan']);
        });
    }
};
