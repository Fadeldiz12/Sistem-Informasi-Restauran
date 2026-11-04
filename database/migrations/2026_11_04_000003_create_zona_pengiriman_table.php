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
        Schema::create('zona_pengiriman', function (Blueprint $table) {
            $table->id();
            $table->string('nama_zona', 100);
            $table->decimal('radius_km', 6, 2);
            $table->decimal('tarif_per_km', 12, 2);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('zona_pengiriman');
    }
};
