<?php

namespace App\Models;

use App\Models\Concerns\IdTerenkripsiDiUrl;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

#[Table('tutup_kasir')]
#[Fillable(['id_kasir', 'mulai_dari', 'waktu_tutup', 'jumlah_pesanan', 'jumlah_batal', 'total_pemasukan', 'total_tunai', 'total_nontunai', 'total_setoran'])]
class TutupKasir extends Model
{
    use IdTerenkripsiDiUrl;

    protected function casts(): array
    {
        return [
            'mulai_dari' => 'datetime',
            'waktu_tutup' => 'datetime',
            'total_pemasukan' => 'decimal:2',
            'total_tunai' => 'decimal:2',
            'total_nontunai' => 'decimal:2',
            'total_setoran' => 'decimal:2',
        ];
    }

    public function kasir(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_kasir');
    }

    /**
     * Awal periode yang boleh dilihat kasir: EOD terakhir hari ini, atau awal hari ini bila belum EOD
     * (besoknya otomatis mulai dari nol walau kasir lupa EOD).
     */
    public static function batas(): Carbon
    {
        $terakhir = static::where('waktu_tutup', '>=', today())->max('waktu_tutup');

        return $terakhir ? Carbon::parse($terakhir) : today();
    }
}
