<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Resep per porsi menu: dipakai untuk mengurangi stok bahan baku otomatis saat dapur mulai memasak.
     */
    public function up(): void
    {
        Schema::create('resep', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_menu')->constrained('menu')->cascadeOnDelete();
            $table->foreignId('id_bahan')->constrained('bahan_baku')->cascadeOnDelete();
            $table->decimal('jumlah', 12, 2);
            $table->timestamps();
            $table->unique(['id_menu', 'id_bahan']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('resep');
    }
};
