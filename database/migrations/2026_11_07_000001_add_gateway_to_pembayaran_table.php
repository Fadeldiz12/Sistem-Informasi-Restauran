<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Data pembayaran otomatis lewat Midtrans (Virtual Account & QRIS).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pembayaran', function (Blueprint $table) {
            $table->string('kode_transaksi', 64)->nullable()->unique()->after('status_bayar')->comment('order_id di Midtrans');
            $table->string('bank', 20)->nullable()->after('kode_transaksi')->comment('bca, bni, bri, permata');
            $table->string('nomor_va', 50)->nullable()->after('bank');
            $table->string('qris_url', 500)->nullable()->after('nomor_va');
            $table->dateTime('kedaluwarsa_pada')->nullable()->after('qris_url');
        });
    }

    public function down(): void
    {
        Schema::table('pembayaran', function (Blueprint $table) {
            $table->dropUnique(['kode_transaksi']);
            $table->dropColumn(['kode_transaksi', 'bank', 'nomor_va', 'qris_url', 'kedaluwarsa_pada']);
        });
    }
};
