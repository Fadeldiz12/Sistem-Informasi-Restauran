<?php

namespace Database\Seeders;

use App\Models\Menu;
use App\Models\Pesanan;
use App\Models\User;
use App\Models\ZonaPengiriman;
use App\Services\PesananService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Riwayat penjualan contoh (6 bulan terakhir sampai jam sekarang) agar dashboard owner dan
 * laporan keuangan langsung berisi data. Hanya berjalan bila belum ada pesanan sama sekali.
 */
class PenjualanContohSeeder extends Seeder
{
    public function run(PesananService $svc): void
    {
        if (Pesanan::exists()) {
            $this->command?->info('Pesanan sudah ada, data penjualan contoh dilewati.');

            return;
        }

        mt_srand(2026);
        $menu = Menu::all(['id', 'nama_menu', 'harga'])->values();
        $zona = ZonaPengiriman::orderBy('radius_km')->get();
        $kasir = User::where('role', User::ROLE_KASIR)->value('id');
        $kurir = User::where('role', User::ROLE_KURIR)->value('id');
        $pelanggan = User::where('role', User::ROLE_PELANGGAN)->value('id');
        if ($menu->isEmpty() || ! $kasir) {
            return;
        }

        // menu favorit lebih sering dipesan
        $bobot = $menu->mapWithKeys(fn ($m, $i) => [$i => max(1, 10 - $i)])->all();
        $pilihMenu = function () use ($bobot, $menu) {
            $r = mt_rand(1, array_sum($bobot));
            foreach ($bobot as $i => $b) {
                if (($r -= $b) <= 0) {
                    return $menu[$i];
                }
            }

            return $menu[0];
        };
        $jamRamai = [11, 12, 12, 13, 13, 14, 17, 18, 18, 19, 19, 20, 21, 10, 15, 16];

        DB::transaction(function () use ($svc, $pilihMenu, $jamRamai, $zona, $kasir, $kurir, $pelanggan) {
            for ($h = 180; $h >= 0; $h--) {
                $hari = today()->subDays($h);
                $jumlahPesanan = mt_rand(8, 12) + ($hari->isWeekend() ? mt_rand(4, 6) : 0) + intdiv(180 - $h, 45);

                for ($n = 0; $n < $jumlahPesanan; $n++) {
                    $waktu = $hari->copy()->setTime($jamRamai[array_rand($jamRamai)], mt_rand(0, 59));
                    if ($waktu->copy()->addHour()->isFuture()) {
                        continue; // hari ini: hanya pesanan yang sudah selesai sebelum sekarang
                    }
                    $r = mt_rand(1, 100);
                    $layanan = $r <= 48 ? 'dine_in' : ($r <= 78 || ! $pelanggan || $zona->isEmpty() ? 'take_away' : 'delivery');

                    $items = [];
                    foreach (range(1, mt_rand(1, 3)) as $_) {
                        $m = $pilihMenu();
                        $items[$m->id] = ['menu' => $m, 'jumlah' => ($items[$m->id]['jumlah'] ?? 0) + mt_rand(1, 2)];
                    }
                    $subtotal = collect($items)->sum(fn ($i) => $i['jumlah'] * (float) $i['menu']->harga);
                    $z = $layanan === 'delivery' ? $zona[mt_rand(0, $zona->count() - 1)] : null;
                    $hitung = $svc->hitung($subtotal, $z ? $z->ongkir() : 0);

                    $idPesanan = DB::table('pesanan')->insertGetId([
                        'id_pelanggan' => $layanan === 'delivery' ? $pelanggan : null,
                        'id_kasir' => $kasir,
                        'jenis_layanan' => $layanan,
                        'nomor_meja' => $layanan === 'dine_in' ? str_pad((string) mt_rand(1, 20), 2, '0', STR_PAD_LEFT) : null,
                        'nama_pemesan' => $layanan === 'take_away' ? 'Pembeli' : null,
                        'waktu_pesan' => $waktu,
                        'subtotal' => $subtotal,
                        'ongkir' => $hitung['ongkir'],
                        'total' => $hitung['total'],
                        'status' => Pesanan::STATUS_SELESAI,
                        'created_at' => $waktu, 'updated_at' => $waktu,
                    ]);
                    DB::table('detail_pesanan')->insert(collect($items)->map(fn ($i) => [
                        'id_pesanan' => $idPesanan, 'id_menu' => $i['menu']->id, 'jumlah' => $i['jumlah'], 'catatan' => null,
                        'subtotal' => $i['jumlah'] * (float) $i['menu']->harga, 'created_at' => $waktu, 'updated_at' => $waktu,
                    ])->values()->all());

                    $bayar = $waktu->copy()->addMinutes(mt_rand(20, 50));
                    $metode = $layanan === 'delivery' ? (mt_rand(0, 1) ? 'transfer' : 'qris') : (mt_rand(1, 10) <= 6 ? 'tunai' : 'qris');
                    DB::table('pembayaran')->insert([
                        'id_pesanan' => $idPesanan, 'id_kasir' => $metode === 'tunai' ? $kasir : null, 'metode' => $metode,
                        'jumlah' => $hitung['total'], 'status_bayar' => 'lunas', 'waktu_bayar' => $bayar,
                        'bank' => $metode === 'transfer' ? 'bca' : null, 'created_at' => $bayar, 'updated_at' => $bayar,
                    ]);

                    if ($z) {
                        DB::table('pengiriman')->insert([
                            'id_pesanan' => $idPesanan, 'id_kurir' => $kurir, 'id_zona' => $z->id, 'alamat' => 'Jl. Contoh No. '.mt_rand(1, 99).', Medan',
                            'jarak_km' => $z->radius_km, 'waktu_siap' => $waktu->copy()->addMinutes(25), 'status_kirim' => 'diterima',
                            'bukti_kirim' => null, 'created_at' => $waktu, 'updated_at' => $waktu->copy()->addMinutes(55),
                        ]);
                    }
                }
            }
        });
    }
}
