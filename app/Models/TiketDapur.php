<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Table('tiket_dapur')]
#[Fillable(['id_pesanan', 'waktu_masuk', 'status_masak', 'mulai_pada', 'selesai_pada'])]
class TiketDapur extends Model
{
    public const STATUS_ANTRE = 'antre';

    public const STATUS_DIMASAK = 'dimasak';

    public const STATUS_SELESAI = 'selesai';

    protected function casts(): array
    {
        return [
            'mulai_pada' => 'datetime',
            'selesai_pada' => 'datetime',
            'waktu_masuk' => 'datetime',
        ];
    }

    public const LABEL_STATUS = [
        self::STATUS_ANTRE => 'Antre',
        self::STATUS_DIMASAK => 'Sedang Dimasak',
        self::STATUS_SELESAI => 'Siap',
    ];

    /** Contoh: T-0007 */
    public function kode(): string
    {
        return 'T-'.str_pad((string) $this->id, 4, '0', STR_PAD_LEFT);
    }

    public function statusLabel(): string
    {
        return self::LABEL_STATUS[$this->status_masak] ?? $this->status_masak;
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
