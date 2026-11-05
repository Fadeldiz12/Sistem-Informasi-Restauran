<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\KodeOtp;
use App\Models\User;
use App\Services\OtpService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as AturanPassword;
use Illuminate\View\View;

/**
 * Lupa password: email → kode OTP → password baru + konfirmasi.
 */
class PasswordController extends Controller
{
    public function showRequest(): View
    {
        return view('auth.lupa-password');
    }

    public function sendCode(Request $request, OtpService $otp): RedirectResponse
    {
        $data = $request->validate(['email' => ['required', 'email']], [
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
        ]);
        $email = Str::lower($data['email']);

        $otp->kirim($email, KodeOtp::TUJUAN_RESET);
        $request->session()->put('otp', ['email' => $email, 'tujuan' => KodeOtp::TUJUAN_RESET]);

        // Pesan sama untuk email terdaftar maupun tidak, agar daftar akun tidak bisa ditebak.
        return to_route('otp.form')->with('status', 'Jika email terdaftar, kode OTP sudah dikirim. Periksa kotak masuk atau folder spam.');
    }

    public function showReset(Request $request): View|RedirectResponse
    {
        if (! $this->izinReset($request)) {
            return to_route('password.request')->withErrors(['email' => 'Sesi atur ulang password sudah habis. Minta kode OTP baru.']);
        }

        return view('auth.reset-password', ['email' => $request->session()->get('reset.email')]);
    }

    public function reset(Request $request): RedirectResponse
    {
        if (! $this->izinReset($request)) {
            return to_route('password.request')->withErrors(['email' => 'Sesi atur ulang password sudah habis. Minta kode OTP baru.']);
        }

        $data = $request->validate([
            'password' => ['required', 'confirmed', AturanPassword::min(8)],
        ], [
            'password.required' => 'Password baru wajib diisi.',
            'password.confirmed' => 'Konfirmasi password tidak sama.',
            'password.min' => 'Password minimal 8 karakter.',
        ]);

        $user = User::where('email', $request->session()->get('reset.email'))->firstOrFail();
        $user->forceFill([
            'password' => $data['password'],
            'remember_token' => Str::random(60),
            'email_verified_at' => $user->email_verified_at ?? now(), // kode OTP membuktikan email miliknya
        ])->save();
        $request->session()->forget('reset');

        return to_route('login')->with('status', 'Password berhasil diubah. Silakan masuk dengan password baru.');
    }

    private function izinReset(Request $request): bool
    {
        $reset = $request->session()->get('reset');

        return $reset && $reset['sampai'] >= now()->timestamp;
    }
}
