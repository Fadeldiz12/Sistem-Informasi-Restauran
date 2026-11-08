<?php

namespace App\Http\Controllers\Pelanggan;

use App\Http\Controllers\Controller;
use App\Models\Pesanan;
use App\Support\TimelinePesanan;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TrackingController extends Controller
{
    public function __invoke(Request $request): View
    {
        $daftar = $request->user()->pesanan()
            ->with(['detail', 'pembayaran', 'pengiriman.zona', 'pengiriman.kurir', 'tiketDapur'])
            ->latest('waktu_pesan')->latest('id')
            ->limit(30)->get();

        $aktif = $daftar->firstWhere('id', Pesanan::idDariKode($request->query('pesanan')))
            ?? $daftar->first(fn ($p) => ! in_array($p->status, [Pesanan::STATUS_SELESAI, Pesanan::STATUS_DIBATALKAN], true))
            ?? $daftar->first();

        return view('pelanggan.tracking', [
            'daftar' => $daftar,
            'pesanan' => $aktif?->load('detail.menu'),
            'timeline' => $aktif ? TimelinePesanan::untuk($aktif) : [],
        ]);
    }
}
