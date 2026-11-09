<?php

namespace App\Services;

use App\Models\Pesanan;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Klien Midtrans Core API untuk Virtual Account dan QRIS.
 * Dokumentasi: https://docs.midtrans.com/reference/core-api
 */
class MidtransService
{
    /** Bank Virtual Account yang didukung. */
    public const BANK = [
        'bca' => 'BCA',
        'bni' => 'BNI',
        'bri' => 'BRI',
        'permata' => 'Permata',
    ];

    public function aktif(): bool
    {
        return filled(config('services.midtrans.server_key'));
    }

    public function sandbox(): bool
    {
        return ! config('services.midtrans.is_production');
    }

    private function baseUrl(): string
    {
        return $this->sandbox() ? 'https://api.sandbox.midtrans.com' : 'https://api.midtrans.com';
    }

    private function http(): PendingRequest
    {
        if (! $this->aktif()) {
            throw new RuntimeException('Pembayaran online sedang tidak tersedia. Silakan pilih bayar tunai.');
        }

        return Http::baseUrl($this->baseUrl())
            ->withBasicAuth(config('services.midtrans.server_key'), '')
            ->acceptJson()->asJson()->timeout(20);
    }

    /**
     * Buat transaksi VA (metode "transfer" + bank) atau QRIS.
     *
     * @return array{kode_transaksi:string, bank:?string, nomor_va:?string, qris_url:?string, kedaluwarsa_pada:?string}
     */
    public function charge(Pesanan $pesanan, string $metode, ?string $bank = null): array
    {
        $kode = 'SR-'.$pesanan->id.'-'.now()->format('ymdHis');
        $payload = [
            'transaction_details' => ['order_id' => $kode, 'gross_amount' => (int) round((float) $pesanan->total)],
            'customer_details' => array_filter([
                'first_name' => $pesanan->namaPemesan(),
                'email' => $pesanan->pelanggan?->email ?? $pesanan->email_pemesan,
                'phone' => $pesanan->noHpPemesan(),
            ]),
            'custom_expiry' => ['expiry_duration' => config('services.midtrans.kedaluwarsa_menit'), 'unit' => 'minute'],
        ];

        if ($metode === 'qris') {
            $payload += ['payment_type' => 'qris', 'qris' => ['acquirer' => 'gopay']];
        } else {
            if (! isset(self::BANK[$bank])) {
                throw new RuntimeException('Pilih bank Virtual Account.');
            }
            $payload += ['payment_type' => 'bank_transfer', 'bank_transfer' => ['bank' => $bank]];
        }

        $res = $this->kirim(fn () => $this->http()->post('/v2/charge', $payload));

        return [
            'kode_transaksi' => $kode,
            'bank' => $metode === 'qris' ? null : $bank,
            'nomor_va' => $res['va_numbers'][0]['va_number'] ?? $res['permata_va_number'] ?? null,
            'qris_url' => collect($res['actions'] ?? [])->firstWhere('name', 'generate-qr-code')['url'] ?? null,
            'kedaluwarsa_pada' => $res['expiry_time'] ?? null,
        ];
    }

    /**
     * Cek status transaksi (pending, settlement, capture, expire, cancel, deny, failure).
     *
     * @return array{transaction_status:?string, fraud_status:?string}
     */
    public function status(string $kodeTransaksi): array
    {
        // status_code bisa 201 (pending), 200 (settlement), 407 (expire), dst. Yang penting transaction_status.
        $res = $this->kirim(fn () => $this->http()->get('/v2/'.rawurlencode($kodeTransaksi).'/status'), boleh404: true, terimaStatus: true);

        return ['transaction_status' => $res['transaction_status'] ?? null, 'fraud_status' => $res['fraud_status'] ?? null];
    }

    /**
     * Batalkan transaksi yang masih pending (mis. pelanggan ganti metode). Gagal diabaikan.
     */
    public function batal(string $kodeTransaksi): void
    {
        try {
            $this->http()->post('/v2/'.rawurlencode($kodeTransaksi).'/cancel');
        } catch (\Throwable) {
            // transaksi lama dibiarkan kedaluwarsa sendiri
        }
    }

    /**
     * Notifikasi (webhook) valid jika signature_key = SHA512(order_id + status_code + gross_amount + server_key).
     */
    public function signatureValid(array $payload): bool
    {
        $expected = hash('sha512', ($payload['order_id'] ?? '').($payload['status_code'] ?? '').($payload['gross_amount'] ?? '').config('services.midtrans.server_key'));

        return $this->aktif() && hash_equals($expected, (string) ($payload['signature_key'] ?? ''));
    }

    private function kirim(callable $request, bool $boleh404 = false, bool $terimaStatus = false): array
    {
        try {
            $res = $request();
        } catch (ConnectionException) {
            throw new RuntimeException('Tidak bisa terhubung ke Midtrans. Periksa koneksi internet lalu coba lagi.');
        }

        $body = $res->json() ?? [];
        $kode = (string) ($body['status_code'] ?? $res->status());

        if ($terimaStatus && $res->successful() && isset($body['transaction_status'])) {
            return $body;
        }

        // Midtrans bisa membalas HTTP 200 dengan status_code error di body
        if ($res->failed() || ! str_starts_with($kode, '2')) {
            if ($boleh404 && $kode === '404') {
                return [];
            }
            $pesan = $body['status_message'] ?? ($body['error_messages'][0] ?? 'Permintaan ke Midtrans gagal.');
            throw new RuntimeException('Midtrans: '.$pesan);
        }

        return $body;
    }
}
