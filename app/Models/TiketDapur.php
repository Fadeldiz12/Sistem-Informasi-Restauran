<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Table('tiket_dapur')]
#[Fillable(['id_pesanan', 'waktu_masuk', 'status_masak'])]
class TiketDapur extends Model
{
    public const STATUS_ANTRE = 'antre';

    public const STATUS_DIMASAK = 'dimasak';

    public const STATUS_SELESAI = 'selesai';

    protected function casts(): array
    {
        return [
            'waktu_masuk' => 'datetime',
        ];
    }

    public function pesanan(): BelongsTo
    {
        return $this->belongsTo(Pesanan::class, 'id_pesanan');
    }

    /**
     * Urutkan Antrean (FIFO): waktu_masuk paling lama di atas.
     */
    #[Scope]
    protected function fifo(Builder $query): void
    {
        $query->orderBy('waktu_masuk')->orderBy('id');
    }
}
