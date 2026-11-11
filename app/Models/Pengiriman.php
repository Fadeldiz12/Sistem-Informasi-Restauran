<?php

namespace App\Models;

use App\Models\Concerns\IdTerenkripsiDiUrl;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Table('pengiriman')]
#[Fillable(['id_pesanan', 'id_kurir', 'id_zona', 'nama_penerima', 'no_hp_penerima', 'alamat', 'lat', 'lng', 'patokan', 'jarak_km', 'waktu_siap', 'status_kirim', 'bukti_kirim', 'berangkat_pada', 'diterima_pada'])]
class Pengiriman extends Model
{
    use IdTerenkripsiDiUrl;

    public const STATUS_MENUNGGU_KURIR = 'menunggu_kurir';

    public const STATUS_DALAM_PERJALANAN = 'dalam_perjalanan';

    public const STATUS_DITERIMA = 'diterima';

    protected function casts(): array
    {
        return [
            'alamat' => 'encrypted',
            'no_hp_penerima' => 'encrypted',
            'patokan' => 'encrypted',
            'berangkat_pada' => 'datetime',
            'diterima_pada' => 'datetime',
            'jarak_km' => 'decimal:2',
            'waktu_siap' => 'datetime',
        ];
    }

    public function pesanan(): BelongsTo
    {
        return $this->belongsTo(Pesanan::class, 'id_pesanan');
    }

    public function kurir(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_kurir');
    }

    public function zona(): BelongsTo
    {
        return $this->belongsTo(ZonaPengiriman::class, 'id_zona');
    }

    /**
     * Tugas pengiriman FIFO: sudah siap, waktu_siap paling lama di atas.
     */
    #[Scope]
    protected function fifo(Builder $query): void
    {
        $query->whereNotNull('waktu_siap')->orderBy('waktu_siap')->orderBy('id');
    }
}
