<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Kategori;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class KategoriController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        Kategori::create($this->validated($request));

        return back()->with('status', 'Kategori berhasil ditambahkan.');
    }

    public function update(Request $request, Kategori $kategori): RedirectResponse
    {
        $kategori->update($this->validated($request, $kategori));

        return back()->with('status', 'Kategori berhasil diperbarui.');
    }

    public function destroy(Kategori $kategori): RedirectResponse
    {
        if ($kategori->menu()->exists()) {
            return back()->with('error', "Kategori {$kategori->nama_kategori} masih berisi menu. Pindahkan atau hapus menunya dulu.");
        }

        $kategori->delete();

        return to_route('admin.menu')->with('status', 'Kategori berhasil dihapus.');
    }

    private function validated(Request $request, ?Kategori $kategori = null): array
    {
        return $request->validate([
            'nama_kategori' => ['required', 'string', 'max:100', Rule::unique('kategori', 'nama_kategori')->ignore($kategori)],
        ]);
    }
}
