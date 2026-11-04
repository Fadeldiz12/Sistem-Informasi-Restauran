<?php

namespace App\Http\Controllers\Pelanggan;

use App\Http\Controllers\Controller;
use App\Models\PengaturanSistem;
use App\Models\Pesanan;
use App\Models\ZonaPengiriman;
use App\Services\PesananService;
use App\Support\Keranjang;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CheckoutController extends Controller
{
    public function __construct(private Keranjang $keranjang, private PesananService $pesanan) {}

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
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'jenis_layanan' => ['required', Rule::in(array_keys(Pesanan::LABEL_LAYANAN))],
            'nomor_meja' => ['required_if:jenis_layanan,'.Pesanan::LAYANAN_DINE_IN, 'nullable', 'string', 'max:10'],
            'alamat' => ['required_if:jenis_layanan,'.Pesanan::LAYANAN_DELIVERY, 'nullable', 'string', 'max:500'],
            'id_zona' => ['required_if:jenis_layanan,'.Pesanan::LAYANAN_DELIVERY, 'nullable', 'integer', 'exists:zona_pengiriman,id'],
        ], [
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

        $pesanan = $this->pesanan->buat(
            $items->map(fn ($it) => ['id_menu' => $it['menu']->id, 'jumlah' => $it['jumlah'], 'catatan' => $it['catatan']])->all(),
            $data['jenis_layanan'],
            pelanggan: $request->user(),
            data: $data,
        );

        $this->keranjang->kosongkan();

        return to_route('pelanggan.pembayaran', $pesanan)->with('pesanan_baru', true);
    }
}
