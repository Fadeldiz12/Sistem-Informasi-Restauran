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
        Schema::create('tiket_dapur', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_pesanan')->unique()->constrained('pesanan');
            $table->dateTime('waktu_masuk');
            $table->enum('status_masak', ['antre', 'dimasak', 'selesai'])->default('antre');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tiket_dapur');
    }
};
