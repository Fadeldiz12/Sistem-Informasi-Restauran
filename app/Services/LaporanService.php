<?php

namespace App\Services;

use App\Models\DetailPesanan;
use App\Models\Pembayaran;
use App\Models\PengaturanSistem;
use App\Models\Pengeluaran;
use App\Models\Pesanan;
use Carbon\CarbonImmutable;
use Carbon\CarbonPeriod;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Data penjualan & keuangan untuk owner.
 * Pendapatan = pembayaran berstatus Lunas (tanggal = waktu_bayar), pesanan yang dibatalkan tidak dihitung.
 * Laba = pendapatan − pengeluaran operasional.
 */
class LaporanService
{
    public const PERIODE = ['harian' => 'Harian', 'mingguan' => 'Mingguan', 'bulanan' => 'Bulanan'];

    /**
     * Pembayaran lunas dalam rentang waktu.
     */
    public function lunas(CarbonImmutable $dari, CarbonImmutable $sampai): Builder
    {
        return Pembayaran::query()
            ->where('status_bayar', Pembayaran::STATUS_LUNAS)
            ->whereBetween('waktu_bayar', [$dari->startOfDay(), $sampai->endOfDay()])
            ->whereHas('pesanan', fn ($q) => $q->where('status', '!=', Pesanan::STATUS_DIBATALKAN));
    }

    /**
     * Dashboard penjualan: harian (per jam hari ini), mingguan (per hari minggu ini), bulanan (6 bulan terakhir).
     */
    public function dashboard(string $periode): array
    {
        $now = CarbonImmutable::now();

        [$dari, $sampai, $label, $sub] = match ($periode) {
            'mingguan' => [$now->startOfWeek(), $now->endOfWeek(), 'minggu ini', 'Grafik penjualan per hari · minggu ini'],
            'bulanan' => [$now->subMonths(5)->startOfMonth(), $now->endOfMonth(), '6 bulan terakhir', 'Grafik penjualan per bulan · 6 bulan terakhir'],
            default => [$now->startOfDay(), $now->endOfDay(), 'hari ini', 'Grafik penjualan per jam · hari ini'],
        };

        $bayar = $this->lunas($dari, $sampai)->get(['id_pesanan', 'jumlah', 'waktu_bayar']);

        // kelompok grafik
        $grup = match ($periode) {
            'mingguan' => collect(CarbonPeriod::create($dari, '1 day', $sampai->startOfDay()))
                ->mapWithKeys(fn ($d) => [$d->format('Y-m-d') => $d->translatedFormat('D')]),
            'bulanan' => collect(range(5, 0))->mapWithKeys(fn ($i) => [$now->subMonths($i)->format('Y-m') => $now->subMonths($i)->translatedFormat('M')]),
            default => $this->jamGrafik($bayar),
        };
        $kunci = fn ($w) => match ($periode) {
            'mingguan' => $w->format('Y-m-d'),
            'bulanan' => $w->format('Y-m'),
            default => $w->format('H'),
        };
        $nilai = $grup->map(fn () => 0.0);
        foreach ($bayar as $b) {
            $k = $kunci($b->waktu_bayar);
            if ($nilai->has($k)) {
                $nilai[$k] += (float) $b->jumlah;
            }
        }

        $idPesanan = $bayar->pluck('id_pesanan');
        $layanan = Pesanan::whereIn('id', $idPesanan)->selectRaw('jenis_layanan, count(*) as jumlah')->groupBy('jenis_layanan')->pluck('jumlah', 'jenis_layanan')
            ->map(fn ($n) => (int) $n);
        $jml = $idPesanan->count();

        return [
            'periode' => $periode,
            'label' => $label,
            'sub' => $sub,
            'total' => (float) $bayar->sum('jumlah'),
            'jumlah' => $jml,
            'grafik' => ['label' => $grup->values()->all(), 'nilai' => $nilai->values()->all()],
            'terlaris' => $this->menuTerlaris($idPesanan),
            'layanan' => collect(Pesanan::LABEL_LAYANAN)->map(fn ($nama, $k) => [
                'nama' => $nama,
                'jumlah' => $layanan[$k] ?? 0,
                'persen' => $jml ? (int) round(($layanan[$k] ?? 0) / $jml * 100) : 0,
            ]),
        ];
    }

