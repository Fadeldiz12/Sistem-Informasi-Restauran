<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ZonaPengiriman;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ZonaController extends Controller
{
    public function index(): View
    {
        $zona = ZonaPengiriman::query()
            ->withCount(['pengiriman as kirim_bulan_ini' => fn ($q) => $q->where('created_at', '>=', now()->startOfMonth())])
            ->orderBy('radius_km')
            ->get();

        return view('admin.zona', ['zona' => $zona]);
    }

    public function store(Request $request): RedirectResponse
    {
        ZonaPengiriman::create($this->validated($request));

        return back()->with('status', 'Zona pengiriman berhasil ditambahkan.');
    }

    public function update(Request $request, ZonaPengiriman $zona): RedirectResponse
    {
        $zona->update($this->validated($request, $zona));

        return back()->with('status', 'Zona pengiriman berhasil diperbarui.');
    }

    public function destroy(ZonaPengiriman $zona): RedirectResponse
    {
        if ($zona->pengiriman()->exists()) {
            return back()->with('error', "{$zona->nama_zona} sudah dipakai pada data pengiriman sehingga tidak bisa dihapus.");
        }

        $zona->delete();

        return back()->with('status', 'Zona pengiriman berhasil dihapus.');
    }

    private function validated(Request $request, ?ZonaPengiriman $zona = null): array
    {
        $request->merge(['tarif_per_km' => str_replace(['.', ' '], '', (string) $request->input('tarif_per_km'))]);

        return $request->validate([
            'nama_zona' => ['required', 'string', 'max:100', Rule::unique('zona_pengiriman', 'nama_zona')->ignore($zona)],
            'radius_km' => ['required', 'numeric', 'gt:0', 'max:9999'],
            'tarif_per_km' => ['required', 'numeric', 'min:0', 'max:9999999999'],
        ]);
    }
}
