<?php

namespace App\Services;

use App\Models\Menu;
use App\Models\Pembayaran;
use App\Models\PengaturanSistem;
use App\Models\Pesanan;
use App\Models\TiketDapur;
use App\Models\User;
use App\Models\ZonaPengiriman;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use RuntimeException;

/**
 * Aturan inti pesanan: hitung total, buat pesanan, konfirmasi/tolak, dan pembayaran.
 */
class PesananService
{
    /**
     * Batas kolom decimal(12,2) untuk subtotal/total.
     */
    public const TOTAL_MAKS = 9_999_999_999.99;

    /** Delivery yang diinput kasir: dibayar sekarang di kasir */
    public const BAYAR_DI_KASIR = 'kasir';

    /** Delivery yang diinput kasir: dibayar tunai ke kurir saat pesanan tiba (COD) */
    public const BAYAR_KE_KURIR = 'kurir';

    public function __construct(private MidtransService $midtrans) {}

    /**
     * Total = subtotal + pajak% + biaya layanan% + ongkir.
     *
     * @return array{subtotal: float, pajak: float, biaya_layanan: float, ongkir: float, total: float}
     */
    public function hitung(float $subtotal, float $ongkir = 0): array
    {
        $set = PengaturanSistem::aktif();
        $pajak = $set->hitungPajak($subtotal);
        $layanan = $set->hitungBiayaLayanan($subtotal);

        return [
            'subtotal' => round($subtotal, 2),
            'pajak' => $pajak,
            'biaya_layanan' => $layanan,
            'ongkir' => round($ongkir, 2),
            'total' => round($subtotal + $pajak + $layanan + $ongkir, 2),
        ];
    }

    /**
     * Buat pesanan beserta detailnya.
     *
     * @param  array<int, array{id_menu:int, jumlah:int, catatan?:?string}>  $items
     * @param  array{nomor_meja?:?string, nama_pemesan?:?string, alamat?:?string, id_zona?:?int}  $data
     *
     * Pesanan dari pelanggan berstatus "menunggu" (menunggu konfirmasi kasir).
     * Pesanan yang diinput kasir langsung "diproses" dan tiket otomatis dikirim ke dapur.
     */
    public function buat(array $items, string $jenisLayanan, ?User $pelanggan = null, ?User $kasir = null, array $data = []): Pesanan
    {
        if (! $items) {
            throw ValidationException::withMessages(['items' => 'Pesanan belum berisi menu.']);
        }

        return DB::transaction(function () use ($items, $jenisLayanan, $pelanggan, $kasir, $data) {
            [$baris, $subtotal] = $this->barisPesanan($items);

            $nomorMeja = $jenisLayanan === Pesanan::LAYANAN_DINE_IN
                ? $this->nomorMeja($data['nomor_meja'] ?? null, cekTerpakai: $kasir !== null)
                : null;

            $zona = null;
            if ($jenisLayanan === Pesanan::LAYANAN_DELIVERY) {
                $zona = ZonaPengiriman::find($data['id_zona'] ?? null);
                if (! $zona) {
                    throw ValidationException::withMessages(['id_zona' => 'Pilih zona pengiriman.']);
                }
            }

            $hasil = $this->hitung($subtotal, $zona?->ongkir() ?? 0);
            if ($hasil['total'] > self::TOTAL_MAKS) {
                throw ValidationException::withMessages(['items' => 'Total pesanan terlalu besar. Kurangi jumlah porsi atau periksa harga menu.']);
            }

            $pesanan = Pesanan::create([
                'id_pelanggan' => $pelanggan?->id,
                'id_kasir' => $kasir?->id,
                'jenis_layanan' => $jenisLayanan,
                'nomor_meja' => $nomorMeja,
                'nama_pemesan' => $data['nama_pemesan'] ?? null,
                'waktu_pesan' => now(),
                'subtotal' => $hasil['subtotal'],
                'ongkir' => $hasil['ongkir'],
                'total' => $hasil['total'],
                'status' => $kasir ? Pesanan::STATUS_DIPROSES : Pesanan::STATUS_MENUNGGU,
            ]);

            $pesanan->detail()->createMany($baris);

            if ($zona) {
                // jarak sebenarnya dari titik peta; bila tidak ada titik, memakai radius zona
                $pesanan->pengiriman()->create([
                    'id_zona' => $zona->id,
                    'nama_penerima' => $data['nama_penerima'] ?? null,
                    'no_hp_penerima' => $data['no_hp_penerima'] ?? null,
                    'alamat' => $data['alamat'],
                    'lat' => $data['lat'] ?? null,
                    'lng' => $data['lng'] ?? null,
                    'patokan' => $data['patokan'] ?? null,
                    'jarak_km' => $data['jarak_km'] ?? $zona->radius_km,
                ]);
            }

            if ($kasir) {
                $this->aturBayarDeliveryKasir($pesanan, $data['bayar'] ?? null);
                $this->kirimTiketDapur($pesanan);
            }

            return $pesanan;
        });
    }

