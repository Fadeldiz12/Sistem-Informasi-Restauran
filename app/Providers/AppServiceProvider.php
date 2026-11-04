<?php

namespace App\Providers;

use App\Models\Menu;
use App\Models\PengaturanSistem;
use App\Models\User;
use App\Models\ZonaPengiriman;
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
