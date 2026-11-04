<?php

namespace App\Http\Controllers\Dapur;

use App\Http\Controllers\Controller;
use App\Models\BahanBaku;
use App\Models\TiketDapur;
use App\Services\DapurService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AntreanController extends Controller
{
    public function __construct(private DapurService $dapur) {}

    public function index(Request $request): View
    {
        // Urutkan Antrean (FIFO): waktu_masuk paling lama di atas
        $antrean = TiketDapur::fifo()
            ->whereIn('status_masak', [TiketDapur::STATUS_ANTRE, TiketDapur::STATUS_DIMASAK])
            ->with(['pesanan.detail.menu', 'pesanan.pelanggan', 'pesanan.pengiriman.zona'])
            ->get();

        return view('dapur.antrean', [
            'antrean' => $antrean,
            'tiket' => $antrean->firstWhere('id', $request->integer('tiket')) ?? $antrean->first(),
            'selesaiHariIni' => TiketDapur::where('status_masak', TiketDapur::STATUS_SELESAI)->whereDate('updated_at', today())->count(),
            'kritis' => BahanBaku::stokKritis()->orderBy('nama_bahan')->pluck('nama_bahan'),
        ]);
    }

    public function mulai(TiketDapur $tiket): RedirectResponse
    {
        $this->dapur->mulaiMasak($tiket);

        return to_route('dapur.antrean', ['tiket' => $tiket->id])->with('status', $tiket->kode().' sedang dimasak');
    }

    public function selesai(TiketDapur $tiket): RedirectResponse
    {
        $this->dapur->selesaiMasak($tiket);
        $pesanan = $tiket->pesanan;

        return to_route('dapur.antrean')->with('status', $tiket->kode().' siap'.($pesanan->isDelivery() ? ' · masuk antrean kurir' : ' · kasir diberi tahu'));
    }
}
