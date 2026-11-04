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
     * Pajak dalam persen dari subtotal, contoh: 10 = 10%.
     */
    public function hitungPajak(float $subtotal): float
    {
        return round($subtotal * (float) $this->pajak / 100, 2);
    }

    /**
     * Biaya layanan dalam persen dari subtotal, contoh: 5 = 5%.
     */
    public function hitungBiayaLayanan(float $subtotal): float
    {
        return round($subtotal * (float) $this->biaya_layanan / 100, 2);
    }

    /**
     * Angka persen tanpa nol di belakang, contoh: 10.00 -> "10", 2.50 -> "2,5".
     */
    public static function persen(float|string|null $nilai): string
    {
        return rtrim(rtrim(number_format((float) $nilai, 2, ',', ''), '0'), ',');
    }

    /**
     * Contoh: "10.00 – 22.00".
     */
    public function jamLabel(): string
    {
        return substr($this->jam_buka, 0, 5).' – '.substr($this->jam_tutup, 0, 5);
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
