<?php

namespace App\Models;

use App\Models\Concerns\TercatatDiLog;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

#[Table('pengaturan_sistem')]
#[Fillable(['pajak', 'biaya_layanan', 'jam_buka', 'jam_tutup', 'lat_resto', 'lng_resto', 'batas_diam_menit', 'ip_outlet', 'cek_gps', 'radius_outlet_m', 'wajib_perangkat', 'wajib_shift'])]
class PengaturanSistem extends Model
{
    use TercatatDiLog;

    public const LOG_KODE = 'pengaturan';

    public const LOG_JENIS = 'Pengaturan sistem';

    public const LOG_JUDUL = null;

    protected function casts(): array
    {
        return [
            'pajak' => 'decimal:2',
            'biaya_layanan' => 'decimal:2',
            'cek_gps' => 'boolean',
            'wajib_perangkat' => 'boolean',
            'wajib_shift' => 'boolean',
        ];
    }

    /**
     * Pajak dalam persen dari subtotal, contoh: 10 = 10%.
     */
    public function hitungPajak(float $subtotal): float
    {
        return round($subtotal * (float) $this->pajak / 100);
    }

    /**
     * Biaya layanan dalam persen dari subtotal, contoh: 5 = 5%.
     */
    public function hitungBiayaLayanan(float $subtotal): float
    {
        return round($subtotal * (float) $this->biaya_layanan / 100);
    }

    /**
     * Angka persen tanpa nol di belakang, contoh: 10.00 -> "10", 2.50 -> "2,5".
     */
    public static function persen(float|string|null $nilai): string
    {
        return rtrim(rtrim(number_format((float) $nilai, 2, ',', ''), '0'), ',');
    }

    /**
     * Apakah restoran sedang buka (dipakai untuk membatasi pesanan pelanggan).
     */
    public function sedangBuka(?\DateTimeInterface $waktu = null): bool
    {
        $jam = ($waktu ? Carbon::instance($waktu) : now())->format('H:i:s');
        $buka = $this->normalJam($this->jam_buka);
        $tutup = $this->normalJam($this->jam_tutup);

        // jam tutup lebih awal dari jam buka = tutup keesokan harinya (mis. 16:00 – 02:00)
        return $tutup > $buka
            ? $jam >= $buka && $jam < $tutup
            : $jam >= $buka || $jam < $tutup;
    }

    public function lewatTengahMalam(): bool
    {
        return $this->normalJam($this->jam_tutup) <= $this->normalJam($this->jam_buka);
    }

    private function normalJam(string $jam): string
    {
        return strlen($jam) === 5 ? $jam.':00' : $jam;
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
            'lat_resto' => self::LAT_AWAL,
            'lng_resto' => self::LNG_AWAL,
        ]);
    }

    /**
     * Titik awal restoran (Jl. Gatot Subroto, Medan) bila admin belum menentukan titiknya di peta.
     */
    public const LAT_AWAL = 3.5905;

    public const LNG_AWAL = 98.6437;

    /**
     * Jarak garis lurus (km) dari restoran ke sebuah titik, rumus haversine.
     */
    public function jarakKm(float $lat, float $lng): float
    {
        $lat0 = deg2rad((float) ($this->lat_resto ?? self::LAT_AWAL));
        $lng0 = deg2rad((float) ($this->lng_resto ?? self::LNG_AWAL));
        $lat1 = deg2rad($lat);
        $lng1 = deg2rad($lng);

        $a = sin(($lat1 - $lat0) / 2) ** 2 + cos($lat0) * cos($lat1) * sin(($lng1 - $lng0) / 2) ** 2;

        return round(6371 * 2 * asin(min(1, sqrt($a))), 2);
    }
}
