<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Table('mutasi_bahan_baku')]
#[Fillable(['id_bahan', 'jenis', 'jumlah', 'tanggal'])]
class MutasiBahanBaku extends Model
{
    public const JENIS_MASUK = 'masuk';

    public const JENIS_PEMAKAIAN = 'pemakaian';

    protected function casts(): array
    {
        return [
            'jumlah' => 'decimal:2',
            'tanggal' => 'date',
        ];
    }

    public function bahanBaku(): BelongsTo
    {
        return $this->belongsTo(BahanBaku::class, 'id_bahan')->withTrashed();
    }
}
