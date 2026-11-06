<?php

namespace App\Http\Controllers\Kasir;

use App\Http\Controllers\Controller;
use App\Models\Pesanan;
use Illuminate\Http\JsonResponse;

/**
 * Dicek berkala oleh semua halaman kasir: pesanan Dine In / Take Away yang sudah siap dari dapur.
 */
class NotifikasiController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $siap = Pesanan::where('status', Pesanan::STATUS_SIAP)->where('jenis_layanan', '!=', Pesanan::LAYANAN_DELIVERY)
            ->orderBy('updated_at')->get(['id', 'jenis_layanan', 'nomor_meja', 'nama_pemesan', 'id_pelanggan']);

        return response()->json([
            'siap' => $siap->count(),
            'pesanan' => $siap->map(fn ($p) => ['id' => $p->id, 'kode' => $p->kode(), 'layanan' => $p->layananDetail()])->values(),
        ]);
    }
}
