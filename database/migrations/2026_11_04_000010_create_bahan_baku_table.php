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
        Schema::create('bahan_baku', function (Blueprint $table) {
            $table->id();
            $table->string('nama_bahan', 100);
            $table->string('satuan', 20);
            $table->decimal('stok', 12, 2)->default(0);
            $table->decimal('rata_pemakaian', 12, 2)->default(0);
            $table->integer('lead_time')->comment('Satuan hari');
            $table->decimal('safety_stock', 12, 2);
            $table->decimal('rop', 12, 2)->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bahan_baku');
    }
};
