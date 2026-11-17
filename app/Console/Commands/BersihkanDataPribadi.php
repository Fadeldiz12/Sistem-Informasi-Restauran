<?php

namespace App\Console\Commands;

use App\Models\LogAktivitas;
use App\Models\Pesanan;
use App\Models\User;
use App\Models\WajahStaf;
use App\Services\KurirService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

#[Signature('resto:bersihkan-data-pribadi {--coba : Hanya hitung, tidak menghapus apa pun}')]
#[Description('Hapus data pribadi yang melewati masa simpan (pesanan lama, wajah staf tidak aktif, akun pelanggan tidak aktif)')]
class BersihkanDataPribadi extends Command
{
    /** Pengganti alamat antar yang dihapus (kolom alamat wajib terisi). */
    public const ALAMAT_DIHAPUS = '[dihapus sesuai masa simpan data]';

    public function handle(): int
    {
        $coba = (bool) $this->option('coba');
        $retensi = config('resto.retensi');

        $pesanan = $this->pesananLama((int) $retensi['pesanan_hari'], $coba);
        $wajah = $this->wajahTidakAktif((int) $retensi['wajah_hari'], $coba);
        $pelanggan = $this->pelangganTidakAktif((int) $retensi['pelanggan_hari'], $coba);

        $ringkas = "{$pesanan} pesanan lama dianonimkan, {$wajah} data wajah staf tidak aktif dihapus, {$pelanggan} akun pelanggan tidak aktif dihapus";
        if ($coba) {
            $this->info("Mode coba (tidak ada yang dihapus): {$ringkas}.");

            return self::SUCCESS;
        }
        if ($pesanan + $wajah + $pelanggan > 0) {
            LogAktivitas::catat('data.retensi', 'Pembersihan data pribadi otomatis: '.$ringkas);
        }
        $this->info("Selesai: {$ringkas}.");

        return self::SUCCESS;
    }

    /**
     * Nama, HP, email pemesan; alamat, titik peta, patokan, penerima, dan foto bukti kirim dari pesanan selesai/batal
     * yang lebih tua dari masa simpan. Total, menu, dan pembayaran tetap ada untuk laporan keuangan.
     */
    private function pesananLama(int $hari, bool $coba): int
    {
        $query = Pesanan::whereNull('data_pribadi_dihapus_at')
            ->whereIn('status', [Pesanan::STATUS_SELESAI, Pesanan::STATUS_DIBATALKAN])
            ->where('waktu_pesan', '<', now()->subDays($hari));
        if ($coba) {
            return $query->count();
        }

        $jumlah = 0;
        $query->with('pengiriman')->chunkById(200, function ($daftar) use (&$jumlah) {
            foreach ($daftar as $p) {
                DB::transaction(function () use ($p) {
                    if ($kirim = $p->pengiriman) {
                        if ($kirim->bukti_kirim) {
                            Storage::disk(KurirService::DISK)->delete($kirim->bukti_kirim);
                        }
                        $kirim->forceFill(['alamat' => self::ALAMAT_DIHAPUS, 'nama_penerima' => null, 'no_hp_penerima' => null,
                            'lat' => null, 'lng' => null, 'patokan' => null, 'bukti_kirim' => null])->saveQuietly();
                    }
                    $p->forceFill(['nama_pemesan' => null, 'no_hp_pemesan' => null, 'email_pemesan' => null, 'data_pribadi_dihapus_at' => now()])->saveQuietly();
                });
                $jumlah++;
            }
        });

        return $jumlah;
    }

    /**
     * Data wajah staf yang tidak login selama masa simpan (staf sudah berhenti tapi akunnya belum dihapus).
     */
    private function wajahTidakAktif(int $hari, bool $coba): int
    {
        $batas = now()->subDays($hari);
        $tidakAktif = WajahStaf::with('user')->where('created_at', '<', $batas)->get()->filter(function (WajahStaf $w) use ($batas) {
            $terakhir = LogAktivitas::where('id_user', $w->id_user)->where('aksi', 'akun.login')->max('created_at');

            return ! $terakhir || $batas->gt($terakhir);
        });
        if (! $coba) {
            foreach ($tidakAktif as $w) {
                $w->delete();
                LogAktivitas::catat('wajah.hapus', "Data wajah {$w->user?->nama} dihapus otomatis: tidak login lebih dari {$hari} hari", $w->user);
            }
        }

        return $tidakAktif->count();
    }

    /**
     * Akun pelanggan yang dibuat dan tidak memesan selama masa simpan. Pesanannya tetap ada (tanpa tautan ke akun).
     */
    private function pelangganTidakAktif(int $hari, bool $coba): int
    {
        $batas = now()->subDays($hari);
        $query = User::where('role', User::ROLE_PELANGGAN)->where('created_at', '<', $batas)
            ->whereDoesntHave('pesanan', fn ($q) => $q->where(fn ($w) => $w->where('waktu_pesan', '>=', $batas)
                ->orWhereNotIn('status', [Pesanan::STATUS_SELESAI, Pesanan::STATUS_DIBATALKAN])));
        if ($coba) {
            return $query->count();
        }

        $jumlah = 0;
        $query->chunkById(200, function ($daftar) use (&$jumlah) {
            foreach ($daftar as $u) {
                $u->deleteQuietly();
                $jumlah++;
            }
        });

        return $jumlah;
    }
}
