<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Table('detail_pesanan')]
#[Fillable(['id_pesanan', 'id_menu', 'jumlah', 'catatan', 'subtotal'])]
class DetailPesanan extends Model
{
    protected function casts(): array
    {
        return [
            'jumlah' => 'integer',
            'subtotal' => 'decimal:2',
        ];
    }

    public function pesanan(): BelongsTo
    {
        return $this->belongsTo(Pesanan::class, 'id_pesanan');
    }

    public function menu(): BelongsTo
    {
        return $this->belongsTo(Menu::class, 'id_menu');
    }
}
