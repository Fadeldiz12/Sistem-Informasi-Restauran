<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Table('zona_pengiriman')]
#[Fillable(['nama_zona', 'contoh_wilayah', 'radius_km', 'tarif_per_km'])]
class ZonaPengiriman extends Model
{
    protected function casts(): array
    {
        return [
            'radius_km' => 'decimal:2',
            'tarif_per_km' => 'decimal:2',
        ];
    }

    public function pengiriman(): HasMany
    {
        return $this->hasMany(Pengiriman::class, 'id_zona');
    }

    /**
     * Ongkir zona ini. Jarak pengiriman memakai radius_km zona,
     * jadi ongkir = radius_km × tarif_per_km.
     */
    public function ongkir(): float
    {
        return $this->hitungOngkir((float) $this->radius_km);
    }

    /**
     * Hitung Ongkos Kirim: ongkir = jarak_km × tarif_per_km.
     */
    public function hitungOngkir(float $jarakKm): float
    {
        return round($jarakKm * (float) $this->tarif_per_km, 2);
    }
}
