<?php

namespace App\Http\Controllers\Kasir;

use App\Http\Controllers\Controller;
use App\Models\Pembayaran;
use App\Models\Pesanan;
use App\Models\TutupKasir;
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
        $daftar = Pesanan::belumLunas()->with(['pelanggan', 'pembayaran', 'pengiriman.zona', 'kasir', 'tiketDapur'])->orderBy('waktu_pesan')->get();
        $dipilih = $daftar->firstWhere('id', Pesanan::idDariKode($request->query('pesanan'))) ?? $daftar->first();

        $struk = session('struk') ? Pesanan::with(['detail.menu', 'pembayaran.kasir', 'pelanggan', 'pengiriman.zona'])->find(session('struk')) : null;

        $lunasHariIni = Pesanan::with(['pembayaran', 'pelanggan'])
            ->whereHas('pembayaran', fn ($b) => $b->where('status_bayar', Pembayaran::STATUS_LUNAS)->where('waktu_bayar', '>=', TutupKasir::batas()))
            ->get()->sortByDesc(fn ($p) => $p->pembayaran->waktu_bayar)->values();

        return view('kasir.pembayaran', [
            'tab' => $request->query('tab') === 'lunas' ? 'lunas' : 'belum',
            'daftar' => $daftar,
            'lunasHariIni' => $lunasHariIni,
            'pesanan' => $dipilih?->load('detail.menu'),
            'struk' => $struk,
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

        return $request->boolean('cetak') ? $redirect->with('struk', $pesanan->id) : $redirect;
    }

    /**
     * Halaman struk yang bisa dicetak (dan dicetak ulang). Uang diterima & kembalian diambil dari database.
     */
    public function struk(Pesanan $pesanan): View
    {
        abort_unless($pesanan->isLunas(), 404);
        abort_if($pesanan->pembayaran->waktu_bayar < TutupKasir::batas(), 403, 'Transaksi ini sudah masuk tutup hari (EOD). Struk lama hanya bisa dilihat owner.');

        return view('kasir.struk', [
            'pesanan' => $pesanan->load(['detail.menu', 'pembayaran.kasir', 'pelanggan', 'pengiriman.zona']),
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
