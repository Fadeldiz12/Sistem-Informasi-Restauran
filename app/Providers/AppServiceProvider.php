<?php

namespace App\Providers;

use App\Models\BahanBaku;
use App\Models\Kategori;
use App\Models\Menu;
use App\Models\Pembayaran;
use App\Models\PengaturanSistem;
use App\Models\Pesanan;
use App\Models\TiketDapur;
use App\Models\User;
use App\Models\ZonaPengiriman;
use App\Support\Keranjang;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Aplikasi berbahasa Indonesia: tanggal & pesan validasi selalu Bahasa Indonesia,
        // walaupun APP_LOCALE di .env lama masih "en".
        app()->setLocale('id');
        Carbon::setLocale('id');

        // Semua password (pelanggan & staf): minimal 8 karakter, berisi huruf dan angka
        Password::defaults(fn () => Password::min(8)->letters()->numbers());

        // Jam operasional di sidebar semua panel staf
        View::composer('partials.panel.hours', function ($view) {
            $view->with('jamOperasional', PengaturanSistem::aktif()->jamLabel());
        });

        // Angka keranjang di header pelanggan
        View::composer(['partials.pelanggan.cart-button', 'partials.pelanggan.bottomnav', 'pelanggan.beranda'], function ($view) {
            $view->with('keranjangInfo', app(Keranjang::class)->ringkasan());
        });

        // Kategori di navbar pelanggan (dipilih admin, maks 6)
        View::composer(['partials.pelanggan.nav', 'partials.pelanggan.catstrip'], function ($view) {
            $view->with('navKategori', Kategori::navbar()->get());
            // baris kategori di HP: semua kategori yang punya menu (urutan navbar dulu), sama dengan chip daftar menu
            $view->with('stripKategori', Kategori::whereHas('menu')->get()
                ->reject(fn ($k) => $k->isPromo())
                ->sortBy(fn ($k) => [$k->tampil_navbar ? 0 : 1, $k->urutan_navbar ?? 99, $k->nama_kategori])->values());
            $view->with('adaPromo', Menu::promo()->exists());
        });

        // Angka badge di sidebar dapur
        View::composer('partials.panel.sidebar-dapur', function ($view) {
            $view->with('sidebarCount', [
                'antrean' => TiketDapur::whereIn('status_masak', [TiketDapur::STATUS_ANTRE, TiketDapur::STATUS_DIMASAK])->count(),
                'kritis' => BahanBaku::stokKritis()->count(),
            ]);
        });

        // Angka badge di sidebar kasir
        View::composer('partials.panel.sidebar-kasir', function ($view) {
            $view->with('sidebarCount', [
                'online' => Pesanan::menungguKonfirmasi()->count(),
                'siap' => Pesanan::where('status', Pesanan::STATUS_SIAP)->where('jenis_layanan', '!=', Pesanan::LAYANAN_DELIVERY)->count(),
                'pembayaran' => Pesanan::belumLunas()->count(),
                'setoran' => Pembayaran::belumDisetor()->distinct()->count('id_kurir'),
            ]);
        });

        // Angka badge di sidebar admin
        View::composer('partials.panel.sidebar-admin', function ($view) {
            $view->with('sidebarCount', [
                'pengguna' => User::count(),
                'menu' => Menu::count(),
                'zona' => ZonaPengiriman::count(),
            ]);
        });
    }
}
