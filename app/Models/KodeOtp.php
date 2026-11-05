<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;

/**
 * Kode OTP 6 digit untuk verifikasi email (registrasi) dan lupa password.
 * Kode disimpan dalam bentuk hash.
 */
#[Table('kode_otp')]
#[Fillable(['email', 'tujuan', 'kode_hash', 'percobaan', 'kedaluwarsa_pada'])]
class KodeOtp extends Model
{
    public const TUJUAN_REGISTRASI = 'registrasi';

    public const TUJUAN_RESET = 'reset_password';

    protected $hidden = ['kode_hash'];

    protected function casts(): array
    {
        return [
            'percobaan' => 'integer',
            'kedaluwarsa_pada' => 'datetime',
        ];
    }
}
