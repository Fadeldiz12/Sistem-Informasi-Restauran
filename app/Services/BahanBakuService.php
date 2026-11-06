<?php

namespace App\Services;

use App\Models\BahanBaku;
use App\Models\MutasiBahanBaku;
use App\Models\Pesanan;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Stok bahan baku dan Reorder Point (ROP).
 * ROP = rata_pemakaian × lead_time + safety_stock; rata_pemakaian = pemakaian N hari terakhir ÷ N.
 */
class BahanBakuService
{
    /**
     * Tambah bahan baku. Stok awal dicatat sebagai mutasi "masuk"; ROP langsung dihitung.
     */
    public function tambah(array $data): BahanBaku
    {
        return DB::transaction(function () use ($data) {
            $stokAwal = (float) ($data['stok'] ?? 0);
            $bahan = BahanBaku::create([
                'nama_bahan' => $data['nama_bahan'],
                'satuan' => $data['satuan'],
                'stok' => $stokAwal,
                'rata_pemakaian' => 0,
                'lead_time' => $data['lead_time'],
                'safety_stock' => $data['safety_stock'],
            ]);

            if ($stokAwal > 0) {
                $bahan->mutasi()->create(['jenis' => MutasiBahanBaku::JENIS_MASUK, 'jumlah' => $stokAwal, 'tanggal' => today()]);
            }

            return $this->hitungUlang($bahan);
        });
    }

    /**
     * Pasangan satuan yang bisa dikonversi otomatis: [satuan lama][satuan baru] => faktor pengali.
     */
    public const KONVERSI = [
        'kg' => ['gram' => 1000],
        'gram' => ['kg' => 0.001],
        'liter' => ['ml' => 1000],
        'ml' => ['liter' => 0.001],
    ];

    /**
     * Ubah data bahan (stok tidak diubah di sini, hanya lewat mutasi). ROP dihitung ulang.
     * Bila satuan diganti (kg↔gram, liter↔ml), stok, safety stock, takaran resep, dan riwayat mutasi ikut dikonversi.
     */
    public function ubah(BahanBaku $bahan, array $data): BahanBaku
    {
        return DB::transaction(function () use ($bahan, $data) {
            $safetyStock = (float) $data['safety_stock'];

            if ($data['satuan'] !== $bahan->satuan) {
                $faktor = $this->gantiSatuan($bahan, $data['satuan']);
                // safety stock yang tidak diubah di form masih dalam satuan lama
                if ($safetyStock === (float) $bahan->getOriginal('safety_stock')) {
                    $safetyStock = round($safetyStock * $faktor, 2);
                }
            }

            $bahan->update([
                'nama_bahan' => $data['nama_bahan'],
                'satuan' => $data['satuan'],
                'lead_time' => $data['lead_time'],
                'safety_stock' => $safetyStock,
            ]);

            return $this->hitungUlang($bahan);
        });
    }

    /**
     * Konversi stok, takaran resep, dan riwayat mutasi ke satuan baru. Mengembalikan faktor pengalinya.
     * Bahan yang belum punya stok, resep, maupun riwayat boleh ganti satuan apa saja (faktor 1).
     */
    private function gantiSatuan(BahanBaku $bahan, string $satuanBaru): float
    {
        $faktor = self::KONVERSI[$bahan->satuan][$satuanBaru] ?? null;
        $resep = $bahan->resep()->get();
        $mutasi = $bahan->mutasi()->get();

        if ($faktor === null) {
            if ((float) $bahan->stok > 0 || $resep->isNotEmpty() || $mutasi->isNotEmpty()) {
                throw ValidationException::withMessages([
                    'satuan' => "Satuan {$bahan->satuan} tidak bisa diganti ke {$satuanBaru} karena bahan ini sudah punya stok, resep, atau riwayat. "
                        .'Yang bisa dikonversi otomatis hanya kg ↔ gram dan liter ↔ ml.',
                ]);
            }

            return 1.0;
        }

        $nilai = collect([(float) $bahan->stok, (float) $bahan->safety_stock])
            ->merge($resep->map(fn ($r) => (float) $r->jumlah))
            ->merge($mutasi->map(fn ($m) => (float) $m->jumlah));
        if ($nilai->contains(fn ($n) => abs(round($n * $faktor, 2) - $n * $faktor) > 1e-9)) {
            throw ValidationException::withMessages([
                'satuan' => "Ada stok, takaran resep, atau riwayat yang terlalu kecil untuk diubah ke {$satuanBaru} (lebih dari 2 angka desimal). Satuan tetap {$bahan->satuan}.",
            ]);
        }

        $bahan->stok = round((float) $bahan->stok * $faktor, 2);
        $resep->each(fn ($r) => $r->update(['jumlah' => round((float) $r->jumlah * $faktor, 2)]));
        $mutasi->each(fn ($m) => $m->update(['jumlah' => round((float) $m->jumlah * $faktor, 2)]));

        return $faktor;
    }

