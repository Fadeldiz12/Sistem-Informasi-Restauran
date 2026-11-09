<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Halaman pemesanan pelanggan: boleh dibuka tamu (tanpa login) dan akun pelanggan.
 * Staf yang sedang login tidak memesan dari sini.
 */
class PelangganAtauTamu
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if ($user && ! $user->hasRole(User::ROLE_PELANGGAN)) {
            abort(403, 'Halaman pemesanan hanya untuk pelanggan. Keluar dulu dari akun staf untuk memesan.');
        }

        return $next($request);
    }
}
