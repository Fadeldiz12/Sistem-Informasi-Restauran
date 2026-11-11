<?php

namespace App\Http\Middleware;

use App\Support\SesiStaf;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Sesi staf berakhir bila akunnya terkunci, dipakai masuk di perangkat lain, atau terlalu lama tanpa aktivitas.
 */
class AmankanSesiStaf
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (! $user) {
            return $next($request);
        }

        if ($alasan = SesiStaf::alasanBerakhir($request, $user)) {
            $pesan = SesiStaf::akhiri($request, $user, $alasan);

            if ($request->expectsJson()) {
                return response()->json(['message' => $pesan, 'login' => route('login')], 401);
            }

            return redirect()->route('login')->with('status', $pesan);
        }

        // cek notifikasi berkala (GET JSON) dan kirim GPS berkala bukan aktivitas pengguna
        if (! ($request->isMethod('GET') && $request->expectsJson()) && ! $request->routeIs('sesi.lokasi')) {
            $request->session()->put('terakhir_aktif', now()->timestamp);
        }

        return $next($request);
    }
}
