<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Audit log: siapa melakukan apa dan kapan (login, ubah harga, hapus data, tandai lunas, dst.).
     * Hanya ditambah, tidak pernah diubah atau dihapus dari aplikasi.
     */
    public function up(): void
    {
        Schema::create('log_aktivitas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_user')->nullable()->constrained('users')->nullOnDelete();
            $table->string('nama_user', 100)->nullable(); // tetap terbaca walau akunnya sudah dihapus
            $table->string('role_user', 20)->nullable();
            $table->string('aksi', 50);
            $table->string('keterangan', 500);
            $table->string('subjek_tipe', 50)->nullable();
            $table->unsignedBigInteger('subjek_id')->nullable();
            $table->json('data')->nullable();
            $table->string('ip', 45)->nullable();
            $table->string('perangkat', 255)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['created_at']);
            $table->index(['aksi', 'created_at']);
            $table->index(['id_user', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('log_aktivitas');
    }
};
