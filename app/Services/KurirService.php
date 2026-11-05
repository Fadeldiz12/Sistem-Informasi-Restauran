<?php

namespace App\Services;

use App\Models\Pembayaran;
use App\Models\Pengiriman;
use App\Models\Pesanan;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

/**
 * Tugas pengiriman kurir: antrean FIFO (waktu_siap), terima tugas, konfirmasi diterima dengan foto bukti.
 */
class KurirService
{
    public const DISK = 'local';

    /**
     * Antrean tugas: pesanan Delivery yang siap diantar dan belum diambil kurir, urut FIFO.
     */
    public static function antrean(): Builder
    {
        return Pengiriman::query()->fifo()
            ->where('status_kirim', Pengiriman::STATUS_MENUNGGU_KURIR)
            ->whereNull('id_kurir')
            ->whereHas('pesanan', fn ($q) => $q->where('status', Pesanan::STATUS_SIAP));
    }

    /**
     * Terima tugas. Hanya tugas teratas (FIFO #1) yang boleh diambil.
     */
    public function terima(Pengiriman $kirim, User $kurir): Pengiriman
    {
        return DB::transaction(function () use ($kirim, $kurir) {
            $teratas = self::antrean()->lockForUpdate()->first();

            if (! $teratas || $teratas->id !== $kirim->id) {
                throw ValidationException::withMessages(['tugas' => self::antrean()->whereKey($kirim->id)->exists()
                    ? 'Ambil tugas sesuai urutan FIFO. Tugas #1 harus diterima lebih dulu.'
                    : 'Tugas ini sudah diambil kurir lain atau belum siap diantar.']);
            }

            $teratas->update(['id_kurir' => $kurir->id, 'status_kirim' => Pengiriman::STATUS_DALAM_PERJALANAN]);
            $teratas->pesanan()->update(['status' => Pesanan::STATUS_DIANTAR]);

            return $teratas;
        });
    }

    /**
     * Pesanan diterima pelanggan: foto bukti wajib. Pesanan tunai yang belum lunas dibayar ke kurir.
     */
    public function diterima(Pengiriman $kirim, User $kurir, UploadedFile $foto, bool $uangDiterima = false): Pengiriman
    {
        if ($kirim->id_kurir !== $kurir->id) {
            abort(403, 'Tugas ini bukan milikmu.');
        }
        if ($kirim->status_kirim !== Pengiriman::STATUS_DALAM_PERJALANAN) {
            throw ValidationException::withMessages(['tugas' => 'Pengiriman ini sudah selesai.']);
        }

        $pesanan = $kirim->pesanan()->with('pembayaran')->first();
        $tagihTunai = $this->perluTagihTunai($pesanan);
        if ($tagihTunai && ! $uangDiterima) {
            throw ValidationException::withMessages(['uang_diterima' => 'Konfirmasi dulu bahwa uang tunai sudah diterima dari pelanggan.']);
        }

        $path = $foto->store('bukti-kirim', self::DISK);

        try {
            DB::transaction(function () use ($kirim, $kurir, $pesanan, $path, $tagihTunai) {
                $kirim->update(['status_kirim' => Pengiriman::STATUS_DITERIMA, 'bukti_kirim' => $path]);
                $pesanan->update(['status' => Pesanan::STATUS_SELESAI]);

                if ($tagihTunai) {
                    // uang dibawa kurir sampai disetor ke kasir (menu Setoran Kurir)
                    $pesanan->pembayaran()->updateOrCreate([], [
                        'id_kurir' => $kurir->id,
                        'metode' => Pembayaran::METODE_TUNAI,
                        'jumlah' => $pesanan->total,
                        'status_bayar' => Pembayaran::STATUS_LUNAS,
                        'waktu_bayar' => now(),
                    ]);
                }
            });
        } catch (\Throwable $e) {
            Storage::disk(self::DISK)->delete($path);
            throw $e;
        }

        return $kirim;
    }

    /**
     * Bayar tunai di tempat (COD) dan belum dilunasi kasir.
     */
    public function perluTagihTunai(Pesanan $pesanan): bool
    {
        return ! $pesanan->isLunas()
            && ($pesanan->pembayaran?->metode ?? Pembayaran::METODE_TUNAI) === Pembayaran::METODE_TUNAI;
    }
}
