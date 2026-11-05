<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Login Google dihapus; diganti verifikasi email dengan kode OTP
     * (saat registrasi dan lupa password).
     */
    public function up(): void
    {
        if (Schema::hasColumn('users', 'google_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropUnique(['google_id']);
                $table->dropColumn('google_id');
            });
        }

        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('email_verified_at')->nullable()->after('email');
        });
        // akun yang sudah ada dianggap terverifikasi
        DB::table('users')->update(['email_verified_at' => now()]);

        Schema::create('kode_otp', function (Blueprint $table) {
            $table->id();
            $table->string('email', 100);
            $table->enum('tujuan', ['registrasi', 'reset_password']);
            $table->string('kode_hash', 255);
            $table->unsignedTinyInteger('percobaan')->default(0);
            $table->dateTime('kedaluwarsa_pada');
            $table->timestamps();
            $table->index(['email', 'tujuan']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kode_otp');
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('email_verified_at');
        });
    }
};
