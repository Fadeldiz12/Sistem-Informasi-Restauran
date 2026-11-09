<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\KodeOtp;
use App\Models\User;
use App\Services\OtpService;
use App\Support\AturanAkun;
use App\Support\PesananTamu;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function showLogin(): View
    {
        return view('auth.login');
    }

    /**
     * Percobaan masuk yang gagal per email + IP sebelum dikunci sementara.
     */
    public const MAKS_GAGAL_LOGIN = 5;

    public function login(Request $request): RedirectResponse
    {
        $request->merge(['email' => Str::lower(trim((string) $request->input('email')))]);
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ], [
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'password.required' => 'Password wajib diisi.',
        ]);

        // hanya percobaan GAGAL yang dihitung, per email + IP: staf yang login bergantian di wifi yang sama tidak terblokir
        $kunci = 'login:'.$credentials['email'].'|'.$request->ip();
        if (RateLimiter::tooManyAttempts($kunci, self::MAKS_GAGAL_LOGIN)) {
            return back()
                ->withErrors(['email' => 'Terlalu banyak percobaan masuk yang salah. Coba lagi dalam '.RateLimiter::availableIn($kunci).' detik, atau gunakan "Lupa password?".'])
                ->onlyInput('email');
        }

        if (! Auth::validate($credentials)) {
            RateLimiter::hit($kunci, 60);

            return back()
                ->withErrors(['email' => 'Email atau password salah.'])
                ->onlyInput('email');
        }
        RateLimiter::clear($kunci);

        /** @var User $user */
        $user = Auth::getLastAttempted();

        // Akun yang belum memasukkan kode OTP registrasi: kirim kode lagi lalu ke halaman OTP
        if (! $user->email_verified_at) {
            return $this->mulaiOtp($request, $user->email, 'Akunmu belum diverifikasi. Masukkan kode OTP yang kami kirim ke email.');
        }

        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();

        $redirect = redirect()->to($this->tujuanSetelahLogin($request, $user));
        // pesanan tanpa akun dari browser ini masuk ke riwayat akun
        $ditautkan = $user->hasRole(User::ROLE_PELANGGAN) ? PesananTamu::tautkanKe($user) : 0;

        return $ditautkan ? $redirect->with('status', "{$ditautkan} pesanan tanpa akun sebelumnya kini masuk ke riwayat pesananmu.") : $redirect;
    }

    /**
     * Halaman yang tadi dibuka sebelum diminta login (bila sesuai role), atau halaman utama role.
     */
    private function tujuanSetelahLogin(Request $request, User $user): string
    {
        $tujuan = $request->session()->pull('url.intended');
        $segmen = $tujuan ? explode('/', trim((string) parse_url($tujuan, PHP_URL_PATH), '/'))[0] : null;
        $areaStaf = [User::ROLE_ADMIN, User::ROLE_OWNER, User::ROLE_KASIR, User::ROLE_DAPUR, User::ROLE_KURIR];

        $boleh = $tujuan && (in_array($segmen, $areaStaf, true) ? $segmen === $user->role : $user->role === User::ROLE_PELANGGAN);

        return $boleh ? $tujuan : route($user->homeRoute());
    }

    public function showRegister(Request $request): View
    {
        // tamu yang baru memesan: nama, HP, dan email dari pesanannya langsung terisi
        return view('auth.registrasi', ['kontakTamu' => $request->session()->get('kontak_tamu', [])]);
    }

    /**
     * Registrasi akun pelanggan; role otomatis "pelanggan".
     */
    public function register(Request $request): RedirectResponse
    {
        $request->merge(['email' => Str::lower(trim((string) $request->input('email'))), 'hp' => AturanAkun::rapikanHp($request->input('hp'))]);
        $data = $request->validate([
            'nama' => ['required', 'string', 'max:100', 'regex:'.AturanAkun::REGEX_NAMA],
            'hp' => ['required', 'string', 'max:20', 'regex:'.AturanAkun::REGEX_HP],
            // email yang terdaftar tapi belum diverifikasi boleh didaftarkan ulang (mis. email OTP sebelumnya gagal terkirim)
            'email' => ['required', 'email', 'max:100', Rule::unique('users', 'email')->whereNotNull('email_verified_at')],
            'alamat' => ['nullable', 'string', 'max:1000'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'setuju' => ['accepted'],
        ], [
            'setuju.accepted' => 'Setujui syarat & ketentuan terlebih dahulu.',
            'nama.required' => 'Nama wajib diisi.',
            'nama.max' => 'Nama maksimal 100 karakter.',
            'hp.required' => 'Nomor HP wajib diisi.',
            'hp.max' => 'Nomor HP maksimal 20 karakter.',
            'hp.regex' => AturanAkun::PESAN_HP,
            'nama.regex' => AturanAkun::PESAN_NAMA,
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'email.max' => 'Email maksimal 100 karakter.',
            'email.unique' => 'Email sudah terdaftar. Silakan masuk.',
            'password.required' => 'Password wajib diisi.',
            'password.confirmed' => 'Konfirmasi password tidak sama.',
            'password.min' => 'Password minimal 8 karakter.',
        ], [
            'password' => 'Password',
        ]);

        // Akun dan kode OTP dibuat dalam satu transaksi: bila email gagal terkirim, akun tidak tersimpan
        // dan form tampil lagi dengan pesan yang jelas.
        DB::transaction(function () use ($data) {
            User::updateOrCreate(['email' => $data['email']], [
                'nama' => $data['nama'],
                'no_hp' => $data['hp'],
                'alamat' => $data['alamat'] ?? null,
                'password' => $data['password'],
                'role' => User::ROLE_PELANGGAN,
            ]);
            $this->kirimOtpBaru($data['email']);
        });

        $request->session()->put('otp', ['email' => $data['email'], 'tujuan' => KodeOtp::TUJUAN_REGISTRASI]);

        // Akun aktif setelah kode OTP dari email dimasukkan
        return to_route('otp.form')->with('status', 'Akun dibuat. Masukkan kode OTP yang kami kirim ke email untuk mengaktifkannya.');
    }

    /**
     * Kirim kode registrasi baru, mengabaikan jeda kirim ulang (pendaftaran ulang selalu mendapat kode baru).
     */
    private function kirimOtpBaru(string $email): void
    {
        KodeOtp::where('email', $email)->where('tujuan', KodeOtp::TUJUAN_REGISTRASI)->delete();
        app(OtpService::class)->kirim($email, KodeOtp::TUJUAN_REGISTRASI);
    }

    /**
     * Kirim kode OTP registrasi dan buka halaman OTP.
     */
    private function mulaiOtp(Request $request, string $email, string $pesan): RedirectResponse
    {
        $request->session()->put('otp', ['email' => $email, 'tujuan' => KodeOtp::TUJUAN_REGISTRASI]);

        try {
            app(OtpService::class)->kirim($email, KodeOtp::TUJUAN_REGISTRASI);
        } catch (ValidationException $e) {
            // email gagal terkirim: tetap di halaman login dengan pesan yang jelas
            if (array_key_exists(OtpService::FIELD_GAGAL_KIRIM, $e->errors())) {
                $request->session()->forget('otp');

                throw $e;
            }
            // selain itu: kode sebelumnya masih baru dikirim, tetap pakai kode itu
        }

        return to_route('otp.form')->with('status', $pesan);
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('status', 'Anda sudah keluar.');
    }
}
