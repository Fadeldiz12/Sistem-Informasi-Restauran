<?php

namespace App\Support;

/**
 * Time-based One-Time Password (RFC 6238, kompatibel Google Authenticator / Microsoft Authenticator / Authy):
 * HMAC-SHA1 atas nomor langkah waktu 30 detik, 6 digit. Rahasia 160 bit disimpan dalam base32 (RFC 4648).
 */
class Totp
{
    public const PERIODE = 30;

    public const DIGIT = 6;

    /** Toleransi jam HP yang sedikit maju/mundur: 1 langkah (±30 detik). */
    public const TOLERANSI = 1;

    private const BASE32 = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

    public static function buatRahasia(): string
    {
        return self::base32(random_bytes(20));
    }

    public static function langkahSekarang(): int
    {
        return intdiv(now()->getTimestamp(), self::PERIODE);
    }

    public static function kode(string $rahasia, int $langkah): string
    {
        $hash = hash_hmac('sha1', pack('J', $langkah), self::dariBase32($rahasia), true);
        $offset = ord($hash[19]) & 0x0F;
        $angka = ((ord($hash[$offset]) & 0x7F) << 24) | (ord($hash[$offset + 1]) << 16) | (ord($hash[$offset + 2]) << 8) | ord($hash[$offset + 3]);

        return str_pad((string) ($angka % (10 ** self::DIGIT)), self::DIGIT, '0', STR_PAD_LEFT);
    }

    /**
     * Langkah waktu tempat kode cocok, atau null. Kode dari langkah yang sudah pernah dipakai ($terakhir) ditolak.
     */
    public static function cocok(string $rahasia, string $kode, ?int $terakhir = null): ?int
    {
        $kode = preg_replace('/\D/', '', $kode) ?? '';
        if (strlen($kode) !== self::DIGIT) {
            return null;
        }
        $sekarang = self::langkahSekarang();
        for ($i = -self::TOLERANSI; $i <= self::TOLERANSI; $i++) {
            $langkah = $sekarang + $i;
            if (($terakhir === null || $langkah > $terakhir) && hash_equals(self::kode($rahasia, $langkah), $kode)) {
                return $langkah;
            }
        }

        return null;
    }

    /**
     * Tautan otpauth:// untuk aplikasi authenticator (dipakai sebagai isi QR).
     */
    public static function uri(string $rahasia, string $akun, string $penerbit): string
    {
        return 'otpauth://totp/'.rawurlencode($penerbit.':'.$akun).'?'.http_build_query([
            'secret' => $rahasia, 'issuer' => $penerbit, 'algorithm' => 'SHA1', 'digits' => self::DIGIT, 'period' => self::PERIODE,
        ], '', '&', PHP_QUERY_RFC3986);
    }

    public static function base32(string $biner): string
    {
        $bit = '';
        foreach (str_split($biner) as $c) {
            $bit .= str_pad(decbin(ord($c)), 8, '0', STR_PAD_LEFT);
        }

        return implode('', array_map(fn ($b) => self::BASE32[bindec(str_pad($b, 5, '0'))], str_split($bit, 5)));
    }

    public static function dariBase32(string $teks): string
    {
        $bit = '';
        foreach (str_split(strtoupper(preg_replace('/[^A-Za-z2-7]/', '', $teks) ?? '')) as $c) {
            $bit .= str_pad(decbin((int) strpos(self::BASE32, $c)), 5, '0', STR_PAD_LEFT);
        }

        return implode('', array_map(fn ($b) => chr(bindec($b)), array_filter(str_split($bit, 8), fn ($b) => strlen($b) === 8)));
    }
}
