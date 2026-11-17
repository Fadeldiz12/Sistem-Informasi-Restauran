<?php

namespace App\Http\Controllers;

use App\Models\Pesanan;
use App\Support\TandaTanganDigital;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

/**
 * Halaman publik cek keaslian struk & laporan (dari kode verifikasi / QR yang tercetak).
 */
class KeaslianDokumenController extends Controller
{
    public function struk(Request $request, Pesanan $pesanan): View
    {
        $pesanan->load(['detail.menu', 'pembayaran']);
        $asli = $pesanan->isLunas() && TandaTanganDigital::cocok(TandaTanganDigital::isiStruk($pesanan), $request->query('s'));

        return view('keaslian', ['jenis' => 'struk', 'asli' => $asli, 'pesanan' => $asli ? $pesanan : null, 'kode' => $request->query('s')]);
    }

    public function laporan(Request $request): View
    {
        $p = $request->only(['dari', 'sampai', 'pd', 'pk', 'lb', 'w']);
        $isi = TandaTanganDigital::isiLaporan($p);
        // laporan baru membawa tanda tangan Ed25519 (wajib cocok); laporan lama hanya punya kode HMAC
        $ed25519 = $request->filled('sig');
        $asli = $ed25519
            ? TandaTanganDigital::cocokEd25519($isi, (string) $request->query('sig')) && TandaTanganDigital::cocok($isi, $request->query('s'))
            : TandaTanganDigital::cocok($isi, $request->query('s'));
        $waktu = $asli ? rescue(fn () => Carbon::createFromFormat('YmdHi', (string) $p['w']), null, false) : null;

        return view('keaslian', ['jenis' => 'laporan', 'asli' => $asli, 'laporan' => $asli ? $p + ['waktu' => $waktu] : null,
            'kode' => $request->query('s'), 'ed25519' => $ed25519 && $asli]);
    }

    /**
     * Kunci publik Ed25519 untuk memverifikasi tanda tangan laporan secara mandiri (tanpa kunci rahasia restoran).
     */
    public function kunciPublik(): JsonResponse
    {
        $kunci = TandaTanganDigital::kunciPublik();
        abort_unless($kunci, 404, 'Tanda tangan Ed25519 belum aktif di server ini.');

        return response()->json([
            'penerbit' => config('resto.nama'),
            'algoritma' => 'Ed25519 (RFC 8032)',
            'kunci_publik' => $kunci[0],
            'sidik' => TandaTanganDigital::sidikKunci($kunci[0]),
            'kunci_publik_lama' => array_slice($kunci, 1),
            'cara_verifikasi' => 'Pesan = "laporan|{dari}|{sampai}|{pd}|{pk}|{lb}|{w}" dari parameter tautan laporan; '
                .'tanda tangan = parameter "sig" (base64url). Verifikasi dengan libsodium crypto_sign_verify_detached / OpenSSL Ed25519.',
        ]);
    }
}
