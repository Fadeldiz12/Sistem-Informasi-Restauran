<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\Pengeluaran;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PengeluaranController extends Controller
{
    public function index(Request $request): View
    {
        $q = trim((string) $request->query('q'));
        $kategori = in_array($request->query('kategori'), Pengeluaran::KATEGORI, true) ? $request->query('kategori') : null;

        $bulanIni = Pengeluaran::whereDate('tanggal', '>=', today()->startOfMonth()->toDateString())->whereDate('tanggal', '<=', today()->endOfMonth()->toDateString());
        $totalBulan = (float) (clone $bulanIni)->sum('nominal');
        $terbesar = (clone $bulanIni)->selectRaw('kategori, sum(nominal) as total')->groupBy('kategori')->orderByDesc('total')->first();

        return view('owner.pengeluaran', [
            'pengeluaran' => Pengeluaran::query()
                ->when($q !== '', fn ($w) => $w->where(fn ($x) => $x->where('keterangan', 'like', "%{$q}%")->orWhere('kategori', 'like', "%{$q}%")))
                ->when($kategori, fn ($w) => $w->where('kategori', $kategori))
                ->orderByDesc('tanggal')->orderByDesc('id')
                ->paginate(15)->withQueryString(),
            'q' => $q,
            'kategoriAktif' => $kategori,
            'totalBulan' => $totalBulan,
            'jmlBulan' => (clone $bulanIni)->count(),
            'terbesar' => $terbesar,
            'persenTerbesar' => $terbesar && $totalBulan > 0 ? (int) round($terbesar->total / $totalBulan * 100) : 0,
            'rataHarian' => $totalBulan / today()->day,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Pengeluaran::create($this->validated($request) + ['id_owner' => $request->user()->id]);

        return back()->with('status', 'Pengeluaran ditambahkan');
    }

    public function update(Request $request, Pengeluaran $pengeluaran): RedirectResponse
    {
        $pengeluaran->update($this->validated($request));

        return back()->with('status', 'Pengeluaran diperbarui');
    }

    public function destroy(Pengeluaran $pengeluaran): RedirectResponse
    {
        $pengeluaran->delete();

        return back()->with('status', 'Pengeluaran dihapus');
    }

    private function validated(Request $request): array
    {
        $request->merge(['nominal' => str_replace(['.', ' '], '', (string) $request->input('nominal'))]);

        return $request->validate([
            'tanggal' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
            'kategori' => ['required', Rule::in(Pengeluaran::KATEGORI)],
            'nominal' => ['required', 'numeric', 'gt:0', 'max:9999999999'],
            'keterangan' => ['nullable', 'string', 'max:500'],
        ], [
            'tanggal.before_or_equal' => 'Tanggal pengeluaran tidak boleh di masa depan.',
        ]);
    }
}
