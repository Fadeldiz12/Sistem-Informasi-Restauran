<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Kategori;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class KategoriController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        DB::transaction(fn () => $this->simpan(new Kategori, $data));

        return back()->with('status', 'Kategori berhasil ditambahkan.');
    }

    public function update(Request $request, Kategori $kategori): RedirectResponse
    {
        $data = $this->validated($request, $kategori);

        DB::transaction(fn () => $this->simpan($kategori, $data));

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
        $data = $request->validate([
            'nama_kategori' => ['required', 'string', 'max:100', Rule::unique('kategori', 'nama_kategori')->ignore($kategori)],
            'urutan_navbar' => ['nullable', 'required_if:tampil_navbar,1', 'integer', 'between:1,'.Kategori::MAKS_NAVBAR],
        ], [
            'urutan_navbar.required_if' => 'Pilih urutan kategori di navbar.',
        ]);

        $data['tampil_navbar'] = $request->boolean('tampil_navbar');

        if ($data['tampil_navbar'] && ! $kategori?->tampil_navbar) {
            $terpakai = Kategori::where('tampil_navbar', true)->count();

            if ($terpakai >= Kategori::MAKS_NAVBAR) {
                throw ValidationException::withMessages([
                    'tampil_navbar' => 'Navbar sudah berisi '.Kategori::MAKS_NAVBAR.' kategori. Sembunyikan salah satu kategori dulu.',
                ]);
            }
        }

        if (! $data['tampil_navbar']) {
            $data['urutan_navbar'] = null;
        }

        return $data;
    }

    /**
     * Simpan kategori. Kalau urutan navbar sudah dipakai kategori lain,
     * kategori itu bertukar posisi (atau pindah ke posisi kosong pertama).
     */
    private function simpan(Kategori $kategori, array $data): void
    {
        $urutanBaru = $data['urutan_navbar'];
        $urutanLama = $kategori->tampil_navbar ? $kategori->urutan_navbar : null;

        if ($urutanBaru !== null) {
            $bentrok = Kategori::where('tampil_navbar', true)
                ->where('urutan_navbar', $urutanBaru)
                ->when($kategori->exists, fn ($q) => $q->whereKeyNot($kategori->getKey()))
                ->first();

            if ($bentrok) {
                $terpakai = Kategori::where('tampil_navbar', true)->pluck('urutan_navbar')->all();
                $kosong = collect(range(1, Kategori::MAKS_NAVBAR))->first(fn ($n) => ! in_array($n, $terpakai, true));
                $bentrok->update(['urutan_navbar' => $urutanLama ?? $kosong]);
            }
        }

        $kategori->fill($data)->save();
    }
}
