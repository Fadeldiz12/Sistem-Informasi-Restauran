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
    /**
     * Cookie (tanpa enkripsi, diisi JS di beranda) berisi jenis layanan yang dipilih di kartu "Pilih layanan".
     */
    public const COOKIE_LAYANAN = 'layanan_pilihan';

    /**
     * Session: id pesanan yang terakhir dibuat lewat checkout (untuk tombol Back setelah checkout).
     */
    public const SESI_PESANAN_BARU = 'pesanan_baru_id';

    public function __construct(private Keranjang $keranjang, private PesananService $pesanan, private MidtransService $midtrans) {}

    public function create(Request $request): View|RedirectResponse
    {
        $items = $this->keranjang->items();
        if ($dibuang = $this->keranjang->dibuang()) {
            session()->flash('error', $this->pesanDibuang($dibuang));
        }
        if ($items->isEmpty()) {
            // tombol Back dari halaman pembayaran: arahkan ke pesanan yang baru dibuat, bukan pesan error
            $baru = session(self::SESI_PESANAN_BARU) ? $request->user()->pesanan()->whereKey(session(self::SESI_PESANAN_BARU))
                ->where('waktu_pesan', '>=', now()->subHour())->first() : null;
            if ($baru) {
                return to_route('pelanggan.pembayaran', $baru)->with('status', "Pesanan {$baru->kode()} sudah dibuat. Status bisa dipantau di Lacak Pesanan.");
            }

            return to_route('pelanggan.keranjang')->with('error', $dibuang ? $this->pesanDibuang($dibuang) : 'Keranjang masih kosong. Pilih menu dulu.');
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
            // layanan yang dipilih di beranda ikut terpilih di checkout (QA A2-#6)
            'layananPilihan' => array_key_exists((string) $request->cookie(self::COOKIE_LAYANAN), Pesanan::LABEL_LAYANAN)
                ? $request->cookie(self::COOKIE_LAYANAN) : Pesanan::LAYANAN_DELIVERY,
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
            // banner "Restoran sedang tutup" sudah tampil di halaman checkout; tidak perlu pesan kedua
            return back()->withInput()->with('tutup', true);
        }

        $items = $this->keranjang->items();
        if ($dibuang = $this->keranjang->dibuang()) {
            return to_route('pelanggan.keranjang')->with('error', $this->pesanDibuang($dibuang));
        }
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
        $request->session()->put(self::SESI_PESANAN_BARU, $pesanan->id);

        return to_route('pelanggan.pembayaran', $pesanan)->with('pesanan_baru', true);
    }

    /**
     * @param  array<int, string>  $dibuang
     */
    private function pesanDibuang(array $dibuang): string
    {
        return collect($dibuang)->join(', ', ' dan ').' sedang habis dan sudah dihapus dari keranjang.';
    }
}
