<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BahanBaku;
use App\Models\Kategori;
use App\Models\Menu;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class MenuController extends Controller
{
    public function index(Request $request): View
    {
        $kategoriId = $request->integer('kategori') ?: null;
        $status = in_array($request->query('status'), ['tersedia', 'habis'], true) ? $request->query('status') : null;
        $q = trim((string) $request->query('q'));

        $menu = Menu::query()
            ->with(['kategori', 'resep'])
            ->when($kategoriId, fn ($query) => $query->where('id_kategori', $kategoriId))
            ->when($status, fn ($query) => $query->where('tersedia', $status === 'tersedia'))
            ->when($q !== '', fn ($query) => $query->where('nama_menu', 'like', "%{$q}%"))
            ->orderBy('nama_menu')
            ->paginate(10)
            ->withQueryString();

        return view('admin.menu', [
            'menu' => $menu,
            'kategori' => Kategori::withCount('menu')->orderBy('nama_kategori')->get(),
            'navbar' => Kategori::navbar()->get(),
            'kategoriId' => $kategoriId,
            'status' => $status,
            'q' => $q,
            'totalMenu' => Menu::count(),
            'totalTersedia' => Menu::where('tersedia', true)->count(),
            'bahan' => BahanBaku::orderBy('nama_bahan')->get(['id', 'nama_bahan', 'satuan']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $resep = $this->resepValid($request);
        $data['foto'] = $request->file('foto')?->store('menu', 'public');

        DB::transaction(fn () => $this->simpanResep(Menu::create($data), $resep));

        return back()->with('status', 'Menu berhasil ditambahkan.');
    }

    public function update(Request $request, Menu $menu): RedirectResponse
    {
        $data = $this->validated($request);
        $resep = $this->resepValid($request);

        if ($request->hasFile('foto')) {
            $this->hapusFoto($menu);
            $data['foto'] = $request->file('foto')->store('menu', 'public');
        }

        DB::transaction(function () use ($menu, $data, $resep) {
            $menu->update($data);
            $this->simpanResep($menu, $resep);
        });

        return back()->with('status', 'Menu berhasil diperbarui.');
    }

    /**
     * Ubah status tersedia / habis langsung dari tabel.
     */
    public function tersedia(Request $request, Menu $menu): JsonResponse
    {
        $data = $request->validate(['tersedia' => ['required', 'boolean']]);

        $menu->update(['tersedia' => $data['tersedia']]);

        return response()->json([
            'tersedia' => $menu->tersedia,
            'message' => $menu->tersedia ? 'Menu ditandai Tersedia' : 'Menu ditandai Habis',
        ]);
    }

    public function destroy(Menu $menu): RedirectResponse
    {
        if ($menu->detailPesanan()->exists()) {
            return back()->with('error', "Menu {$menu->nama_menu} sudah pernah dipesan sehingga tidak bisa dihapus. Ubah statusnya menjadi Habis saja.");
        }

        $this->hapusFoto($menu);
        $menu->delete();

        return back()->with('status', 'Menu berhasil dihapus.');
    }

    private function validated(Request $request): array
    {
        // Harga boleh diketik dengan titik ribuan, contoh 109.000
        $request->merge(['harga' => str_replace(['.', ' '], '', (string) $request->input('harga'))]);

        $data = $request->validate([
            'nama_menu' => ['required', 'string', 'max:100'],
            'id_kategori' => ['required', 'integer', 'exists:kategori,id'],
            'harga' => ['required', 'numeric', 'min:0', 'max:9999999999'],
            'foto' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        unset($data['foto']);
        $data['tersedia'] = $request->boolean('tersedia');

        return $data;
    }

    /**
     * Baris resep: bahan + takaran per porsi. Baris kosong diabaikan, bahan yang sama dijumlahkan.
     *
     * @return array<int, float> id_bahan => jumlah
     */
    private function resepValid(Request $request): array
    {
        $baris = collect($request->input('resep', []))
            ->filter(fn ($r) => is_array($r) && (filled($r['id_bahan'] ?? null) || filled($r['jumlah'] ?? null)))
            ->map(fn ($r) => ['id_bahan' => $r['id_bahan'] ?? null, 'jumlah' => str_replace(',', '.', (string) ($r['jumlah'] ?? ''))])
            ->values()->all();
        $request->merge(['resep' => $baris]);

        $request->validate([
            'resep' => ['array', 'max:30'],
            'resep.*.id_bahan' => ['required', 'integer', 'exists:bahan_baku,id'],
            'resep.*.jumlah' => ['required', 'numeric', 'gt:0', 'max:99999'],
        ], [], [
            'resep.*.id_bahan' => 'bahan resep',
            'resep.*.jumlah' => 'takaran resep',
        ]);

        $hasil = [];
        foreach ($baris as $r) {
            $hasil[(int) $r['id_bahan']] = round(($hasil[(int) $r['id_bahan']] ?? 0) + (float) $r['jumlah'], 2);
        }

        return $hasil;
    }

    /**
     * @param  array<int, float>  $resep
     */
    private function simpanResep(Menu $menu, array $resep): void
    {
        $menu->resep()->whereNotIn('id_bahan', array_keys($resep) ?: [0])->delete();
        foreach ($resep as $idBahan => $jumlah) {
            $menu->resep()->updateOrCreate(['id_bahan' => $idBahan], ['jumlah' => $jumlah]);
        }
    }

    private function hapusFoto(Menu $menu): void
    {
        if ($menu->foto && ! Str::startsWith($menu->foto, ['http://', 'https://'])) {
            Storage::disk('public')->delete($menu->foto);
        }
    }
}
