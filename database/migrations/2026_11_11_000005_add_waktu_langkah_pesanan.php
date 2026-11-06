<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Jam setiap langkah pesanan untuk timeline Lacak Pesanan (mulai masak, selesai masak, kurir berangkat, diterima, selesai).
     */
    public function up(): void
    {
        Schema::table('tiket_dapur', function (Blueprint $table) {
            $table->dateTime('mulai_pada')->nullable()->after('status_masak');
            $table->dateTime('selesai_pada')->nullable()->after('mulai_pada');
        });
        Schema::table('pengiriman', function (Blueprint $table) {
            $table->dateTime('berangkat_pada')->nullable()->after('waktu_siap');
            $table->dateTime('diterima_pada')->nullable()->after('berangkat_pada');
        });
        Schema::table('pesanan', function (Blueprint $table) {
            $table->dateTime('selesai_pada')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('tiket_dapur', fn (Blueprint $table) => $table->dropColumn(['mulai_pada', 'selesai_pada']));
        Schema::table('pengiriman', fn (Blueprint $table) => $table->dropColumn(['berangkat_pada', 'diterima_pada']));
        Schema::table('pesanan', fn (Blueprint $table) => $table->dropColumn('selesai_pada'));
    }
};
