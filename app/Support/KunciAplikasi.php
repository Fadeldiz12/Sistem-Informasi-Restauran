<?php

namespace App\Support;

/**
 * Daftar APP_KEY yang berlaku: kunci sekarang dulu, lalu kunci lama dari APP_PREVIOUS_KEYS.
 * Dipakai fitur kriptografi buatan sendiri (sidik nomor HP, kode URL, tanda tangan dokumen, backup),
 * supaya data & dokumen lama tetap bisa dibaca/dicek setelah APP_KEY diganti.
 */
class KunciAplikasi
{
    /**
     * @return list<string>
     */
    public static function semua(): array
    {
        $kunci = array_merge([(string) config('app.key')], array_map('strval', (array) config('app.previous_keys', [])));

        return array_values(array_unique(array_filter(array_map('trim', $kunci))));
    }

    public static function sekarang(): string
    {
        return (string) config('app.key');
    }

    /**
     * @return list<string>
     */
    public static function lama(): array
    {
        return array_values(array_diff(self::semua(), [self::sekarang()]));
    }

    /**
     * Bentuk biner kunci ("base64:..." dibuka dulu).
     */
    public static function biner(string $kunci): string
    {
        return str_starts_with($kunci, 'base64:') ? (string) base64_decode(substr($kunci, 7), true) : $kunci;
    }
}
