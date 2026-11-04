<?php

namespace App\Http\Controllers\Pelanggan;

use App\Http\Controllers\Controller;
use App\Models\Pembayaran;
use App\Models\Pesanan;
use App\Services\PesananService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PembayaranController extends Controller
{
    public function __construct(private PesananService $pesanan) {}

    /**
     * Tanpa nomor pesanan: buka pesanan terbaru yang belum lunas.
     */
    public function terakhir(Request $request): RedirectResponse
    {
        $p = $request->user()->pesanan()
            ->where('status', '!=', Pesanan::STATUS_DIBATALKAN)
            ->where(fn ($q) => $q->whereDoesntHave('pembayaran')->orWhereHas('pembayaran', fn ($b) => $b->where('status_bayar', '!=', Pembayaran::STATUS_LUNAS)))
            ->latest('waktu_pesan')->latest('id')->first();

        return $p ? to_route('pelanggan.pembayaran', $p) : to_route('pelanggan.tracking')->with('status', 'Tidak ada pesanan yang perlu dibayar.');
    }

    public function show(Request $request, Pesanan $pesanan): View
    {
        $this->milikSendiri($request, $pesanan);
        $pesanan->load('detail.menu', 'pembayaran', 'pengiriman.zona');

        return view('pelanggan.pembayaran', [
            'pesanan' => $pesanan,
            'rekening' => config('resto.rekening'),
        ]);
    }

    public function store(Request $request, Pesanan $pesanan): RedirectResponse
    {
        $this->milikSendiri($request, $pesanan);

        $data = $request->validate([
            'metode' => ['required', Rule::in(array_keys(Pembayaran::LABEL_METODE))],
            'bukti_bayar' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        $bayar = $this->pesanan->pilihPembayaran($pesanan, $data['metode'], $request->file('bukti_bayar'));

        return to_route('pelanggan.pembayaran', $pesanan)->with('pembayaran_terkirim', $bayar->metode);
    }

    private function milikSendiri(Request $request, Pesanan $pesanan): void
    {
        abort_unless($pesanan->id_pelanggan === $request->user()->id, 404);
    }
}
