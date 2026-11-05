<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Setoran tunai kurir: pembayaran COD yang diterima kurir dicatat kapan disetor ke kasir.
     * Kasir penerima setoran disimpan di id_kasir.
     */
    public function up(): void
    {
        Schema::table('pembayaran', function (Blueprint $table) {
            $table->foreignId('id_kurir')->nullable()->after('id_kasir')->constrained('users')->nullOnDelete()->comment('kurir yang menerima uang tunai (COD)');
            $table->dateTime('disetor_pada')->nullable()->after('waktu_bayar');
        });
    }

    public function down(): void
    {
        Schema::table('pembayaran', function (Blueprint $table) {
            $table->dropConstrainedForeignId('id_kurir');
            $table->dropColumn('disetor_pada');
        });
    }
};
