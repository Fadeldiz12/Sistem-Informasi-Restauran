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
            'tertunda' => $request->session()->get('wajah_tertunda.id') === $user->id ? $request->session()->get('wajah_tertunda') : null,
        ]);
    }

    public function store(Request $request, User $user): RedirectResponse
    {
        abort_unless($user->isStaf(), 404);
        $request->validate(['setuju' => ['accepted']], ['setuju.accepted' => 'Centang pernyataan bahwa staf sudah memberi persetujuan tertulis.']);

        // langkah kedua: wajah sangat mirip akun lain dan Owner sudah memastikan ini orang berbeda
        $tertunda = $request->session()->get('wajah_tertunda');
        if ($request->boolean('konfirmasi_mirip') && ($tertunda['id'] ?? null) === $user->id && $tertunda['sampai'] >= now()->timestamp) {
            $request->session()->forget('wajah_tertunda');

            return $this->simpan($request, $user, $tertunda['ciri'], $tertunda['jumlah'], $tertunda['catatan']);
        }

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

        // 1:1 tetap dipakai saat login, tapi wajah yang terlalu mirip akun lain (mis. kembar) ditahan dulu
        $mirip = WajahStaf::with('user')->where('id_user', '!=', $user->id)->get()
            ->map(fn (WajahStaf $w) => ['nama' => $w->user?->nama, 'jarak' => $this->wajah->jarak($ciri, $w->ciri)])
            ->filter(fn ($m) => $m['jarak'] < WajahService::AMBANG_MIRIP)->sortBy('jarak')->first();
        if ($mirip) {
            $catatan = "Sangat mirip wajah {$mirip['nama']} (jarak ".number_format($mirip['jarak'], 2).'), disetujui Owner';
            $request->session()->put('wajah_tertunda', ['id' => $user->id, 'ciri' => $ciri, 'jumlah' => count($hasil['ciri']), 'catatan' => $catatan, 'sampai' => now()->addMinutes(5)->timestamp]);

            return back()->with('status', "Wajah ini sangat mirip dengan wajah {$mirip['nama']}. Pastikan ini orang yang berbeda (mis. kembar), lalu setujui.");
        }

        return $this->simpan($request, $user, $ciri, count($hasil['ciri']), null);
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
    private function simpan(Request $request, User $user, array $ciri, int $jumlah, ?string $catatan): RedirectResponse
    {
        WajahStaf::updateOrCreate(['id_user' => $user->id], [
            'ciri' => $ciri, 'jumlah_sampel' => $jumlah, 'id_pendaftar' => $request->user()->id, 'persetujuan_at' => now(), 'catatan_mirip' => $catatan,
        ]);
        $user->forceFill(['gagal_wajah' => 0])->saveQuietly();
        LogAktivitas::catat('wajah.daftar', "Wajah {$user->nama} ({$user->roleLabel()}) didaftarkan, {$jumlah} sampel, persetujuan tertulis dikonfirmasi".($catatan ? " · {$catatan}" : ''), $user);

        return to_route('owner.keamanan')->with('status', "Wajah {$user->nama} berhasil didaftarkan.");
    }
}
