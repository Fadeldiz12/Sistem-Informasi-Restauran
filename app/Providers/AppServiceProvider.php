<?php

namespace App\Providers;

use App\Models\Kategori;
use App\Models\Menu;
use App\Models\PengaturanSistem;
use App\Models\Pesanan;
use App\Models\User;
use App\Models\ZonaPengiriman;
use App\Support\Keranjang;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

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
        // Jam operasional di sidebar semua panel staf
        View::composer('partials.panel.hours', function ($view) {
            $view->with('jamOperasional', PengaturanSistem::aktif()->jamLabel());
        });

        // Angka keranjang di header pelanggan
        View::composer(['partials.pelanggan.cart-button', 'pelanggan.beranda'], function ($view) {
            $view->with('keranjangInfo', app(Keranjang::class)->ringkasan());
        });

        // Kategori di navbar pelanggan (dipilih admin, maks 6)
        View::composer(['partials.pelanggan.nav', 'partials.pelanggan.catstrip'], function ($view) {
            $view->with('navKategori', Kategori::navbar()->get());
        });

        // Angka badge di sidebar kasir
        View::composer('partials.panel.sidebar-kasir', function ($view) {
            $view->with('sidebarCount', [
                'online' => Pesanan::menungguKonfirmasi()->count(),
                'pembayaran' => Pesanan::belumLunas()->count(),
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
