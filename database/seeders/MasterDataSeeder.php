<?php

namespace Database\Seeders;

use App\Models\Kategori;
use App\Models\Menu;
use App\Models\PengaturanSistem;
use App\Models\ZonaPengiriman;
use Illuminate\Database\Seeder;

/**
 * Data master contoh: kategori, menu, zona pengiriman, pengaturan sistem.
 */
class MasterDataSeeder extends Seeder
{
    public function run(): void
    {
        $foto = fn (string $id) => "https://images.unsplash.com/photo-{$id}?w=600&q=75";

        $menu = [
            'Pizza' => [
                ['Pizza Beef Pepperoni', 109000, '1534308983496-4fabb1a015ee', true],
                ['Pizza Margherita', 89000, '1574071318508-1cdbab80d002', true],
                ['Pizza Hawaiian', 95000, '1565299624946-b28f40a0ae38', true],
                ['Pizza Supreme', 119000, '1513104890138-7c749659a591', true],
                ['Pizza Four Cheese', 105000, '1593560708920-61dd98c46a4e', true],
                ['Pizza BBQ Chicken', 99000, '1565299624946-b28f40a0ae38', true],
                ['Pizza Tuna Melt', 92000, '1513104890138-7c749659a591', true],
                ['Pizza Veggie Garden', 85000, '1574071318508-1cdbab80d002', true],
                ['Pizza Smoked Beef', 109000, '1534308983496-4fabb1a015ee', true],
                ['Pizza Meat Lovers', 125000, '1593560708920-61dd98c46a4e', true],
            ],
            'Pasta' => [
                ['Penne Bolognese', 59000, '1621996346565-e3dbc646d9a9', true],
                ['Spaghetti Seafood', 69000, '1563379926898-05f4575a45d8', false],
                ['Fettuccine Carbonara', 62000, '1612874742237-6526221588e3', true],
                ['Spaghetti Aglio Olio', 52000, '1551183053-bf91a1d81141', true],
                ['Lasagna Beef', 68000, '1473093295043-cdd812d0e601', true],
            ],
            'Burger' => [
                ['Classic Beef Burger', 55000, '1568901346375-23c9450c58cd', true],
                ['Double Cheese Burger', 72000, '1550547660-d9450f859349', true],
                ['Chicken Crispy Burger', 49000, '1571091718767-18b5b1457add', true],
                ['BBQ Smoky Burger', 65000, '1553979459-d2229ba7433b', true],
                ['Mushroom Swiss Burger', 62000, '1572802419224-296b0aeee0d9', true],
                ['Fish Burger', 52000, '1586190848861-99aa4a171e90', true],
                ['Spicy Chicken Burger', 52000, '1571091718767-18b5b1457add', true],
            ],
            'Camilan' => [
                ['Chicken Wings BBQ', 45000, '1608039755401-742074f0548d', false],
                ['Kentang Goreng', 28000, '1573080496219-bb080dd4f877', true],
                ['Kentang Goreng Keju', 33000, '1573080496219-bb080dd4f877', true],
                ['Onion Rings', 30000, '1639024471283-03518883512d', true],
                ['Chicken Nuggets', 32000, '1562967914-608f82629710', true],
                ['Cheese Sticks', 35000, '1541592106381-b31e9677c0e5', true],
                ['Garlic Bread', 25000, '1619535860434-ba1d8fa12536', true],
                ['Nachos Cheese', 38000, '1513456852971-30c0b8199d4d', true],
                ['Popcorn Chicken', 34000, '1562967914-608f82629710', true],
                ['Calamari Goreng', 42000, '1599487488170-d11ec9c172f0', true],
            ],
            'Salad' => [
                ['Caesar Salad', 42000, '1550304943-4f24f54ddde9', true],
                ['Greek Salad', 45000, '1512621776951-a57141f2eefd', true],
            ],
            'Minuman' => [
                ['Lemon Iced Tea', 18000, '1556679343-c7306c1976bc', true],
                ['Es Teh Manis', 10000, '1556679343-c7306c1976bc', true],
                ['Es Jeruk', 15000, '1600271886742-f049cd451bba', true],
                ['Lemonade Mint', 22000, '1544145945-f90425340c7e', true],
                ['Kopi Susu Gula Aren', 25000, '1461023058943-07fcbe16d735', true],
                ['Americano', 22000, '1509042239860-f550ce710b93', true],
                ['Cappuccino', 28000, '1495474472287-4d71bcdd2085', true],
                ['Matcha Latte', 30000, '1515823064-d6e0c04616a7', true],
                ['Chocolate Ice', 26000, '1542990253-0d0f5be5f0ed', true],
                ['Jus Alpukat', 25000, '1546173159-315724a31696', true],
                ['Strawberry Smoothie', 30000, '1553530666-ba11a7da3888', true],
                ['Air Mineral', 8000, '1548839140-29a749e1cf4d', true],
            ],
            'Dessert' => [
                ['Choco Brownie Ice Cream', 32000, '1624353365286-3f8d62daad51', true],
                ['Tiramisu', 38000, '1571877227200-a0d98ea607e9', true],
                ['Panna Cotta', 30000, '1551024506-0bccd828d307', true],
                ['Cheesecake Blueberry', 40000, '1533134242443-d4fd215305ad', true],
                ['Pisang Goreng Keju', 25000, '1563805042-7684c019e1cb', true],
            ],
            'Nasi' => [
                ['Nasi Goreng Spesial', 45000, '1512058564366-18510be2db19', true],
                ['Nasi Goreng Seafood', 52000, '1603133872878-684f208fb84b', true],
                ['Nasi Goreng Kampung', 38000, '1512058564366-18510be2db19', true],
                ['Nasi Ayam Geprek', 35000, '1604908176997-125f25cc6f3d', true],
                ['Nasi Ayam Teriyaki', 42000, '1546069901-ba9599a7e63c', true],
                ['Nasi Ayam Katsu', 42000, '1604908176997-125f25cc6f3d', true],
                ['Nasi Beef Blackpepper', 55000, '1504674900247-0877df9cc836', true],
                ['Nasi Cumi Hitam', 48000, '1603133872878-684f208fb84b', true],
                ['Nasi Ayam Bakar Madu', 45000, '1555939594-58d7cb561ad1', true],
                ['Nasi Rendang', 50000, '1546069901-ba9599a7e63c', true],
            ],
        ];

        // kategori yang tampil di navbar beranda (urutan 1 - 6), sesuai UI
        $navbar = ['Pizza', 'Pasta', 'Burger', 'Camilan', 'Minuman', 'Dessert'];

        foreach ($menu as $namaKategori => $daftar) {
            $urutan = array_search($namaKategori, $navbar, true);
            $kategori = Kategori::updateOrCreate(['nama_kategori' => $namaKategori], [
                'tampil_navbar' => $urutan !== false,
                'urutan_navbar' => $urutan === false ? null : $urutan + 1,
            ]);

            foreach ($daftar as [$nama, $harga, $fotoId, $tersedia]) {
                Menu::updateOrCreate(
                    ['nama_menu' => $nama],
                    ['id_kategori' => $kategori->id, 'harga' => $harga, 'foto' => $foto($fotoId), 'tersedia' => $tersedia],
                );
            }
        }

        // ongkir = radius_km × tarif_per_km → Rp 9.000, Rp 15.000, Rp 22.000
        foreach ([['Zona A', 3, 3000], ['Zona B', 6, 2500], ['Zona C', 10, 2200]] as [$nama, $radius, $tarif]) {
            ZonaPengiriman::updateOrCreate(['nama_zona' => $nama], ['radius_km' => $radius, 'tarif_per_km' => $tarif]);
        }

        PengaturanSistem::aktif()->update([
            'pajak' => 10,
            'biaya_layanan' => 5, // persen dari subtotal
            'jam_buka' => '10:00',
            'jam_tutup' => '22:00',
        ]);
    }
}
