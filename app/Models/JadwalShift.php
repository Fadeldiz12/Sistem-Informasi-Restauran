<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Jadwal shift mingguan staf. Staf hanya bisa masuk dari 30 menit sebelum shift sampai 60 menit sesudahnya.
 */
#[Table('jadwal_shift')]
#[Fillable(['id_user', 'hari', 'jam_mulai', 'jam_selesai'])]
class JadwalShift extends Model
{
    public const HARI = [1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu', 7 => 'Minggu'];

    public const MASUK_LEBIH_AWAL_MENIT = 30;

    public const TOLERANSI_PULANG_MENIT = 60;

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_user');
    }

    /**
     * Rentang shift yang dimulai pada tanggal $hari (jam selesai lebih awal = keesokan harinya).
     *
     * @return array{0: Carbon, 1: Carbon}
     */
    public function rentangPada(Carbon $hari): array
    {
        $mulai = $hari->copy()->setTimeFromTimeString($this->jam_mulai);
        $selesai = $hari->copy()->setTimeFromTimeString($this->jam_selesai);
        if ($selesai->lte($mulai)) {
            $selesai->addDay();
        }

        return [$mulai, $selesai];
    }

    public function label(): string
    {
        return substr($this->jam_mulai, 0, 5).'–'.substr($this->jam_selesai, 0, 5);
    }
}
