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

    public function isDelivery(): bool
    {
        return $this->jenis_layanan === self::LAYANAN_DELIVERY;
    }
}
