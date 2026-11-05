<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Akun pelanggan dari Google harus mengisi nomor HP sebelum memesan.
 */
class EnsureProfilLengkap
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()?->perluLengkapiProfil()) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Lengkapi nomor HP terlebih dahulu.'], 409);
            }

            return redirect()->guest(route('profil.lengkapi'));
        }

        return $next($request);
    }
}
