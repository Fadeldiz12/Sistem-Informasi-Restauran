<?php

namespace App\Services;

use App\Models\Pesanan;
use App\Models\TiketDapur;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Ubah Status Masakan: antre → dimasak → selesai.
 */
class DapurService
{
    public function mulaiMasak(TiketDapur $tiket): void
    {
        if ($tiket->status_masak !== TiketDapur::STATUS_ANTRE) {
            throw ValidationException::withMessages(['tiket' => 'Tiket ini sudah mulai dimasak.']);
        }

        $tiket->update(['status_masak' => TiketDapur::STATUS_DIMASAK]);
    }

    /**
     * Selesai dimasak: pesanan menjadi "siap". Untuk Delivery, waktu_siap diisi
     * sehingga pesanan masuk antrean tugas kurir (FIFO dari waktu_siap).
     */
    public function selesaiMasak(TiketDapur $tiket): void
    {
        if ($tiket->status_masak !== TiketDapur::STATUS_DIMASAK) {
            throw ValidationException::withMessages(['tiket' => 'Mulai masak dulu sebelum menandai selesai.']);
        }

        DB::transaction(function () use ($tiket) {
            $tiket->update(['status_masak' => TiketDapur::STATUS_SELESAI]);

            $pesanan = $tiket->pesanan()->lockForUpdate()->first();
            if ($pesanan->status === Pesanan::STATUS_DIPROSES) {
                $pesanan->update(['status' => Pesanan::STATUS_SIAP]);
            }
            if ($pesanan->isDelivery()) {
                $pesanan->pengiriman()->whereNull('waktu_siap')->update(['waktu_siap' => now()]);
            }
        });
    }
}
