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
        Schema::create('pengiriman', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_pesanan')->unique()->constrained('pesanan');
            $table->foreignId('id_kurir')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('id_zona')->constrained('zona_pengiriman');
            $table->text('alamat');
            $table->decimal('jarak_km', 6, 2);
            $table->dateTime('waktu_siap')->nullable();
            $table->enum('status_kirim', ['menunggu_kurir', 'dalam_perjalanan', 'diterima'])->default('menunggu_kurir');
            $table->string('bukti_kirim', 255)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pengiriman');
    }
};
