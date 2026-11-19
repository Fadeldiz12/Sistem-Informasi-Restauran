<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Kolom terenkripsi yang bisa terisi teks biasa oleh seeder lama (insert langsung tanpa cast model).
     *
     * @var array<string, list<string>>
     */
    private const KOLOM = [
        'users' => ['no_hp', 'alamat'],
        'pesanan' => ['no_hp_pemesan'],
        'pengiriman' => ['alamat', 'no_hp_penerima', 'patokan'],
    ];

    /**
     * Enkripsi nilai yang masih teks biasa dan isi sidik nomor HP yang kosong. Aman dijalankan berulang.
     */
    public function up(): void
    {
        foreach (self::KOLOM as $tabel => $kolom) {
            DB::table($tabel)->select(array_merge(['id'], $kolom))->orderBy('id')->chunkById(500, function ($baris) use ($tabel, $kolom) {
                foreach ($baris as $b) {
                    $data = [];
                    foreach ($kolom as $k) {
                        if ($b->{$k} !== null && $b->{$k} !== '' && ! $this->terenkripsi($b->{$k})) {
                            $data[$k] = Crypt::encryptString($b->{$k});
                        }
                    }
                    if ($data) {
                        DB::table($tabel)->where('id', $b->id)->update($data);
                    }
                }
            });
        }

        DB::table('users')->whereNotNull('no_hp')->whereNull('no_hp_hash')->orderBy('id')->chunkById(200, function ($users) {
            foreach ($users as $u) {
                DB::table('users')->where('id', $u->id)->update(['no_hp_hash' => User::hashHp(Crypt::decryptString($u->no_hp))]);
            }
        });
    }

    public function down(): void
    {
        // tidak dikembalikan ke teks biasa
    }

    private function terenkripsi(string $nilai): bool
    {
        try {
            Crypt::decryptString($nilai);

            return true;
        } catch (Throwable) {
            return false;
        }
    }
};
