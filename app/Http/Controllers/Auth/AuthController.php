<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\KodeOtp;
use App\Models\User;
use App\Services\OtpService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function showLogin(): View
    {
        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ], [
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'password.required' => 'Password wajib diisi.',
        ]);

        if (! Auth::validate($credentials)) {
            return back()
                ->withErrors(['email' => 'Email atau password salah.'])
                ->onlyInput('email');
        }

        /** @var User $user */
        $user = Auth::getLastAttempted();

        // Akun yang belum memasukkan kode OTP registrasi: kirim kode lagi lalu ke halaman OTP
        if (! $user->email_verified_at) {
            return $this->mulaiOtp($request, $user->email, 'Akunmu belum diverifikasi. Masukkan kode OTP yang kami kirim ke email.');
        }

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route($request->user()->homeRoute());
    }

    public function showRegister(): View
    {
        return view('auth.registrasi');
    }

    /**
     * Registrasi akun pelanggan; role otomatis "pelanggan".
     */
    public function register(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'nama' => ['required', 'string', 'max:100'],
            'hp' => ['required', 'string', 'max:20', 'regex:/^[0-9+\-\s]+$/'],
            'email' => ['required', 'email', 'max:100', 'unique:users,email'],
            'alamat' => ['nullable', 'string', 'max:1000'],
            'password' => ['required', 'confirmed', Password::min(8)],
            'setuju' => ['accepted'],
        ], [
            'setuju.accepted' => 'Setujui syarat & ketentuan terlebih dahulu.',
            'nama.required' => 'Nama wajib diisi.',
            'nama.max' => 'Nama maksimal 100 karakter.',
            'hp.required' => 'Nomor HP wajib diisi.',
            'hp.max' => 'Nomor HP maksimal 20 karakter.',
            'hp.regex' => 'Nomor HP hanya boleh berisi angka.',
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'email.max' => 'Email maksimal 100 karakter.',
            'email.unique' => 'Email sudah terdaftar. Silakan masuk.',
            'password.required' => 'Password wajib diisi.',
            'password.confirmed' => 'Konfirmasi password tidak sama.',
            'password.min' => 'Password minimal 8 karakter.',
        ]);

        $user = User::create([
            'nama' => $data['nama'],
            'no_hp' => $data['hp'],
            'email' => strtolower($data['email']),
            'alamat' => $data['alamat'] ?? null,
            'password' => $data['password'],
            'role' => User::ROLE_PELANGGAN,
        ]);

        // Akun aktif setelah kode OTP dari email dimasukkan
        return $this->mulaiOtp($request, $user->email, 'Akun dibuat. Masukkan kode OTP yang kami kirim ke email untuk mengaktifkannya.');
    }

    /**
     * Kirim kode OTP registrasi dan buka halaman OTP.
     */
    private function mulaiOtp(Request $request, string $email, string $pesan): RedirectResponse
    {
        $request->session()->put('otp', ['email' => $email, 'tujuan' => KodeOtp::TUJUAN_REGISTRASI]);

        try {
            app(OtpService::class)->kirim($email, KodeOtp::TUJUAN_REGISTRASI);
        } catch (ValidationException) {
            // kode sebelumnya masih baru dikirim: tetap pakai kode itu
        }

        return to_route('otp.form')->with('status', $pesan);
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
