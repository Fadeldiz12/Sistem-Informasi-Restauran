<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Table('setoran')]
#[Fillable(['id_kurir', 'id_kasir', 'jumlah_seharusnya', 'jumlah_diterima', 'catatan', 'waktu_setor'])]
class Setoran extends Model
{
    protected function casts(): array
    {
        return [
            'jumlah_seharusnya' => 'decimal:2',
            'jumlah_diterima' => 'decimal:2',
            'waktu_setor' => 'datetime',
        ];
    }

    public function kurir(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_kurir');
    }

    public function kasir(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_kasir');
    }

    public function pembayaran(): HasMany
    {
        return $this->hasMany(Pembayaran::class, 'id_setoran');
    }

    /**
     * Uang diterima dikurangi yang seharusnya: negatif = kurang, positif = lebih.
     */
    public function selisih(): float
    {
        return round((float) $this->jumlah_diterima - (float) $this->jumlah_seharusnya, 2);
    }
}
