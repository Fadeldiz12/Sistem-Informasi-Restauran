<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Kategori;
use App\Models\Menu;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
            ->with('kategori')
            ->when($kategoriId, fn ($query) => $query->where('id_kategori', $kategoriId))
            ->when($status, fn ($query) => $query->where('tersedia', $status === 'tersedia'))
            ->when($q !== '', fn ($query) => $query->where('nama_menu', 'like', "%{$q}%"))
            ->orderBy('nama_menu')
            ->paginate(10)
            ->withQueryString();

        return view('admin.menu', [
            'menu' => $menu,
            'kategori' => Kategori::withCount('menu')->orderBy('nama_kategori')->get(),
            'kategoriId' => $kategoriId,
            'status' => $status,
            'q' => $q,
            'totalMenu' => Menu::count(),
            'totalTersedia' => Menu::where('tersedia', true)->count(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $data['foto'] = $request->file('foto')?->store('menu', 'public');

        Menu::create($data);

        return back()->with('status', 'Menu berhasil ditambahkan.');
    }

    public function update(Request $request, Menu $menu): RedirectResponse
    {
        $data = $this->validated($request);

        if ($request->hasFile('foto')) {
            $this->hapusFoto($menu);
            $data['foto'] = $request->file('foto')->store('menu', 'public');
        }

        $menu->update($data);

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

    private function hapusFoto(Menu $menu): void
    {
        if ($menu->foto && ! Str::startsWith($menu->foto, ['http://', 'https://'])) {
            Storage::disk('public')->delete($menu->foto);
        }
    }
}
