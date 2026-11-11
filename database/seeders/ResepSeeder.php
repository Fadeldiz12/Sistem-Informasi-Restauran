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

            // Pizza
            'Pizza Hawaiian' => ['Tepung Terigu' => 0.25, 'Keju Mozzarella' => 0.15, 'Saus Tomat' => 0.1, 'Smoked Beef' => 0.06, 'Nanas' => 0.08],
            'Pizza Supreme' => ['Tepung Terigu' => 0.25, 'Keju Mozzarella' => 0.18, 'Saus Tomat' => 0.1, 'Daging Beef Pepperoni' => 0.06, 'Daging Sapi Giling' => 0.06, 'Sayuran Campur' => 0.06, 'Jamur' => 0.04],
            'Pizza Four Cheese' => ['Tepung Terigu' => 0.25, 'Keju Mozzarella' => 0.22, 'Cream Cheese' => 0.05, 'Keju Feta' => 0.03, 'Saus Tomat' => 0.06],
            'Pizza BBQ Chicken' => ['Tepung Terigu' => 0.25, 'Keju Mozzarella' => 0.15, 'Daging Ayam' => 0.12, 'Saus BBQ' => 0.06, 'Bawang Bombay' => 0.03],
            'Pizza Tuna Melt' => ['Tepung Terigu' => 0.25, 'Keju Mozzarella' => 0.18, 'Saus Tomat' => 0.1, 'Tuna' => 0.1, 'Bawang Bombay' => 0.03],
            'Pizza Veggie Garden' => ['Tepung Terigu' => 0.25, 'Keju Mozzarella' => 0.15, 'Saus Tomat' => 0.1, 'Sayuran Campur' => 0.12, 'Jamur' => 0.05],
            'Pizza Smoked Beef' => ['Tepung Terigu' => 0.25, 'Keju Mozzarella' => 0.15, 'Saus Tomat' => 0.1, 'Smoked Beef' => 0.12],
            'Pizza Meat Lovers' => ['Tepung Terigu' => 0.25, 'Keju Mozzarella' => 0.18, 'Saus Tomat' => 0.1, 'Daging Beef Pepperoni' => 0.06, 'Smoked Beef' => 0.06, 'Daging Sapi Giling' => 0.08],

            // Pasta
            'Fettuccine Carbonara' => ['Pasta Fettuccine' => 0.15, 'Krim Masak' => 0.12, 'Smoked Beef' => 0.04, 'Telur' => 1, 'Keju Mozzarella' => 0.02],
            'Spaghetti Aglio Olio' => ['Pasta Spaghetti' => 0.15, 'Bawang Putih' => 0.02, 'Cabai Merah' => 0.01, 'Minyak Goreng' => 0.03],
            'Lasagna Beef' => ['Pasta Lasagna' => 0.12, 'Daging Sapi Giling' => 0.12, 'Saus Tomat' => 0.1, 'Keju Mozzarella' => 0.08, 'Krim Masak' => 0.05],

            // Burger
            'Double Cheese Burger' => ['Roti Burger' => 1, 'Daging Sapi Giling' => 0.3, 'Keju Mozzarella' => 0.06, 'Selada Romaine' => 0.03],
            'Chicken Crispy Burger' => ['Roti Burger' => 1, 'Daging Ayam' => 0.15, 'Tepung Terigu' => 0.04, 'Tepung Panir' => 0.03, 'Selada Romaine' => 0.03, 'Minyak Goreng' => 0.05],
            'BBQ Smoky Burger' => ['Roti Burger' => 1, 'Daging Sapi Giling' => 0.15, 'Smoked Beef' => 0.03, 'Saus BBQ' => 0.03, 'Bawang Bombay' => 0.03],
            'Mushroom Swiss Burger' => ['Roti Burger' => 1, 'Daging Sapi Giling' => 0.15, 'Jamur' => 0.06, 'Keju Mozzarella' => 0.04],
            'Fish Burger' => ['Roti Burger' => 1, 'Ikan Fillet' => 0.12, 'Tepung Panir' => 0.03, 'Selada Romaine' => 0.03, 'Minyak Goreng' => 0.05],
            'Spicy Chicken Burger' => ['Roti Burger' => 1, 'Daging Ayam' => 0.15, 'Tepung Terigu' => 0.04, 'Cabai Merah' => 0.02, 'Selada Romaine' => 0.03, 'Minyak Goreng' => 0.05],

            // Camilan
            'Chicken Wings BBQ' => ['Daging Ayam' => 0.25, 'Saus BBQ' => 0.04, 'Minyak Goreng' => 0.05],
            'Kentang Goreng Keju' => ['Kentang Beku' => 0.2, 'Keju Mozzarella' => 0.04, 'Minyak Goreng' => 0.05],
            'Onion Rings' => ['Bawang Bombay' => 0.15, 'Tepung Terigu' => 0.05, 'Tepung Panir' => 0.04, 'Minyak Goreng' => 0.06],
            'Chicken Nuggets' => ['Daging Ayam' => 0.15, 'Tepung Panir' => 0.04, 'Telur' => 1, 'Minyak Goreng' => 0.05],
            'Cheese Sticks' => ['Keju Mozzarella' => 0.12, 'Tepung Panir' => 0.04, 'Telur' => 1, 'Minyak Goreng' => 0.05],
            'Garlic Bread' => ['Tepung Terigu' => 0.12, 'Mentega' => 0.03, 'Bawang Putih' => 0.01],
            'Nachos Cheese' => ['Tortilla Chips' => 0.12, 'Keju Mozzarella' => 0.06, 'Daging Sapi Giling' => 0.04, 'Saus Tomat' => 0.03],
            'Popcorn Chicken' => ['Daging Ayam' => 0.15, 'Tepung Terigu' => 0.05, 'Minyak Goreng' => 0.05],
            'Calamari Goreng' => ['Seafood Campur' => 0.15, 'Tepung Terigu' => 0.05, 'Minyak Goreng' => 0.06],

            // Salad
            'Greek Salad' => ['Sayuran Campur' => 0.15, 'Keju Feta' => 0.04, 'Bawang Bombay' => 0.02],

            // Minuman
            'Es Teh Manis' => ['Teh Celup' => 1, 'Gula Pasir' => 0.02, 'Es Batu' => 0.15],
            'Es Jeruk' => ['Jeruk' => 0.25, 'Gula Pasir' => 0.02, 'Es Batu' => 0.15],
            'Lemonade Mint' => ['Lemon' => 0.06, 'Daun Mint' => 0.1, 'Gula Pasir' => 0.02, 'Es Batu' => 0.15],
            'Kopi Susu Gula Aren' => ['Biji Kopi' => 0.018, 'Susu Segar' => 0.15, 'Gula Aren Cair' => 0.03, 'Es Batu' => 0.12],
            'Americano' => ['Biji Kopi' => 0.018, 'Es Batu' => 0.1],
            'Cappuccino' => ['Biji Kopi' => 0.018, 'Susu Segar' => 0.15],
            'Matcha Latte' => ['Bubuk Matcha' => 0.008, 'Susu Segar' => 0.2, 'Gula Pasir' => 0.015, 'Es Batu' => 0.1],
            'Chocolate Ice' => ['Bubuk Cokelat' => 0.025, 'Susu Segar' => 0.2, 'Gula Pasir' => 0.015, 'Es Batu' => 0.12],
            'Jus Alpukat' => ['Alpukat' => 0.2, 'Susu Segar' => 0.05, 'Gula Pasir' => 0.02, 'Es Batu' => 0.12],
            'Strawberry Smoothie' => ['Stroberi' => 0.15, 'Susu Segar' => 0.1, 'Es Krim Vanila' => 0.05, 'Es Batu' => 0.1],
            'Air Mineral' => ['Air Mineral Botol' => 1],

            // Dessert
            'Tiramisu' => ['Cream Cheese' => 0.06, 'Biskuit' => 0.04, 'Biji Kopi' => 0.008, 'Bubuk Cokelat' => 0.005, 'Krim Masak' => 0.04],
            'Panna Cotta' => ['Krim Masak' => 0.12, 'Susu Segar' => 0.05, 'Gula Pasir' => 0.02, 'Stroberi' => 0.03],
            'Cheesecake Blueberry' => ['Cream Cheese' => 0.08, 'Biskuit' => 0.04, 'Mentega' => 0.015, 'Selai Blueberry' => 0.03, 'Telur' => 1],
            'Pisang Goreng Keju' => ['Pisang' => 0.15, 'Tepung Terigu' => 0.04, 'Keju Mozzarella' => 0.03, 'Minyak Goreng' => 0.05],

            // Nasi
            'Nasi Goreng Spesial' => ['Beras' => 0.15, 'Telur' => 1, 'Daging Ayam' => 0.05, 'Kecap Manis' => 0.02, 'Bawang Merah' => 0.015, 'Bawang Putih' => 0.01, 'Minyak Goreng' => 0.03],
            'Nasi Goreng Seafood' => ['Beras' => 0.15, 'Telur' => 1, 'Seafood Campur' => 0.1, 'Kecap Manis' => 0.02, 'Bawang Putih' => 0.01, 'Minyak Goreng' => 0.03],
            'Nasi Goreng Kampung' => ['Beras' => 0.15, 'Telur' => 1, 'Cabai Merah' => 0.015, 'Bawang Merah' => 0.02, 'Kecap Manis' => 0.015, 'Minyak Goreng' => 0.03],
            'Nasi Ayam Geprek' => ['Beras' => 0.15, 'Daging Ayam' => 0.15, 'Tepung Terigu' => 0.04, 'Cabai Merah' => 0.03, 'Bawang Putih' => 0.01, 'Minyak Goreng' => 0.06],
            'Nasi Ayam Teriyaki' => ['Beras' => 0.15, 'Daging Ayam' => 0.15, 'Saus Teriyaki' => 0.04, 'Bawang Bombay' => 0.03, 'Sayuran Campur' => 0.04],
            'Nasi Ayam Katsu' => ['Beras' => 0.15, 'Daging Ayam' => 0.15, 'Tepung Panir' => 0.04, 'Telur' => 1, 'Sayuran Campur' => 0.04, 'Minyak Goreng' => 0.06],
            'Nasi Beef Blackpepper' => ['Beras' => 0.15, 'Daging Sapi Iris' => 0.12, 'Saus Lada Hitam' => 0.04, 'Bawang Bombay' => 0.03, 'Sayuran Campur' => 0.04],
            'Nasi Cumi Hitam' => ['Beras' => 0.15, 'Seafood Campur' => 0.12, 'Bawang Merah' => 0.015, 'Cabai Merah' => 0.01, 'Minyak Goreng' => 0.03],
            'Nasi Ayam Bakar Madu' => ['Beras' => 0.15, 'Daging Ayam' => 0.18, 'Madu' => 0.02, 'Kecap Manis' => 0.02, 'Bawang Putih' => 0.01],
            'Nasi Rendang' => ['Beras' => 0.15, 'Daging Sapi Iris' => 0.12, 'Bumbu Rendang' => 0.03, 'Santan' => 0.08],
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