    /**
     * Catat mutasi stok. Masuk menambah stok, pemakaian mengurangi stok. Setelah itu d dan ROP dihitung ulang.
     */
    public function catatMutasi(BahanBaku $bahan, string $jenis, float $jumlah, ?string $tanggal = null): MutasiBahanBaku
    {
        return DB::transaction(function () use ($bahan, $jenis, $jumlah, $tanggal) {
            $bahan = BahanBaku::whereKey($bahan->getKey())->lockForUpdate()->firstOrFail();

            if ($jenis === MutasiBahanBaku::JENIS_PEMAKAIAN && $jumlah > (float) $bahan->stok) {
                throw ValidationException::withMessages([
                    'jumlah' => 'Pemakaian melebihi stok '.$bahan->nama_bahan.' ('.self::angka($bahan->stok).' '.$bahan->satuan.').',
                ]);
            }

            $mutasi = $bahan->mutasi()->create(['jenis' => $jenis, 'jumlah' => $jumlah, 'tanggal' => $tanggal ?? today()]);

            $bahan->stok = round((float) $bahan->stok + ($jenis === MutasiBahanBaku::JENIS_MASUK ? $jumlah : -$jumlah), 2);
            $bahan->save();
            $this->hitungUlang($bahan);

            return $mutasi;
        });
    }

    /**
     * Pemakaian otomatis dari resep untuk satu pesanan (dipanggil saat dapur mulai memasak).
     * Kebutuhan = jumlah porsi × takaran resep. Bila stok tidak cukup, masakan tetap boleh dibuat:
     * stok dipakai sampai 0 (status kritis) dan nama bahannya dikembalikan sebagai peringatan.
     *
     * @return array<int, string> bahan yang stoknya tidak mencukupi
     */
    public function pakaiUntukPesanan(Pesanan $pesanan): array
    {
        $pesanan->loadMissing('detail.menu.resep');

        $kebutuhan = [];
        foreach ($pesanan->detail as $d) {
            foreach ($d->menu?->resep ?? [] as $r) {
                $kebutuhan[$r->id_bahan] = ($kebutuhan[$r->id_bahan] ?? 0) + $d->jumlah * (float) $r->jumlah;
            }
        }

        $kurang = [];
        DB::transaction(function () use ($kebutuhan, &$kurang) {
            foreach ($kebutuhan as $idBahan => $butuh) {
                $bahan = BahanBaku::whereKey($idBahan)->lockForUpdate()->first();
                if (! $bahan) {
                    continue;
                }
                $butuh = round($butuh, 2);
                $pakai = min($butuh, (float) $bahan->stok);
                if ($pakai < $butuh) {
                    $kurang[] = $bahan->nama_bahan;
                }
                if ($pakai > 0) {
                    $bahan->mutasi()->create(['jenis' => MutasiBahanBaku::JENIS_PEMAKAIAN, 'jumlah' => $pakai, 'tanggal' => today()]);
                    $bahan->stok = round((float) $bahan->stok - $pakai, 2);
                    $bahan->save();
                }
                $this->hitungUlang($bahan);
            }
        });

        return $kurang;
    }

    public function hitungUlang(BahanBaku $bahan): BahanBaku
    {
        $bahan->rata_pemakaian = $bahan->hitungRataPemakaian();
        $bahan->rop = $bahan->hitungRop();
        $bahan->save();

        return $bahan;
    }

    public static function angka(float|string|null $n): string
    {
        return rtrim(rtrim(number_format((float) $n, 2, ',', '.'), '0'), ',');
    }
}
