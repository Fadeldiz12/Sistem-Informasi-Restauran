<?php

use App\Support\RantaiLog;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Rantai hash (HMAC-SHA256) untuk log aktivitas: tiap baris menyimpan hash yang menyertakan hash baris sebelumnya,
     * sehingga baris yang diubah, disisipkan, atau dihapus langsung di database ketahuan.
     */
    public function up(): void
    {
        Schema::table('log_aktivitas', function (Blueprint $table) {
            $table->char('hash', 64)->nullable();
        });
        Schema::create('log_rantai', function (Blueprint $table) {
            $table->unsignedTinyInteger('id')->primary(); // selalu satu baris (id = 1): ujung rantai
            $table->unsignedBigInteger('id_terakhir')->nullable();
            $table->char('hash_terakhir', 64)->nullable();
            $table->unsignedBigInteger('jumlah')->default(0);
        });

        // rantai untuk catatan yang sudah ada, urut id
        $sebelum = '';
        $jumlah = 0;
        $idTerakhir = null;
        DB::table('log_aktivitas')->orderBy('id')->chunkById(500, function ($baris) use (&$sebelum, &$jumlah, &$idTerakhir) {
            foreach ($baris as $b) {
                $sebelum = RantaiLog::hitung((array) $b, $sebelum);
                DB::table('log_aktivitas')->where('id', $b->id)->update(['hash' => $sebelum]);
                $jumlah++;
                $idTerakhir = $b->id;
            }
        });
        DB::table('log_rantai')->insert(['id' => 1, 'id_terakhir' => $idTerakhir, 'hash_terakhir' => $sebelum ?: null, 'jumlah' => $jumlah]);
    }

    public function down(): void
    {
        Schema::dropIfExists('log_rantai');
        Schema::table('log_aktivitas', function (Blueprint $table) {
            $table->dropColumn('hash');
        });
    }
};
