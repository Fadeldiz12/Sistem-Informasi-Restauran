<?php

namespace App\Http\Controllers\Dapur;

use App\Http\Controllers\Controller;
use App\Models\BahanBaku;
use App\Models\MutasiBahanBaku;
use App\Services\BahanBakuService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class BahanBakuController extends Controller
{
    public const SATUAN = ['kg', 'gram', 'liter', 'ml', 'pcs', 'butir', 'ikat', 'bungkus'];

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

        return back()->with('status', "{$b->nama_bahan} ditambahkan · ROP ".BahanBakuService::angka($b->rop).' '.$b->satuan);
    }

    public function update(Request $request, BahanBaku $bahan): RedirectResponse
    {
        $b = $this->bahan->ubah($bahan, $this->validated($request, $bahan));

        return back()->with('status', "{$b->nama_bahan} diperbarui · ROP ".BahanBakuService::angka($b->rop).' '.$b->satuan);
    }

    public function destroy(BahanBaku $bahan): RedirectResponse
    {
        $bahan->delete(); // riwayat mutasinya ikut terhapus (on delete cascade)

        return back()->with('status', "{$bahan->nama_bahan} dihapus");
    }

    public function mutasi(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'id_bahan' => ['required', 'integer', 'exists:bahan_baku,id'],
            'jenis' => ['required', Rule::in([MutasiBahanBaku::JENIS_MASUK, MutasiBahanBaku::JENIS_PEMAKAIAN])],
            'jumlah' => ['required', 'numeric', 'gt:0', 'max:999999'],
            'tanggal' => ['required', 'date', 'before_or_equal:today'],
        ], [
            'tanggal.before_or_equal' => 'Tanggal mutasi tidak boleh di masa depan.',
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
            'nama_bahan' => ['required', 'string', 'max:100', Rule::unique('bahan_baku', 'nama_bahan')->ignore($bahan)],
            'satuan' => ['required', 'string', 'max:20'],
            'stok' => [$baru ? 'required' : 'prohibited', 'numeric', 'min:0', 'max:999999'],
            'lead_time' => ['required', 'integer', 'min:0', 'max:365'],
            'safety_stock' => ['required', 'numeric', 'min:0', 'max:999999'],
        ], [
            'stok.prohibited' => 'Stok diubah lewat Catat Mutasi Stok.',
        ]);
    }
}