    /**
     * Menu terlaris (porsi) dari pesanan yang lunas.
     */
    public function menuTerlaris(Collection $idPesanan, int $batas = 5): Collection
    {
        return DetailPesanan::query()->with('menu:id,nama_menu')
            ->whereIn('id_pesanan', $idPesanan)
            ->selectRaw('id_menu, sum(jumlah) as porsi')->groupBy('id_menu')
            ->orderByDesc('porsi')->limit($batas)->get()
            ->map(fn ($d) => ['nama' => $d->menu?->nama_menu ?? 'Menu dihapus', 'porsi' => (int) $d->porsi]);
    }

    /**
     * Laporan keuangan per tanggal.
     *
     * @return array{baris: Collection, pendapatan: float, pengeluaran: float, laba: float, adaData: bool}
     */
    public function keuangan(CarbonImmutable $dari, CarbonImmutable $sampai): array
    {
        $masuk = $this->lunas($dari, $sampai)->get(['jumlah', 'waktu_bayar'])
            ->groupBy(fn ($b) => $b->waktu_bayar->format('Y-m-d'))->map->sum('jumlah');
        $keluar = Pengeluaran::whereDate('tanggal', '>=', $dari->toDateString())->whereDate('tanggal', '<=', $sampai->toDateString())->get(['nominal', 'tanggal'])
            ->groupBy(fn ($p) => $p->tanggal->format('Y-m-d'))->map->sum('nominal');

        $baris = collect(CarbonPeriod::create($dari->startOfDay(), '1 day', $sampai->startOfDay()))->map(function ($d) use ($masuk, $keluar) {
            $k = $d->format('Y-m-d');
            $p = (float) ($masuk[$k] ?? 0);
            $e = (float) ($keluar[$k] ?? 0);

            return ['tanggal' => CarbonImmutable::parse($k), 'pendapatan' => $p, 'pengeluaran' => $e, 'laba' => $p - $e];
        });

        return [
            'baris' => $baris,
            'pendapatan' => (float) $masuk->sum(),
            'pengeluaran' => (float) $keluar->sum(),
            'laba' => (float) $masuk->sum() - (float) $keluar->sum(),
            'adaData' => $masuk->isNotEmpty() || $keluar->isNotEmpty(),
        ];
    }

    /**
     * Jam pada grafik harian: jam operasional, diperluas bila ada pembayaran di luar jam itu.
     */
    private function jamGrafik(Collection $bayar): Collection
    {
        $set = PengaturanSistem::aktif();
        $buka = (int) substr((string) $set->jam_buka, 0, 2);
        $tutup = (int) substr((string) $set->jam_tutup, 0, 2);
        if ($tutup <= $buka) {
            [$buka, $tutup] = [0, 23];
        }
        $jam = $bayar->map(fn ($b) => (int) $b->waktu_bayar->format('H'));
        $buka = min($buka, $jam->min() ?? $buka);
        $tutup = max($tutup - 1, $jam->max() ?? 0);

        return collect(range($buka, $tutup))->mapWithKeys(fn ($h) => [str_pad((string) $h, 2, '0', STR_PAD_LEFT) => str_pad((string) $h, 2, '0', STR_PAD_LEFT)]);
    }

    /**
     * Format ringkas: Rp 38,45 jt / Rp 1,09 M / Rp 950.000.
     */
    public static function ringkas(float $n): string
    {
        $f = fn ($v) => rtrim(rtrim(number_format($v, 2, ',', '.'), '0'), ',');

        $tanda = $n < 0 ? '-' : '';
        $n = abs($n);

        return $tanda.match (true) {
            $n >= 1e9 => 'Rp '.$f($n / 1e9).' miliar',
            $n >= 1e6 => 'Rp '.$f($n / 1e6).' jt',
            default => 'Rp '.number_format($n, 0, ',', '.'),
        };
    }
}
