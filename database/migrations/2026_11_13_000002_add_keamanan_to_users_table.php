<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Kunci akun setelah gagal login berulang, dan satu akun staf hanya aktif di satu perangkat.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedSmallInteger('gagal_login')->default(0)->after('remember_token');
            $table->timestamp('terkunci_at')->nullable()->after('gagal_login');
            $table->string('token_sesi', 64)->nullable()->after('terkunci_at');
        });

        Schema::table('pengaturan_sistem', function (Blueprint $table) {
            $table->unsignedSmallInteger('batas_diam_menit')->default(30)->after('lng_resto');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['gagal_login', 'terkunci_at', 'token_sesi']);
        });

        Schema::table('pengaturan_sistem', function (Blueprint $table) {
            $table->dropColumn('batas_diam_menit');
        });
    }
};
