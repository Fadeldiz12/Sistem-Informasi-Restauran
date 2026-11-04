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
        Schema::create('pesanan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_pelanggan')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('id_kasir')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('jenis_layanan', ['dine_in', 'take_away', 'delivery']);
            $table->dateTime('waktu_pesan');
            $table->decimal('subtotal', 12, 2);
            $table->decimal('ongkir', 12, 2)->default(0);
            $table->decimal('total', 12, 2);
            $table->enum('status', ['menunggu', 'diproses', 'siap', 'diantar', 'selesai', 'dibatalkan'])->default('menunggu');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pesanan');
    }
};