    /**
     * Baris detail pesanan dari daftar item; menu yang habis/tidak ada ditolak.
     *
     * @param  array<int, array{id_menu:int, jumlah:int, catatan?:?string}>  $items
     * @return array{0: array<int, array{id_menu:int, jumlah:int, catatan:?string, subtotal:float}>, 1: float}
     */
    private function barisPesanan(array $items): array
    {
        $menu = Menu::whereIn('id', array_column($items, 'id_menu'))->lockForUpdate()->get()->keyBy('id');

        $baris = [];
        $subtotal = 0;
        foreach ($items as $it) {
            $m = $menu->get($it['id_menu']);
            if (! $m || ! $m->tersedia) {
                throw ValidationException::withMessages(['items' => ($m?->nama_menu ?? 'Salah satu menu').' sedang tidak tersedia. Hapus dari pesanan atau pilih menu lain.']);
            }
            $jumlah = max(1, (int) $it['jumlah']);
            $sub = (float) $m->harga * $jumlah;
            $subtotal += $sub;
            $baris[] = ['id_menu' => $m->id, 'jumlah' => $jumlah, 'catatan' => $it['catatan'] ?? null, 'subtotal' => $sub];
        }

        return [$baris, $subtotal];
    }

    /**
     * Nomor meja Dine In harus ada di restoran (01 s.d. jumlah meja). Untuk pesanan kasir,
     * meja yang masih dipakai pesanan Dine In lain yang belum selesai juga ditolak.
     */
    public function nomorMeja(?string $nomor, bool $cekTerpakai = true, ?Pesanan $kecuali = null): string
    {
        $jumlahMeja = (int) config('resto.jumlah_meja');
        $angka = trim((string) $nomor);
        if (! ctype_digit($angka) || (int) $angka < 1 || (int) $angka > $jumlahMeja) {
            throw ValidationException::withMessages([
                'nomor_meja' => 'Nomor meja tidak dikenal. Pilih meja 01 – '.str_pad((string) $jumlahMeja, 2, '0', STR_PAD_LEFT).'.',
            ]);
        }
        $no = str_pad((string) (int) $angka, 2, '0', STR_PAD_LEFT);

        if ($cekTerpakai) {
            $lain = Pesanan::where('jenis_layanan', Pesanan::LAYANAN_DINE_IN)->where('nomor_meja', $no)
                ->whereNotIn('status', [Pesanan::STATUS_SELESAI, Pesanan::STATUS_DIBATALKAN])
                ->when($kecuali, fn ($q) => $q->whereKeyNot($kecuali->getKey()))
                ->lockForUpdate()->first();
            if ($lain) {
                throw ValidationException::withMessages([
                    'nomor_meja' => "Meja {$no} sedang dipakai pesanan {$lain->kode()}. Pilih meja lain, atau ubah pesanan {$lain->kode()} untuk menambah menu.",
                ]);
            }
        }

        return $no;
    }

