<?php

namespace App\Models\Concerns;

use App\Support\KodeUrl;
use Illuminate\Database\Eloquent\Model;

/**
 * Model yang ID-nya tampil di URL sebagai kode terenkripsi (route model binding & route()).
 *
 * @mixin Model
 */
trait IdTerenkripsiDiUrl
{
    public function getRouteKey(): string
    {
        return KodeUrl::enkripsi((int) $this->getKey(), static::class);
    }

    /**
     * @param  mixed  $value
     * @param  string|null  $field
     */
    public function resolveRouteBinding($value, $field = null): ?Model
    {
        if ($field !== null) {
            return parent::resolveRouteBinding($value, $field);
        }
        $id = KodeUrl::dekripsi($value, static::class);

        return $id === null ? null : $this->newQuery()->find($id);
    }

    /**
     * ID dari kode di URL (mis. ?pesanan=kode), atau null bila tidak sah.
     */
    public static function idDariKode(mixed $kode): ?int
    {
        return KodeUrl::dekripsi($kode, static::class);
    }
}
