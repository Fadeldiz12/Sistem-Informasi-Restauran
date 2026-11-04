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
     * Ubah data bahan (stok tidak diubah di sini, hanya lewat mutasi). ROP dihitung ulang.
     */
    public function ubah(BahanBaku $bahan, array $data): BahanBaku
    {
        $bahan->update([
            'nama_bahan' => $data['nama_bahan'],
            'satuan' => $data['satuan'],
            'lead_time' => $data['lead_time'],
            'safety_stock' => $data['safety_stock'],
        ]);

        return $this->hitungUlang($bahan);
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
