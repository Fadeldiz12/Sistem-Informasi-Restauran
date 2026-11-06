<?php

namespace App\Http\Controllers\Dapur;

use App\Http\Controllers\Controller;
use App\Models\BahanBaku;
use App\Models\TiketDapur;
use App\Services\BahanBakuService;
use App\Services\DapurService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AntreanController extends Controller
{
    public function __construct(private DapurService $dapur, private BahanBakuService $bahan) {}

    public function index(Request $request): View
    {
        // Urutkan Antrean (FIFO): waktu_masuk paling lama di atas
        $antrean = TiketDapur::fifo()
            ->whereIn('status_masak', [TiketDapur::STATUS_ANTRE, TiketDapur::STATUS_DIMASAK])
            ->with(['pesanan.detail.menu', 'pesanan.pelanggan', 'pesanan.pengiriman.zona'])
            ->get();

        $tiket = $antrean->firstWhere('id', $request->integer('tiket')) ?? $antrean->first();

        return view('dapur.antrean', [
            'antrean' => $antrean,
            'tiket' => $tiket,
            'kebutuhan' => $tiket?->status_masak === TiketDapur::STATUS_ANTRE ? $this->bahan->kebutuhanUntukPesanan($tiket->pesanan) : [],
            'selesaiHariIni' => TiketDapur::where('status_masak', TiketDapur::STATUS_SELESAI)->whereDate('updated_at', today())->count(),
            'kritis' => BahanBaku::stokKritis()->orderBy('nama_bahan')->pluck('nama_bahan'),
        ]);
    }

    public function mulai(TiketDapur $tiket): RedirectResponse
    {
        $kurang = $this->dapur->mulaiMasak($tiket);
        $redirect = to_route('dapur.antrean', ['tiket' => $tiket->id]);

        // satu pesan saja: peringatan bila stok kurang, sukses bila cukup
        return $kurang
            ? $redirect->with('error', $tiket->kode().' tetap dimasak, tetapi stok '.collect($kurang)->join(', ', ' dan ')
                .' tidak mencukupi resep. Stok bahan itu dihabiskan (0) dan ditandai kritis, segera lakukan pemesanan ulang.')
            : $redirect->with('status', $tiket->kode().' sedang dimasak · stok bahan baku dikurangi sesuai resep');
    }

    public function selesai(TiketDapur $tiket): RedirectResponse
    {
        $this->dapur->selesaiMasak($tiket);
        $pesanan = $tiket->pesanan;

        return to_route('dapur.antrean')->with('status', $tiket->kode().' siap'.($pesanan->isDelivery() ? ' · masuk antrean kurir' : ' · kasir diberi tahu'));
    }
}
