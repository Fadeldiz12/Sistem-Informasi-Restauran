<?php

namespace App\Http\Controllers\Pelanggan;

use App\Http\Controllers\Controller;
use App\Models\Kategori;
use App\Models\Menu;
use App\Models\Pesanan;
use App\Models\ZonaPengiriman;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BerandaController extends Controller
{
    public function __invoke(Request $request): View
    {
        // hanya menu yang tersedia yang ditampilkan ke pelanggan
        $menu = Menu::tersedia()->with('kategori')->orderBy('nama_menu')->get();

        // kategori: yang ada di navbar (urut admin) lalu kategori lain yang punya menu (alfabet)
        $kategori = Kategori::query()
            ->where(fn ($q) => $q->where('tampil_navbar', true)->orWhereIn('id', $menu->pluck('id_kategori')))
            ->get()
            ->sortBy(fn ($k) => [$k->tampil_navbar ? 0 : 1, $k->urutan_navbar ?? 99, $k->nama_kategori])
            ->values();

        $urutan = $kategori->pluck('id')->flip();
        $menu = $menu->sortBy(fn ($m) => [$urutan[$m->id_kategori] ?? 999, $m->nama_menu])->values();
        $perKategori = $menu->groupBy('id_kategori');

        $aktif = $kategori->first(fn ($k) => $k->slug() === $request->query('kategori'));

        return view('pelanggan.beranda', [
            'menu' => $menu,
            'kategori' => $kategori,
            // kategori + foto menu pertamanya untuk bagian "Mau makan apa hari ini?"
            'kategoriFoto' => $kategori
                ->filter(fn ($k) => $perKategori->has($k->id))
                ->map(fn ($k) => ['k' => $k, 'foto' => $perKategori[$k->id]->first(fn ($m) => $m->foto)?->fotoUrl()])
                ->values(),
            'aktif' => $aktif?->slug(),
            'pesananAktif' => $request->user()->pesanan()
                ->with(['detail', 'tiketDapur', 'pengiriman.kurir', 'pengiriman.zona'])
                ->whereNotIn('status', [Pesanan::STATUS_SELESAI, Pesanan::STATUS_DIBATALKAN])
                ->latest('waktu_pesan')->latest('id')->first(),
            'ongkirMin' => ZonaPengiriman::orderBy('radius_km')->get()->map->ongkir()->min(),
        ]);
    }
}
