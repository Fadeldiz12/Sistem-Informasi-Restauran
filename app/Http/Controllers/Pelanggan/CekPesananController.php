<?php

namespace App\Http\Controllers\Pelanggan;

use App\Http\Controllers\Controller;
use App\Models\Pesanan;
use App\Support\AturanAkun;
use App\Support\PesananTamu;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Tamu (pesan tanpa login) yang kehilangan link lacak: cari pesanan dengan nomor pesanan + nomor HP.
 */
class CekPesananController extends Controller
{
    public function create(): View
    {
        return view('pelanggan.cek-pesanan');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'nomor' => ['required', 'string', 'max:20'],
            'no_hp' => ['required', 'string', 'max:25'],
        ], [
            'nomor.required' => 'Isi nomor pesanan, contoh ORD-0007.',
            'no_hp.required' => 'Isi nomor HP yang dipakai saat memesan.',
        ]);

        $id = PesananTamu::idDariNomor($data['nomor']);
        $pesanan = $id ? Pesanan::where('sumber', Pesanan::SUMBER_ONLINE)->whereNull('id_pelanggan')->find($id) : null;
        $cocok = $pesanan && $pesanan->no_hp_pemesan === AturanAkun::rapikanHp($data['no_hp']);

        if (! $cocok) {
            // pesan sama untuk nomor salah / HP salah, supaya nomor pesanan orang lain tidak bisa ditebak
            return back()->withInput()->withErrors(['nomor' => 'Pesanan tidak ditemukan. Periksa nomor pesanan dan nomor HP. Pesanan yang dibuat dengan akun bisa dilihat setelah masuk.']);
        }

        PesananTamu::ingat($pesanan);

        return to_route('pelanggan.tracking', ['pesanan' => $pesanan]);
    }
}
