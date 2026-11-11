<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\LogAktivitas;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

/**
 * Audit log untuk Owner: siapa melakukan apa dan kapan. Hanya bisa dibaca.
 */
class AktivitasController extends Controller
{
    public function __invoke(Request $request): View
    {
        $request->validate([
            'dari' => ['nullable', 'date_format:Y-m-d'],
            'sampai' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:dari'],
        ], [
            'dari.date_format' => 'Format tanggal tidak valid. Pilih tanggal dari kalender.',
            'sampai.date_format' => 'Format tanggal tidak valid. Pilih tanggal dari kalender.',
            'sampai.after_or_equal' => 'Tanggal akhir tidak boleh sebelum tanggal awal.',
        ]);

        $sampai = $request->filled('sampai') ? Carbon::parse($request->query('sampai')) : today();
        $dari = $request->filled('dari') ? Carbon::parse($request->query('dari')) : $sampai->copy()->subDays(6);
        $kategori = array_key_exists((string) $request->query('kategori'), LogAktivitas::KATEGORI) ? $request->query('kategori') : null;
        $q = trim((string) $request->query('q'));

        $log = LogAktivitas::query()
            ->whereBetween('created_at', [$dari->copy()->startOfDay(), $sampai->copy()->endOfDay()])
            ->when($kategori, fn ($w) => $w->kategori($kategori))
            ->when($q !== '', fn ($w) => $w->where(fn ($x) => $x->where('keterangan', 'like', "%{$q}%")->orWhere('nama_user', 'like', "%{$q}%")->orWhere('ip', $q)))
            ->latest('created_at')->latest('id')
            ->paginate(25)->withQueryString();

        return view('owner.aktivitas', [
            'log' => $log,
            'dari' => $dari,
            'sampai' => $sampai,
            'kategori' => $kategori,
            'q' => $q,
            'loginHariIni' => LogAktivitas::where('aksi', 'akun.login')->where('created_at', '>=', today())->count(),
            'gagal7Hari' => LogAktivitas::where('aksi', 'akun.login_gagal')->where('created_at', '>=', today()->subDays(6))->count(),
            'terkunci' => User::whereNotNull('terkunci_at')->count(),
        ]);
    }
}
