<?php

namespace App\Support;

/**
 * Enkripsi ID untuk URL: angka ID diganti kode acak 22 karakter (AES-128 dengan kunci dari APP_KEY),
 * supaya ID tidak bisa ditebak dengan mengganti angka di alamat. Kode selalu sama untuk ID yang sama
 * dan terikat ke jenis datanya: kode pesanan tidak berlaku sebagai kode menu.
 */
class KodeUrl
{
    private const CIPHER = 'aes-128-ecb';

    public static function enkripsi(int $id, string $jenis): string
    {
        $blok = pack('J', $id).self::tanda($jenis);
        $acak = openssl_encrypt($blok, self::CIPHER, self::kunci(), OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING);

        return rtrim(strtr(base64_encode($acak), '+/', '-_'), '=');
    }

    /**
     * ID dari kode URL, atau null bila kode rusak / bukan untuk jenis data ini.
     */
    public static function dekripsi(mixed $kode, string $jenis): ?int
    {
        if (! is_string($kode) || ! preg_match('/^[A-Za-z0-9_-]{22}$/', $kode)) {
            return null;
        }
        $blok = openssl_decrypt(base64_decode(strtr($kode, '-_', '+/')), self::CIPHER, self::kunci(), OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING);
        if ($blok === false || strlen($blok) !== 16 || ! hash_equals(self::tanda($jenis), substr($blok, 8))) {
            return null;
        }
        $id = unpack('J', substr($blok, 0, 8))[1];

        return $id > 0 ? $id : null;
    }

    private static function tanda(string $jenis): string
    {
        return substr(hash_hmac('sha256', $jenis, self::kunci(), true), 0, 8);
    }

    private static function kunci(): string
    {
        static $kunci = [];
        $appKey = (string) config('app.key');

        return $kunci[$appKey] ??= substr(hash_hmac('sha256', 'kode-url', $appKey, true), 0, 16);
    }
}