    /**
     * Kasir mengubah pesanan di tempat (menu, jumlah, layanan, meja, nama) selama dapur belum mulai memasak
     * dan pesanan belum lunas. Total dihitung ulang.
     *
     * @param  array<int, array{id_menu:int, jumlah:int, catatan?:?string}>  $items
     * @param  array{nomor_meja?:?string, nama_pemesan?:?string}  $data
     */
    public function ubah(Pesanan $pesanan, array $items, string $jenisLayanan, array $data = []): Pesanan
    {
        if (! $items) {
            throw ValidationException::withMessages(['items' => 'Pesanan belum berisi menu.']);
        }

        return DB::transaction(function () use ($pesanan, $items, $jenisLayanan, $data) {
            $pesanan = Pesanan::whereKey($pesanan->getKey())->lockForUpdate()->firstOrFail();
            $tiket = TiketDapur::where('id_pesanan', $pesanan->id)->lockForUpdate()->first();
            $this->pastikanBisaDiubah($pesanan, $tiket);

            [$baris, $subtotal] = $this->barisPesanan($items);
            $zona = $jenisLayanan === Pesanan::LAYANAN_DELIVERY ? ZonaPengiriman::find($data['id_zona'] ?? null) : null;
            if ($jenisLayanan === Pesanan::LAYANAN_DELIVERY && ! $zona) {
                throw ValidationException::withMessages(['lat' => 'Tentukan titik pengantaran di peta.']);
            }
            $hasil = $this->hitung($subtotal, $zona?->ongkir() ?? 0);
            if ($hasil['total'] > self::TOTAL_MAKS) {
                throw ValidationException::withMessages(['items' => 'Total pesanan terlalu besar. Kurangi jumlah porsi atau periksa harga menu.']);
            }

            $pesanan->update([
                'jenis_layanan' => $jenisLayanan,
                'nomor_meja' => $jenisLayanan === Pesanan::LAYANAN_DINE_IN ? $this->nomorMeja($data['nomor_meja'] ?? null, kecuali: $pesanan) : null,
                'nama_pemesan' => $data['nama_pemesan'] ?? null,
                'subtotal' => $hasil['subtotal'],
                'ongkir' => $hasil['ongkir'],
                'total' => $hasil['total'],
            ]);
            $pesanan->detail()->delete();
            $pesanan->detail()->createMany($baris);

            if ($zona) {
                $pesanan->pengiriman()->updateOrCreate([], [
                    'id_zona' => $zona->id,
                    'nama_penerima' => $data['nama_penerima'] ?? null,
                    'no_hp_penerima' => $data['no_hp_penerima'] ?? null,
                    'alamat' => $data['alamat'],
                    'lat' => $data['lat'] ?? null,
                    'lng' => $data['lng'] ?? null,
                    'patokan' => $data['patokan'] ?? null,
                    'jarak_km' => $data['jarak_km'] ?? $zona->radius_km,
                ]);
            } else {
                $pesanan->pengiriman()->delete();
            }
            $this->aturBayarDeliveryKasir($pesanan, $data['bayar'] ?? null);

            return $pesanan;
        });
    }

    /**
     * Delivery yang diinput kasir: COD ke kurir dicatat sebagai pembayaran tunai yang menunggu (ditagih kurir);
     * bayar di kasir dibiarkan tanpa data pembayaran sampai dilunasi di menu Pembayaran.
     */
    private function aturBayarDeliveryKasir(Pesanan $pesanan, ?string $bayar): void
    {
        if ($pesanan->isDelivery() && $bayar === self::BAYAR_KE_KURIR) {
            $pesanan->pembayaran()->updateOrCreate([], [
                'metode' => Pembayaran::METODE_TUNAI, 'jumlah' => $pesanan->total, 'status_bayar' => Pembayaran::STATUS_MENUNGGU,
            ]);
        } else {
            $pesanan->pembayaran()->where('status_bayar', '!=', Pembayaran::STATUS_LUNAS)->delete();
        }
        $pesanan->unsetRelation('pembayaran');
    }

    /**
     * Pesanan yang boleh diubah kasir: diinput di kasir, masih diproses, tiket dapur masih antre, belum lunas.
     */
    public function bisaDiubah(Pesanan $pesanan): bool
    {
        return $this->alasanTidakBisaDiubah($pesanan, $pesanan->tiketDapur) === null;
    }

