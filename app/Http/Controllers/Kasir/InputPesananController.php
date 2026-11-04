<?php

namespace App\Http\Controllers\Kasir;

use App\Http\Controllers\Controller;
use App\Models\Kategori;
use App\Models\Menu;
use App\Models\PengaturanSistem;
use App\Models\Pesanan;
use App\Models\TiketDapur;
use App\Services\PesananService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class InputPesananController extends Controller
{
    public function __construct(private PesananService $pesanan) {}

    public function create(): View
    {
        $baru = session('pesanan_tersimpan') ? Pesanan::with('tiketDapur')->find(session('pesanan_tersimpan')) : null;

        return view('kasir.input', [
            'menu' => Menu::with('kategori')->orderBy('nama_menu')->get(),
            'kategori' => Kategori::whereHas('menu')->orderBy('nama_kategori')->get(),
            'pengaturan' => PengaturanSistem::aktif(),
            'jumlahMeja' => config('resto.jumlah_meja'),
            // meja yang masih dipakai pesanan Dine In yang belum selesai
            'mejaTerpakai' => Pesanan::where('jenis_layanan', Pesanan::LAYANAN_DINE_IN)->whereNotNull('nomor_meja')
                ->whereNotIn('status', [Pesanan::STATUS_SELESAI, Pesanan::STATUS_DIBATALKAN])->pluck('nomor_meja')->all(),
            'baru' => $baru,
            'urutanAntrean' => $baru?->tiketDapur
                ? TiketDapur::where('status_masak', '!=', TiketDapur::STATUS_SELESAI)->where('waktu_masuk', '<=', $baru->tiketDapur->waktu_masuk)->count()
                : null,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'jenis_layanan' => ['required', Rule::in([Pesanan::LAYANAN_DINE_IN, Pesanan::LAYANAN_TAKE_AWAY])],
            'nomor_meja' => ['required_if:jenis_layanan,'.Pesanan::LAYANAN_DINE_IN, 'nullable', 'string', 'max:10'],
            'nama_pemesan' => ['nullable', 'string', 'max:100'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.id_menu' => ['required', 'integer', 'distinct', 'exists:menu,id'],
            'items.*.jumlah' => ['required', 'integer', 'min:1', 'max:99'],
            'items.*.catatan' => ['nullable', 'string', 'max:255'],
        ], [
            'items.required' => 'Pilih minimal satu menu.',
            'nomor_meja.required_if' => 'Pilih nomor meja untuk Dine In.',
        ]);

        $pesanan = $this->pesanan->buat($data['items'], $data['jenis_layanan'], kasir: $request->user(), data: $data);

        return to_route('kasir.input')->with('pesanan_tersimpan', $pesanan->id);
    }
}
