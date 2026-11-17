<?php

namespace App\Support;

use App\Models\Pesanan;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;

/**
 * Tanda tangan digital untuk struk & laporan.
 *
 * - Kode verifikasi pendek (dicetak, bisa diketik): HMAC-SHA256 kunci turunan APP_KEY, dipotong 80 bit.
 * - Laporan keuangan juga ditandatangani Ed25519 (tanda tangan asimetris 512 bit): siapa pun bisa memverifikasi
 *   dengan kunci publik di /cek-keaslian/kunci-publik tanpa memegang kunci rahasia restoran.
 *
 * Dokumen yang ditandatangani dengan APP_KEY lama (APP_PREVIOUS_KEYS) tetap dikenali setelah kunci diganti.
 */
class TandaTanganDigital
{
    private const HURUF = '0123456789ABCDEFGHJKMNPQRSTVWXYZ'; // base32 Crockford (tanpa I, L, O, U)

    public static function tandai(string $isi, ?string $appKey = null): string
    {
        $mentah = substr(hash_hmac('sha256', $isi, 'tanda-tangan-dokumen|'.($appKey ?? KunciAplikasi::sekarang()), true), 0, 10);

        return implode('-', str_split(self::base32($mentah), 4));
    }

    public static function cocok(string $isi, ?string $kode): bool
    {
        $kode = strtoupper(trim((string) $kode));
        foreach (KunciAplikasi::semua() as $appKey) {
            if (hash_equals(self::tandai($isi, $appKey), $kode)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Ekstensi PHP sodium (libsodium) tersedia. Di Windows aktifkan "extension=sodium" di php.ini.
     */
    public static function ed25519Tersedia(): bool
    {
        return function_exists('sodium_crypto_sign_detached');
    }

    /**
     * Tanda tangan Ed25519 (base64url, 64 byte) atas isi dokumen dengan kunci rahasia turunan APP_KEY.
     */
    public static function tandaiEd25519(string $isi): string
    {
        return self::base64url(sodium_crypto_sign_detached($isi, sodium_crypto_sign_secretkey(self::pasanganKunci(KunciAplikasi::sekarang()))));
    }

    public static function cocokEd25519(string $isi, ?string $tandaTangan): bool
    {
        if (! self::ed25519Tersedia()) {
            return false;
        }
        $biner = base64_decode(strtr((string) $tandaTangan, '-_', '+/'), true);
        if ($biner === false || strlen($biner) !== SODIUM_CRYPTO_SIGN_BYTES) {
            return false;
        }
        foreach (self::kunciPublik() as $publik) {
            if (sodium_crypto_sign_verify_detached($biner, $isi, base64_decode($publik))) {
                return true;
            }
        }

        return false;
    }

    /**
     * Kunci publik Ed25519 (base64) yang berlaku: kunci sekarang dulu, lalu kunci dari APP_KEY lama.
     *
     * @return list<string>
     */
    public static function kunciPublik(): array
    {
        if (! self::ed25519Tersedia()) {
            return [];
        }

        return array_map(fn (string $appKey) => base64_encode(sodium_crypto_sign_publickey(self::pasanganKunci($appKey))), KunciAplikasi::semua());
    }

    /**
     * Sidik singkat kunci publik untuk dicetak di dokumen, contoh "7F3A-91C2".
     */
    public static function sidikKunci(string $publikBase64): string
    {
        return implode('-', str_split(strtoupper(substr(hash('sha256', base64_decode($publikBase64)), 0, 8)), 4));
    }

    /**
     * Pasangan kunci Ed25519 deterministik dari APP_KEY: tidak perlu menyimpan file kunci terpisah,
     * dan ikut berganti saat APP_KEY dirotasi.
     */
    private static function pasanganKunci(string $appKey): string
    {
        static $cache = [];

        return $cache[$appKey] ??= sodium_crypto_sign_seed_keypair(hash_hmac('sha256', 'tanda-tangan-ed25519', KunciAplikasi::biner($appKey), true));
    }

    /**
     * Isi struk yang ditandatangani: nomor, total, metode, waktu bayar, dan setiap baris menu.
     */
    public static function isiStruk(Pesanan $pesanan): string
    {
        $bayar = $pesanan->pembayaran;
        $baris = $pesanan->detail->sortBy('id')->map(fn ($d) => $d->id_menu.':'.$d->jumlah.':'.number_format((float) $d->subtotal, 2, '.', ''))->implode(',');

        return implode('|', ['struk', $pesanan->id, $pesanan->kode(), number_format((float) $pesanan->total, 2, '.', ''),
            $bayar?->metode, $bayar?->waktu_bayar?->format('YmdHis'), $baris]);
    }

    /**
     * @return array{kode: string, url: string}
     */
    public static function struk(Pesanan $pesanan): array
    {
        $kode = self::tandai(self::isiStruk($pesanan));

        return ['kode' => $kode, 'url' => route('keaslian.struk', ['pesanan' => $pesanan, 's' => $kode])];
    }

    /**
     * Laporan keuangan: periode & angka ringkasan ikut di tautan, jadi pembaca bisa mencocokkan angka di kertas.
     *
     * @return array{kode: string, url: string, sidik: ?string}
     */
    public static function laporan(string $dari, string $sampai, float $pendapatan, float $pengeluaran, float $laba): array
    {
        $param = ['dari' => $dari, 'sampai' => $sampai, 'pd' => (int) round($pendapatan), 'pk' => (int) round($pengeluaran), 'lb' => (int) round($laba), 'w' => now()->format('YmdHi')];
        $isi = self::isiLaporan($param);
        $kode = self::tandai($isi);
        if (! self::ed25519Tersedia()) {
            return ['kode' => $kode, 'url' => route('keaslian.laporan', $param + ['s' => $kode]), 'sidik' => null];
        }

        return ['kode' => $kode, 'url' => route('keaslian.laporan', $param + ['s' => $kode, 'sig' => self::tandaiEd25519($isi)]),
            'sidik' => self::sidikKunci(self::kunciPublik()[0])];
    }

    /**
     * @param  array<string, mixed>  $p
     */
    public static function isiLaporan(array $p): string
    {
        return implode('|', ['laporan', $p['dari'] ?? '', $p['sampai'] ?? '', (int) ($p['pd'] ?? 0), (int) ($p['pk'] ?? 0), (int) ($p['lb'] ?? 0), $p['w'] ?? '']);
    }

    /**
     * Gambar QR (SVG data URI) bila library bacon/bacon-qr-code terpasang; null bila belum.
     */
    public static function qr(string $url): ?string
    {
        if (! class_exists(Writer::class)) {
            return null;
        }
        $renderer = new ImageRenderer(new RendererStyle(160, 1), new SvgImageBackEnd);

        return 'data:image/svg+xml;base64,'.base64_encode((new Writer($renderer))->writeString($url));
    }

    private static function base64url(string $biner): string
    {
        return rtrim(strtr(base64_encode($biner), '+/', '-_'), '=');
    }

    private static function base32(string $biner): string
    {
        $bit = '';
        foreach (str_split($biner) as $c) {
            $bit .= str_pad(decbin(ord($c)), 8, '0', STR_PAD_LEFT);
        }

        return implode('', array_map(fn ($b) => self::HURUF[bindec(str_pad($b, 5, '0'))], str_split($bit, 5)));
    }
}
