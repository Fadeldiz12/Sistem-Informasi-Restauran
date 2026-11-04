<?php

namespace App\Http\Controllers\Kasir;

use App\Http\Controllers\Controller;
use App\Models\Pesanan;
use App\Services\PesananService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PesananOnlineController extends Controller
{
    public function __construct(private PesananService $pesanan) {}

    public function index(Request $request): View
    {
        $antrean = Pesanan::menungguKonfirmasi()->with(['pelanggan', 'pembayaran', 'pengiriman.zona'])->get();
        $dipilih = $antrean->firstWhere('id', $request->integer('pesanan')) ?? $antrean->first();

        return view('kasir.online', [
            'antrean' => $antrean,
            'pesanan' => $dipilih?->load('detail.menu'),
        ]);
    }

    public function terima(Request $request, Pesanan $pesanan): RedirectResponse
    {
        $this->pesanan->konfirmasi($pesanan, $request->user());

        return to_route('kasir.online')->with('status', "{$pesanan->kode()} dikonfirmasi · tiket dikirim ke antrean dapur");
    }

    public function tolak(Request $request, Pesanan $pesanan): RedirectResponse
    {
        $this->pesanan->tolak($pesanan, $request->user());

        return to_route('kasir.online')->with('status', "{$pesanan->kode()} ditolak · status pesanan menjadi Dibatalkan");
    }
}
