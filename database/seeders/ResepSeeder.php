<?php

namespace Database\Seeders;

use App\Models\BahanBaku;
use App\Models\Menu;
use Illuminate\Database\Seeder;

/**
 * Resep contoh (takaran per porsi). Dipakai untuk pengurangan stok otomatis saat dapur mulai memasak.
 */
class ResepSeeder extends Seeder
{
    public function run(): void
    {
        $resep = [
            'Pizza Beef Pepperoni' => ['Tepung Terigu' => 0.25, 'Keju Mozzarella' => 0.15, 'Saus Tomat' => 0.1, 'Daging Beef Pepperoni' => 0.1],
            'Pizza Margherita' => ['Tepung Terigu' => 0.25, 'Keju Mozzarella' => 0.2, 'Saus Tomat' => 0.12],
            'Penne Bolognese' => ['Pasta Penne' => 0.15, 'Daging Sapi Giling' => 0.1, 'Saus Tomat' => 0.08],
            'Spaghetti Seafood' => ['Saus Tomat' => 0.08],
            'Classic Beef Burger' => ['Daging Sapi Giling' => 0.15, 'Selada Romaine' => 0.03, 'Keju Mozzarella' => 0.03],
            'Kentang Goreng' => ['Kentang Beku' => 0.2],
            'Caesar Salad' => ['Selada Romaine' => 0.15, 'Keju Mozzarella' => 0.02],
            'Lemon Iced Tea' => ['Lemon' => 0.05],
            'Choco Brownie Ice Cream' => ['Cokelat Batang' => 0.06, 'Tepung Terigu' => 0.03],
        ];

        $bahan = BahanBaku::pluck('id', 'nama_bahan');

        foreach ($resep as $namaMenu => $isi) {
            $menu = Menu::firstWhere('nama_menu', $namaMenu);
            if (! $menu) {
                continue;
            }
            foreach ($isi as $namaBahan => $jumlah) {
                if (isset($bahan[$namaBahan])) {
                    $menu->resep()->updateOrCreate(['id_bahan' => $bahan[$namaBahan]], ['jumlah' => $jumlah]);
                }
            }
        }
    }
}
