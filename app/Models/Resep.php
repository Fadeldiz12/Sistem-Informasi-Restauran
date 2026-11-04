<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Takaran satu bahan baku untuk satu porsi menu.
 */
#[Table('resep')]
#[Fillable(['id_menu', 'id_bahan', 'jumlah'])]
class Resep extends Model
{
    protected function casts(): array
    {
        return ['jumlah' => 'decimal:2'];
    }

    public function menu(): BelongsTo
    {
        return $this->belongsTo(Menu::class, 'id_menu');
    }

    public function bahanBaku(): BelongsTo
    {
        return $this->belongsTo(BahanBaku::class, 'id_bahan');
    }
}
