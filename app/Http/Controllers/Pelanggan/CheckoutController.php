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
use Illuminate\Validation\ValidationException;
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
            // titik antar dari peta; zona & ongkir dihitung server dari jarak ke restoran
            'lat' => ['required_if:jenis_layanan,'.Pesanan::LAYANAN_DELIVERY, 'nullable', 'numeric', 'between:-90,90'],
            'lng' => ['required_if:jenis_layanan,'.Pesanan::LAYANAN_DELIVERY, 'nullable', 'numeric', 'between:-180,180'],
            'patokan' => ['nullable', 'string', 'max:255'],
            'metode' => ['required', Rule::in(array_keys(Pembayaran::LABEL_METODE))],
            'bank' => ['nullable', 'required_if:metode,'.Pembayaran::METODE_TRANSFER, Rule::in(array_keys(MidtransService::BANK))],
        ], [
            'metode.required' => 'Pilih metode pembayaran.',
            'metode.in' => 'Pilih metode pembayaran.',
            'bank.required_if' => 'Pilih bank untuk Virtual Account.',
            'nomor_meja.required_if' => 'Isi nomor meja untuk Dine In.',
            'alamat.required_if' => 'Isi alamat pengiriman untuk Delivery.',
            'lat.required_if' => 'Tentukan titik pengantaran di peta (pakai lokasi saya, cari alamat, atau geser pin).',
            'lng.required_if' => 'Tentukan titik pengantaran di peta (pakai lokasi saya, cari alamat, atau geser pin).',
        ]);

        $set = PengaturanSistem::aktif();
        if (! $set->sedangBuka()) {
            // banner "Restoran sedang tutup" sudah tampil di halaman checkout; tidak perlu pesan kedua
            return back()->withInput()->with('tutup', true);
        }

        if ($data['jenis_layanan'] === Pesanan::LAYANAN_DELIVERY) {
            $data += $this->zonaDariTitik($set, (float) $data['lat'], (float) $data['lng']);
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

    /**
     * Zona pengiriman dari jarak titik antar ke restoran. Di luar zona terjauh → ditolak.
     *
     * @return array{id_zona: int, jarak_km: float}
     */
    private function zonaDariTitik(PengaturanSistem $set, float $lat, float $lng): array
    {
        $jarak = $set->jarakKm($lat, $lng);
        $zona = ZonaPengiriman::untukJarak($jarak);
        if (! $zona) {
            $maks = (float) ZonaPengiriman::max('radius_km');
            throw ValidationException::withMessages([
                'lat' => 'Titik pengantaran '.self::km($jarak).' km dari restoran, di luar jangkauan Delivery (maksimal '.self::km($maks).' km). Silakan pilih Take Away.',
            ]);
        }

        return ['id_zona' => $zona->id, 'jarak_km' => $jarak];
    }

    private static function km(float $n): string
    {
        return rtrim(rtrim(number_format($n, 1, ',', '.'), '0'), ',');
    }
}
