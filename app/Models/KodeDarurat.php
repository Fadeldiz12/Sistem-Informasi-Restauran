<?php

namespace App\Models;

use App\Models\Concerns\IdTerenkripsiDiUrl;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Kode dari Owner saat internet / GPS outlet bermasalah: staf tetap bisa masuk tanpa cek lokasi & perangkat. Setiap pemakaian tercatat.
 */
#[Table('kode_darurat')]
#[Fillable(['kode_hash', 'id_owner', 'alasan', 'berlaku_sampai', 'dicabut_at', 'dipakai'])]
#[Hidden(['kode_hash'])]
class KodeDarurat extends Model
{
    use IdTerenkripsiDiUrl;

    protected function casts(): array
    {
        return ['berlaku_sampai' => 'datetime', 'dicabut_at' => 'datetime'];
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_owner');
    }

    #[Scope]
    protected function aktif(Builder $query): void
    {
        $query->whereNull('dicabut_at')->where('berlaku_sampai', '>', now());
    }
}
