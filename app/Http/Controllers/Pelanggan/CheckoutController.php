<?php

namespace App\Http\Controllers\Pelanggan;

use App\Http\Controllers\Controller;
use App\Models\Pembayaran;
use App\Models\PengaturanSistem;
use App\Models\Pesanan;
use App\Models\ZonaPengiriman;
use App\Services\MidtransService;
use App\Services\PesananService;
use App\Support\Keranjang;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CheckoutController extends Controller
{
    public function __construct(private Keranjang $keranjang, private PesananService $pesanan, private MidtransService $midtrans) {}

    public function create(): View|RedirectResponse
    {
        $items = $this->keranjang->items();
        if ($items->isEmpty()) {
            return to_route('pelanggan.keranjang')->with('error', 'Keranjang masih kosong. Pilih menu dulu.');
        }

        $set = PengaturanSistem::aktif();
        $subtotal = (float) $items->sum('subtotal');

        return view('pelanggan.checkout', [
            'items' => $items,
            'ringkasan' => $this->keranjang->ringkasan($items),
            'hitung' => $this->pesanan->hitung($subtotal),
            'pengaturan' => $set,
            'buka' => $set->sedangBuka(),
            'zona' => ZonaPengiriman::orderBy('radius_km')->get(),
            'midtransAktif' => $this->midtrans->aktif(),
            'bank' => MidtransService::BANK,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'jenis_layanan' => ['required', Rule::in(array_keys(Pesanan::LABEL_LAYANAN))],
            'nomor_meja' => ['required_if:jenis_layanan,'.Pesanan::LAYANAN_DINE_IN, 'nullable', 'string', 'max:10'],
            'alamat' => ['required_if:jenis_layanan,'.Pesanan::LAYANAN_DELIVERY, 'nullable', 'string', 'max:500'],
            'id_zona' => ['required_if:jenis_layanan,'.Pesanan::LAYANAN_DELIVERY, 'nullable', 'integer', 'exists:zona_pengiriman,id'],
            'metode' => ['required', Rule::in(array_keys(Pembayaran::LABEL_METODE))],
            'bank' => ['nullable', 'required_if:metode,'.Pembayaran::METODE_TRANSFER, Rule::in(array_keys(MidtransService::BANK))],
        ], [
            'metode.required' => 'Pilih metode pembayaran.',
            'metode.in' => 'Pilih metode pembayaran.',
            'bank.required_if' => 'Pilih bank untuk Virtual Account.',
            'nomor_meja.required_if' => 'Isi nomor meja untuk Dine In.',
            'alamat.required_if' => 'Isi alamat pengiriman untuk Delivery.',
            'id_zona.required_if' => 'Pilih zona pengiriman untuk Delivery.',
        ]);

        $set = PengaturanSistem::aktif();
        if (! $set->sedangBuka()) {
            return back()->withInput()->with('error', 'Restoran sedang tutup. Pesanan bisa dibuat pada jam '.$set->jamLabel().'.');
        }

        $items = $this->keranjang->items();
        if ($items->isEmpty()) {
            return to_route('pelanggan.keranjang')->with('error', 'Keranjang masih kosong.');
        }

        // Pesanan dan metode pembayarannya dibuat sekaligus, supaya tidak ada pesanan tanpa metode bayar
        // yang tidak terlihat oleh kasir (QA A2-#1). Bila VA/QRIS gagal dibuat, pesanan ikut dibatalkan.
        $pesanan = DB::transaction(function () use ($items, $data, $request) {
            $pesanan = $this->pesanan->buat(
                $items->map(fn ($it) => ['id_menu' => $it['menu']->id, 'jumlah' => $it['jumlah'], 'catatan' => $it['catatan']])->all(),
                $data['jenis_layanan'],
                pelanggan: $request->user(),
                data: $data,
            );
            $this->pesanan->pilihPembayaran($pesanan, $data['metode'], $data['bank'] ?? null);

            return $pesanan;
        });

        $this->keranjang->kosongkan();

        return to_route('pelanggan.pembayaran', $pesanan)->with('pesanan_baru', true);
    }
}
