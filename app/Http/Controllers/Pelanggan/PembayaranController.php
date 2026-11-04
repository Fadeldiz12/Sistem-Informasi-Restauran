<?php

namespace App\Http\Controllers\Pelanggan;

use App\Http\Controllers\Controller;
use App\Models\Pembayaran;
use App\Models\Pesanan;
use App\Services\MidtransService;
use App\Services\PesananService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PembayaranController extends Controller
{
    public function __construct(private PesananService $pesanan, private MidtransService $midtrans) {}

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
            'midtransAktif' => $this->midtrans->aktif(),
            'sandbox' => $this->midtrans->sandbox(),
            'bank' => MidtransService::BANK,
        ]);
    }

    public function store(Request $request, Pesanan $pesanan): RedirectResponse
    {
        $this->milikSendiri($request, $pesanan);

        $data = $request->validate([
            'metode' => ['required', Rule::in(array_keys(Pembayaran::LABEL_METODE))],
            'bank' => ['nullable', 'required_if:metode,'.Pembayaran::METODE_TRANSFER, Rule::in(array_keys(MidtransService::BANK))],
        ], [
            'bank.required_if' => 'Pilih bank untuk Virtual Account.',
        ]);

        $bayar = $this->pesanan->pilihPembayaran($pesanan, $data['metode'], $data['bank'] ?? null);

        return to_route('pelanggan.pembayaran', $pesanan)->with('status', $bayar->metode === Pembayaran::METODE_TUNAI
            ? 'Metode tunai disimpan. Pesanan menunggu konfirmasi kasir.'
            : 'Silakan selesaikan pembayaran '.$bayar->metodeLabel().'.');
    }

    /**
     * Dipanggil berkala oleh halaman pembayaran untuk mengecek apakah VA/QRIS sudah dibayar.
     */
    public function status(Request $request, Pesanan $pesanan): JsonResponse
    {
        $this->milikSendiri($request, $pesanan);
        $bayar = $pesanan->pembayaran;

        if ($bayar?->isOtomatis()) {
            // dibatasi agar tidak membanjiri Midtrans
            $kunci = 'cek-bayar:'.$bayar->id;
            if (Cache::add($kunci, true, now()->addSeconds(4))) {
                try {
                    $bayar = $this->pesanan->cekStatusGateway($bayar);
                } catch (\RuntimeException) {
                    // koneksi gagal: coba lagi di permintaan berikutnya
                }
            }
        }

        return response()->json([
            'status_bayar' => $bayar?->status_bayar,
            'lunas' => $bayar?->status_bayar === Pembayaran::STATUS_LUNAS,
            'gagal' => $bayar && ($bayar->status_bayar === Pembayaran::STATUS_DITOLAK || $bayar->isKedaluwarsa()),
            'status_pesanan' => $pesanan->fresh()->status,
        ]);
    }

    private function milikSendiri(Request $request, Pesanan $pesanan): void
    {
        abort_unless($pesanan->id_pelanggan === $request->user()->id, 404);
    }
}
