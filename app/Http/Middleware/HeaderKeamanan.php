<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Security headers untuk semua halaman: cegah clickjacking, MIME sniffing, sumber skrip asing, dan bocoran referer.
 */
class HeaderKeamanan
{
    /**
     * Sumber luar yang memang dipakai: peta Leaflet (unpkg), tile & alamat OpenStreetMap, Google Fonts, foto menu (https).
     */
    public const CSP = [
        "default-src 'self'",
        // wasm-unsafe-eval: MediaPipe Face Landmarker berjalan sebagai WebAssembly (bukan eval JavaScript)
        "script-src 'self' 'unsafe-inline' 'wasm-unsafe-eval' https://unpkg.com",
        "style-src 'self' 'unsafe-inline' https://unpkg.com https://fonts.googleapis.com",
        "font-src 'self' data: https://fonts.gstatic.com",
        "img-src 'self' data: blob: https:",
        "connect-src 'self' https://nominatim.openstreetmap.org",
        "media-src 'self' blob: mediastream:",
        "worker-src 'self' blob:",
        "object-src 'none'",
        "base-uri 'self'",
        "form-action 'self'",
        "frame-ancestors 'self'",
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        $h = $response->headers;

        $h->set('X-Content-Type-Options', 'nosniff');
        $h->set('X-Frame-Options', 'SAMEORIGIN');
        $h->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $h->set('Permissions-Policy', 'camera=(self), geolocation=(self), microphone=(), payment=(), usb=()');
        $h->set('Cross-Origin-Opener-Policy', 'same-origin');
        if (! $h->has('Content-Security-Policy')) {
            $h->set('Content-Security-Policy', implode('; ', self::CSP));
        }
        if ($request->isSecure()) {
            $h->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }
        $h->remove('X-Powered-By');
        header_remove('X-Powered-By');

        return $response;
    }
}
