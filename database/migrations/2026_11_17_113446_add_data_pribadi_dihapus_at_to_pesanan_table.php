<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Penanda pesanan lama yang data pribadinya (nama, HP, email, alamat, foto bukti) sudah dihapus sesuai masa simpan.
     */
    public function up(): void
    {
        Schema::table('pesanan', function (Blueprint $table) {
            $table->timestamp('data_pribadi_dihapus_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('pesanan', function (Blueprint $table) {
            $table->dropColumn('data_pribadi_dihapus_at');
        });
    }
};
