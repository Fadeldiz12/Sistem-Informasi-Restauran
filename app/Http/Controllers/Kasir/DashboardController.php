<?php

namespace App\Http\Controllers\Kasir;

use App\Http\Controllers\Controller;
use App\Models\Pembayaran;
use App\Models\Pesanan;
use App\Models\TutupKasir;
use App\Services\PesananService;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(private PesananService $pesanan) {}

    public function __invoke(): View
    {
        // angka kasir dihitung sejak tutup hari (EOD) terakhir; tanpa EOD hari ini, sejak pukul 00:00
        $batas = TutupKasir::batas();
        $hariIni = Pesanan::where('waktu_pesan', '>=', $batas)->where('status', '!=', Pesanan::STATUS_DIBATALKAN);
        $belumLunas = Pesanan::belumLunas()->with('pembayaran')->get();
        $lunasHariIni = Pembayaran::where('status_bayar', Pembayaran::STATUS_LUNAS)->where('waktu_bayar', '>=', $batas);

        // notifikasi: gabungan kejadian terbaru yang perlu diperhatikan kasir
        $notif = collect()
            ->merge(Pembayaran::with('pesanan.pelanggan')->where('status_bayar', Pembayaran::STATUS_LUNAS)->whereNotNull('kode_transaksi')
                ->where('waktu_bayar', '>=', $batas)->latest('waktu_bayar')->limit(5)->get()
                ->map(fn ($b) => ['ikon' => 'i-wallet', 'm' => true, 'judul' => $b->metodeLabel().' lunas · '.$b->pesanan->kode(), 'ket' => $b->pesanan->namaPemesan().' · Rp '.number_format($b->jumlah, 0, ',', '.').' · otomatis ke dapur', 'waktu' => $b->waktu_bayar, 'url' => route('kasir.dashboard').'#berjalan']))
            ->merge(Pesanan::menungguKonfirmasi()->with('pelanggan', 'pengiriman.zona')->limit(5)->get()
                ->map(fn ($p) => ['ikon' => 'i-bell', 'm' => false, 'judul' => 'Pesanan online masuk · '.$p->kode(), 'ket' => $p->layananDetail(), 'waktu' => $p->waktu_pesan, 'url' => route('kasir.online', ['pesanan' => $p])]))
            ->merge(Pesanan::where('status', Pesanan::STATUS_SIAP)->where('jenis_layanan', '!=', Pesanan::LAYANAN_DELIVERY)->with('pelanggan')->latest('updated_at')->limit(5)->get()
                ->map(fn ($p) => ['ikon' => 'i-check', 'm' => false, 'judul' => 'Pesanan siap · '.$p->kode(), 'ket' => $p->layananDetail().' · dari dapur', 'waktu' => $p->updated_at, 'url' => route('kasir.dashboard').'#berjalan']))
            ->sortByDesc('waktu')->take(6)->values();

        $berjalan = Pesanan::with(['pelanggan', 'pembayaran', 'pengiriman.zona', 'tiketDapur'])
            ->whereNotIn('status', [Pesanan::STATUS_SELESAI, Pesanan::STATUS_DIBATALKAN])
            ->orderBy('waktu_pesan')->limit(50)->get();

        return view('kasir.dashboard', [
            'perLayanan' => (clone $hariIni)->selectRaw('jenis_layanan, count(*) as jumlah')->groupBy('jenis_layanan')->pluck('jumlah', 'jenis_layanan'),
            'menunggu' => Pesanan::menungguKonfirmasi()->count(),
            'belumLunas' => $belumLunas,
            'pemasukan' => (float) (clone $lunasHariIni)->sum('jumlah'),
            'jumlahLunas' => (clone $lunasHariIni)->count(),
            'notif' => $notif,
            'batas' => $batas,
            'berjalan' => $berjalan,
            'bisaDiubah' => $berjalan->filter(fn ($p) => $this->pesanan->bisaDiubah($p))->pluck('id')->all(),
        ]);
    }
}
