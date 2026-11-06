<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\OtpController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\BuktiKirimController;
use App\Http\Controllers\Dapur;
use App\Http\Controllers\Kasir;
use App\Http\Controllers\Kurir;
use App\Http\Controllers\MidtransController;
use App\Http\Controllers\Owner;
use App\Http\Controllers\Pelanggan;
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
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1,login')->name('login.submit');
    Route::get('/registrasi', [AuthController::class, 'showRegister'])->name('registrasi');
    Route::post('/registrasi', [AuthController::class, 'register'])->middleware('throttle:5,1,registrasi')->name('registrasi.submit');

    // Kode OTP (verifikasi akun baru & lupa password)
    Route::get('/verifikasi-otp', [OtpController::class, 'show'])->name('otp.form');
    Route::post('/verifikasi-otp', [OtpController::class, 'verify'])->middleware('throttle:10,1,otp')->name('otp.verifikasi');
    Route::post('/verifikasi-otp/kirim-ulang', [OtpController::class, 'resend'])->middleware('throttle:3,1,otp-ulang')->name('otp.ulang');

    // Lupa password: email → OTP → password baru
    Route::get('/lupa-password', [PasswordController::class, 'showRequest'])->name('password.request');
    Route::post('/lupa-password', [PasswordController::class, 'sendCode'])->middleware('throttle:5,1,lupa-password')->name('password.email');
    Route::get('/reset-password', [PasswordController::class, 'showReset'])->name('password.reset');
    Route::post('/reset-password', [PasswordController::class, 'reset'])->middleware('throttle:5,1,reset-password')->name('password.update');
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
    Route::get('/beranda', Pelanggan\BerandaController::class)->name('beranda');

    Route::get('/keranjang', [Pelanggan\KeranjangController::class, 'index'])->name('keranjang');
    Route::post('/keranjang', [Pelanggan\KeranjangController::class, 'store'])->name('keranjang.store');
    Route::delete('/keranjang', [Pelanggan\KeranjangController::class, 'clear'])->name('keranjang.clear');
    Route::patch('/keranjang/{menu}', [Pelanggan\KeranjangController::class, 'update'])->whereNumber('menu')->name('keranjang.update');
    Route::delete('/keranjang/{menu}', [Pelanggan\KeranjangController::class, 'destroy'])->whereNumber('menu')->name('keranjang.destroy');

    Route::get('/checkout', [Pelanggan\CheckoutController::class, 'create'])->name('checkout');
    Route::post('/checkout', [Pelanggan\CheckoutController::class, 'store'])->middleware('throttle:10,1,checkout')->name('checkout.store');

    Route::get('/pembayaran', [Pelanggan\PembayaranController::class, 'terakhir'])->name('pembayaran.terakhir');
    Route::get('/pesanan/{pesanan}/pembayaran', [Pelanggan\PembayaranController::class, 'show'])->name('pembayaran');
    Route::post('/pesanan/{pesanan}/pembayaran', [Pelanggan\PembayaranController::class, 'store'])->middleware('throttle:10,1,bayar')->name('pembayaran.store');
    Route::get('/pesanan/{pesanan}/pembayaran/status', [Pelanggan\PembayaranController::class, 'status'])->name('pembayaran.status');

    Route::get('/tracking', Pelanggan\TrackingController::class)->name('tracking');
});

// Notifikasi pembayaran dari Midtrans (webhook, tanpa login; diverifikasi lewat signature)
Route::post('/midtrans/notifikasi', MidtransController::class)->name('midtrans.notifikasi');

// Admin
Route::middleware(['auth', 'role:'.User::ROLE_ADMIN])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', Admin\DashboardController::class)->name('dashboard');

    Route::get('/pengguna', [Admin\PenggunaController::class, 'index'])->name('pengguna');
    Route::post('/pengguna', [Admin\PenggunaController::class, 'store'])->name('pengguna.store');
    Route::put('/pengguna/{user}', [Admin\PenggunaController::class, 'update'])->name('pengguna.update');
    Route::delete('/pengguna/{user}', [Admin\PenggunaController::class, 'destroy'])->name('pengguna.destroy');

    Route::get('/menu', [Admin\MenuController::class, 'index'])->name('menu');
    Route::post('/menu', [Admin\MenuController::class, 'store'])->name('menu.store');
    Route::put('/menu/{menu}', [Admin\MenuController::class, 'update'])->name('menu.update');
    Route::patch('/menu/{menu}/tersedia', [Admin\MenuController::class, 'tersedia'])->name('menu.tersedia');
    Route::delete('/menu/{menu}', [Admin\MenuController::class, 'destroy'])->name('menu.destroy');

    Route::post('/kategori', [Admin\KategoriController::class, 'store'])->name('kategori.store');
    Route::put('/kategori/{kategori}', [Admin\KategoriController::class, 'update'])->name('kategori.update');
    Route::delete('/kategori/{kategori}', [Admin\KategoriController::class, 'destroy'])->name('kategori.destroy');

    Route::get('/zona', [Admin\ZonaController::class, 'index'])->name('zona');
    Route::post('/zona', [Admin\ZonaController::class, 'store'])->name('zona.store');
    Route::put('/zona/{zona}', [Admin\ZonaController::class, 'update'])->name('zona.update');
    Route::delete('/zona/{zona}', [Admin\ZonaController::class, 'destroy'])->name('zona.destroy');

    Route::get('/pengaturan', [Admin\PengaturanController::class, 'edit'])->name('pengaturan');
    Route::put('/pengaturan', [Admin\PengaturanController::class, 'update'])->name('pengaturan.update');
});

