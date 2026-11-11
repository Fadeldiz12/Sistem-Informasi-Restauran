<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\KodeDarurat;
use App\Models\KodeOtp;
use App\Models\LogAktivitas;
use App\Models\User;
use App\Services\AksesOutletService;
use App\Services\OtpService;
use App\Support\AturanAkun;
use App\Support\PesananTamu;
use App\Support\SesiStaf;
use Illuminate\Http\JsonResponse;
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
    public function __construct(private AksesOutletService $akses) {}

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

        $akun = User::where('email', $credentials['email'])->first();
        // akun terkunci: pesan yang sama apa pun password-nya, supaya tebakan yang benar tidak ketahuan
        if ($akun?->terkunci()) {
            return back()->withErrors(['email' => $akun->isStaf()
                ? 'Akun ini terkunci karena terlalu banyak percobaan masuk yang salah. Hubungi Owner atau Admin untuk membukanya.'
                : 'Akun ini terkunci karena terlalu banyak percobaan masuk yang salah. Buka kuncinya dengan "Lupa password?".'])->onlyInput('email');
        }

        // hanya percobaan GAGAL yang dihitung, per email + IP: staf yang login bergantian di wifi yang sama tidak terblokir
        $kunci = 'login:'.$credentials['email'].'|'.$request->ip();
        if (RateLimiter::tooManyAttempts($kunci, self::MAKS_GAGAL_LOGIN)) {
            return back()
                ->withErrors(['email' => 'Terlalu banyak percobaan masuk yang salah. Coba lagi dalam '.RateLimiter::availableIn($kunci).' detik, atau gunakan "Lupa password?".'])
                ->onlyInput('email');
        }

        if (! Auth::validate($credentials)) {
            RateLimiter::hit($kunci, 60);
            $akun?->catatGagalLogin();

            if ($akun?->terkunci()) {
                return back()->withErrors(['email' => 'Password salah '.User::MAKS_GAGAL_LOGIN.' kali, akun ini sekarang terkunci. '
                    .($akun->isStaf() ? 'Hubungi Owner atau Admin untuk membukanya.' : 'Buka kuncinya dengan "Lupa password?".')])->onlyInput('email');
            }
            $sisa = $akun ? User::MAKS_GAGAL_LOGIN - $akun->gagal_login : 0;

            return back()
                ->withErrors(['email' => 'Email atau password salah.'.($akun && $sisa <= 3 ? " Sisa {$sisa} kali percobaan sebelum akun dikunci." : '')])
                ->onlyInput('email');
        }
        RateLimiter::clear($kunci);

        /** @var User $user */
        $user = Auth::getLastAttempted();

        // Akun yang belum memasukkan kode OTP registrasi: kirim kode lagi lalu ke halaman OTP
        if (! $user->email_verified_at) {
            return $this->mulaiOtp($request, $user->email, 'Akunmu belum diverifikasi. Masukkan kode OTP yang kami kirim ke email.');
        }

        if ($user->isStaf()) {
            $akses = $this->akses->periksaLogin($request, $user);

            if ($akses['status'] === 'ditolak') {
                LogAktivitas::catat('akun.ditolak', "{$user->nama} mencoba masuk di luar jadwal shift", $user, pelaku: $user);

                return back()->withErrors(['email' => $akses['pesan']])->onlyInput('email');
            }
            if ($akses['status'] === 'verifikasi') {
                LogAktivitas::catat('akun.ditolak', "{$user->nama} mencoba masuk dari ".implode(' & ', array_map(fn ($m) => $m === 'lokasi' ? 'luar outlet' : 'perangkat tidak terdaftar', $akses['masalah']))
                    ." (IP {$request->ip()})", $user, ['masalah' => $akses['masalah']], $user);
                $request->session()->put('login_outlet', [
                    'id' => $user->id, 'sampai' => now()->addMinutes(5)->timestamp, 'masalah' => $akses['masalah'], 'bisa_gps' => $akses['bisa_gps'],
                ]);

                return to_route('outlet.verifikasi');
            }

            return $this->masuk($request, $user, false, $akses['via']);
        }

        return $this->masuk($request, $user, $request->boolean('remember'));
    }

    /**
     * Login berhasil: buat sesi lalu ke halaman tujuan. "Ingat saya" hanya untuk pelanggan
     * (komputer outlet dipakai bergantian, staf harus login ulang).
     */
    private function masuk(Request $request, User $user, bool $ingat, string $via = AksesOutletService::VIA_BEBAS, ?KodeDarurat $kode = null): RedirectResponse
    {
        $request->session()->forget('login_outlet');
        Auth::login($user, $ingat && ! $user->isStaf());
        $request->session()->regenerate();
        SesiStaf::mulai($request, $user, $via, $kode);

        $redirect = redirect()->to($this->tujuanSetelahLogin($request, $user));
        // pesanan tanpa akun dari browser ini masuk ke riwayat akun
        $ditautkan = $user->hasRole(User::ROLE_PELANGGAN) ? PesananTamu::tautkanKe($user) : 0;

        return $ditautkan ? $redirect->with('status', "{$ditautkan} pesanan tanpa akun sebelumnya kini masuk ke riwayat pesananmu.") : $redirect;
    }

    /**
     * Staf yang password-nya benar tapi tidak terdeteksi di outlet / bukan dari perangkat terdaftar.
     */
    public function showVerifikasiOutlet(Request $request): View|RedirectResponse
    {
        $tunda = $this->loginTertunda($request);
        if (! $tunda) {
            return to_route('login')->with('status', 'Waktu verifikasi habis. Silakan masuk lagi.');
        }

        return view('auth.verifikasi-outlet', [
            'masalah' => $tunda['masalah'],
            'bisaGps' => $tunda['bisa_gps'],
            'radius' => (int) $this->akses->pengaturan()->radius_outlet_m,
        ]);
    }

    public function verifikasiOutlet(Request $request): RedirectResponse
    {
        $tunda = $this->loginTertunda($request);
        $user = $tunda ? User::find($tunda['id']) : null;
        if (! $user || $user->terkunci()) {
            $request->session()->forget('login_outlet');

            return to_route('login')->with('status', 'Waktu verifikasi habis. Silakan masuk lagi.');
        }

        if ($request->filled('kode')) {
            $kode = $this->akses->kodeDaruratCocok((string) $request->input('kode'));
            if (! $kode) {
                LogAktivitas::catat('akun.ditolak', "{$user->nama} memasukkan kode darurat yang salah atau kedaluwarsa", $user, pelaku: $user);

                return back()->withErrors(['kode' => 'Kode darurat salah atau sudah tidak berlaku. Minta kode baru ke Owner.']);
            }
            $kode->increment('dipakai');
            LogAktivitas::catat('akun.kode_darurat', "{$user->nama} masuk memakai kode darurat (alasan Owner: {$kode->alasan})", $kode, pelaku: $user);

            return $this->masuk($request, $user, false, AksesOutletService::VIA_KODE, $kode);
        }

        abort_unless($tunda['bisa_gps'], 422, 'Verifikasi GPS tidak tersedia. Pakai kode darurat dari Owner.');
        $data = $request->validate([
            'lat' => ['required', 'numeric', 'between:-90,90'],
            'lng' => ['required', 'numeric', 'between:-180,180'],
            'akurasi' => ['required', 'numeric', 'min:0'],
        ], ['lat.required' => 'Lokasi belum terbaca. Tekan "Periksa lokasi saya" dan izinkan akses lokasi.']);

        $gps = $this->akses->cekGps((float) $data['lat'], (float) $data['lng'], (float) $data['akurasi']);
        if (! $gps['di_outlet']) {
            LogAktivitas::catat('akun.ditolak', "{$user->nama} ditolak: lokasi GPS sekitar {$gps['jarak']} m dari outlet", $user, $data + $gps, $user);

            return back()->withErrors(['lat' => 'Lokasimu terbaca sekitar '.number_format($gps['jarak'], 0, ',', '.')." m dari outlet (batas {$this->akses->pengaturan()->radius_outlet_m} m). "
                .'Pastikan kamu di outlet lalu coba lagi, atau minta kode darurat ke Owner.']);
        }

        return $this->masuk($request, $user, false, AksesOutletService::VIA_GPS);
    }

    /**
     * Data login yang menunggu verifikasi outlet (berlaku 5 menit), atau null.
     *
     * @return array{id: int, sampai: int, masalah: array<int, string>, bisa_gps: bool}|null
     */
    private function loginTertunda(Request $request): ?array
    {
        $tunda = $request->session()->get('login_outlet');

        return $tunda && $tunda['sampai'] >= now()->timestamp ? $tunda : null;
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

    /**
     * Posisi GPS berkala (tiap 5 menit) dari staf yang masuk lewat verifikasi GPS. Di luar area = sesi berakhir.
     */
    public function perbaruiLokasi(Request $request): JsonResponse
    {
        $data = $request->validate([
            'lat' => ['required', 'numeric', 'between:-90,90'],
            'lng' => ['required', 'numeric', 'between:-180,180'],
            'akurasi' => ['required', 'numeric', 'min:0'],
        ]);
        if ($request->session()->get('lokasi_via') !== AksesOutletService::VIA_GPS) {
            return response()->json(['ok' => true]);
        }

        $gps = $this->akses->cekGps((float) $data['lat'], (float) $data['lng'], (float) $data['akurasi']);
        if (! $gps['di_outlet']) {
            $pesan = SesiStaf::akhiri($request, $request->user(), SesiStaf::ALASAN_LUAR_AREA);
            $request->session()->flash('status', $pesan);

            return response()->json(['message' => $pesan, 'login' => route('login')], 401);
        }
        $request->session()->put('gps_at', now()->timestamp);

        return response()->json(['ok' => true, 'jarak' => $gps['jarak']]);
    }

    public function logout(Request $request): RedirectResponse
    {
        $user = $request->user();

        // dikirim script panel saat staf lama tidak beraktivitas
        if ($request->input('alasan') === SesiStaf::ALASAN_DIAM && $user->isStaf()) {
            return redirect()->route('login')->with('status', SesiStaf::akhiri($request, $user, SesiStaf::ALASAN_DIAM));
        }

        if ($user->isStaf()) {
            LogAktivitas::catat('akun.logout', "{$user->nama} keluar", $user);
        }
        SesiStaf::keluar($request, $user);

        return redirect()->route('login')->with('status', 'Anda sudah keluar.');
    }
}
