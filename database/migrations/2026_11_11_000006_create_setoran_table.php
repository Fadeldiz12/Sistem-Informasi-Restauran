<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Satu setoran tunai kurir ke kasir: jumlah yang seharusnya, uang yang benar-benar dihitung kasir, dan selisihnya.
     */
    public function up(): void
    {
        Schema::create('setoran', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_kurir')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('id_kasir')->nullable()->constrained('users')->nullOnDelete();
            $table->decimal('jumlah_seharusnya', 12, 2);
            $table->decimal('jumlah_diterima', 12, 2);
            $table->string('catatan', 255)->nullable();
            $table->dateTime('waktu_setor');
            $table->timestamps();
        });

        Schema::table('pembayaran', function (Blueprint $table) {
            $table->foreignId('id_setoran')->nullable()->after('disetor_pada')->constrained('setoran')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('pembayaran', function (Blueprint $table) {
            $table->dropConstrainedForeignId('id_setoran');
        });
        Schema::dropIfExists('setoran');
    }
};
