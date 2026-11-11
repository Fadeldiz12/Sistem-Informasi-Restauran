<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Middleware\KonfirmasiUlang;
use App\Models\LogAktivitas;
use App\Services\WajahService;
use App\Support\SesiStaf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

/**
 * Konfirmasi identitas sebelum aksi sensitif: wajah (bila terdaftar) + password.
 */
class KonfirmasiController extends Controller
{
    public function __construct(private WajahService $wajah) {}

    public function show(Request $request): View
    {
        $pakaiWajah = $request->user()->wajah()->exists();

        return view('auth.konfirmasi', [
            'pakaiWajah' => $pakaiWajah,
            'tantangan' => $pakaiWajah ? $this->wajah->buatTantangan($request, 'konfirmasi') : null,
            'berlakuMenit' => KonfirmasiUlang::BERLAKU_MENIT,
            'kembali' => route($request->user()->homeRoute()),
        ]);
    }

    public function store(Request $request): RedirectResponse|Response
    {
        $user = $request->user();
        $request->validate(['password' => ['required', 'string']], ['password.required' => 'Masukkan password kamu.']);

        if ($user->wajah()->exists()) {
            $hasil = $this->wajah->periksaBukti($request, 'konfirmasi', (array) json_decode((string) $request->input('bukti'), true));
            if (! $hasil['ok'] || ! $this->wajah->cocok($user, $hasil['ciri'])) {
                $alasan = $hasil['ok'] ? 'wajah tidak cocok' : $hasil['alasan'];
                $this->wajah->catatGagal($user, "konfirmasi aksi sensitif: {$alasan}", $hasil['liveness'] && ! $hasil['ok']);
                if ($user->fresh()->terkunci()) {
                    return redirect()->route('login')->with('status', SesiStaf::akhiri($request, $user->fresh(), SesiStaf::ALASAN_TERKUNCI));
                }

                return back()->withErrors(['password' => 'Verifikasi wajah gagal: '.($hasil['ok'] ? 'wajah tidak cocok dengan akun ini.' : $hasil['alasan'])]);
            }
            $this->wajah->catatBerhasil($user);
        }

        if (! Hash::check($request->input('password'), $user->password)) {
            $user->catatGagalLogin();
            if ($user->fresh()->terkunci()) {
                return redirect()->route('login')->with('status', SesiStaf::akhiri($request, $user->fresh(), SesiStaf::ALASAN_TERKUNCI));
            }

            return back()->withErrors(['password' => 'Password salah.']);
        }

        $user->forceFill(['gagal_login' => 0])->saveQuietly();
        $request->session()->put('konfirmasi_at', now()->timestamp);
        LogAktivitas::catat('akun.konfirmasi', "{$user->nama} mengonfirmasi identitas untuk aksi sensitif".($user->wajah()->exists() ? ' (wajah + password)' : ' (password)'), $user);

        $tujuan = $request->session()->pull('konfirmasi_tujuan', route($user->homeRoute()));
        $aksi = $request->session()->pull('konfirmasi_aksi');
        if ($aksi && $aksi['sampai'] >= now()->timestamp) {
            // kirim ulang otomatis aksi yang tadi tertahan (dengan token CSRF baru); back() sesudahnya kembali ke halaman asal
            $request->session()->setPreviousUrl($tujuan);

            return response()->view('auth.ulangi-aksi', ['aksi' => $aksi, 'isian' => self::ratakan($aksi['input'])]);
        }

        return redirect()->to($tujuan);
    }

    /**
     * ['hari' => [1 => ['mulai' => '08:00']]] → ['hari[1][mulai]' => '08:00'] untuk form tersembunyi.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, string>
     */
    private static function ratakan(array $data, string $awal = ''): array
    {
        $hasil = [];
        foreach ($data as $k => $v) {
            $nama = $awal === '' ? (string) $k : "{$awal}[{$k}]";
            if (is_array($v)) {
                $hasil += self::ratakan($v, $nama);
            } elseif ($v !== null) {
                $hasil[$nama] = is_bool($v) ? (string) (int) $v : (string) $v;
            }
        }

        return $hasil;
    }
}