// Kasir
Route::middleware(['auth', 'role:'.User::ROLE_KASIR])->prefix('kasir')->name('kasir.')->group(function () {
    Route::get('/dashboard', Kasir\DashboardController::class)->name('dashboard');

    Route::get('/input', [Kasir\InputPesananController::class, 'create'])->name('input');
    Route::post('/input', [Kasir\InputPesananController::class, 'store'])->name('input.store');
    Route::get('/pesanan/{pesanan}/ubah', [Kasir\InputPesananController::class, 'edit'])->name('pesanan.ubah');
    Route::put('/pesanan/{pesanan}', [Kasir\InputPesananController::class, 'update'])->name('pesanan.update');
    Route::post('/pesanan/{pesanan}/batal', [Kasir\InputPesananController::class, 'batal'])->name('pesanan.batal');

    Route::get('/online', [Kasir\PesananOnlineController::class, 'index'])->name('online');
    Route::post('/online/{pesanan}/terima', [Kasir\PesananOnlineController::class, 'terima'])->name('online.terima');
    Route::post('/online/{pesanan}/tolak', [Kasir\PesananOnlineController::class, 'tolak'])->name('online.tolak');

    Route::get('/pembayaran', [Kasir\PembayaranController::class, 'index'])->name('pembayaran');
    Route::post('/pembayaran/{pesanan}/lunas', [Kasir\PembayaranController::class, 'lunas'])->name('pembayaran.lunas');
    Route::get('/pesanan/{pesanan}/struk', [Kasir\PembayaranController::class, 'struk'])->name('struk');
    Route::post('/pesanan/{pesanan}/selesai', [Kasir\PembayaranController::class, 'selesai'])->name('selesai');

    Route::get('/setoran', [Kasir\SetoranController::class, 'index'])->name('setoran');
    Route::post('/setoran/{kurir}', [Kasir\SetoranController::class, 'terima'])->name('setoran.terima');
});

// Dapur
Route::middleware(['auth', 'role:'.User::ROLE_DAPUR])->prefix('dapur')->name('dapur.')->group(function () {
    Route::get('/antrean', [Dapur\AntreanController::class, 'index'])->name('antrean');
    Route::post('/tiket/{tiket}/mulai', [Dapur\AntreanController::class, 'mulai'])->name('tiket.mulai');
    Route::post('/tiket/{tiket}/selesai', [Dapur\AntreanController::class, 'selesai'])->name('tiket.selesai');

    Route::get('/bahan-baku', [Dapur\BahanBakuController::class, 'index'])->name('bahan-baku');
    Route::post('/bahan-baku', [Dapur\BahanBakuController::class, 'store'])->name('bahan-baku.store');
    Route::put('/bahan-baku/{bahan}', [Dapur\BahanBakuController::class, 'update'])->name('bahan-baku.update');
    Route::delete('/bahan-baku/{bahan}', [Dapur\BahanBakuController::class, 'destroy'])->name('bahan-baku.destroy');
    Route::post('/mutasi', [Dapur\BahanBakuController::class, 'mutasi'])->name('mutasi.store');
});

// Kurir
Route::middleware(['auth', 'role:'.User::ROLE_KURIR])->prefix('kurir')->name('kurir.')->group(function () {
    Route::get('/tugas', [Kurir\TugasController::class, 'index'])->name('tugas');
    Route::get('/tugas/{pengiriman}', [Kurir\TugasController::class, 'show'])->name('tugas.show');
    Route::post('/tugas/{pengiriman}/terima', [Kurir\TugasController::class, 'terima'])->name('tugas.terima');
    Route::get('/status', [Kurir\TugasController::class, 'status'])->name('status');
    Route::post('/tugas/{pengiriman}/diterima', [Kurir\TugasController::class, 'diterima'])->name('tugas.diterima');
    Route::get('/riwayat', [Kurir\TugasController::class, 'riwayat'])->name('riwayat');
});

// Foto bukti pengiriman (privat): pelanggan pemilik, kurir pengantar, kasir, admin, owner
Route::get('/pengiriman/{pengiriman}/bukti', BuktiKirimController::class)->middleware('auth')->name('pengiriman.bukti');

// Owner
Route::middleware(['auth', 'role:'.User::ROLE_OWNER])->prefix('owner')->name('owner.')->group(function () {
    Route::get('/dashboard', Owner\DashboardController::class)->name('dashboard');

    Route::get('/laporan', [Owner\LaporanController::class, 'index'])->name('laporan');
    Route::get('/laporan/pdf', [Owner\LaporanController::class, 'pdf'])->name('laporan.pdf');
    Route::get('/laporan/excel', [Owner\LaporanController::class, 'excel'])->name('laporan.excel');

    // Owner mengatur semua pengguna & role, termasuk owner lain
    Route::get('/pengguna', [Admin\PenggunaController::class, 'index'])->name('pengguna');
    Route::post('/pengguna', [Admin\PenggunaController::class, 'store'])->name('pengguna.store');
    Route::put('/pengguna/{user}', [Admin\PenggunaController::class, 'update'])->name('pengguna.update');
    Route::delete('/pengguna/{user}', [Admin\PenggunaController::class, 'destroy'])->name('pengguna.destroy');

    Route::get('/pengeluaran', [Owner\PengeluaranController::class, 'index'])->name('pengeluaran');
    Route::post('/pengeluaran', [Owner\PengeluaranController::class, 'store'])->name('pengeluaran.store');
    Route::put('/pengeluaran/{pengeluaran}', [Owner\PengeluaranController::class, 'update'])->name('pengeluaran.update');
    Route::delete('/pengeluaran/{pengeluaran}', [Owner\PengeluaranController::class, 'destroy'])->name('pengeluaran.destroy');
});
