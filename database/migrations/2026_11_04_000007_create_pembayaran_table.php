<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('pembayaran', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_pesanan')->unique()->constrained('pesanan');
            $table->foreignId('id_kasir')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('metode', ['tunai', 'transfer', 'qris']);
            $table->decimal('jumlah', 12, 2);
            $table->string('bukti_bayar', 255)->nullable();
            $table->enum('status_bayar', ['menunggu', 'lunas', 'ditolak'])->default('menunggu');
            $table->dateTime('waktu_bayar')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pembayaran');
    }
};
