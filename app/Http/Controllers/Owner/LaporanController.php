<?php

namespace App\Http\Controllers\Owner;

use App\Exports\LaporanKeuanganExport;
use App\Http\Controllers\Controller;
use App\Services\LaporanService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class LaporanController extends Controller
{
    /**
     * Batas rentang laporan agar tabel per tanggal tetap wajar.
     */
    public const MAKS_HARI = 366;

    public function __construct(private LaporanService $laporan) {}

    public function index(Request $request): View
    {
        [$dari, $sampai] = $this->periode($request);

        return view('owner.laporan', $this->laporan->keuangan($dari, $sampai) + $this->info($dari, $sampai));
    }

    /**
     * Ekstensi PHP yang wajib aktif untuk tiap jenis unduhan (di XAMPP Windows sering belum aktif di php.ini).
     */
    public const EKSTENSI = [
        'PDF' => ['dom', 'mbstring'],
        'Excel' => ['zip', 'xmlwriter', 'dom', 'mbstring'],
    ];

    public function pdf(Request $request): Response|RedirectResponse
    {
        [$dari, $sampai] = $this->periode($request);

        return $this->buatUnduhan($request, 'PDF', function () use ($dari, $sampai) {
            $data = $this->laporan->keuangan($dari, $sampai) + $this->info($dari, $sampai) + ['pakaiLogo' => extension_loaded('gd')];

            // font subsetting: hanya huruf yang dipakai yang disimpan, PDF jauh lebih kecil & cepat dibuat
            return Pdf::loadView('owner.laporan-pdf', $data)->setPaper('a4')
                ->setOption(['isFontSubsettingEnabled' => true, 'isRemoteEnabled' => false])
                ->download('laporan-keuangan-'.$dari->format('Ymd').'-'.$sampai->format('Ymd').'.pdf');
        });
    }

    public function excel(Request $request): BinaryFileResponse|RedirectResponse
    {
        [$dari, $sampai] = $this->periode($request);

        return $this->buatUnduhan($request, 'Excel', function () use ($dari, $sampai) {
            $data = $this->laporan->keuangan($dari, $sampai) + $this->info($dari, $sampai);

            return Excel::download(new LaporanKeuanganExport($data), 'laporan-keuangan-'.$dari->format('Ymd').'-'.$sampai->format('Ymd').'.xlsx');
        });
    }

    /**
     * Buat file unduhan. Bila ekstensi PHP belum aktif atau pembuatan file gagal, owner kembali ke halaman
     * laporan dengan pesan yang jelas (bukan halaman error), dan detailnya dicatat di log.
     *
     * @param  callable(): (Response|BinaryFileResponse)  $buat
     */
    private function buatUnduhan(Request $request, string $jenis, callable $buat): Response|BinaryFileResponse|RedirectResponse
    {
        $kembali = to_route('owner.laporan', $request->only(['dari', 'sampai', 'cepat']));

        $kurang = array_values(array_filter(self::EKSTENSI[$jenis], fn ($e) => ! extension_loaded($e)));
        if ($kurang) {
            return $kembali->withErrors(['unduh' => "{$jenis} belum bisa dibuat: ekstensi PHP ".implode(', ', $kurang)
                .' belum aktif. Buka php.ini, hapus tanda ; di depan extension='.implode(' dan extension=', $kurang).', lalu restart Apache / php artisan serve.']);
        }

        @set_time_limit(120); // laporan setahun bisa lebih lama dari batas bawaan 30 detik
        try {
            return $buat();
        } catch (\Throwable $e) {
            report($e);

            return $kembali->withErrors(['unduh' => "Gagal membuat file {$jenis}: ".rtrim(Str::limit($e->getMessage(), 160), '.').'. Coba rentang tanggal lebih pendek, atau lihat storage/logs/laravel.log.']);
        }
    }

    /**
     * Periode dari query (?dari=&sampai= atau ?cepat=hari|7hari|bulan). Bawaan: 7 hari terakhir.
     *
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}
     */
    private function periode(Request $request): array
    {
        $hariIni = CarbonImmutable::today();

        $cepat = match ($request->query('cepat')) {
            'hari' => [$hariIni, $hariIni],
            'bulan' => [$hariIni->startOfMonth(), $hariIni],
            '7hari' => [$hariIni->subDays(6), $hariIni],
            default => null,
        };
        if ($cepat) {
            return $cepat;
        }
        if (! $request->filled('dari') && ! $request->filled('sampai')) {
            return [$hariIni->subDays(6), $hariIni];
        }

        $data = $request->validate([
            'dari' => ['required', 'date_format:Y-m-d'],
            'sampai' => ['required', 'date_format:Y-m-d', 'after_or_equal:dari'],
        ], [
            'sampai.after_or_equal' => 'Tanggal akhir tidak boleh sebelum tanggal awal.',
            'dari.date_format' => 'Format tanggal tidak valid. Pilih tanggal dari kalender.',
            'sampai.date_format' => 'Format tanggal tidak valid. Pilih tanggal dari kalender.',
        ], ['dari' => 'dari tanggal', 'sampai' => 'sampai tanggal']);

        $dari = CarbonImmutable::parse($data['dari']);
        $sampai = CarbonImmutable::parse($data['sampai']);
        if ($dari->diffInDays($sampai) + 1 > self::MAKS_HARI) {
            throw ValidationException::withMessages(['sampai' => 'Rentang laporan maksimal '.self::MAKS_HARI.' hari.']);
        }

        return [$dari, $sampai];
    }

    private function info(CarbonImmutable $dari, CarbonImmutable $sampai): array
    {
        $hariIni = CarbonImmutable::today();
        $judul = $dari->isSameDay($sampai)
            ? $dari->translatedFormat('j M Y')
            : $dari->translatedFormat($dari->year === $sampai->year ? 'j M' : 'j M Y').' – '.$sampai->translatedFormat('j M Y');

        return [
            'dari' => $dari,
            'sampai' => $sampai,
            'judulPeriode' => $judul,
            'cepat' => match (true) {
                $dari->isSameDay($hariIni) && $sampai->isSameDay($hariIni) => 'hari',
                $dari->isSameDay($hariIni->subDays(6)) && $sampai->isSameDay($hariIni) => '7hari',
                $dari->isSameDay($hariIni->startOfMonth()) && $sampai->isSameDay($hariIni) => 'bulan',
                default => null,
            },
        ];
    }
}
