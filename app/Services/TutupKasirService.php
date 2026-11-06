<?php

namespace App\Services;

use App\Models\Pembayaran;
use App\Models\Pesanan;
use App\Models\Setoran;
use App\Models\TutupKasir;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * EOD (end of day) kasir: rekap penjualan sejak penutupan sebelumnya, lalu tutup periode.
 */
class TutupKasirService
{
    /**
     * Rekap transaksi dalam periode [dari, sampai].
     *
     * @return array{jumlah_pesanan:int, jumlah_batal:int, jumlah_lunas:int, total_pemasukan:float, total_tunai:float, total_nontunai:float,
     *               total_setoran:float, per_metode: Collection<string, float>, per_layanan: Collection<string, int>}
     */
    public function rekap(Carbon $dari, Carbon $sampai): array
    {
        $pesanan = Pesanan::whereBetween('waktu_pesan', [$dari, $sampai]);
        $lunas = Pembayaran::where('status_bayar', Pembayaran::STATUS_LUNAS)->whereBetween('waktu_bayar', [$dari, $sampai])->get();

        return [
            'jumlah_pesanan' => (clone $pesanan)->where('status', '!=', Pesanan::STATUS_DIBATALKAN)->count(),
            'jumlah_batal' => (clone $pesanan)->where('status', Pesanan::STATUS_DIBATALKAN)->count(),
            'jumlah_lunas' => $lunas->count(),
            'total_pemasukan' => (float) $lunas->sum('jumlah'),
            'total_tunai' => (float) $lunas->where('metode', Pembayaran::METODE_TUNAI)->sum('jumlah'),
            'total_nontunai' => (float) $lunas->where('metode', '!=', Pembayaran::METODE_TUNAI)->sum('jumlah'),
            'total_setoran' => (float) Setoran::whereBetween('waktu_setor', [$dari, $sampai])->sum('jumlah_diterima'),
            'per_metode' => $lunas->groupBy(fn ($b) => $b->metodeLabel())->map(fn ($g) => (float) $g->sum('jumlah')),
            'per_layanan' => (clone $pesanan)->where('status', '!=', Pesanan::STATUS_DIBATALKAN)
                ->selectRaw('jenis_layanan, count(*) as jumlah')->groupBy('jenis_layanan')->pluck('jumlah', 'jenis_layanan')
                ->mapWithKeys(fn ($n, $k) => [Pesanan::LABEL_LAYANAN[$k] ?? $k => (int) $n]),
        ];
    }

    /**
     * Hal yang harus dibereskan sebelum EOD: pesanan yang belum selesai dan uang tunai kurir yang belum disetor.
     *
     * @return array{pesanan: Collection<int, Pesanan>, setoran: Collection<int, Pembayaran>}
     */
    public function penghalang(): array
    {
        return [
            'pesanan' => Pesanan::with(['pelanggan', 'pengiriman.zona'])
                ->whereIn('status', [Pesanan::STATUS_MENUNGGU, Pesanan::STATUS_DIPROSES, Pesanan::STATUS_SIAP, Pesanan::STATUS_DIANTAR])
                ->orderBy('waktu_pesan')->get(),
            'setoran' => Pembayaran::belumDisetor()->with(['kurir', 'pesanan'])->get(),
        ];
    }

    public function tutup(User $kasir): TutupKasir
    {
        return DB::transaction(function () use ($kasir) {
            $halang = $this->penghalang();
            if ($halang['pesanan']->isNotEmpty() || $halang['setoran']->isNotEmpty()) {
                throw ValidationException::withMessages(['eod' => 'Masih ada '.collect([
                    $halang['pesanan']->isNotEmpty() ? $halang['pesanan']->count().' pesanan yang belum selesai' : null,
                    $halang['setoran']->isNotEmpty() ? $halang['setoran']->pluck('id_kurir')->unique()->count().' kurir yang belum menyetor uang tunai' : null,
                ])->filter()->join(' dan ').'. Bereskan dulu sebelum tutup hari.']);
            }

            $dari = TutupKasir::batas();
            $sampai = now();
            $r = $this->rekap($dari, $sampai);

            return TutupKasir::create([
                'id_kasir' => $kasir->id,
                'mulai_dari' => $dari,
                'waktu_tutup' => $sampai,
                'jumlah_pesanan' => $r['jumlah_pesanan'],
                'jumlah_batal' => $r['jumlah_batal'],
                'total_pemasukan' => $r['total_pemasukan'],
                'total_tunai' => $r['total_tunai'],
                'total_nontunai' => $r['total_nontunai'],
                'total_setoran' => $r['total_setoran'],
            ]);
        });
    }
}
