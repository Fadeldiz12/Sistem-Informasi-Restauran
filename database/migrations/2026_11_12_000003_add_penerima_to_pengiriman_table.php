<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Penerima Delivery yang diinput kasir (pembeli datang ke restoran, diantar ke alamat lain).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pengiriman', function (Blueprint $table) {
            $table->string('nama_penerima', 100)->nullable()->after('id_zona');
            $table->string('no_hp_penerima', 20)->nullable()->after('nama_penerima');
        });
    }

    public function down(): void
    {
        Schema::table('pengiriman', function (Blueprint $table) {
            $table->dropColumn(['nama_penerima', 'no_hp_penerima']);
        });
    }
};
