<?php

namespace App\Models;

use App\Models\Concerns\IdTerenkripsiDiUrl;
use App\Models\Concerns\TercatatDiLog;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Table('pengeluaran')]
#[Fillable(['id_owner', 'kategori', 'nominal', 'keterangan', 'tanggal'])]
class Pengeluaran extends Model
{
    use TercatatDiLog;

    public const LOG_KODE = 'pengeluaran';

    public const LOG_JENIS = 'Pengeluaran';

    public const LOG_JUDUL = 'kategori';

    use IdTerenkripsiDiUrl;

    public const KATEGORI = ['Bahan baku', 'Gas', 'Utilitas', 'Gaji', 'Perawatan', 'Kemasan', 'Lainnya'];

    protected function casts(): array
    {
        return [
            'nominal' => 'decimal:2',
            'tanggal' => 'date',
        ];
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_owner');
    }
}
