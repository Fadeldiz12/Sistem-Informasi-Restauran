<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Kolom data pribadi yang disimpan terenkripsi (AES-256, kunci APP_KEY).
     *
     * @var array<string, array<int, string>>
     */
    private const KOLOM = [
        'users' => ['no_hp', 'alamat'],
        'pesanan' => ['no_hp_pemesan'],
        'pengiriman' => ['alamat', 'no_hp_penerima', 'patokan'],
    ];

    public function up(): void
    {
        // teks terenkripsi jauh lebih panjang dari aslinya
        Schema::table('users', function (Blueprint $table) {
            $table->text('no_hp')->nullable()->change();
            $table->char('no_hp_hash', 64)->nullable()->after('no_hp')->index(); // untuk mencari pengguna dari nomor HP
        });
        Schema::table('pesanan', fn (Blueprint $table) => $table->text('no_hp_pemesan')->nullable()->change());
        Schema::table('pengiriman', function (Blueprint $table) {
            $table->text('no_hp_penerima')->nullable()->change();
            $table->text('patokan')->nullable()->change();
        });

        $this->ubahSemua(function (?string $nilai) {
            if ($nilai === null || $nilai === '') {
                return $nilai;
            }
            try {
                Crypt::decryptString($nilai);

                return $nilai; // sudah terenkripsi
            } catch (Throwable) {
                return Crypt::encryptString($nilai);
            }
        });

        DB::table('users')->whereNotNull('no_hp')->orderBy('id')->chunkById(200, function ($users) {
            foreach ($users as $u) {
                DB::table('users')->where('id', $u->id)->update(['no_hp_hash' => User::hashHp(Crypt::decryptString($u->no_hp))]);
            }
        });
    }

    public function down(): void
    {
        $this->ubahSemua(function (?string $nilai) {
            try {
                return $nilai ? Crypt::decryptString($nilai) : $nilai;
            } catch (Throwable) {
                return $nilai;
            }
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['no_hp_hash']);
            $table->dropColumn('no_hp_hash');
        });
    }

    private function ubahSemua(callable $ubah): void
    {
        foreach (self::KOLOM as $tabel => $kolom) {
            DB::table($tabel)->select(array_merge(['id'], $kolom))->orderBy('id')->chunkById(200, function ($baris) use ($tabel, $kolom, $ubah) {
                foreach ($baris as $b) {
                    $data = [];
                    foreach ($kolom as $k) {
                        $baru = $ubah($b->{$k});
                        if ($baru !== $b->{$k}) {
                            $data[$k] = $baru;
                        }
                    }
                    if ($data) {
                        DB::table($tabel)->where('id', $b->id)->update($data);
                    }
                }
            });
        }
    }
};
