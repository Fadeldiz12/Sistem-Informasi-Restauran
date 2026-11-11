<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Ciri wajah staf (rata-rata beberapa sampel), terenkripsi. Tidak ada foto yang disimpan.
 */
#[Table('wajah_staf')]
#[Fillable(['id_user', 'ciri', 'jumlah_sampel', 'id_pendaftar', 'persetujuan_at', 'catatan_mirip'])]
#[Hidden(['ciri'])]
class WajahStaf extends Model
{
    protected function casts(): array
    {
        return [
            'ciri' => 'encrypted:array',
            'persetujuan_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_user');
    }

    public function pendaftar(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_pendaftar');
    }
}
