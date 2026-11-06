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
        $data = $request->validate([
            'pajak' => ['required', 'numeric', 'min:0', 'max:100'],
            'biaya_layanan' => ['required', 'numeric', 'min:0', 'max:100'],
            'jam_buka' => ['required', 'date_format:H:i'],
            'jam_tutup' => ['required', 'date_format:H:i', 'different:jam_buka'],
            'lat_resto' => ['required', 'numeric', 'between:-90,90'],
            'lng_resto' => ['required', 'numeric', 'between:-180,180'],
        ], [
            'lat_resto.required' => 'Tentukan titik restoran di peta.',
            'jam_tutup.different' => 'Jam tutup tidak boleh sama dengan jam buka.',
        ]);

        PengaturanSistem::aktif()->update($data);

        return to_route('admin.pengaturan')->with('status', 'Pengaturan sistem berhasil disimpan.');
    }
}
