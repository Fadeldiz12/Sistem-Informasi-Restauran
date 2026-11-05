<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Symfony\Component\HttpFoundation\RedirectResponse as SymfonyRedirect;

/**
 * Masuk / daftar dengan Google. Akun baru otomatis menjadi pelanggan dan diminta
 * melengkapi nomor HP. Akun yang sudah ada (email sama) dihubungkan ke Google.
 */
class GoogleController extends Controller
{
    public static function aktif(): bool
    {
        return filled(config('services.google.client_id')) && filled(config('services.google.client_secret'));
    }

    public function redirect(): RedirectResponse|SymfonyRedirect
    {
        if (! self::aktif()) {
            return to_route('login')->withErrors(['email' => 'Login dengan Google belum diaktifkan. Hubungi admin restoran atau masuk dengan email.']);
        }

        return Socialite::driver('google')->redirect();
    }

    public function callback(Request $request): RedirectResponse
    {
        if (! self::aktif()) {
            return to_route('login');
        }

        try {
            $g = Socialite::driver('google')->user();
        } catch (\Throwable $e) {
            Log::warning('Login Google gagal: '.$e->getMessage());

            return to_route('login')->withErrors(['email' => 'Login dengan Google dibatalkan atau gagal. Silakan coba lagi.']);
        }

        $email = Str::lower((string) $g->getEmail());
        $terverifikasi = (bool) ($g->user['email_verified'] ?? $g->user['verified_email'] ?? false);
        if ($email === '' || ! $terverifikasi) {
            return to_route('login')->withErrors(['email' => 'Email akun Google belum terverifikasi.']);
        }

        $user = User::where('google_id', $g->getId())->first()
            ?? User::where('email', $email)->first();

        if ($user) {
            if (! $user->google_id) {
                $user->forceFill(['google_id' => $g->getId()])->save();
            }
        } else {
            $user = User::create([
                'nama' => Str::limit($g->getName() ?: Str::before($email, '@'), 100, ''),
                'email' => $email,
                'google_id' => $g->getId(),
                'password' => Str::password(32), // bisa diganti lewat "Lupa password"
                'role' => User::ROLE_PELANGGAN,
            ]);
        }

        Auth::login($user, remember: true);
        $request->session()->regenerate();

        return $user->perluLengkapiProfil()
            ? to_route('profil.lengkapi')
            : redirect()->intended(route($user->homeRoute()));
    }
}
