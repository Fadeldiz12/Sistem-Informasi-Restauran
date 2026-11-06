<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Lokasi di peta: titik restoran (pengaturan) dan titik antar pesanan Delivery,
     * supaya zona ongkir dihitung otomatis dari jarak dan kurir tidak salah antar.
     */
    public function up(): void
    {
        Schema::table('pengaturan_sistem', function (Blueprint $table) {
            $table->decimal('lat_resto', 10, 7)->nullable();
            $table->decimal('lng_resto', 10, 7)->nullable();
        });
        // titik awal: Jl. Gatot Subroto, Medan (bisa digeser admin di Pengaturan Sistem)
        DB::table('pengaturan_sistem')->whereNull('lat_resto')->update(['lat_resto' => 3.5905, 'lng_resto' => 98.6437]);

        Schema::table('pengiriman', function (Blueprint $table) {
            $table->decimal('lat', 10, 7)->nullable()->after('alamat');
            $table->decimal('lng', 10, 7)->nullable()->after('lat');
            $table->string('patokan', 255)->nullable()->after('lng')->comment('catatan untuk kurir, mis. pagar hijau');
        });
    }

    public function down(): void
    {
        Schema::table('pengiriman', fn (Blueprint $table) => $table->dropColumn(['lat', 'lng', 'patokan']));
        Schema::table('pengaturan_sistem', fn (Blueprint $table) => $table->dropColumn(['lat_resto', 'lng_resto']));
    }
};
