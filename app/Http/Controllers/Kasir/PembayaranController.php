<?php

namespace App\Http\Controllers\Kasir;

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

    public function index(Request $request): View
    {
        $daftar = Pesanan::belumLunas()->with(['pelanggan', 'pembayaran', 'pengiriman.zona', 'kasir'])->orderBy('waktu_pesan')->get();
        $dipilih = $daftar->firstWhere('id', $request->integer('pesanan')) ?? $daftar->first();

        $struk = session('struk') ? Pesanan::with(['detail.menu', 'pembayaran.kasir', 'pelanggan', 'pengiriman.zona'])->find(session('struk')) : null;

        return view('kasir.pembayaran', [
            'daftar' => $daftar,
            'pesanan' => $dipilih?->load('detail.menu'),
            'struk' => $struk,
            'uangDiterima' => session('uang_diterima'),
        ]);
    }

    public function lunas(Request $request, Pesanan $pesanan): RedirectResponse
    {
        $request->merge(['uang_diterima' => str_replace(['.', ' '], '', (string) $request->input('uang_diterima'))]);
        $data = $request->validate([
            'metode' => ['required', Rule::in(array_keys(Pembayaran::LABEL_METODE))],
            'uang_diterima' => ['nullable', 'required_if:metode,'.Pembayaran::METODE_TUNAI, 'numeric', 'min:0'],
        ], [
            'uang_diterima.required_if' => 'Masukkan jumlah uang yang diterima.',
        ]);

        $this->pesanan->lunasi($pesanan, $request->user(), $data['metode'], isset($data['uang_diterima']) ? (float) $data['uang_diterima'] : null);

        $redirect = to_route('kasir.pembayaran')->with('status', "{$pesanan->kode()} lunas · pemasukan tercatat");

        return $request->boolean('cetak')
            ? $redirect->with('struk', $pesanan->id)->with('uang_diterima', $data['metode'] === Pembayaran::METODE_TUNAI ? (float) $data['uang_diterima'] : null)
            : $redirect;
    }

    public function tolakBukti(Request $request, Pesanan $pesanan): RedirectResponse
    {
        $this->pesanan->tolakBukti($pesanan, $request->user());

        return to_route('kasir.pembayaran')->with('status', "Bukti pembayaran {$pesanan->kode()} ditolak · pelanggan diminta unggah ulang");
    }

    /**
     * Halaman struk yang bisa dicetak.
     */
    public function struk(Request $request, Pesanan $pesanan): View
    {
        abort_unless($pesanan->isLunas(), 404);

        return view('kasir.struk', [
            'pesanan' => $pesanan->load(['detail.menu', 'pembayaran.kasir', 'pelanggan', 'pengiriman.zona']),
            'uangDiterima' => $request->filled('tunai') ? (float) $request->query('tunai') : null,
        ]);
    }

    /**
     * Pesanan Dine In / Take Away yang sudah siap dan lunas diserahkan ke pelanggan.
     */
    public function selesai(Pesanan $pesanan): RedirectResponse
    {
        $this->pesanan->selesaikan($pesanan);

        return back()->with('status', "{$pesanan->kode()} selesai diserahkan");
    }
}
