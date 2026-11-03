<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Route UI sementara (tampilan statis)
|--------------------------------------------------------------------------
| Route di bawah ini hanya menampilkan halaman UI (Blade) tanpa logika.
| Nanti diganti dengan controller + middleware auth/role.
*/

Route::redirect('/', '/login');

// Umum (Auth)
Route::view('/login', 'auth.login')->name('login');
Route::view('/registrasi', 'auth.registrasi')->name('registrasi');

// Sementara: submit form langsung diarahkan tanpa proses autentikasi
Route::post('/login', fn () => redirect()->route('pelanggan.beranda'))->name('login.submit');
Route::post('/registrasi', fn () => redirect()->route('login'))->name('registrasi.submit');

// Pelanggan
Route::name('pelanggan.')->group(function () {
    Route::view('/beranda', 'pelanggan.beranda')->name('beranda');
    Route::view('/keranjang', 'pelanggan.keranjang')->name('keranjang');
    Route::view('/checkout', 'pelanggan.checkout')->name('checkout');
    Route::view('/pembayaran', 'pelanggan.pembayaran')->name('pembayaran');
    Route::view('/tracking', 'pelanggan.tracking')->name('tracking');
});

// Admin
Route::prefix('admin')->name('admin.')->group(function () {
    Route::view('/dashboard', 'admin.dashboard')->name('dashboard');
    Route::view('/pengguna', 'admin.pengguna')->name('pengguna');
    Route::view('/menu', 'admin.menu')->name('menu');
    Route::view('/zona', 'admin.zona')->name('zona');
    Route::view('/pengaturan', 'admin.pengaturan')->name('pengaturan');
});

// Kasir
Route::prefix('kasir')->name('kasir.')->group(function () {
    Route::view('/dashboard', 'kasir.dashboard')->name('dashboard');
    Route::view('/input', 'kasir.input')->name('input');
    Route::view('/online', 'kasir.online')->name('online');
    Route::view('/pembayaran', 'kasir.pembayaran')->name('pembayaran');
});

// Dapur
Route::prefix('dapur')->name('dapur.')->group(function () {
    Route::view('/antrean', 'dapur.antrean')->name('antrean');
    Route::view('/bahan-baku', 'dapur.bahan-baku')->name('bahan-baku');
});

// Kurir
Route::prefix('kurir')->name('kurir.')->group(function () {
    Route::view('/tugas', 'kurir.tugas')->name('tugas');
});

// Owner
Route::prefix('owner')->name('owner.')->group(function () {
    Route::view('/dashboard', 'owner.dashboard')->name('dashboard');
    Route::view('/laporan', 'owner.laporan')->name('laporan');
    Route::view('/pengeluaran', 'owner.pengeluaran')->name('pengeluaran');
});
