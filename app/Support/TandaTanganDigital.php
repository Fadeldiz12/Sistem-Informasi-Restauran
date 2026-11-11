<?php

namespace App\Support;

use App\Models\Pesanan;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;

/**
 * Tanda tangan digital untuk struk & laporan: HMAC-SHA256 (kunci turunan APP_KEY) atas angka-angka penting dokumen.
 * Kode verifikasi dicetak di dokumen bersama tautan/QR ke halaman cek keaslian; angka yang diedit = tanda tangan tidak cocok.
 */
class TandaTanganDigital
{
    private const HURUF = '0123456789ABCDEFGHJKMNPQRSTVWXYZ'; // base32 Crockford (tanpa I, L, O, U)

    public static function tandai(string $isi): string
    {
        $mentah = substr(hash_hmac('sha256', $isi, 'tanda-tangan-dokumen|'.config('app.key'), true), 0, 10);

        return implode('-', str_split(self::base32($mentah), 4));
    }

    public static function cocok(string $isi, ?string $kode): bool
    {
        return hash_equals(self::tandai($isi), strtoupper(trim((string) $kode)));
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
     * @return array{kode: string, url: string}
     */
    public static function laporan(string $dari, string $sampai, float $pendapatan, float $pengeluaran, float $laba): array
    {
        $param = ['dari' => $dari, 'sampai' => $sampai, 'pd' => (int) round($pendapatan), 'pk' => (int) round($pengeluaran), 'lb' => (int) round($laba), 'w' => now()->format('YmdHi')];
        $kode = self::tandai(self::isiLaporan($param));

        return ['kode' => $kode, 'url' => route('keaslian.laporan', $param + ['s' => $kode])];
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

    private static function base32(string $biner): string
    {
        $bit = '';
        foreach (str_split($biner) as $c) {
            $bit .= str_pad(decbin(ord($c)), 8, '0', STR_PAD_LEFT);
        }

        return implode('', array_map(fn ($b) => self::HURUF[bindec(str_pad($b, 5, '0'))], str_split($bit, 5)));
    }
}