    /**
     * Alasan pesanan tidak bisa diubah kasir, atau null bila boleh diubah.
     */
    public function alasanTidakBisaDiubah(Pesanan $pesanan, ?TiketDapur $tiket): ?string
    {
        return match (true) {
            $pesanan->isOnline() => 'Pesanan online tidak bisa diubah kasir. Batalkan bila perlu, lalu minta pelanggan memesan ulang.',
            $pesanan->status !== Pesanan::STATUS_DIPROSES => 'Pesanan ini sudah '.mb_strtolower($pesanan->statusLabel()).' dan tidak bisa diubah lagi.',
            $tiket && $tiket->status_masak !== TiketDapur::STATUS_ANTRE => 'Dapur sudah mulai memasak pesanan ini. Pesanan tidak bisa diubah lagi.',
            $pesanan->isLunas() => 'Pesanan ini sudah lunas dan tidak bisa diubah lagi.',
            default => null,
        };
    }

    private function pastikanBisaDiubah(Pesanan $pesanan, ?TiketDapur $tiket): void
    {
        if ($pesan = $this->alasanTidakBisaDiubah($pesanan, $tiket)) {
            throw ValidationException::withMessages(['pesanan' => $pesan]);
        }
    }

    /**
     * Kasir membatalkan pesanan yang belum lunas (salah input, pelanggan batal, dsb.). Tiket dapur ikut dihapus
     * dari antrean. Bila dapur sudah mulai memasak, stok bahan hanya dikembalikan bila kasir memilihnya.
     *
     * @return array{stok_dikembalikan: int}
     */
    public function batalkan(Pesanan $pesanan, User $kasir, string $alasan, bool $kembalikanStok = false): array
    {
        return DB::transaction(function () use ($pesanan, $kasir, $alasan, $kembalikanStok) {
            $pesanan = Pesanan::whereKey($pesanan->getKey())->lockForUpdate()->firstOrFail();

            $pesan = match (true) {
                $pesanan->status === Pesanan::STATUS_DIBATALKAN => "{$pesanan->kode()} sudah dibatalkan sebelumnya.",
                $pesanan->status === Pesanan::STATUS_MENUNGGU => "{$pesanan->kode()} belum dikonfirmasi. Gunakan tombol Tolak di menu Pesanan Online.",
                $pesanan->status === Pesanan::STATUS_DIANTAR => "{$pesanan->kode()} sedang diantar kurir dan tidak bisa dibatalkan.",
                $pesanan->status === Pesanan::STATUS_SELESAI => "{$pesanan->kode()} sudah selesai dan tidak bisa dibatalkan.",
                $pesanan->isLunas() => "{$pesanan->kode()} sudah lunas. Pesanan yang sudah dibayar tidak bisa dibatalkan di kasir.",
                default => null,
            };
            if ($pesan) {
                throw ValidationException::withMessages(['pesanan' => $pesan]);
            }

            $dikembalikan = $kembalikanStok ? app(BahanBakuService::class)->kembalikanUntukPesanan($pesanan) : 0;

            $pesanan->tiketDapur()->delete();
            $pesanan->update(['status' => Pesanan::STATUS_DIBATALKAN, 'alasan_batal' => $alasan, 'id_kasir' => $pesanan->id_kasir ?? $kasir->id]);

            return ['stok_dikembalikan' => $dikembalikan];
        });
    }

    /**
     * Kirim Tiket ke Dapur: satu data tiket_dapur per pesanan, masuk antrean FIFO.
     */
    public function kirimTiketDapur(Pesanan $pesanan): TiketDapur
    {
        return $pesanan->tiketDapur()->firstOrCreate([], [
            'waktu_masuk' => now(),
            'status_masak' => TiketDapur::STATUS_ANTRE,
        ]);
    }

    /**
     * Kasir menerima pesanan online: status jadi diproses dan tiket dapur dibuat.
     */
    public function konfirmasi(Pesanan $pesanan, User $kasir): void
    {
        $this->pastikanMenunggu($pesanan);

        $bayar = $pesanan->pembayaran;
        if (! $bayar) {
            throw ValidationException::withMessages(['pesanan' => 'Pelanggan belum memilih metode pembayaran.']);
        }
        if ($bayar->metode !== Pembayaran::METODE_TUNAI) {
            throw ValidationException::withMessages(['pesanan' => 'Pesanan VA/QRIS otomatis masuk dapur setelah lunas, tidak perlu dikonfirmasi.']);
        }

        DB::transaction(function () use ($pesanan, $kasir) {
            $pesanan->update(['status' => Pesanan::STATUS_DIPROSES, 'id_kasir' => $kasir->id]);
            $this->kirimTiketDapur($pesanan);
        });
    }

