<?php

namespace App\Models;

use App\Services\MidtransService;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Table('pembayaran')]
#[Fillable(['id_pesanan', 'id_kasir', 'metode', 'jumlah', 'bukti_bayar', 'status_bayar', 'waktu_bayar', 'kode_transaksi', 'bank', 'nomor_va', 'qris_url', 'kedaluwarsa_pada'])]
class Pembayaran extends Model
{
    public const METODE_TUNAI = 'tunai';

    public const METODE_TRANSFER = 'transfer';

    public const METODE_QRIS = 'qris';

    public const STATUS_MENUNGGU = 'menunggu';

    public const STATUS_LUNAS = 'lunas';

    public const STATUS_DITOLAK = 'ditolak';

    protected function casts(): array
    {
        return [
            'jumlah' => 'decimal:2',
            'waktu_bayar' => 'datetime',
            'kedaluwarsa_pada' => 'datetime',
        ];
    }

    public const LABEL_METODE = [
        self::METODE_TUNAI => 'Tunai',
        self::METODE_TRANSFER => 'Transfer',
        self::METODE_QRIS => 'QRIS',
    ];

    /**
     * Contoh: "Tunai", "VA BCA", "QRIS", "Transfer" (transfer manual di kasir).
     */
    public function metodeLabel(): string
    {
        if ($this->metode === self::METODE_TRANSFER && $this->bank) {
            return 'VA '.(MidtransService::BANK[$this->bank] ?? strtoupper($this->bank));
        }

        return self::LABEL_METODE[$this->metode] ?? $this->metode;
    }

    /**
     * Dibayar otomatis lewat Midtrans (Virtual Account / QRIS).
     */
    public function isOtomatis(): bool
    {
        return $this->metode !== self::METODE_TUNAI && $this->kode_transaksi !== null;
    }

    public function isKedaluwarsa(): bool
    {
        return $this->status_bayar === self::STATUS_MENUNGGU && $this->kedaluwarsa_pada?->isPast();
    }

    public function pesanan(): BelongsTo
    {
        return $this->belongsTo(Pesanan::class, 'id_pesanan');
    }

    public function kasir(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_kasir');
    }
}
