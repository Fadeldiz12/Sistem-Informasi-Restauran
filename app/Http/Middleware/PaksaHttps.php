<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Di produksi dengan APP_URL https://, permintaan lewat http dialihkan ke https supaya password, cookie sesi,
 * dan data pribadi tidak pernah terkirim tanpa enkripsi. Health check /up tetap bisa lewat http (dipakai pemantau hosting).
 */
class PaksaHttps
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! self::aktif() || $request->isSecure() || $request->is('up')) {
            return $next($request);
        }

        // 308 untuk selain GET/HEAD supaya browser mengulang dengan metode & isi yang sama
        $kode = $request->isMethod('GET') || $request->isMethod('HEAD') ? 301 : 308;

        return redirect()->to('https://'.$request->getHttpHost().$request->getRequestUri(), $kode);
    }

    public static function aktif(): bool
    {
        return app()->environment('production') && str_starts_with((string) config('app.url'), 'https://');
    }
}
