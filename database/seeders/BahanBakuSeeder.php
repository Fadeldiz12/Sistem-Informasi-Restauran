<?php

namespace Database\Seeders;

use App\Models\BahanBaku;
use App\Models\MutasiBahanBaku;
use App\Services\BahanBakuService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

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
            // bahan untuk menu tambahan (resep di ResepSeeder)
            ['Beras', 'kg', 45, 5.5, 2, 6],
            ['Daging Ayam', 'kg', 22, 4.2, 1, 4],
            ['Telur', 'butir', 180, 32, 2, 30],
            ['Bawang Putih', 'kg', 4, 0.5, 2, 0.5],
            ['Bawang Merah', 'kg', 3.5, 0.4, 2, 0.5],
            ['Bawang Bombay', 'kg', 5, 0.6, 2, 0.6],
            ['Cabai Merah', 'kg', 2.5, 0.35, 1, 0.4],
            ['Kecap Manis', 'liter', 6, 0.4, 3, 0.5],
            ['Minyak Goreng', 'liter', 30, 3.5, 3, 4],
            ['Seafood Campur', 'kg', 7, 1.2, 1, 1.5],
            ['Daging Sapi Iris', 'kg', 8, 1.1, 2, 1.2],
            ['Bumbu Rendang', 'kg', 3, 0.3, 3, 0.4],
            ['Santan', 'liter', 5, 0.6, 1, 0.8],
            ['Roti Burger', 'pcs', 90, 14, 1, 15],
            ['Ikan Fillet', 'kg', 4, 0.5, 1, 0.6],
            ['Jamur', 'kg', 3, 0.4, 1, 0.5],
            ['Saus BBQ', 'liter', 6, 0.5, 3, 0.6],
            ['Saus Teriyaki', 'liter', 4, 0.3, 3, 0.4],
            ['Saus Lada Hitam', 'liter', 3, 0.25, 3, 0.3],
            ['Krim Masak', 'liter', 8, 0.8, 2, 1],
            ['Pasta Spaghetti', 'kg', 15, 1.6, 3, 1.5],
            ['Pasta Fettuccine', 'kg', 6, 0.6, 3, 0.8],
            ['Pasta Lasagna', 'kg', 5, 0.4, 3, 0.5],
            ['Smoked Beef', 'kg', 4, 0.5, 2, 0.5],
            ['Tuna', 'kg', 3, 0.3, 2, 0.4],
            ['Nanas', 'kg', 3, 0.3, 1, 0.4],
            ['Sayuran Campur', 'kg', 10, 1.8, 1, 2],
            ['Keju Feta', 'kg', 1.5, 0.15, 3, 0.2],
            ['Tepung Panir', 'kg', 8, 0.9, 3, 1],
            ['Tortilla Chips', 'kg', 4, 0.4, 3, 0.5],
            ['Mentega', 'kg', 5, 0.5, 3, 0.6],
            ['Teh Celup', 'pcs', 300, 40, 3, 40],
            ['Gula Pasir', 'kg', 20, 2.2, 3, 2],
            ['Jeruk', 'kg', 10, 1.6, 1, 1.5],
            ['Daun Mint', 'ikat', 15, 2, 1, 2],
            ['Biji Kopi', 'kg', 6, 0.7, 3, 0.8],
            ['Susu Segar', 'liter', 25, 3.5, 1, 4],
            ['Gula Aren Cair', 'liter', 5, 0.5, 3, 0.6],
            ['Bubuk Matcha', 'kg', 1.2, 0.12, 5, 0.2],
            ['Bubuk Cokelat', 'kg', 2.5, 0.25, 3, 0.3],
            ['Alpukat', 'kg', 6, 1, 1, 1],
            ['Stroberi', 'kg', 3, 0.5, 1, 0.5],
            ['Air Mineral Botol', 'pcs', 240, 25, 3, 30],
            ['Es Batu', 'kg', 40, 12, 1, 10],
            ['Es Krim Vanila', 'liter', 6, 0.6, 2, 0.8],
            ['Cream Cheese', 'kg', 3, 0.3, 3, 0.4],
            ['Biskuit', 'kg', 3, 0.25, 3, 0.3],
            ['Selai Blueberry', 'kg', 2, 0.15, 3, 0.2],
            ['Pisang', 'kg', 6, 0.8, 1, 1],
            ['Madu', 'liter', 3, 0.2, 3, 0.3],
        ];

        // satu transaksi + riwayat disimpan sekaligus: cepat juga di SQLite / MySQL lokal
        DB::transaction(function () use ($data, $service) {
            $hari = BahanBaku::HARI_RATA_PEMAKAIAN;
            $sekarang = now();

            foreach ($data as [$nama, $satuan, $stokAkhir, $perHari, $lead, $ss]) {
                $bahan = BahanBaku::updateOrCreate(['nama_bahan' => $nama], [
                    'satuan' => $satuan, 'lead_time' => $lead, 'safety_stock' => $ss, 'stok' => $stokAkhir,
                ]);
                $bahan->mutasi()->delete();

                // stok masuk di awal periode + pemakaian harian 30 hari terakhir
                $baris = [['jenis' => MutasiBahanBaku::JENIS_MASUK, 'jumlah' => $stokAkhir + $perHari * $hari, 'tanggal' => today()->subDays($hari)]];
                for ($i = $hari - 1; $i >= 0; $i--) {
                    $baris[] = ['jenis' => MutasiBahanBaku::JENIS_PEMAKAIAN, 'jumlah' => $perHari, 'tanggal' => today()->subDays($i)];
                }
                MutasiBahanBaku::insert(array_map(fn ($b) => $b + ['id_bahan' => $bahan->id, 'created_at' => $sekarang, 'updated_at' => $sekarang], $baris));

                $service->hitungUlang($bahan);
            }
        });
    }
}
