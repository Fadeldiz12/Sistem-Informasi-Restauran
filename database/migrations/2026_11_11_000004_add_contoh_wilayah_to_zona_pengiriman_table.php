<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Contoh wilayah per zona (mis. "Medan Petisah, Medan Baru") supaya pelanggan tidak menebak zona ongkirnya.
     */
    public function up(): void
    {
        Schema::table('zona_pengiriman', function (Blueprint $table) {
            $table->string('contoh_wilayah', 255)->nullable()->after('nama_zona');
        });
    }

    public function down(): void
    {
        Schema::table('zona_pengiriman', function (Blueprint $table) {
            $table->dropColumn('contoh_wilayah');
        });
    }
};
