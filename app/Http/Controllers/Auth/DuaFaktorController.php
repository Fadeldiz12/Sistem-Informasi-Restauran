<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\KodeDarurat;
use App\Models\LogAktivitas;
use App\Models\User;
use App\Services\AksesOutletService;
use App\Support\DuaFaktor;
use App\Support\SesiStaf;
use App\Support\TandaTanganDigital;
use App\Support\Totp;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Verifikasi 2 langkah Owner & Admin: halaman kode saat login, serta halaman mengaktifkan / mematikannya.
 */
class DuaFaktorController extends Controller
{
    public function show(Request $request): View|RedirectResponse
    {
        if (! $this->tertunda($request)) {
            return to_route('login')->with('status', 'Waktu verifikasi habis. Silakan masuk lagi.');
        }

        return view('auth.dua-faktor');
    }

    public function verifikasi(Request $request): RedirectResponse
    {
        $tunda = $this->tertunda($request);
        $user = $tunda ? User::find($tunda['id']) : null;
        if (! $user || $user->terkunci() || ! DuaFaktor::aktif($user)) {
            $request->session()->forget('login_2fa');

            return to_route('login')->with('status', $user?->terkunci() ? 'Akun ini terkunci. Hubungi Owner.' : 'Waktu verifikasi habis. Silakan masuk lagi.');
        }
        $request->validate(['kode' => ['required', 'string', 'max:20']], ['kode.required' => 'Masukkan kode 6 angka dari aplikasi authenticator.']);

        $hasil = DuaFaktor::periksa($user, (string) $request->input('kode'));
        if (! $hasil) {
            $tunda['gagal']++;
            $request->session()->put('login_2fa', $tunda);
            LogAktivitas::catat('akun.2fa_gagal', "Kode verifikasi 2 langkah salah saat masuk ke akun {$user->nama} ({$tunda['gagal']}/".DuaFaktor::MAKS_GAGAL.')', $user, pelaku: $user);
            $user->catatGagalLogin(); // password sudah benar: kode salah berulang = tanda akun diserang

            if ($user->fresh()->terkunci() || $tunda['gagal'] >= DuaFaktor::MAKS_GAGAL) {
                $request->session()->forget('login_2fa');

                return to_route('login')->withErrors(['email' => $user->fresh()->terkunci()
                    ? 'Terlalu banyak kode salah, akun ini dikunci dan Owner sudah diberi tahu.'
                    : 'Terlalu banyak kode salah. Masuk lagi dari awal.']);
            }

            return back()->withErrors(['kode' => 'Kode salah atau sudah dipakai. Sisa '.(DuaFaktor::MAKS_GAGAL - $tunda['gagal']).' kali percobaan.']);
        }

        $request->session()->put('dua_faktor_lolos', $user->id);
        $kode = $tunda['kode'] ? KodeDarurat::find($tunda['kode']) : null;
        $redirect = SesiStaf::masukkan($request, $user, (bool) $tunda['ingat'], $tunda['via'] ?? AksesOutletService::VIA_BEBAS, $kode);
        $sisa = count((array) $user->fresh()->dua_faktor_cadangan);

        return $hasil === 'cadangan'
            ? $redirect->with('status', "Masuk dengan kode cadangan. Sisa {$sisa} kode; buat kode baru di menu Verifikasi 2 Langkah bila hampir habis.")
            : $redirect;
    }

    /**
     * Halaman pengaturan: status, langkah mengaktifkan (QR / kunci), kode cadangan.
     */
    public function pengaturan(Request $request): View
    {
        $user = $request->user();
        abort_unless(DuaFaktor::bisaDipakai($user), 404);

        $data = ['user' => $user, 'aktif' => DuaFaktor::aktif($user), 'kodeCadangan' => session('kode_cadangan')];
        if (! $data['aktif']) {
            $rahasia = $request->session()->get('dua_faktor_baru') ?? Totp::buatRahasia();
            $request->session()->put('dua_faktor_baru', $rahasia);
            $uri = Totp::uri($rahasia, $user->email, (string) config('resto.nama', 'Resto Flow'));
            $data += ['rahasia' => trim(chunk_split($rahasia, 4, ' ')), 'uri' => $uri, 'qr' => TandaTanganDigital::qr($uri)];
        } else {
            $data['sisaCadangan'] = count((array) $user->dua_faktor_cadangan);
        }

        return view('auth.pengaturan-dua-faktor', $data);
    }

