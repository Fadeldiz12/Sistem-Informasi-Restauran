<?php

namespace App\Http\Controllers;

use App\Models\Pembayaran;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Bukti pembayaran disimpan privat; hanya pemilik pesanan, kasir, owner, dan admin yang boleh melihat.
 */
class BuktiBayarController extends Controller
{
    public function __invoke(Request $request, Pembayaran $pembayaran): StreamedResponse
    {
        $user = $request->user();
        $boleh = $user->hasRole(User::ROLE_KASIR, User::ROLE_OWNER, User::ROLE_ADMIN)
            || $pembayaran->pesanan?->id_pelanggan === $user->id;

        abort_unless($boleh && $pembayaran->bukti_bayar && Storage::disk('local')->exists($pembayaran->bukti_bayar), 404);

        return Storage::disk('local')->response($pembayaran->bukti_bayar);
    }
}
