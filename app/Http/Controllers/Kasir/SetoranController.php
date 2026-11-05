<?php

namespace App\Http\Controllers\Kasir;

use App\Http\Controllers\Controller;
use App\Models\Pembayaran;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Setoran tunai kurir: uang pesanan bayar-di-tempat (COD) yang diterima kurir
 * diserahkan ke kasir. Kasir mencocokkan jumlahnya lalu menandai sudah disetor.
 */
class SetoranController extends Controller
{
    public function index(): View
    {
        $belum = Pembayaran::belumDisetor()
            ->with(['kurir', 'pesanan.pelanggan'])
            ->orderBy('waktu_bayar')->get()
            ->groupBy('id_kurir');

        return view('kasir.setoran', [
            'perKurir' => $belum,
            'totalBelum' => (float) $belum->flatten()->sum('jumlah'),
            'riwayat' => Pembayaran::whereNotNull('id_kurir')->whereDate('disetor_pada', today())
                ->with(['kurir', 'kasir', 'pesanan'])->latest('disetor_pada')->get(),
        ]);
    }

    /**
     * Terima setoran satu kurir. Hanya pembayaran yang tampil di layar kasir (ids) yang ditandai,
     * agar COD baru yang masuk setelah halaman dibuka tidak ikut tercatat.
     */
    public function terima(Request $request, User $kurir): RedirectResponse
    {
        $data = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
        ]);

        $jumlah = DB::transaction(function () use ($data, $kurir, $request) {
            $daftar = Pembayaran::belumDisetor()->where('id_kurir', $kurir->id)
                ->whereIn('id', $data['ids'])->lockForUpdate()->get();

            Pembayaran::whereKey($daftar->modelKeys())->update([
                'disetor_pada' => now(),
                'id_kasir' => $request->user()->id,
            ]);

            return [$daftar->count(), (float) $daftar->sum('jumlah')];
        });

        if ($jumlah[0] === 0) {
            return back()->with('error', 'Setoran ini sudah diterima sebelumnya.');
        }

        return back()->with('status', "Setoran {$kurir->nama} diterima · {$jumlah[0]} pesanan · Rp ".number_format($jumlah[1], 0, ',', '.'));
    }
}
