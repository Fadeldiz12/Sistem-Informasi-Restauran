<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Verifikasi 2 langkah (TOTP, mis. Google Authenticator) untuk Owner & Admin.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->text('dua_faktor_rahasia')->nullable(); // terenkripsi APP_KEY
            $table->text('dua_faktor_cadangan')->nullable(); // terenkripsi; isinya hash SHA-256 kode cadangan
            $table->timestamp('dua_faktor_aktif_at')->nullable();
            $table->unsignedBigInteger('dua_faktor_langkah_terakhir')->nullable(); // cegah kode yang sama dipakai dua kali
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['dua_faktor_rahasia', 'dua_faktor_cadangan', 'dua_faktor_aktif_at', 'dua_faktor_langkah_terakhir']);
        });
    }
};
