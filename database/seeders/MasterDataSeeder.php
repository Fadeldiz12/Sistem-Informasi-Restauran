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
            ],
            'Pasta' => [
                ['Penne Bolognese', 59000, '1621996346565-e3dbc646d9a9', true],
                ['Spaghetti Seafood', 69000, '1563379926898-05f4575a45d8', false],
            ],
            'Burger' => [
                ['Classic Beef Burger', 55000, '1568901346375-23c9450c58cd', true],
            ],
            'Camilan' => [
                ['Chicken Wings BBQ', 45000, '1608039755401-742074f0548d', false],
                ['Kentang Goreng', 28000, '1573080496219-bb080dd4f877', true],
            ],
            'Salad' => [
                ['Caesar Salad', 42000, '1550304943-4f24f54ddde9', true],
            ],
            'Minuman' => [
                ['Lemon Iced Tea', 18000, '1556679343-c7306c1976bc', true],
            ],
            'Dessert' => [
                ['Choco Brownie Ice Cream', 32000, '1624353365286-3f8d62daad51', true],
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
