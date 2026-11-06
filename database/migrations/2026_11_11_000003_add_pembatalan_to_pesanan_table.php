<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Alasan pesanan ditolak/dibatalkan kasir (tampil ke pelanggan), dan penanda pemakaian stok
     * per pesanan supaya stok bisa dikembalikan bila pesanan dibatalkan.
     */
    public function up(): void
    {
        Schema::table('pesanan', function (Blueprint $table) {
            $table->string('alasan_batal', 255)->nullable()->after('status');
        });

        Schema::table('mutasi_bahan_baku', function (Blueprint $table) {
            $table->foreignId('id_pesanan')->nullable()->after('id_bahan')->constrained('pesanan')->nullOnDelete()
                ->comment('pemakaian otomatis dari resep untuk pesanan ini');
        });
    }

    public function down(): void
    {
        Schema::table('mutasi_bahan_baku', function (Blueprint $table) {
            $table->dropConstrainedForeignId('id_pesanan');
        });

        Schema::table('pesanan', function (Blueprint $table) {
            $table->dropColumn('alasan_batal');
        });
    }
};
