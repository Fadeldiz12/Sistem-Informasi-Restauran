<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Table('kategori')]
#[Fillable(['nama_kategori'])]
class Kategori extends Model
{
    public function menu(): HasMany
    {
        return $this->hasMany(Menu::class, 'id_kategori');
    }
}
