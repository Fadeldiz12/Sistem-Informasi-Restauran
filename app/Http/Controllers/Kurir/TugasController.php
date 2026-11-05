<?php

namespace App\Http\Controllers\Kurir;

use App\Http\Controllers\Controller;
use App\Models\Pembayaran;
use App\Models\Pengiriman;
use App\Services\KurirService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TugasController extends Controller
{
    private const RELASI = ['pesanan.pelanggan', 'pesanan.detail.menu', 'pesanan.pembayaran', 'zona'];

    public function __construct(private KurirService $kurir) {}

    /**
     * Daftar tugas pengiriman (FIFO dari waktu siap paling awal).
     */
    public function index(Request $request): View
    {
        return view('kurir.tugas', $this->ringkasan($request) + [
            'antrean' => KurirService::antrean()->with(self::RELASI)->get(),
        ]);
    }

    public function show(Request $request, Pengiriman $pengiriman): View|RedirectResponse
    {
        $saya = $pengiriman->id_kurir === $request->user()->id;
        if ($pengiriman->id_kurir && ! $saya) {
            return to_route('kurir.tugas')->with('error', 'Tugas ini sudah diambil kurir lain.');
        }
        if ($saya && $pengiriman->status_kirim === Pengiriman::STATUS_DALAM_PERJALANAN) {
            return to_route('kurir.status', ['pengiriman' => $pengiriman->id]);
        }

        $antrean = KurirService::antrean()->pluck('id');

        return view('kurir.detail', $this->ringkasan($request) + [
            'kirim' => $pengiriman->load(self::RELASI),
            'urutan' => $antrean->search($pengiriman->id),
        ]);
    }

    public function terima(Request $request, Pengiriman $pengiriman): RedirectResponse
    {
        $this->kurir->terima($pengiriman, $request->user());

        return to_route('kurir.status', ['pengiriman' => $pengiriman->id])
            ->with('status', 'Tugas diterima · status Dalam Perjalanan · pelanggan dapat melacak pesanan');
    }

    /**
     * Status pengiriman yang sedang diantar kurir ini.
     */
    public function status(Request $request): View
    {
        $aktif = $request->user()->tugasPengiriman()
            ->where('status_kirim', Pengiriman::STATUS_DALAM_PERJALANAN)
            ->with(self::RELASI)->orderBy('updated_at')->get();
        $kirim = $aktif->firstWhere('id', $request->integer('pengiriman')) ?? $aktif->first();

        return view('kurir.status', $this->ringkasan($request) + [
            'aktif' => $aktif,
            'kirim' => $kirim,
            'tagihTunai' => $kirim ? $this->kurir->perluTagihTunai($kirim->pesanan) : false,
        ]);
    }

    public function diterima(Request $request, Pengiriman $pengiriman): RedirectResponse
    {
        $data = $request->validate([
            'bukti' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'uang_diterima' => ['nullable', 'boolean'],
        ], [
            'bukti.required' => 'Foto bukti pengiriman wajib diunggah.',
            'bukti.max' => 'Ukuran foto bukti maksimal 5 MB.',
        ]);

        $this->kurir->diterima($pengiriman, $request->user(), $data['bukti'], $request->boolean('uang_diterima'));

        return to_route('kurir.riwayat')->with('status', $pengiriman->pesanan->kode().' diterima · pesanan Selesai');
    }

    public function riwayat(Request $request): View
    {
        return view('kurir.riwayat', $this->ringkasan($request) + [
            'riwayat' => $request->user()->tugasPengiriman()
                ->where('status_kirim', Pengiriman::STATUS_DITERIMA)
                ->with(self::RELASI)->latest('updated_at')->limit(30)->get(),
        ]);
    }

    /**
     * Angka di header: antrean, sedang diantar, selesai hari ini.
     */
    private function ringkasan(Request $request): array
    {
        $saya = $request->user()->tugasPengiriman();

        return [
            'jmlAntrean' => KurirService::antrean()->count(),
            'jmlDiantar' => (clone $saya)->where('status_kirim', Pengiriman::STATUS_DALAM_PERJALANAN)->count(),
            'jmlSelesai' => (clone $saya)->where('status_kirim', Pengiriman::STATUS_DITERIMA)->whereDate('updated_at', today())->count(),
            'tunaiDibawa' => (float) Pembayaran::belumDisetor()->where('id_kurir', $request->user()->id)->sum('jumlah'),
        ];
    }
}
