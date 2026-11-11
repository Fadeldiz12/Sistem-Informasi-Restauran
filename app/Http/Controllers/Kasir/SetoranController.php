<?php

namespace App\Http\Controllers\Kasir;

use App\Http\Controllers\Controller;
use App\Models\LogAktivitas;
use App\Models\Pembayaran;
use App\Models\Setoran;
use App\Models\TutupKasir;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Setoran tunai kurir: uang pesanan bayar-di-tempat (COD) yang diterima kurir
 * diserahkan ke kasir. Kasir mencocokkan jumlahnya lalu menandai sudah disetor.
 */
class SetoranController extends Controller
{
    public function index(): View
    {
        $batas = TutupKasir::batas();
        $belum = Pembayaran::belumDisetor()
            ->with(['kurir', 'pesanan.pelanggan'])
            ->orderBy('waktu_bayar')->get()
            ->groupBy('id_kurir');

        return view('kasir.setoran', [
            'perKurir' => $belum,
            'totalBelum' => (float) $belum->flatten()->sum('jumlah'),
            'riwayat' => Pembayaran::whereNotNull('id_kurir')->where('disetor_pada', '>=', $batas)
                ->with(['kurir', 'kasir', 'pesanan'])->latest('disetor_pada')->get(),
            'setoranHariIni' => Setoran::with(['kurir', 'kasir'])->where('waktu_setor', '>=', $batas)->latest('waktu_setor')->get(),
        ]);
    }

    /**
     * Terima setoran satu kurir. Hanya pembayaran yang tampil di layar kasir (ids) yang ditandai,
     * agar COD baru yang masuk setelah halaman dibuka tidak ikut tercatat.
     */
    public function terima(Request $request, User $kurir): RedirectResponse
    {
        $request->merge(['uang_dihitung' => str_replace(['.', ' '], '', (string) $request->input('uang_dihitung'))]);
        $data = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
            'uang_dihitung' => ['required', 'numeric', 'min:0', 'max:9999999999'],
            'catatan' => ['nullable', 'string', 'max:255'],
        ], [
            'uang_dihitung.required' => 'Hitung uang dari kurir lalu isi jumlahnya.',
        ]);

        $hasil = DB::transaction(function () use ($data, $kurir, $request) {
            $daftar = Pembayaran::belumDisetor()->where('id_kurir', $kurir->id)
                ->whereIn('id', $data['ids'])->lockForUpdate()->get();
            if ($daftar->isEmpty()) {
                return null;
            }

            $seharusnya = (float) $daftar->sum('jumlah');
            $diterima = (float) $data['uang_dihitung'];
            if (round($diterima - $seharusnya, 2) != 0 && blank($data['catatan'] ?? null)) {
                throw ValidationException::withMessages(['catatan' => 'Uang yang dihitung berbeda dari seharusnya. Tulis catatan selisihnya (mis. kurang kembalian, akan dilunasi besok).']);
            }

            $setoran = Setoran::create([
                'id_kurir' => $kurir->id,
                'id_kasir' => $request->user()->id,
                'jumlah_seharusnya' => $seharusnya,
                'jumlah_diterima' => $diterima,
                'catatan' => $data['catatan'] ?? null,
                'waktu_setor' => now(),
            ]);
            Pembayaran::whereKey($daftar->modelKeys())->update([
                'disetor_pada' => now(),
                'id_kasir' => $request->user()->id,
                'id_setoran' => $setoran->id,
            ]);

            return [$daftar->count(), $setoran];
        });

        if ($hasil === null) {
            return back()->with('error', 'Setoran ini sudah diterima sebelumnya.');
        }

        [$jumlah, $setoran] = $hasil;
        $selisih = $setoran->selisih();
        LogAktivitas::catat('setoran.terima', "Setoran tunai {$kurir->nama} diterima: {$jumlah} pesanan, Rp ".number_format((float) $setoran->jumlah_diterima, 0, ',', '.')
            .' dari seharusnya Rp '.number_format((float) $setoran->jumlah_seharusnya, 0, ',', '.').($setoran->catatan ? ". Catatan: {$setoran->catatan}" : ''), $setoran);

        return back()->with('status', "Setoran {$kurir->nama} diterima · {$jumlah} pesanan · Rp ".number_format((float) $setoran->jumlah_diterima, 0, ',', '.')
            .($selisih != 0 ? ' · selisih '.($selisih < 0 ? '-' : '+').'Rp '.number_format(abs($selisih), 0, ',', '.').' dicatat' : ''));
    }
}
