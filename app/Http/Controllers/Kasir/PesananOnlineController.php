<?php

namespace App\Http\Controllers\Kasir;

use App\Http\Controllers\Controller;
use App\Models\Pesanan;
use App\Services\PesananService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PesananOnlineController extends Controller
{
    public function __construct(private PesananService $pesanan) {}

    public function index(Request $request): View
    {
        $antrean = Pesanan::menungguKonfirmasi()->with(['pelanggan', 'pembayaran', 'pengiriman.zona'])->get();
        $dipilih = $antrean->firstWhere('id', Pesanan::idDariKode($request->query('pesanan'))) ?? $antrean->first();

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

    /**
     * Pilihan alasan menolak pesanan online; "Lainnya" wajib diisi teks.
     */
    public const ALASAN_TOLAK = ['Menu habis', 'Di luar jangkauan pengiriman', 'Restoran akan tutup', 'Lainnya'];

    public function tolak(Request $request, Pesanan $pesanan): RedirectResponse
    {
        $data = $request->validate([
            'alasan' => ['required', Rule::in(self::ALASAN_TOLAK)],
            'alasan_lain' => ['nullable', 'required_if:alasan,Lainnya', 'string', 'max:200'],
        ], [
            'alasan.required' => 'Pilih alasan penolakan.',
            'alasan.in' => 'Pilih alasan penolakan.',
            'alasan_lain.required_if' => 'Tulis alasan penolakan.',
        ]);

        $this->pesanan->tolak($pesanan, $request->user(), $data['alasan'] === 'Lainnya' ? $data['alasan_lain'] : $data['alasan']);

        return to_route('kasir.online')->with('status', "{$pesanan->kode()} ditolak · status pesanan menjadi Dibatalkan");
    }
}
