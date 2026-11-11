<?php

namespace App\Http\Middleware;

use App\Services\WajahService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Aksi sensitif (data pengguna, pengaturan, keamanan outlet, unduh laporan keuangan) meminta wajah + password lagi
 * bila verifikasi wajah diaktifkan Owner. Konfirmasi berlaku beberapa menit.
 */
class KonfirmasiUlang
{
    public const BERLAKU_MENIT = 10;

    public function __construct(private WajahService $wajah) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (! $user || ! $user->isStaf() || ! $this->wajah->wajib()) {
            return $next($request);
        }
        if (now()->timestamp - (int) $request->session()->get('konfirmasi_at') < self::BERLAKU_MENIT * 60) {
            return $next($request);
        }

        $get = $request->isMethod('GET');
        $request->session()->put('konfirmasi_tujuan', $get ? $request->fullUrl() : url()->previous());
        // aksi yang tertahan (simpan/hapus) dijalankan otomatis setelah konfirmasi berhasil; password tidak ikut disimpan
        $request->session()->put('konfirmasi_aksi', $get || $request->allFiles() ? null : [
            'url' => $request->fullUrl(),
            'method' => $request->method(),
            'input' => $request->except(['_token', '_method', 'password', 'password_confirmation']),
            'sampai' => now()->addMinutes(self::BERLAKU_MENIT)->timestamp,
        ]);
        $pesan = $get ? 'Konfirmasi identitas dulu untuk melanjutkan.' : 'Demi keamanan, konfirmasi identitas dulu. Setelah itu perubahanmu langsung disimpan.';

        if ($request->expectsJson()) {
            return response()->json(['message' => $pesan, 'konfirmasi' => route('konfirmasi')], 423);
        }

        return redirect()->route('konfirmasi')->with('status', $pesan);
    }
}
