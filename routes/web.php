<?php

use App\Http\Controllers\Auth\AuthController;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Umum: login, registrasi, logout
|--------------------------------------------------------------------------
*/

// Halaman awal: ke dashboard sesuai role kalau sudah login, kalau belum ke login
Route::get('/', function (Request $request) {
    return $request->user()
        ? redirect()->route($request->user()->homeRoute())
        : redirect()->route('login');
});

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1')->name('login.submit');
    Route::get('/registrasi', [AuthController::class, 'showRegister'])->name('registrasi');
    Route::post('/registrasi', [AuthController::class, 'register'])->middleware('throttle:5,1')->name('registrasi.submit');
});

Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

/*
|--------------------------------------------------------------------------
| Halaman per role
|--------------------------------------------------------------------------
| Masih menampilkan UI statis (Route::view); diganti controller per tahap.
*/

// Pelanggan
Route::middleware(['auth', 'role:'.User::ROLE_PELANGGAN])->name('pelanggan.')->group(function () {
    Route::view('/beranda', 'pelanggan.beranda')->name('beranda');
    Route::view('/keranjang', 'pelanggan.keranjang')->name('keranjang');
    Route::view('/checkout', 'pelanggan.checkout')->name('checkout');
    Route::view('/pembayaran', 'pelanggan.pembayaran')->name('pembayaran');
    Route::view('/tracking', 'pelanggan.tracking')->name('tracking');
});

// Admin
Route::middleware(['auth', 'role:'.User::ROLE_ADMIN])->prefix('admin')->name('admin.')->group(function () {
    Route::view('/dashboard', 'admin.dashboard')->name('dashboard');
    Route::view('/pengguna', 'admin.pengguna')->name('pengguna');
    Route::view('/menu', 'admin.menu')->name('menu');
    Route::view('/zona', 'admin.zona')->name('zona');
    Route::view('/pengaturan', 'admin.pengaturan')->name('pengaturan');
});

// Kasir
Route::middleware(['auth', 'role:'.User::ROLE_KASIR])->prefix('kasir')->name('kasir.')->group(function () {
    Route::view('/dashboard', 'kasir.dashboard')->name('dashboard');
    Route::view('/input', 'kasir.input')->name('input');
    Route::view('/online', 'kasir.online')->name('online');
    Route::view('/pembayaran', 'kasir.pembayaran')->name('pembayaran');
});

// Dapur
Route::middleware(['auth', 'role:'.User::ROLE_DAPUR])->prefix('dapur')->name('dapur.')->group(function () {
    Route::view('/antrean', 'dapur.antrean')->name('antrean');
    Route::view('/bahan-baku', 'dapur.bahan-baku')->name('bahan-baku');
});

// Kurir
Route::middleware(['auth', 'role:'.User::ROLE_KURIR])->prefix('kurir')->name('kurir.')->group(function () {
    Route::view('/tugas', 'kurir.tugas')->name('tugas');
});

// Owner
Route::middleware(['auth', 'role:'.User::ROLE_OWNER])->prefix('owner')->name('owner.')->group(function () {
    Route::view('/dashboard', 'owner.dashboard')->name('dashboard');
    Route::view('/laporan', 'owner.laporan')->name('laporan');
    Route::view('/pengeluaran', 'owner.pengeluaran')->name('pengeluaran');
});
