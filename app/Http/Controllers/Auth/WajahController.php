<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\KodeDarurat;
use App\Models\LogAktivitas;
use App\Models\User;
use App\Services\AksesOutletService;
use App\Services\WajahService;
use App\Support\SesiStaf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\View\View;

/**
 * Langkah login terakhir untuk staf: verifikasi wajah 1:1 dengan liveness acak.
 * Bila wajah gagal, tidak ada cadangan lewat email: Owner/Admin yang hadir di outlet menyetujui dengan password mereka.
 */
class WajahController extends Controller
{
    public function __construct(private WajahService $wajah) {}

    public function show(Request $request): View|RedirectResponse
    {
        if (! $this->tertunda($request)) {
            return to_route('login')->with('status', 'Waktu verifikasi habis. Silakan masuk lagi.');
        }

        return view('auth.verifikasi-wajah', [
            'tantangan' => $this->wajah->buatTantangan($request, 'login'),
            'kirimKe' => route('wajah.verifikasi.submit'),
            'tantanganBaru' => route('wajah.tantangan'),
            'judul' => 'Verifikasi Wajah',
            'persetujuan' => true,
        ]);
    }

    /** Tantangan baru (mis. setelah percobaan pertama gagal di browser). */
    public function tantangan(Request $request): JsonResponse
    {
        $untuk = $request->user() ? 'konfirmasi' : 'login';
        abort_unless($request->user() || $this->tertunda($request), 419);

        return response()->json($this->wajah->buatTantangan($request, $untuk));
    }

    public function verifikasi(Request $request): RedirectResponse
    {
        $tunda = $this->tertunda($request);
        $user = $tunda ? User::find($tunda['id']) : null;
        if (! $user || $user->terkunci()) {
            $request->session()->forget('login_wajah');

            return to_route('login')->with('status', $user?->terkunci() ? 'Akun ini terkunci. Hubungi Owner atau Admin.' : 'Waktu verifikasi habis. Silakan masuk lagi.');
        }

        $hasil = $this->wajah->periksaBukti($request, 'login', (array) json_decode((string) $request->input('bukti'), true));
        $cocok = $hasil['ok'] && $this->wajah->cocok($user, $hasil['ciri']);
        if (! $cocok) {
            $alasan = $hasil['ok'] ? 'wajah tidak cocok dengan data akun ini' : $hasil['alasan'];
            $this->wajah->catatGagal($user, $alasan, $hasil['liveness'] && ! $hasil['ok']);
            if ($user->fresh()->terkunci()) {
                $request->session()->forget('login_wajah');

                return to_route('login')->withErrors(['email' => 'Verifikasi wajah gagal '.WajahService::MAKS_GAGAL.' kali, akun ini dikunci dan Owner sudah diberi tahu.']);
            }

            return back()->withErrors(['wajah' => ($hasil['ok'] ? 'Wajah tidak cocok dengan akun ini.' : $hasil['alasan'])
                .' Sisa '.(WajahService::MAKS_GAGAL - $user->fresh()->gagal_wajah).' kali percobaan.']);
        }

        $this->wajah->catatBerhasil($user);
        $request->session()->put('wajah_at', now()->timestamp);

        return $this->selesai($request, $user, $tunda);
    }

    /**
     * Wajah gagal terus (mis. luka, pencahayaan): Owner/Admin yang hadir menyetujui dengan email & password mereka sendiri.
     */
    public function setujui(Request $request): RedirectResponse
    {
        $tunda = $this->tertunda($request);
        $user = $tunda ? User::find($tunda['id']) : null;
        if (! $user || $user->terkunci()) {
            return to_route('login')->with('status', 'Waktu verifikasi habis. Silakan masuk lagi.');
        }

        $data = $request->validate(['email_penyetuju' => ['required', 'email'], 'password_penyetuju' => ['required', 'string']], [
            'email_penyetuju.required' => 'Isi email Owner/Admin yang menyetujui.',
            'password_penyetuju.required' => 'Owner/Admin memasukkan password-nya sendiri.',
        ]);
        $kunci = 'setuju-wajah:'.$request->ip();
        if (RateLimiter::tooManyAttempts($kunci, 5)) {
            return back()->withErrors(['email_penyetuju' => 'Terlalu banyak percobaan. Coba lagi dalam '.RateLimiter::availableIn($kunci).' detik.']);
        }

        $penyetuju = User::where('email', mb_strtolower(trim($data['email_penyetuju'])))->first();
        $sah = $penyetuju && ! $penyetuju->is($user) && ! $penyetuju->terkunci()
            && $penyetuju->hasRole(User::ROLE_OWNER, User::ROLE_ADMIN)
            && ($user->role !== User::ROLE_OWNER || $penyetuju->hasRole(User::ROLE_OWNER))
            && Hash::check($data['password_penyetuju'], $penyetuju->password);
        if (! $sah) {
            RateLimiter::hit($kunci, 300);
            if ($penyetuju?->hasRole(User::ROLE_OWNER, User::ROLE_ADMIN)) {
                $penyetuju->catatGagalLogin();
            }
            LogAktivitas::catat('akun.ditolak', "Persetujuan masuk tanpa wajah untuk {$user->nama} ditolak (email/password penyetuju salah)", $user, pelaku: $user);

            return back()->withErrors(['email_penyetuju' => 'Persetujuan ditolak: harus email & password Owner/Admin lain yang benar.']);
        }

        RateLimiter::clear($kunci);
        LogAktivitas::catat('akun.wajah_disetujui', "{$penyetuju->nama} ({$penyetuju->roleLabel()}) menyetujui {$user->nama} masuk tanpa verifikasi wajah", $user, pelaku: $penyetuju);

        return $this->selesai($request, $user, $tunda);
    }

    /**
     * @param  array{id: int, sampai: int, via: string, kode: ?int}  $tunda
     */
    private function selesai(Request $request, User $user, array $tunda): RedirectResponse
    {
        $kode = $tunda['kode'] ? KodeDarurat::find($tunda['kode']) : null;

        return SesiStaf::masukkan($request, $user, false, $tunda['via'] ?? AksesOutletService::VIA_BEBAS, $kode);
    }

    /**
     * @return array{id: int, sampai: int, via: string, kode: ?int}|null
     */
    private function tertunda(Request $request): ?array
    {
        $tunda = $request->session()->get('login_wajah');

        return $tunda && $tunda['sampai'] >= now()->timestamp ? $tunda : null;
    }
}
