<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BahanBaku;
use App\Models\Menu;
use App\Models\PengaturanSistem;
use App\Models\Pesanan;
use App\Models\User;
use App\Models\ZonaPengiriman;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $hariIni = Pesanan::query()->whereDate('waktu_pesan', today());

        $perStatus = (clone $hariIni)->selectRaw('status, count(*) as jumlah')->groupBy('status')->pluck('jumlah', 'status');
        $perLayanan = (clone $hariIni)->selectRaw('jenis_layanan, count(*) as jumlah')->groupBy('jenis_layanan')->pluck('jumlah', 'jenis_layanan');
        $totalHariIni = $perStatus->sum();
        $totalKemarin = Pesanan::whereDate('waktu_pesan', today()->subDay())->count();

        $zona = ZonaPengiriman::orderBy('radius_km')->get();
        $ongkir = $zona->map->ongkir();

        return view('admin.dashboard', [
            'totalHariIni' => $totalHariIni,
            'totalKemarin' => $totalKemarin,
            'perStatus' => $perStatus,
            'perLayanan' => $perLayanan,
            'perRole' => User::query()->selectRaw('role, count(*) as total')->groupBy('role')->pluck('total', 'role'),
            'totalMenu' => Menu::count(),
            'menuTersedia' => Menu::where('tersedia', true)->count(),
            'jumlahZona' => $zona->count(),
            'ongkirMin' => $ongkir->min(),
            'ongkirMax' => $ongkir->max(),
            'pengaturan' => PengaturanSistem::aktif(),
            'bahanKritis' => BahanBaku::stokKritis()->count(),
            'menungguKonfirmasi' => Pesanan::where('status', Pesanan::STATUS_MENUNGGU)->count(),
            'pesananTerbaru' => Pesanan::with(['pelanggan', 'pengiriman.zona'])->latest('waktu_pesan')->latest('id')->limit(6)->get(),
        ]);
    }
}