    public function aktifkan(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless(DuaFaktor::bisaDipakai($user), 404);
        $request->validate(['kode' => ['required', 'string', 'max:20']], ['kode.required' => 'Masukkan kode 6 angka yang tampil di aplikasi.']);

        $rahasia = (string) $request->session()->get('dua_faktor_baru');
        $langkah = $rahasia !== '' ? Totp::cocok($rahasia, (string) $request->input('kode')) : null;
        if ($langkah === null) {
            return back()->withErrors(['kode' => 'Kode tidak cocok. Pastikan jam HP otomatis (sinkron jaringan) lalu masukkan kode yang sedang tampil.']);
        }

        $user->forceFill(['dua_faktor_rahasia' => $rahasia, 'dua_faktor_aktif_at' => now(), 'dua_faktor_langkah_terakhir' => $langkah])->saveQuietly();
        $request->session()->forget('dua_faktor_baru');
        $cadangan = DuaFaktor::buatCadangan($user);
        LogAktivitas::catat('akun.2fa_aktif', "{$user->nama} mengaktifkan verifikasi 2 langkah", $user);

        return to_route('akun.dua-faktor')->with('status', 'Verifikasi 2 langkah aktif.')->with('kode_cadangan', $cadangan);
    }

    public function cadanganBaru(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless(DuaFaktor::aktif($user), 404);
        $cadangan = DuaFaktor::buatCadangan($user);
        LogAktivitas::catat('akun.2fa_cadangan_baru', "{$user->nama} membuat kode cadangan 2 langkah baru (kode lama tidak berlaku)", $user);

        return back()->with('status', 'Kode cadangan baru dibuat. Kode lama tidak berlaku lagi.')->with('kode_cadangan', $cadangan);
    }

    public function matikan(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless(DuaFaktor::aktif($user), 404);
        $request->validate(['password' => ['required', 'current_password']], [
            'password.required' => 'Masukkan password untuk mematikan verifikasi 2 langkah.',
            'password.current_password' => 'Password salah.',
        ]);
        DuaFaktor::matikan($user);
        LogAktivitas::catat('akun.2fa_mati', "{$user->nama} mematikan verifikasi 2 langkah", $user);

        return back()->with('status', 'Verifikasi 2 langkah dimatikan. Akun kini hanya dilindungi password.');
    }

    /**
     * Owner mereset 2FA akun lain (mis. HP Admin hilang). Pemilik akun bisa mengaktifkannya lagi setelah masuk.
     */
    public function reset(Request $request, User $user): RedirectResponse
    {
        abort_unless($request->user()->hasRole(User::ROLE_OWNER) && ! $user->is($request->user()) && DuaFaktor::aktif($user), 403);
        DuaFaktor::matikan($user);
        LogAktivitas::catat('akun.2fa_reset', "Verifikasi 2 langkah {$user->nama} ({$user->roleLabel()}) direset oleh {$request->user()->nama}", $user);

        return back()->with('status', "Verifikasi 2 langkah {$user->nama} direset. Minta ia mengaktifkannya lagi setelah masuk.");
    }

    /**
     * @return array{id: int, ingat: bool, via: string, kode: ?int, sampai: int, gagal: int}|null
     */
    private function tertunda(Request $request): ?array
    {
        $tunda = $request->session()->get('login_2fa');

        return $tunda && $tunda['sampai'] >= now()->timestamp ? $tunda : null;
    }
}
