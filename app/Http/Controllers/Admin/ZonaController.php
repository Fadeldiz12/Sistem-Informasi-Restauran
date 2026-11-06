<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ZonaPengiriman;
use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
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

        $data = $request->validate([
            'nama_zona' => ['required', 'string', 'max:100', Rule::unique('zona_pengiriman', 'nama_zona')->ignore($zona)],
            'contoh_wilayah' => ['nullable', 'string', 'max:255'],
            'radius_km' => ['required', 'numeric', 'gt:0', 'max:9999', function (string $attribute, mixed $value, Closure $fail) use ($zona) {
                $sama = ZonaPengiriman::where('radius_km', round((float) $value, 2))
                    ->when($zona, fn ($q) => $q->whereKeyNot($zona->getKey()))
                    ->first();
                if ($sama) {
                    $fail("Radius {$sama->radius_km} km sudah dipakai {$sama->nama_zona}. Setiap zona harus punya radius berbeda supaya jangkauannya tidak tumpang tindih.");
                }
            }],
            'tarif_per_km' => ['required', 'numeric', 'min:0', 'max:99999999'],
            'konfirmasi_gratis' => ['nullable', 'boolean'],
        ], [
            'tarif_per_km.max' => 'Tarif per km maksimal Rp 99.999.999.',
        ]);

        if ((float) $data['tarif_per_km'] === 0.0 && ! $request->boolean('konfirmasi_gratis')) {
            throw ValidationException::withMessages([
                'tarif_per_km' => 'Tarif Rp 0 berarti ongkir gratis. Centang "Ongkir gratis untuk zona ini" bila memang disengaja.',
            ]);
        }
        unset($data['konfirmasi_gratis']);

        return $data;
    }
}
