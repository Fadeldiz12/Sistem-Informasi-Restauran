<?php

namespace App\Http\Controllers\Dapur;

use App\Http\Controllers\Controller;
use App\Models\BahanBaku;
use App\Models\MutasiBahanBaku;
use App\Services\BahanBakuService;
use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class BahanBakuController extends Controller
{
    public const SATUAN = ['kg', 'gram', 'liter', 'ml', 'pcs', 'butir', 'ikat', 'bungkus'];

    /**
     * Mutasi stok hanya boleh dicatat paling lama sekian hari ke belakang.
     */
    public const MAKS_HARI_MUTASI = 30;

    public function __construct(private BahanBakuService $bahan) {}

    public function index(): View
    {
        $bahan = BahanBaku::orderByRaw('stok <= rop desc')->orderBy('nama_bahan')->get();
        $mutasiHariIni = MutasiBahanBaku::whereDate('tanggal', today());

        return view('dapur.bahan-baku', [
            'bahan' => $bahan,
            'kritis' => $bahan->filter->isStokKritis()->values(),
            'masukHariIni' => (clone $mutasiHariIni)->where('jenis', MutasiBahanBaku::JENIS_MASUK)->count(),
            'pakaiHariIni' => (clone $mutasiHariIni)->where('jenis', MutasiBahanBaku::JENIS_PEMAKAIAN)->count(),
            'riwayat' => MutasiBahanBaku::with('bahanBaku')->latest('tanggal')->latest('id')->limit(12)->get(),
            'satuan' => self::SATUAN,
            'hariRata' => BahanBaku::HARI_RATA_PEMAKAIAN,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request, baru: true);
        $b = $this->bahan->tambah($data);

        return back()->with('status', "{$b->nama_bahan} ditambahkan · batas pesan ulang ".BahanBakuService::angka($b->rop).' '.$b->satuan);
    }

    public function update(Request $request, BahanBaku $bahan): RedirectResponse
    {
        $satuanLama = $bahan->satuan;
        $b = $this->bahan->ubah($bahan, $this->validated($request, $bahan));

        $pesan = "{$b->nama_bahan} diperbarui · batas pesan ulang ".BahanBakuService::angka($b->rop).' '.$b->satuan;
        if ($b->satuan !== $satuanLama) {
            $pesan .= " · satuan diubah dari {$satuanLama} ke {$b->satuan}, stok & resep dikonversi otomatis (stok ".BahanBakuService::angka($b->stok)." {$b->satuan})";
        }

        return back()->with('status', $pesan);
    }

    /**
     * Bahan yang masih dipakai resep tidak bisa dihapus. Bahan lain diarsipkan (soft delete)
     * supaya riwayat mutasi stoknya tetap tersimpan.
     */
    public function destroy(BahanBaku $bahan): RedirectResponse
    {
        $menu = $bahan->resep()->with('menu')->get()->pluck('menu.nama_menu')->filter()->unique()->values();
        if ($menu->isNotEmpty()) {
            return back()->with('error', "{$bahan->nama_bahan} masih dipakai di resep: {$menu->join(', ', ' dan ')}. "
                .'Hapus bahan ini dari resep menu tersebut dulu (menu Kategori & Menu di admin), atau ganti bahannya.');
        }

        $bahan->delete();

        return back()->with('status', "{$bahan->nama_bahan} diarsipkan · riwayat stok tetap tersimpan");
    }

    public function mutasi(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'id_bahan' => ['required', 'integer', Rule::exists('bahan_baku', 'id')->whereNull('deleted_at')],
            'jenis' => ['required', Rule::in([MutasiBahanBaku::JENIS_MASUK, MutasiBahanBaku::JENIS_PEMAKAIAN])],
            'jumlah' => ['required', 'numeric', 'gt:0', 'max:999999'],
            'tanggal' => ['required', 'date', 'before_or_equal:today', 'after_or_equal:'.today()->subDays(self::MAKS_HARI_MUTASI)->toDateString()],
        ], [
            'tanggal.before_or_equal' => 'Tanggal mutasi tidak boleh di masa depan.',
            'tanggal.after_or_equal' => 'Tanggal mutasi paling lama '.self::MAKS_HARI_MUTASI.' hari ke belakang.',
        ]);

        $b = BahanBaku::findOrFail($data['id_bahan']);
        $this->bahan->catatMutasi($b, $data['jenis'], (float) $data['jumlah'], $data['tanggal']);
        $b->refresh();

        return back()->with('status', ($data['jenis'] === MutasiBahanBaku::JENIS_MASUK ? 'Stok masuk' : 'Pemakaian')." {$b->nama_bahan} dicatat · stok "
            .BahanBakuService::angka($b->stok).' '.$b->satuan.($b->isStokKritis() ? ' · KRITIS' : ''));
    }

    private function validated(Request $request, ?BahanBaku $bahan = null, bool $baru = false): array
    {
        foreach (['stok', 'safety_stock'] as $f) {
            if ($request->filled($f)) {
                $request->merge([$f => str_replace(',', '.', (string) $request->input($f))]);
            }
        }

        return $request->validate([
            'nama_bahan' => ['required', 'string', 'max:100', function (string $attribute, mixed $value, Closure $fail) use ($bahan) {
                $sudahAda = BahanBaku::whereRaw('LOWER(nama_bahan) = ?', [mb_strtolower((string) $value)])
                    ->when($bahan, fn ($q) => $q->whereKeyNot($bahan->getKey()))
                    ->first();
                if ($sudahAda) {
                    $fail("Bahan \"{$sudahAda->nama_bahan}\" sudah ada. Pakai bahan itu atau beri nama lain.");
                }
            }],
            'satuan' => ['required', Rule::in(self::SATUAN)],
            'stok' => [$baru ? 'required' : 'prohibited', 'numeric', 'min:0', 'max:999999'],
            'lead_time' => ['required', 'integer', 'min:0', 'max:365'],
            'safety_stock' => ['required', 'numeric', 'min:0', 'max:999999'],
        ], [
            'stok.prohibited' => 'Stok diubah lewat Catat Mutasi Stok.',
            'satuan.in' => 'Pilih satuan dari daftar.',
        ]);
    }
}
