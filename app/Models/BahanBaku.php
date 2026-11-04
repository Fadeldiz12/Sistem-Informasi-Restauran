<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Table('bahan_baku')]
#[Fillable(['nama_bahan', 'satuan', 'stok', 'rata_pemakaian', 'lead_time', 'safety_stock', 'rop'])]
class BahanBaku extends Model
{
    /**
     * Jumlah hari (N) untuk menghitung rata-rata pemakaian.
     */
    public const HARI_RATA_PEMAKAIAN = 30;

    protected function casts(): array
    {
        return [
            'stok' => 'decimal:2',
            'rata_pemakaian' => 'decimal:2',
            'lead_time' => 'integer',
            'safety_stock' => 'decimal:2',
            'rop' => 'decimal:2',
        ];
    }

    public function mutasi(): HasMany
    {
        return $this->hasMany(MutasiBahanBaku::class, 'id_bahan');
    }

    /**
     * Hitung ROP: rop = rata_pemakaian × lead_time + safety_stock.
     */
    public function hitungRop(): float
    {
        return round((float) $this->rata_pemakaian * $this->lead_time + (float) $this->safety_stock, 2);
    }

    /**
     * Rata-rata pemakaian = total pemakaian N hari terakhir ÷ N.
     */
    public function hitungRataPemakaian(int $hari = self::HARI_RATA_PEMAKAIAN): float
    {
        $total = $this->mutasi()
            ->where('jenis', MutasiBahanBaku::JENIS_PEMAKAIAN)
            ->whereDate('tanggal', '>', now()->subDays($hari)->toDateString())
            ->sum('jumlah');

        return round((float) $total / $hari, 2);
    }

    /**
     * Notifikasi Stok Kritis: stok ≤ rop.
     */
    public function isStokKritis(): bool
    {
        return (float) $this->stok <= (float) $this->rop;
    }

    #[Scope]
    protected function stokKritis(Builder $query): void
    {
        $query->whereColumn('stok', '<=', 'rop');
    }
}
