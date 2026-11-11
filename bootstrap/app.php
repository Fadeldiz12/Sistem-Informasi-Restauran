<?php

use App\Http\Controllers\Pelanggan\CheckoutController;
use App\Http\Middleware\AmankanSesiStaf;
use App\Http\Middleware\EnsureUserHasRole;
use App\Http\Middleware\JanganSimpanHalamanBerlogin;
use App\Http\Middleware\KonfirmasiUlang;
use App\Http\Middleware\PelangganAtauTamu;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // webhook Midtrans tidak membawa token CSRF (keasliannya dicek lewat signature)
        $middleware->validateCsrfTokens(except: ['midtrans/notifikasi']);

        $middleware->web(append: [AmankanSesiStaf::class, JanganSimpanHalamanBerlogin::class]);
        // pilihan layanan di beranda disimpan JS sebagai cookie biasa
        $middleware->encryptCookies(except: [CheckoutController::COOKIE_LAYANAN]);

        $middleware->alias([
            'role' => EnsureUserHasRole::class,
            'pelanggan-atau-tamu' => PelangganAtauTamu::class,
            'konfirmasi' => KonfirmasiUlang::class,
        ]);

        // Tamu yang membuka halaman berlogin: diberi tahu kenapa diarahkan ke halaman masuk
        $middleware->redirectGuestsTo(function (Request $request) {
            if (! $request->expectsJson()) {
                $request->session()->flash('status', 'Silakan masuk dulu untuk membuka halaman itu.');
            }

            return route('login');
        });

        // Pengguna yang sudah login dan membuka /login diarahkan ke dashboard role-nya
        $middleware->redirectUsersTo(fn (Request $request) => route($request->user()->homeRoute()));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        // Batas percobaan: form tetap tampil dengan pesan Indonesia & sisa detik, isian tidak hilang
        $exceptions->render(function (ThrottleRequestsException $e, Request $request) {
            $detik = (int) ($e->getHeaders()['Retry-After'] ?? 60);
            $pesan = "Terlalu banyak percobaan. Coba lagi dalam {$detik} detik.";

            if ($request->expectsJson()) {
                return response()->json(['message' => $pesan], 429, $e->getHeaders());
            }
            if (! $request->isMethod('GET')) {
                return back()
                    ->withInput($request->except(['password', 'password_confirmation', 'kode']))
                    ->withErrors([$request->routeIs('otp.*') ? 'kode' : 'email' => $pesan]);
            }

            return null; // halaman errors/429
        });

        // Permintaan JSON (mis. tombol + keranjang) dari sesi yang sudah habis
        $exceptions->render(function (AuthenticationException $e, Request $request) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Sesi kamu sudah berakhir. Silakan masuk lagi.', 'login' => route('login')], 401);
            }

            return null;
        });
    })->create();
