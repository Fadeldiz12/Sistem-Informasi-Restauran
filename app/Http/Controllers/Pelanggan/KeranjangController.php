<?php

namespace App\Http\Controllers\Pelanggan;

use App\Http\Controllers\Controller;
use App\Models\Menu;
use App\Models\PengaturanSistem;
use App\Models\Pesanan;
use App\Support\Keranjang;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class KeranjangController extends Controller
{
    public function __construct(private Keranjang $keranjang) {}

    public function index(Request $request): View
    {
        $items = $this->keranjang->items();
        $diKeranjang = $items->pluck('menu.id');
        $dibuang = $this->keranjang->dibuang();

        return view('pelanggan.keranjang', [
            'dibuang' => $dibuang,
            'set' => PengaturanSistem::aktif(),
            'pesananTerakhir' => $items->isEmpty() ? $this->pesananBaru($request) : null,
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
            'catatan' => ['nullable', 'string', 'max:255'],
        ]);

        $menu = Menu::findOrFail($data['id_menu']);
        if (! $menu->tersedia) {
            return $this->balas($request, "{$menu->nama_menu} sedang habis.", 422);
        }

        if ($this->keranjang->tambah($menu, $data['jumlah'] ?? 1, $data['catatan'] ?? null) === 0) {
            return $this->balas($request, $menu->nama_menu.' sudah '.Keranjang::MAKS_JUMLAH.' porsi, batas maksimal per menu.', 422);
        }

        return $this->balas($request, "{$menu->nama_menu} ditambahkan ke keranjang");
    }

    /**
     * Pesanan yang baru dibuat di sesi ini (mis. pelanggan menekan Back dari halaman pembayaran).
     */
    private function pesananBaru(Request $request): ?Pesanan
    {
        $id = $request->session()->get(CheckoutController::SESI_PESANAN_BARU);

        return $id ? $request->user()->pesanan()->whereKey($id)->where('waktu_pesan', '>=', now()->subHour())->first() : null;
    }

    public function update(Request $request, string $menu): JsonResponse
    {
        $menu = $this->idMenu($menu);
        $data = $request->validate([
            'jumlah' => ['nullable', 'integer', 'min:1', 'max:'.Keranjang::MAKS_JUMLAH],
            'catatan' => ['nullable', 'string', 'max:255'],
        ]);

        $this->keranjang->ubah($menu, $data['jumlah'] ?? null, $data['catatan'] ?? null, $request->has('catatan'));

        return $this->balas($request, 'Keranjang diperbarui');
    }

    public function destroy(Request $request, string $menu): JsonResponse|RedirectResponse
    {
        $menu = $this->idMenu($menu);
        $this->keranjang->hapus($menu);

        return $this->balas($request, 'Menu dihapus dari keranjang');
    }

    /**
     * ID menu dari kode di URL (menu yang sudah dihapus admin tetap bisa dibuang dari keranjang).
     */
    private function idMenu(string $kode): int
    {
        return Menu::idDariKode($kode) ?? abort(404);
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
