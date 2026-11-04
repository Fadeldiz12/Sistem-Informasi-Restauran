<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Table('menu')]
#[Fillable(['id_kategori', 'nama_menu', 'harga', 'foto', 'tersedia'])]
class Menu extends Model
{
    protected function casts(): array
    {
        return [
            'harga' => 'decimal:2',
            'tersedia' => 'boolean',
        ];
    }

    public function kategori(): BelongsTo
    {
        return $this->belongsTo(Kategori::class, 'id_kategori');
    }

    public function detailPesanan(): HasMany
    {
        return $this->hasMany(DetailPesanan::class, 'id_menu');
    }

    /**
     * Hanya menu yang tersedia (yang ditampilkan ke pelanggan).
     */
    #[Scope]
    protected function tersedia(Builder $query): void
    {
        $query->where('tersedia', true);
    }
}
