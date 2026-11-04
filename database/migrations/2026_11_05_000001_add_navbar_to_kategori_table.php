<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Kategori yang tampil di navbar beranda pelanggan (maksimal 6, urutan diatur admin).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kategori', function (Blueprint $table) {
            $table->boolean('tampil_navbar')->default(false)->after('nama_kategori');
            $table->unsignedTinyInteger('urutan_navbar')->nullable()->after('tampil_navbar')->comment('1 - 6');
        });
    }

    public function down(): void
    {
        Schema::table('kategori', function (Blueprint $table) {
            $table->dropColumn(['tampil_navbar', 'urutan_navbar']);
        });
    }
};
