<?php

namespace Database\Seeders;

use App\Models\BahanBaku;
use App\Models\MutasiBahanBaku;
use App\Services\BahanBakuService;
use Illuminate\Database\Seeder;

/**
 * Bahan baku contoh (sesuai UI) beserta riwayat pemakaian 30 hari,
 * sehingga d (rata-rata pemakaian) dan ROP langsung bernilai realistis.
 */
class BahanBakuSeeder extends Seeder
{
    public function run(BahanBakuService $service): void
    {
        // [nama, satuan, stok akhir, pemakaian per hari, lead time, safety stock]
        $data = [
            ['Keju Mozzarella', 'kg', 6.5, 3.2, 2, 2],
            ['Daging Beef Pepperoni', 'kg', 4, 1.8, 2, 1],
            ['Saus Tomat', 'liter', 5, 2.5, 1, 2.5],
            ['Tepung Terigu', 'kg', 42, 6, 3, 6],
            ['Pasta Penne', 'kg', 18, 2.4, 3, 2],
            ['Daging Sapi Giling', 'kg', 12.5, 2.2, 2, 2],
            ['Kentang Beku', 'kg', 25, 4.5, 2, 3],
            ['Selada Romaine', 'kg', 4.6, 1.1, 1, 1],
            ['Lemon', 'kg', 3, 0.9, 1, 0.5],
            ['Cokelat Batang', 'kg', 2.8, 0.6, 3, 0.5],
        ];

        foreach ($data as [$nama, $satuan, $stokAkhir, $perHari, $lead, $ss]) {
            $bahan = BahanBaku::updateOrCreate(['nama_bahan' => $nama], [
                'satuan' => $satuan, 'lead_time' => $lead, 'safety_stock' => $ss, 'stok' => $stokAkhir,
            ]);
            $bahan->mutasi()->delete();

            // stok masuk di awal periode + pemakaian harian 30 hari terakhir
            $hari = BahanBaku::HARI_RATA_PEMAKAIAN;
            $bahan->mutasi()->create(['jenis' => MutasiBahanBaku::JENIS_MASUK, 'jumlah' => $stokAkhir + $perHari * $hari, 'tanggal' => today()->subDays($hari)]);
            for ($i = $hari - 1; $i >= 0; $i--) {
                $bahan->mutasi()->create(['jenis' => MutasiBahanBaku::JENIS_PEMAKAIAN, 'jumlah' => $perHari, 'tanggal' => today()->subDays($i)]);
            }

            $service->hitungUlang($bahan);
        }
    }
}