    public function tolak(Pesanan $pesanan, User $kasir, ?string $alasan = null): void
    {
        $this->pastikanMenunggu($pesanan);

        $pesanan->update(['status' => Pesanan::STATUS_DIBATALKAN, 'id_kasir' => $kasir->id, 'alasan_batal' => $alasan]);
    }

    /**
     * Pelanggan memilih metode pembayaran.
     * - Tunai: dibayar di kasir / ke kurir, kasir yang menandai lunas.
     * - Transfer (Virtual Account) & QRIS: dibuat lewat Midtrans dan lunas otomatis.
     */
    public function pilihPembayaran(Pesanan $pesanan, string $metode, ?string $bank = null): Pembayaran
    {
        if ($pesanan->status === Pesanan::STATUS_DIBATALKAN) {
            throw ValidationException::withMessages(['metode' => 'Pesanan ini sudah dibatalkan.']);
        }
        if ($pesanan->isLunas()) {
            throw ValidationException::withMessages(['metode' => 'Pesanan ini sudah lunas.']);
        }
        if ($pesanan->status !== Pesanan::STATUS_MENUNGGU) {
            throw ValidationException::withMessages(['metode' => 'Pesanan sudah dikonfirmasi kasir, jadi metode pembayaran tidak bisa diganti lagi.']);
        }

        $lama = $pesanan->pembayaran;

        if ($metode === Pembayaran::METODE_TUNAI) {
            $this->batalkanTransaksiLama($lama);

            return $pesanan->pembayaran()->updateOrCreate([], [
                'metode' => $metode, 'jumlah' => $pesanan->total, 'status_bayar' => Pembayaran::STATUS_MENUNGGU,
                'waktu_bayar' => null, 'bukti_bayar' => null,
                'kode_transaksi' => null, 'bank' => null, 'nomor_va' => null, 'qris_url' => null, 'kedaluwarsa_pada' => null,
            ]);
        }

        // VA / QRIS yang sama dan masih berlaku dipakai lagi (tidak membuat transaksi baru)
        if ($lama?->isOtomatis() && $lama->metode === $metode && $lama->bank === ($metode === Pembayaran::METODE_QRIS ? null : $bank)
            && $lama->status_bayar === Pembayaran::STATUS_MENUNGGU && ! $lama->isKedaluwarsa()) {
            return $lama;
        }

        try {
            $hasil = $this->midtrans->charge($pesanan->loadMissing('pelanggan'), $metode, $bank);
        } catch (RuntimeException $e) {
            throw ValidationException::withMessages(['metode' => $e->getMessage()]);
        }

        $this->batalkanTransaksiLama($lama);

        return $pesanan->pembayaran()->updateOrCreate([], $hasil + [
            'metode' => $metode, 'jumlah' => $pesanan->total, 'status_bayar' => Pembayaran::STATUS_MENUNGGU,
            'waktu_bayar' => null, 'bukti_bayar' => null,
        ]);
    }

    /**
     * Terapkan status dari Midtrans (notifikasi atau cek status).
     * Lunas → pesanan online yang masih menunggu langsung diproses dan tiket dikirim ke dapur.
     */
    public function terapkanStatusGateway(Pembayaran $bayar, ?string $status, ?string $fraud = null): Pembayaran
    {
        if ($bayar->status_bayar === Pembayaran::STATUS_LUNAS) {
            return $bayar;
        }

        $lunas = $status === 'settlement' || ($status === 'capture' && in_array($fraud, [null, 'accept'], true));
        $gagal = in_array($status, ['expire', 'cancel', 'deny', 'failure'], true);

        if ($lunas) {
            DB::transaction(function () use ($bayar) {
                $bayar->update(['status_bayar' => Pembayaran::STATUS_LUNAS, 'waktu_bayar' => now()]);

                $pesanan = $bayar->pesanan()->lockForUpdate()->first();
                if ($pesanan && $pesanan->status === Pesanan::STATUS_MENUNGGU) {
                    $pesanan->update(['status' => Pesanan::STATUS_DIPROSES]);
                    $this->kirimTiketDapur($pesanan);
                }
            });
        } elseif ($gagal) {
            $bayar->update(['status_bayar' => Pembayaran::STATUS_DITOLAK]);
        }

        return $bayar->refresh();
    }

