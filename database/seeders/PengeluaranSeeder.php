<?php

namespace Database\Seeders;

use App\Models\Pengeluaran;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Pengeluaran operasional contoh untuk 6 bulan terakhir.
 */
class PengeluaranSeeder extends Seeder
{
    public function run(): void
    {
        $owner = User::where('role', User::ROLE_OWNER)->value('id');
        if (! $owner || Pengeluaran::exists()) {
            return;
        }

        mt_srand(11);
        $rutin = [
            // [kategori, keterangan, nominal min, nominal maks, setiap n hari]
            ['Bahan baku', 'Belanja keju & daging', 1800000, 3000000, 3],
            ['Bahan baku', 'Tepung, saus, sayuran', 1200000, 2400000, 5],
            ['Gas', 'Isi ulang tabung gas 12 kg', 600000, 1000000, 7],
            ['Kemasan', 'Box pizza & paper bag', 500000, 900000, 10],
            ['Perawatan', 'Servis peralatan dapur', 750000, 1500000, 30],
        ];

        for ($h = 180; $h >= 0; $h--) {
            $tgl = today()->subDays($h);
            foreach ($rutin as [$kat, $ket, $min, $maks, $tiap]) {
                if ($h % $tiap === 0) {
                    Pengeluaran::create(['id_owner' => $owner, 'kategori' => $kat, 'keterangan' => $ket, 'nominal' => round(mt_rand($min, $maks), -3), 'tanggal' => $tgl]);
                }
            }
            if ($tgl->day === 1) {
                Pengeluaran::create(['id_owner' => $owner, 'kategori' => 'Utilitas', 'keterangan' => 'Listrik & air '.$tgl->copy()->subMonth()->translatedFormat('F'), 'nominal' => round(mt_rand(2200000, 2800000), -3), 'tanggal' => $tgl]);
                Pengeluaran::create(['id_owner' => $owner, 'kategori' => 'Gaji', 'keterangan' => 'Gaji karyawan '.$tgl->copy()->subMonth()->translatedFormat('F'), 'nominal' => 18500000, 'tanggal' => $tgl]);
            }
        }
    }
}
