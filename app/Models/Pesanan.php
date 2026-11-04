<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Table('pesanan')]
#[Fillable(['id_pelanggan', 'id_kasir', 'jenis_layanan', 'waktu_pesan', 'subtotal', 'ongkir', 'total', 'status'])]
class Pesanan extends Model
{
    public const LAYANAN_DINE_IN = 'dine_in';

    public const LAYANAN_TAKE_AWAY = 'take_away';

    public const LAYANAN_DELIVERY = 'delivery';

    public const STATUS_MENUNGGU = 'menunggu';

    public const STATUS_DIPROSES = 'diproses';

    public const STATUS_SIAP = 'siap';

    public const STATUS_DIANTAR = 'diantar';

    public const STATUS_SELESAI = 'selesai';

    public const STATUS_DIBATALKAN = 'dibatalkan';

    protected function casts(): array
    {
        return [
            'waktu_pesan' => 'datetime',
            'subtotal' => 'decimal:2',
            'ongkir' => 'decimal:2',
            'total' => 'decimal:2',
        ];
    }

    public function pelanggan(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_pelanggan');
    }

    public function kasir(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_kasir');
    }

    public function detail(): HasMany
    {
        return $this->hasMany(DetailPesanan::class, 'id_pesanan');
    }

    public function pembayaran(): HasOne
    {
        return $this->hasOne(Pembayaran::class, 'id_pesanan');
    }

    public function tiketDapur(): HasOne
    {
        return $this->hasOne(TiketDapur::class, 'id_pesanan');
    }

    public function pengiriman(): HasOne
    {
        return $this->hasOne(Pengiriman::class, 'id_pesanan');
    }

    public const LABEL_LAYANAN = [
        self::LAYANAN_DINE_IN => 'Dine In',
        self::LAYANAN_TAKE_AWAY => 'Take Away',
        self::LAYANAN_DELIVERY => 'Delivery',
    ];

    public const LABEL_STATUS = [
        self::STATUS_MENUNGGU => 'Menunggu Konfirmasi',
        self::STATUS_DIPROSES => 'Diproses',
        self::STATUS_SIAP => 'Siap',
        self::STATUS_DIANTAR => 'Diantar',
        self::STATUS_SELESAI => 'Selesai',
        self::STATUS_DIBATALKAN => 'Dibatalkan',
    ];

    /**
     * Nomor pesanan untuk ditampilkan, contoh: ORD-0007.
     */
    public function kode(): string
    {
        return 'ORD-'.str_pad((string) $this->id, 4, '0', STR_PAD_LEFT);
    }

    public function layananLabel(): string
    {
        return self::LABEL_LAYANAN[$this->jenis_layanan] ?? $this->jenis_layanan;
    }

    public function statusLabel(): string
    {
        return self::LABEL_STATUS[$this->status] ?? $this->status;
    }

    /**
     * Kelas warna .pill untuk status pesanan.
     */
    public function statusPill(): string
    {
        return match ($this->status) {
            self::STATUS_MENUNGGU => 'b',
            self::STATUS_DIPROSES, self::STATUS_DIANTAR => 'm',
            self::STATUS_SELESAI, self::STATUS_DIBATALKAN => 'p',
            default => '',
        };
    }

    public function isDelivery(): bool
    {
        return $this->jenis_layanan === self::LAYANAN_DELIVERY;
    }
}
