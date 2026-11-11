<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\LogAktivitas;
use App\Models\User;
use App\Models\WajahStaf;
use App\Services\WajahService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Pendaftaran wajah staf dilakukan Owner di outlet, dengan staf hadir di depan kamera dan sudah memberi persetujuan tertulis.
 */
class WajahStafController extends Controller
{
    public const SAMPEL = 5;

    public function __construct(private WajahService $wajah) {}

    public function create(Request $request, User $user): View
    {
        abort_unless($user->isStaf(), 404);

        return view('owner.wajah', [
            'staf' => $user,
            'tantangan' => $this->wajah->buatTantangan($request, 'daftar'),
        ]);
    }

    public function store(Request $request, User $user): RedirectResponse
    {
        abort_unless($user->isStaf(), 404);
        $request->validate(['setuju' => ['accepted']], ['setuju.accepted' => 'Centang pernyataan bahwa staf sudah memberi persetujuan tertulis.']);

        $hasil = $this->wajah->periksaBukti($request, 'daftar', (array) json_decode((string) $request->input('bukti'), true), self::SAMPEL);
        if (! $hasil['ok']) {
            return back()->withErrors(['wajah' => $hasil['alasan'].' Ulangi pendaftaran.']);
        }

        $ciri = $this->wajah->rataRata($hasil['ciri']);
        foreach ($hasil['ciri'] as $c) {
            if ($this->wajah->jarak($ciri, $c) > WajahService::AMBANG_COCOK * 0.9) {
                return back()->withErrors(['wajah' => 'Sampel wajah tidak konsisten (mungkin ada orang lain yang ikut terekam). Ulangi dengan hanya satu orang di depan kamera.']);
            }
        }

        // satu wajah hanya untuk satu akun (satu role): wajah yang sama / sangat mirip akun lain ditolak
        $sudahAda = WajahStaf::with('user')->where('id_user', '!=', $user->id)->get()
            ->map(fn (WajahStaf $w) => ['user' => $w->user, 'jarak' => $this->wajah->jarak($ciri, $w->ciri)])
            ->filter(fn ($m) => $m['jarak'] < WajahService::AMBANG_MIRIP)->sortBy('jarak')->first();
        if ($sudahAda) {
            $pemilik = $sudahAda['user'];
            LogAktivitas::catat('wajah.ditolak', "Pendaftaran wajah untuk {$user->nama} ({$user->roleLabel()}) ditolak: wajah sudah terdaftar di akun {$pemilik?->nama} ({$pemilik?->roleLabel()})", $user);

            return back()->withErrors(['wajah' => "Wajah ini sudah terdaftar untuk akun {$pemilik?->nama} ({$pemilik?->roleLabel()}). Satu wajah hanya boleh dipakai satu akun. "
                .'Bila orang ini pindah tugas, hapus dulu data wajah di akun lamanya (atau ubah role akun lamanya) lalu daftarkan lagi.']);
        }

        return $this->simpan($request, $user, $ciri, count($hasil['ciri']));
    }

    /** Tantangan baru setelah percobaan di browser gagal. */
    public function tantangan(Request $request, User $user): JsonResponse
    {
        return response()->json($this->wajah->buatTantangan($request, 'daftar'));
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        $user->wajah()->delete();
        LogAktivitas::catat('wajah.hapus', "Data wajah {$user->nama} dihapus oleh {$request->user()->nama}", $user);

        return back()->with('status', "Data wajah {$user->nama} sudah dihapus permanen.");
    }

    /**
     * @param  array<int, float>  $ciri
     */
    private function simpan(Request $request, User $user, array $ciri, int $jumlah): RedirectResponse
    {
        WajahStaf::updateOrCreate(['id_user' => $user->id], [
            'ciri' => $ciri, 'jumlah_sampel' => $jumlah, 'id_pendaftar' => $request->user()->id, 'persetujuan_at' => now(), 'catatan_mirip' => null,
        ]);
        $user->forceFill(['gagal_wajah' => 0])->saveQuietly();
        LogAktivitas::catat('wajah.daftar', "Wajah {$user->nama} ({$user->roleLabel()}) didaftarkan, {$jumlah} sampel, persetujuan tertulis dikonfirmasi", $user);

        return to_route('owner.keamanan')->with('status', "Wajah {$user->nama} berhasil didaftarkan.");
    }
}
