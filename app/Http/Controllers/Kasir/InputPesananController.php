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
        return $this->form();
    }

    /**
     * Ubah pesanan di tempat memakai halaman Input Pesanan yang sama (isian sudah terisi).
     */
    public function edit(Pesanan $pesanan): View|RedirectResponse
    {
        $pesanan->load(['tiketDapur', 'pembayaran']);
        if ($alasan = $this->pesanan->alasanTidakBisaDiubah($pesanan, $pesanan->tiketDapur)) {
            return to_route('kasir.dashboard')->with('error', $alasan);
        }

        return $this->form($pesanan->load('detail'));
    }

    public function update(Request $request, Pesanan $pesanan): RedirectResponse
    {
        $data = $this->validated($request);
        $this->pesanan->ubah($pesanan, $data['items'], $data['jenis_layanan'], $data);

        return to_route('kasir.dashboard')->with('status', "{$pesanan->kode()} diperbarui · total & tiket dapur ikut berubah");
    }

    /**
     * Batalkan pesanan yang belum lunas. Alasan wajib diisi; tiket dapur dihapus dari antrean.
     */
    public function batal(Request $request, Pesanan $pesanan): RedirectResponse
    {
        $data = $request->validate([
            'alasan' => ['required', 'string', 'max:255'],
            'kembalikan_stok' => ['nullable', 'boolean'],
        ], [
            'alasan.required' => 'Tulis alasan pembatalan.',
        ]);

        $hasil = $this->pesanan->batalkan($pesanan, $request->user(), $data['alasan'], $request->boolean('kembalikan_stok'));

        return back()->with('status', "{$pesanan->kode()} dibatalkan".($hasil['stok_dikembalikan'] ? " · stok {$hasil['stok_dikembalikan']} bahan dikembalikan" : ''));
    }

    private function form(?Pesanan $edit = null): View
    {
        $baru = session('pesanan_tersimpan') ? Pesanan::with('tiketDapur')->find(session('pesanan_tersimpan')) : null;

        return view('kasir.input', [
            'edit' => $edit,
            'menu' => Menu::with('kategori')->orderBy('nama_menu')->get(),
            'kategori' => Kategori::whereHas('menu')->orderBy('nama_kategori')->get(),
            'pengaturan' => PengaturanSistem::aktif(),
            'jumlahMeja' => config('resto.jumlah_meja'),
            // meja yang masih dipakai pesanan Dine In yang belum selesai
            'mejaTerpakai' => Pesanan::where('jenis_layanan', Pesanan::LAYANAN_DINE_IN)->whereNotNull('nomor_meja')
                ->whereNotIn('status', [Pesanan::STATUS_SELESAI, Pesanan::STATUS_DIBATALKAN])
                ->when($edit, fn ($q) => $q->whereKeyNot($edit->getKey()))->pluck('nomor_meja')->all(),
            'baru' => $baru,
            'urutanAntrean' => $baru?->tiketDapur
                ? TiketDapur::where('status_masak', '!=', TiketDapur::STATUS_SELESAI)->where('waktu_masuk', '<=', $baru->tiketDapur->waktu_masuk)->count()
                : null,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $pesanan = $this->pesanan->buat($data['items'], $data['jenis_layanan'], kasir: $request->user(), data: $data);

        return to_route('kasir.input')->with('pesanan_tersimpan', $pesanan->id);
    }

    private function validated(Request $request): array
    {
        return $request->validate([
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
            'items.*.jumlah.min' => 'Jumlah porsi minimal 1.',
            'items.*.jumlah.max' => 'Jumlah porsi maksimal 99 per menu.',
            'items.*.jumlah.integer' => 'Jumlah porsi harus angka bulat.',
            'items.*.id_menu.exists' => 'Salah satu menu sudah tidak tersedia. Muat ulang halaman lalu pilih lagi.',
            'items.*.id_menu.distinct' => 'Menu yang sama dipilih dua kali.',
        ], [
            'items.*.jumlah' => 'jumlah porsi',
            'items.*.id_menu' => 'menu',
            'items.*.catatan' => 'catatan',
        ]);
    }
}
