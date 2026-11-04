<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Table('pembayaran')]
#[Fillable(['id_pesanan', 'id_kasir', 'metode', 'jumlah', 'bukti_bayar', 'status_bayar', 'waktu_bayar'])]
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
        ];
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
