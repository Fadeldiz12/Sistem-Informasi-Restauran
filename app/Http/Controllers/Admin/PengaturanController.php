<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PengaturanSistem;
use App\Models\ZonaPengiriman;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PengaturanController extends Controller
{
    public function edit(): View
    {
        return view('admin.pengaturan', [
            'pengaturan' => PengaturanSistem::aktif(),
            // contoh perhitungan memakai ongkir zona terdekat
            'contohOngkir' => ZonaPengiriman::orderBy('radius_km')->first()?->ongkir() ?? 0,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $request->merge(['biaya_layanan' => str_replace(['.', ' '], '', (string) $request->input('biaya_layanan'))]);

        $data = $request->validate([
            'pajak' => ['required', 'numeric', 'min:0', 'max:100'],
            'biaya_layanan' => ['required', 'numeric', 'min:0', 'max:9999999999'],
            'jam_buka' => ['required', 'date_format:H:i'],
            'jam_tutup' => ['required', 'date_format:H:i', 'after:jam_buka'],
        ], [
            'jam_tutup.after' => 'Jam tutup harus setelah jam buka.',
        ]);

        PengaturanSistem::aktif()->update($data);

        return to_route('admin.pengaturan')->with('status', 'Pengaturan sistem berhasil disimpan.');
    }
}
