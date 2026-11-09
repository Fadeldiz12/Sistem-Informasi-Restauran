<?php

namespace App\Http\Controllers\Pelanggan;

use App\Http\Controllers\Controller;
use App\Models\Pesanan;
use App\Support\PesananTamu;
use App\Support\TimelinePesanan;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TrackingController extends Controller
{
    public function __invoke(Request $request): View
    {
        $diminta = Pesanan::idDariKode($request->query('pesanan'));
        // tamu membuka link lacak (mis. dari email / perangkat lain): pesanannya diingat di browser ini
        if (! $request->user() && $diminta && ($tamu = Pesanan::find($diminta)) && $tamu->isTamu()) {
            PesananTamu::ingat($tamu);
        }

        $daftar = PesananTamu::milik($request->user())
            ->with(['detail', 'pembayaran', 'pengiriman.zona', 'pengiriman.kurir', 'tiketDapur'])
            ->latest('waktu_pesan')->latest('id')
            ->limit(30)->get();

        $aktif = $daftar->firstWhere('id', $diminta)
            ?? $daftar->first(fn ($p) => ! in_array($p->status, [Pesanan::STATUS_SELESAI, Pesanan::STATUS_DIBATALKAN], true))
            ?? $daftar->first();

        return view('pelanggan.tracking', [
            'daftar' => $daftar,
            'pesanan' => $aktif?->load('detail.menu'),
            'timeline' => $aktif ? TimelinePesanan::untuk($aktif) : [],
        ]);
    }
}
