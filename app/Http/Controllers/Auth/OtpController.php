<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\KodeOtp;
use App\Models\User;
use App\Services\OtpService;
use App\Support\PesananTamu;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Halaman penerimaan kode OTP: verifikasi akun baru dan lupa password.
 */
class OtpController extends Controller
{
    public function __construct(private OtpService $otp) {}

    public function show(Request $request): View|RedirectResponse
    {
        $sesi = $request->session()->get('otp');
        if (! $sesi) {
            return to_route('login');
        }

        return view('auth.otp', [
            // reset password: tampilkan email lengkap yang diketik pengguna sendiri, supaya salah ketik terlihat
            'email' => $sesi['tujuan'] === KodeOtp::TUJUAN_RESET ? $sesi['email'] : OtpService::samarkan($sesi['email']),
            'reset' => $sesi['tujuan'] === KodeOtp::TUJUAN_RESET,
            'jeda' => $this->otp->sisaJeda($sesi['email'], $sesi['tujuan']),
            'berlaku' => OtpService::BERLAKU_MENIT,
        ]);
    }

    public function verify(Request $request): RedirectResponse
    {
        $sesi = $request->session()->get('otp');
        if (! $sesi) {
            return to_route('login');
        }

        $data = $request->validate(['kode' => ['required', 'digits:6']], [
            'kode.required' => 'Masukkan 6 digit kode OTP.',
            'kode.digits' => 'Kode OTP terdiri dari 6 angka.',
        ]);

        $this->otp->cocokkan($sesi['email'], $sesi['tujuan'], $data['kode']);
        $request->session()->forget('otp');
        $user = User::where('email', $sesi['email'])->firstOrFail();

        if ($sesi['tujuan'] === KodeOtp::TUJUAN_RESET) {
            // izin membuat password baru selama 15 menit
            $request->session()->put('reset', ['email' => $user->email, 'sampai' => now()->addMinutes(15)->timestamp]);

            return to_route('password.reset');
        }

        $user->forceFill(['email_verified_at' => now()])->save();
        Auth::login($user);
        $request->session()->regenerate();
        // pesanan yang dibuat tanpa akun (email sama / dari browser ini) masuk ke riwayat akun baru
        $ditautkan = PesananTamu::tautkanKe($user);
        $request->session()->forget('kontak_tamu');

        return redirect()->route($user->homeRoute())->with('status', 'Akun aktif. Selamat datang, '.$user->namaDepan().'!'
            .($ditautkan ? " {$ditautkan} pesanan sebelumnya sudah masuk ke riwayatmu." : ''));
    }

    public function resend(Request $request): RedirectResponse
    {
        $sesi = $request->session()->get('otp');
        if (! $sesi) {
            return to_route('login');
        }

        $this->otp->kirim($sesi['email'], $sesi['tujuan']);

        return back()->with('status', 'Kode baru sudah dikirim. Kode sebelumnya tidak berlaku lagi.');
    }
}
