<?php

namespace App\Http\Controllers;

use App\Models\Pengiriman;
use App\Models\User;
use App\Services\KurirService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Foto bukti pengiriman disimpan privat. Hanya pelanggan pemilik pesanan, kurir pengantar,
 * serta kasir, admin, dan owner yang boleh melihat.
 */
class BuktiKirimController extends Controller
{
    public function __invoke(Request $request, Pengiriman $pengiriman): StreamedResponse
    {
        $user = $request->user();
        $boleh = $user->hasRole(User::ROLE_ADMIN, User::ROLE_KASIR, User::ROLE_OWNER)
            || $pengiriman->id_kurir === $user->id
            || $pengiriman->pesanan->id_pelanggan === $user->id;

        abort_unless($boleh, 403);
        abort_unless($pengiriman->bukti_kirim && Storage::disk(KurirService::DISK)->exists($pengiriman->bukti_kirim), 404);

        return Storage::disk(KurirService::DISK)->response($pengiriman->bukti_kirim, null, ['Cache-Control' => 'private, max-age=3600']);
    }
}
