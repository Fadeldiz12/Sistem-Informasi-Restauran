<?php

namespace App\Models;

use App\Models\Concerns\IdTerenkripsiDiUrl;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Validation\ValidationException;

#[Table('zona_pengiriman')]
#[Fillable(['nama_zona', 'contoh_wilayah', 'radius_km', 'tarif_per_km'])]
class ZonaPengiriman extends Model
{
    use IdTerenkripsiDiUrl;

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

    /**
     * Zona untuk jarak tertentu: zona dengan radius terkecil yang masih mencakup jarak itu, atau null bila di luar jangkauan.
     */
    public static function untukJarak(float $km): ?self
    {
        return static::where('radius_km', '>=', $km)->orderBy('radius_km')->first();
    }

    /**
     * Zona & jarak untuk titik pengantaran di peta. Titik di luar jangkauan ditolak dengan pesan untuk kolom lat.
     *
     * @return array{id_zona: int, jarak_km: float}
     */
    public static function dariTitik(float $lat, float $lng): array
    {
        $jarak = PengaturanSistem::aktif()->jarakKm($lat, $lng);
        $zona = static::untukJarak($jarak);
        if (! $zona) {
            $km = fn (float $n): string => rtrim(rtrim(number_format($n, 1, ',', '.'), '0'), ',');
            throw ValidationException::withMessages([
                'lat' => 'Titik pengantaran '.$km($jarak).' km dari restoran, di luar jangkauan Delivery (maksimal '.$km((float) static::max('radius_km')).' km). Silakan pilih Take Away.',
            ]);
        }

        return ['id_zona' => $zona->id, 'jarak_km' => $jarak];
    }

    /**
     * URL Google Maps untuk titik (lat, lng), dipakai tombol "Buka di Maps" kurir.
     */
    public static function urlMaps(?float $lat, ?float $lng, string $alamat): string
    {
        return $lat !== null && $lng !== null
            ? 'https://www.google.com/maps/dir/?api=1&destination='.$lat.','.$lng
            : 'https://www.google.com/maps/search/?api=1&query='.urlencode($alamat);
    }
}
