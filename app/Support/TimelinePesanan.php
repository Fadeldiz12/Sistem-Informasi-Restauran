<?php

namespace App\Support;

use App\Models\Pengiriman;
use App\Models\Pesanan;
use App\Models\TiketDapur;

/**
 * Langkah-langkah status pesanan untuk halaman Lacak Pesanan.
 * Dine In / Take Away: dibuat → dikonfirmasi → dimasak → siap → selesai.
 * Delivery: dibuat → dikonfirmasi → dimasak → siap diantar → dalam perjalanan → diterima.
 */
class TimelinePesanan
{
    /**
     * @return array<int, array{label:string, ket:string, waktu:?string, state:string, ikon:?string}>
     */
    public static function untuk(Pesanan $p): array
    {
        $tiket = $p->tiketDapur;
        $kirim = $p->pengiriman;
        $urut = [Pesanan::STATUS_MENUNGGU, Pesanan::STATUS_DIPROSES, Pesanan::STATUS_SIAP, Pesanan::STATUS_DIANTAR, Pesanan::STATUS_SELESAI];
        $posisi = array_search($p->status, $urut, true);
        $sampai = fn (string $status) => $posisi !== false && $posisi >= array_search($status, $urut, true);
        $jam = fn ($t) => $t?->format('H:i');

        $siapLabel = match ($p->jenis_layanan) {
            Pesanan::LAYANAN_DINE_IN => 'Siap Disajikan',
            Pesanan::LAYANAN_TAKE_AWAY => 'Siap Diambil',
            default => 'Siap Diantar',
        };

        $langkah = [
            ['label' => 'Pesanan Dibuat', 'ket' => 'Menunggu konfirmasi kasir', 'waktu' => $jam($p->waktu_pesan), 'done' => true, 'ikon' => null],
            ['label' => 'Dikonfirmasi', 'ket' => 'Kasir menerima pesanan, tiket dikirim ke dapur', 'waktu' => $jam($tiket?->waktu_masuk), 'done' => $sampai(Pesanan::STATUS_DIPROSES), 'ikon' => null],
            ['label' => 'Dimasak', 'ket' => 'Pesanan diolah sesuai antrean dapur (FIFO)', 'waktu' => null,
                'done' => $sampai(Pesanan::STATUS_SIAP) || in_array($tiket?->status_masak, [TiketDapur::STATUS_DIMASAK, TiketDapur::STATUS_SELESAI], true), 'ikon' => 'i-dine'],
            ['label' => $siapLabel, 'ket' => $p->isDelivery() ? 'Pesanan masuk antrean tugas kurir (FIFO)' : ($p->jenis_layanan === Pesanan::LAYANAN_DINE_IN ? 'Pesanan diantar ke meja' : 'Ambil pesanan di kasir'),
                'waktu' => $jam($kirim?->waktu_siap), 'done' => $sampai(Pesanan::STATUS_SIAP), 'ikon' => null],
        ];

        if ($p->isDelivery()) {
            $langkah[] = ['label' => 'Dalam Perjalanan', 'ket' => $kirim?->kurir ? 'Kurir '.$kirim->kurir->nama.' menuju alamatmu' : 'Menunggu kurir mengambil pesanan',
                'waktu' => null, 'done' => $sampai(Pesanan::STATUS_DIANTAR), 'ikon' => 'i-truck'];
            $diterima = $kirim?->status_kirim === Pengiriman::STATUS_DITERIMA;
            $langkah[] = ['label' => 'Diterima', 'ket' => 'Kurir mengunggah bukti pengiriman', 'waktu' => $diterima ? $jam($kirim->updated_at) : null,
                'done' => $sampai(Pesanan::STATUS_SELESAI) || $kirim?->status_kirim === Pengiriman::STATUS_DITERIMA, 'ikon' => null];
        } else {
            $langkah[] = ['label' => 'Selesai', 'ket' => 'Pesanan sudah diterima pelanggan', 'waktu' => null, 'done' => $sampai(Pesanan::STATUS_SELESAI), 'ikon' => null];
        }

        $batal = $p->status === Pesanan::STATUS_DIBATALKAN;
        $sekarangDitandai = false;

        return array_map(function ($l) use ($batal, &$sekarangDitandai) {
            $state = $l['done'] ? 'done' : 'todo';
            if (! $l['done'] && ! $batal && ! $sekarangDitandai) {
                $state = 'now';
                $sekarangDitandai = true;
            }
            unset($l['done']);

            return $l + ['state' => $state];
        }, $langkah);
    }
}
