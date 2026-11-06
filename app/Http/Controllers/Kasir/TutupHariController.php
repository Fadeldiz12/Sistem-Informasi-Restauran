<?php

namespace App\Http\Controllers\Kasir;

use App\Http\Controllers\Controller;
use App\Models\TutupKasir;
use App\Services\TutupKasirService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Tutup Hari (EOD) kasir: lihat rekap sejak EOD terakhir, tutup periode, cetak rekap.
 */
class TutupHariController extends Controller
{
    public function __construct(private TutupKasirService $eod) {}

    public function index(): View
    {
        $dari = TutupKasir::batas();

        return view('kasir.tutup-hari', [
            'dari' => $dari,
            'rekap' => $this->eod->rekap($dari, now()),
            'halang' => $this->eod->penghalang(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $tutup = $this->eod->tutup($request->user());
        // rekap yang baru ditutup hanya bisa dicetak oleh kasir ini di sesi ini
        $request->session()->put('eod_cetak', $tutup->id);

        return to_route('kasir.eod.cetak', $tutup)->with('status', 'Hari ditutup. Penjualan kasir dimulai dari nol.');
    }

    public function cetak(Request $request, TutupKasir $tutup): View
    {
        abort_unless($request->session()->get('eod_cetak') === $tutup->id && $tutup->id_kasir === $request->user()->id, 403, 'Rekap tutup hari hanya bisa dicetak sesaat setelah EOD. Rekap lengkap ada di laporan owner.');

        return view('kasir.tutup-hari-cetak', [
            'tutup' => $tutup->load('kasir'),
            'rekap' => $this->eod->rekap($tutup->mulai_dari, $tutup->waktu_tutup),
        ]);
    }
}
