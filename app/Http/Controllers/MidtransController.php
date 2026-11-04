<?php

namespace App\Http\Controllers;

use App\Models\Pembayaran;
use App\Services\MidtransService;
use App\Services\PesananService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Menerima notifikasi pembayaran (HTTP notification) dari Midtrans.
 * Atur Payment Notification URL di dashboard Midtrans: {APP_URL}/midtrans/notifikasi
 */
class MidtransController extends Controller
{
    public function __invoke(Request $request, MidtransService $midtrans, PesananService $pesanan): JsonResponse
    {
        $data = $request->all();

        if (! $midtrans->signatureValid($data)) {
            Log::warning('Notifikasi Midtrans dengan signature tidak valid', ['order_id' => $data['order_id'] ?? null]);

            return response()->json(['message' => 'Signature tidak valid'], 403);
        }

        $bayar = Pembayaran::where('kode_transaksi', $data['order_id'])->first();
        if (! $bayar) {
            // transaksi lama yang sudah diganti pelanggan: abaikan
            return response()->json(['message' => 'Transaksi tidak dikenal']);
        }

        if ((int) round((float) ($data['gross_amount'] ?? 0)) !== (int) round((float) $bayar->jumlah)) {
            Log::warning('Nominal notifikasi Midtrans tidak cocok', ['order_id' => $data['order_id']]);

            return response()->json(['message' => 'Nominal tidak cocok'], 422);
        }

        $pesanan->terapkanStatusGateway($bayar, $data['transaction_status'] ?? null, $data['fraud_status'] ?? null);

        return response()->json(['message' => 'OK']);
    }
}
