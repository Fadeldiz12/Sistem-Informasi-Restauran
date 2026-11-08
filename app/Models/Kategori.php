<?php

namespace App\Models;

use App\Models\Concerns\IdTerenkripsiDiUrl;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

#[Table('kategori')]
#[Fillable(['nama_kategori', 'tampil_navbar', 'urutan_navbar'])]
class Kategori extends Model
{
    use IdTerenkripsiDiUrl;

    /**
     * Menu di kategori bernama "Promo" tampil sebagai promo di beranda (menu paket dengan harga promo).
     */
    public const NAMA_PROMO = 'Promo';

    public function isPromo(): bool
    {
        return mb_strtolower(trim($this->nama_kategori)) === mb_strtolower(self::NAMA_PROMO);
    }

    /**
     * Jumlah maksimal kategori di navbar beranda.
     */
    public const MAKS_NAVBAR = 6;

    protected function casts(): array
    {
        return [
            'tampil_navbar' => 'boolean',
            'urutan_navbar' => 'integer',
        ];
    }

    public function menu(): HasMany
    {
        return $this->hasMany(Menu::class, 'id_kategori');
    }

    /**
     * Kategori di navbar, urut sesuai pilihan admin.
     */
    #[Scope]
    protected function navbar(Builder $query): void
    {
        $query->where('tampil_navbar', true)->orderBy('urutan_navbar')->orderBy('nama_kategori');
    }

    /**
     * Dipakai di URL beranda, contoh: ?kategori=paket-hemat
     */
    public function slug(): string
    {
        return Str::slug($this->nama_kategori) ?: (string) $this->id;
    }
}
