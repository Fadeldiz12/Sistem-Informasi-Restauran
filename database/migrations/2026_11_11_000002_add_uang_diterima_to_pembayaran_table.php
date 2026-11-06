<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Uang tunai yang diterima kasir disimpan supaya struk bisa dicetak ulang dengan angka yang sama
     * (sebelumnya hanya dibawa lewat URL dan bisa diubah).
     */
    public function up(): void
    {
        Schema::table('pembayaran', function (Blueprint $table) {
            $table->decimal('uang_diterima', 12, 2)->nullable()->after('jumlah');
        });
    }

    public function down(): void
    {
        Schema::table('pembayaran', function (Blueprint $table) {
            $table->dropColumn('uang_diterima');
        });
    }
};
