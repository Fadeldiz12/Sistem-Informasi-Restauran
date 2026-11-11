<?php

namespace App\Http\Controllers;

use App\Models\Pesanan;
use App\Support\TandaTanganDigital;
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
        $asli = TandaTanganDigital::cocok(TandaTanganDigital::isiLaporan($p), $request->query('s'));
        $waktu = $asli ? rescue(fn () => Carbon::createFromFormat('YmdHi', (string) $p['w']), null, false) : null;

        return view('keaslian', ['jenis' => 'laporan', 'asli' => $asli, 'laporan' => $asli ? $p + ['waktu' => $waktu] : null, 'kode' => $request->query('s')]);
    }
}
