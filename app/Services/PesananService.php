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
            $menu = Menu::whereIn('id', array_column($items, 'id_menu'))->lockForUpdate()->get()->keyBy('id');

            $baris = [];
            $subtotal = 0;
            foreach ($items as $it) {
                $m = $menu->get($it['id_menu']);
                if (! $m || ! $m->tersedia) {
                    throw ValidationException::withMessages(['items' => ($m?->nama_menu ?? 'Salah satu menu').' sedang tidak tersedia.']);
                }
                $jumlah = max(1, (int) $it['jumlah']);
                $sub = (float) $m->harga * $jumlah;
                $subtotal += $sub;
                $baris[] = ['id_menu' => $m->id, 'jumlah' => $jumlah, 'catatan' => $it['catatan'] ?? null, 'subtotal' => $sub];
            }

            $zona = null;
            if ($jenisLayanan === Pesanan::LAYANAN_DELIVERY) {
                $zona = ZonaPengiriman::find($data['id_zona'] ?? null);
                if (! $zona) {
                    throw ValidationException::withMessages(['id_zona' => 'Pilih zona pengiriman.']);
                }
            }

            $hasil = $this->hitung($subtotal, $zona?->ongkir() ?? 0);

            $pesanan = Pesanan::create([
                'id_pelanggan' => $pelanggan?->id,
                'id_kasir' => $kasir?->id,
                'jenis_layanan' => $jenisLayanan,
                'nomor_meja' => $jenisLayanan === Pesanan::LAYANAN_DINE_IN ? ($data['nomor_meja'] ?? null) : null,
                'nama_pemesan' => $data['nama_pemesan'] ?? null,
                'waktu_pesan' => now(),
                'subtotal' => $hasil['subtotal'],
                'ongkir' => $hasil['ongkir'],
                'total' => $hasil['total'],
                'status' => $kasir ? Pesanan::STATUS_DIPROSES : Pesanan::STATUS_MENUNGGU,
            ]);

            $pesanan->detail()->createMany($baris);

            if ($zona) {
                // jarak pengiriman memakai radius zona
                $pesanan->pengiriman()->create([
                    'id_zona' => $zona->id,
                    'alamat' => $data['alamat'],
                    'jarak_km' => $zona->radius_km,
                ]);
            }

            if ($kasir) {
                $this->kirimTiketDapur($pesanan);
            }

            return $pesanan;
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

    public function tolak(Pesanan $pesanan, User $kasir): void
    {
        $this->pastikanMenunggu($pesanan);

        $pesanan->update(['status' => Pesanan::STATUS_DIBATALKAN, 'id_kasir' => $kasir->id]);
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
        if ($metode === Pembayaran::METODE_TUNAI && ($uangDiterima ?? 0) < (float) $pesanan->total) {
            throw ValidationException::withMessages(['uang_diterima' => 'Uang diterima kurang dari total tagihan.']);
        }

        return $pesanan->pembayaran()->updateOrCreate([], [
            'id_kasir' => $kasir->id,
            'metode' => $metode,
            'jumlah' => $pesanan->total,
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

        $pesanan->update(['status' => Pesanan::STATUS_SELESAI]);
    }

    private function pastikanMenunggu(Pesanan $pesanan): void
    {
        if ($pesanan->status !== Pesanan::STATUS_MENUNGGU) {
            throw ValidationException::withMessages(['pesanan' => 'Pesanan ini sudah diproses sebelumnya.']);
        }
    }
}
