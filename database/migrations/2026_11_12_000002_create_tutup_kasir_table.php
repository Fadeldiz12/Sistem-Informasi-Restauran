<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * EOD (end of day) kasir: rekap penjualan sejak penutupan sebelumnya. Setelah EOD, kasir hanya melihat transaksi baru.
     */
    public function up(): void
    {
        Schema::create('tutup_kasir', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_kasir')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('mulai_dari');
            $table->dateTime('waktu_tutup');
            $table->unsignedInteger('jumlah_pesanan')->default(0);
            $table->unsignedInteger('jumlah_batal')->default(0);
            $table->decimal('total_pemasukan', 14, 2)->default(0);
            $table->decimal('total_tunai', 14, 2)->default(0);
            $table->decimal('total_nontunai', 14, 2)->default(0);
            $table->decimal('total_setoran', 14, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tutup_kasir');
    }
};
