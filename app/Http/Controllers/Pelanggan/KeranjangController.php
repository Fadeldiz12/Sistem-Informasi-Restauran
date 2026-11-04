<?php

namespace App\Http\Controllers\Pelanggan;

use App\Http\Controllers\Controller;
use App\Models\Menu;
use App\Support\Keranjang;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class KeranjangController extends Controller
{
    public function __construct(private Keranjang $keranjang) {}

    public function index(): View
    {
        $items = $this->keranjang->items();
        $diKeranjang = $items->pluck('menu.id');

        return view('pelanggan.keranjang', [
            'items' => $items,
            'ringkasan' => $this->keranjang->ringkasan($items),
            // rekomendasi: menu tersedia lain yang belum ada di keranjang
            'rekomendasi' => Menu::tersedia()->whereNotIn('id', $diKeranjang)->inRandomOrder()->limit(3)->get(),
        ]);
    }

    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $data = $request->validate([
            'id_menu' => ['required', 'integer', 'exists:menu,id'],
            'jumlah' => ['nullable', 'integer', 'min:1', 'max:'.Keranjang::MAKS_JUMLAH],
        ]);

        $menu = Menu::findOrFail($data['id_menu']);
        if (! $menu->tersedia) {
            return $this->balas($request, "{$menu->nama_menu} sedang habis.", 422);
        }

        $this->keranjang->tambah($menu, $data['jumlah'] ?? 1);

        return $this->balas($request, "{$menu->nama_menu} ditambahkan ke keranjang");
    }

    public function update(Request $request, int $menu): JsonResponse
    {
        $data = $request->validate([
            'jumlah' => ['nullable', 'integer', 'min:1', 'max:'.Keranjang::MAKS_JUMLAH],
            'catatan' => ['nullable', 'string', 'max:255'],
        ]);

        $this->keranjang->ubah($menu, $data['jumlah'] ?? null, $data['catatan'] ?? null, $request->has('catatan'));

        return $this->balas($request, 'Keranjang diperbarui');
    }

    public function destroy(Request $request, int $menu): JsonResponse|RedirectResponse
    {
        $this->keranjang->hapus($menu);

        return $this->balas($request, 'Menu dihapus dari keranjang');
    }

    public function clear(Request $request): JsonResponse|RedirectResponse
    {
        $this->keranjang->kosongkan();

        return $this->balas($request, 'Keranjang dikosongkan');
    }

    private function balas(Request $request, string $pesan, int $status = 200): JsonResponse|RedirectResponse
    {
        if ($request->expectsJson()) {
            return response()->json(['message' => $pesan] + $this->keranjang->ringkasan(), $status);
        }

        return $status >= 400 ? back()->with('error', $pesan) : back()->with('status', $pesan);
    }
}
