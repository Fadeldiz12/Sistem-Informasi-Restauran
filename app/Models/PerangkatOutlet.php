<?php

namespace App\Models;

use App\Models\Concerns\IdTerenkripsiDiUrl;
use App\Models\Concerns\TercatatDiLog;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * PC / tablet outlet yang boleh dipakai staf untuk masuk. Pengenalnya token acak di cookie perangkat.
 */
#[Table('perangkat_outlet')]
#[Fillable(['nama', 'token_hash', 'id_pendaftar', 'keterangan_perangkat', 'terakhir_dipakai_at'])]
#[Hidden(['token_hash'])]
class PerangkatOutlet extends Model
{
    use IdTerenkripsiDiUrl;
    use TercatatDiLog;

    public const LOG_KODE = 'perangkat';

    public const LOG_JENIS = 'Perangkat outlet';

    public const LOG_JUDUL = 'nama';

    public const LOG_ABAIKAN = ['token_hash', 'terakhir_dipakai_at'];

    public const COOKIE = 'perangkat_outlet';

    protected function casts(): array
    {
        return ['terakhir_dipakai_at' => 'datetime'];
    }

    public function pendaftar(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_pendaftar');
    }

    public static function dariToken(?string $token): ?self
    {
        return $token ? static::firstWhere('token_hash', hash('sha256', $token)) : null;
    }
}
