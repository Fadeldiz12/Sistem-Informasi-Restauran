<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;

#[Table('pengaturan_sistem')]
#[Fillable(['pajak', 'biaya_layanan', 'jam_buka', 'jam_tutup'])]
class PengaturanSistem extends Model
{
    protected function casts(): array
    {
        return [
            'pajak' => 'decimal:2',
            'biaya_layanan' => 'decimal:2',
        ];
    }

    /**
     * Pengaturan sistem hanya 1 data; ambil yang pertama atau buat default.
     */
    public static function aktif(): self
    {
        return static::query()->firstOrCreate([], [
            'pajak' => 0,
            'biaya_layanan' => 0,
            'jam_buka' => '10:00',
            'jam_tutup' => '22:00',
        ]);
    }
}
