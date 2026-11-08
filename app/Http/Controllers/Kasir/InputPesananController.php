<?php

namespace App\Http\Controllers\Kasir;

use App\Http\Controllers\Controller;
use App\Models\Kategori;
use App\Models\Menu;
use App\Models\PengaturanSistem;
use App\Models\Pesanan;
use App\Models\TiketDapur;
use App\Models\ZonaPengiriman;
use App\Services\PesananService;
use App\Support\AturanAkun;
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
        $pesanan->load(['tiketDapur', 'pembayaran', 'pengiriman']);
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
            'adaZona' => ZonaPengiriman::exists(),
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

    /**
     * Isian pesanan kasir. Delivery: penerima, titik di peta (zona & ongkir dari jarak), dan cara bayar.
     *
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        $delivery = 'required_if:jenis_layanan,'.Pesanan::LAYANAN_DELIVERY;
        $request->merge(['no_hp_penerima' => AturanAkun::rapikanHp($request->input('no_hp_penerima')) ?: null]);
        if ($request->input('jenis_layanan') !== Pesanan::LAYANAN_DELIVERY) {
            // isian Delivery yang tersisa saat kasir pindah ke Dine In / Take Away diabaikan
            $request->merge(array_fill_keys(['nama_penerima', 'no_hp_penerima', 'alamat', 'lat', 'lng', 'patokan', 'bayar'], null));
        }

        $data = $request->validate([
            'jenis_layanan' => ['required', Rule::in([Pesanan::LAYANAN_DINE_IN, Pesanan::LAYANAN_TAKE_AWAY, Pesanan::LAYANAN_DELIVERY])],
            'nomor_meja' => ['required_if:jenis_layanan,'.Pesanan::LAYANAN_DINE_IN, 'nullable', 'string', 'max:10'],
            'nama_pemesan' => ['nullable', 'string', 'max:100'],
            'nama_penerima' => [$delivery, 'nullable', 'string', 'max:100'],
            'no_hp_penerima' => [$delivery, 'nullable', 'string', 'max:20', 'regex:'.AturanAkun::REGEX_HP],
            'alamat' => [$delivery, 'nullable', 'string', 'max:500'],
            'lat' => [$delivery, 'nullable', 'numeric', 'between:-90,90'],
            'lng' => [$delivery, 'nullable', 'numeric', 'between:-180,180'],
            'patokan' => ['nullable', 'string', 'max:255'],
            'bayar' => [$delivery, 'nullable', Rule::in([PesananService::BAYAR_DI_KASIR, PesananService::BAYAR_KE_KURIR])],
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
            'nama_penerima.required_if' => 'Isi nama penerima pesanan.',
            'no_hp_penerima.required_if' => 'Isi nomor HP penerima supaya kurir bisa menghubungi.',
            'no_hp_penerima.regex' => AturanAkun::PESAN_HP,
            'alamat.required_if' => 'Isi alamat pengantaran.',
            'lat.required_if' => 'Tentukan titik pengantaran di peta (cari alamat atau geser pin).',
            'lng.required_if' => 'Tentukan titik pengantaran di peta (cari alamat atau geser pin).',
            'bayar.required_if' => 'Pilih cara bayar: di kasir sekarang atau tunai ke kurir.',
        ], [
            'items.*.jumlah' => 'jumlah porsi',
            'items.*.id_menu' => 'menu',
            'items.*.catatan' => 'catatan',
        ]);

        if ($data['jenis_layanan'] === Pesanan::LAYANAN_DELIVERY) {
            $data['nama_pemesan'] = $data['nama_penerima'];
            $data += ZonaPengiriman::dariTitik((float) $data['lat'], (float) $data['lng']);
        }

        return $data;
    }
}
