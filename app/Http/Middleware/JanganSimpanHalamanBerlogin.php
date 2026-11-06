<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Halaman yang dibuka saat login tidak boleh disimpan cache browser, supaya tombol Back
 * setelah logout tidak menampilkan data akun sebelumnya (penting di HP/tablet yang dipakai bergantian).
 */
class JanganSimpanHalamanBerlogin
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($request->user() && ! $response->headers->has('Content-Disposition')) {
            $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0, private');
            $response->headers->set('Pragma', 'no-cache');
            $response->headers->set('Expires', '0');
        }

        return $response;
    }
}