    /**
     * Cek status VA/QRIS ke Midtrans (dipakai halaman pembayaran pelanggan, aman untuk localhost tanpa webhook).
     */
    public function cekStatusGateway(Pembayaran $bayar): Pembayaran
    {
        if (! $bayar->isOtomatis() || $bayar->status_bayar !== Pembayaran::STATUS_MENUNGGU) {
            return $bayar;
        }

        $res = $this->midtrans->status($bayar->kode_transaksi);

        return $this->terapkanStatusGateway($bayar, $res['transaction_status'], $res['fraud_status']);
    }

    private function batalkanTransaksiLama(?Pembayaran $lama): void
    {
        if ($lama?->isOtomatis() && $lama->status_bayar === Pembayaran::STATUS_MENUNGGU) {
            $this->midtrans->batal($lama->kode_transaksi);
        }
    }

    /**
     * Kasir menandai lunas. Untuk tunai, uang diterima harus cukup.
     */
    public function lunasi(Pesanan $pesanan, User $kasir, string $metode, ?float $uangDiterima = null): Pembayaran
    {
        if ($pesanan->status === Pesanan::STATUS_DIBATALKAN) {
            throw ValidationException::withMessages(['pesanan' => 'Pesanan ini sudah dibatalkan.']);
        }
        if ($pesanan->isLunas()) {
            throw ValidationException::withMessages(['pesanan' => 'Pesanan ini sudah lunas.']);
        }
        if ($pesanan->isDelivery() && ! $pesanan->isDeliveryBayarDiKasir()) {
            throw ValidationException::withMessages(['pesanan' => 'Pesanan Delivery ini dibayar tunai ke kurir saat diantar, tidak dilunasi di kasir.']);
        }
        if ($pesanan->status === Pesanan::STATUS_MENUNGGU) {
            throw ValidationException::withMessages(['pesanan' => 'Konfirmasi pesanan ini dulu di menu Pesanan Online sebelum dibayar.']);
        }
        if ($metode === Pembayaran::METODE_TUNAI && ($uangDiterima ?? 0) < (float) $pesanan->total) {
            throw ValidationException::withMessages(['uang_diterima' => 'Uang diterima kurang dari total tagihan.']);
        }

        return $pesanan->pembayaran()->updateOrCreate([], [
            'id_kasir' => $kasir->id,
            'metode' => $metode,
            'jumlah' => $pesanan->total,
            'uang_diterima' => $metode === Pembayaran::METODE_TUNAI ? $uangDiterima : null,
            'status_bayar' => Pembayaran::STATUS_LUNAS,
            'waktu_bayar' => now(),
        ]);
    }

    /**
     * Pesanan Dine In / Take Away yang sudah siap dan lunas diserahkan ke pelanggan.
     */
    public function selesaikan(Pesanan $pesanan): void
    {
        if ($pesanan->isDelivery()) {
            throw ValidationException::withMessages(['pesanan' => 'Pesanan Delivery diselesaikan oleh kurir.']);
        }
        if ($pesanan->status !== Pesanan::STATUS_SIAP) {
            throw ValidationException::withMessages(['pesanan' => 'Pesanan belum siap dari dapur.']);
        }
        if (! $pesanan->isLunas()) {
            throw ValidationException::withMessages(['pesanan' => 'Pesanan belum lunas.']);
        }

        $pesanan->update(['status' => Pesanan::STATUS_SELESAI, 'selesai_pada' => now()]);
    }

    private function pastikanMenunggu(Pesanan $pesanan): void
    {
        if ($pesanan->status === Pesanan::STATUS_DIBATALKAN) {
            throw ValidationException::withMessages(['pesanan' => "{$pesanan->kode()} sudah ditolak/dibatalkan."]);
        }
        if ($pesanan->status !== Pesanan::STATUS_MENUNGGU) {
            throw ValidationException::withMessages(['pesanan' => "{$pesanan->kode()} sudah dikonfirmasi sebelumnya."]);
        }
    }
}
