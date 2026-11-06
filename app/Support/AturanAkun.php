<?php

namespace App\Support;

/**
 * Aturan isian akun yang dipakai di registrasi, profil pelanggan, dan form pengguna admin.
 */
class AturanAkun
{
    /**
     * Nomor HP Indonesia: 08xx / 628xx / +628xx, 10-14 digit (spasi & tanda strip diabaikan).
     */
    public const REGEX_HP = '/^(\+62|62|0)8[0-9]{8,12}$/';

    /**
     * Nama: huruf (termasuk huruf beraksen), spasi, dan tanda baca wajar.
     */
    public const REGEX_NAMA = "/^[\\pL\\s'.,-]+$/u";

    public const PESAN_HP = 'Nomor HP tidak valid. Gunakan nomor HP aktif, contoh: 081234567890.';

    public const PESAN_NAMA = "Nama hanya boleh berisi huruf, spasi, dan tanda baca (' . , -).";

    /**
     * Rapikan nomor HP sebelum divalidasi: hapus spasi, titik, dan tanda strip.
     */
    public static function rapikanHp(?string $hp): string
    {
        return preg_replace('/[\s.\-]/', '', (string) $hp);
    }
}
