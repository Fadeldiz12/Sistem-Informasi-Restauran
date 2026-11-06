<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

#[Table('menu')]
#[Fillable(['id_kategori', 'nama_menu', 'harga', 'foto', 'tersedia'])]
class Menu extends Model
{
    /**
     * Harga menu tertinggi (Rp 99.999.999) supaya total pesanan tidak melebihi kapasitas kolom decimal(12,2).
     */
    public const HARGA_MAKS = 99_999_999;

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
     * Resep per porsi (bahan baku yang dipakai saat menu dimasak).
     */
    public function resep(): HasMany
    {
        return $this->hasMany(Resep::class, 'id_menu');
    }

    /**
     * URL foto menu. Kolom foto berisi path di disk "public" (hasil upload)
     * atau URL lengkap (data contoh).
     */
    public function fotoUrl(): ?string
    {
        if (! $this->foto) {
            return null;
        }

        return Str::startsWith($this->foto, ['http://', 'https://'])
            ? $this->foto
            : asset('storage/'.$this->foto);
    }

    /**
     * Hanya menu yang tersedia (yang ditampilkan ke pelanggan).
     */
    /**
     * Menu promo yang bisa dipesan: menu tersedia di kategori "Promo".
     */
    #[Scope]
    protected function promo(Builder $query): void
    {
        $query->where('tersedia', true)
            ->whereHas('kategori', fn ($k) => $k->whereRaw('LOWER(TRIM(nama_kategori)) = ?', [mb_strtolower(Kategori::NAMA_PROMO)]));
    }

    #[Scope]
    protected function tersedia(Builder $query): void
    {
        $query->where('tersedia', true);
    }
}
