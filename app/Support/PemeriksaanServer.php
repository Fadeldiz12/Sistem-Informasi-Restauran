<?php

namespace App\Support;

use BaconQrCode\Writer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

/**
 * Daftar cek konfigurasi server yang penting untuk keamanan, ditampilkan ke Owner.
 */
class PemeriksaanServer
{
    /**
     * @return array<int, array{label: string, ok: bool, saran: string}>
     */
    public static function daftar(Request $request): array
    {
        $backup = collect(File::glob(BackupTerenkripsi::folder().'/resto-*.backup'))->map(fn ($f) => File::lastModified($f))->max();
        $https = $request->isSecure() || str_starts_with((string) config('app.url'), 'https://');

        return [
            ['label' => 'Mode debug mati', 'ok' => ! config('app.debug'), 'saran' => 'Ubah APP_DEBUG=false di file .env supaya pesan error tidak membocorkan isi kode & konfigurasi.'],
            ['label' => 'Mode produksi', 'ok' => app()->environment('production'), 'saran' => 'Ubah APP_ENV=production di file .env saat sudah dipakai sungguhan.'],
            ['label' => 'HTTPS aktif', 'ok' => $https, 'saran' => 'Pasang sertifikat SSL (mis. Let\'s Encrypt) dan ubah APP_URL ke https://. Kamera & GPS di HP/PC lain hanya jalan lewat HTTPS.'],
            ['label' => 'Cookie sesi hanya lewat HTTPS', 'ok' => (bool) config('session.secure'), 'saran' => 'Setelah HTTPS aktif, tambahkan SESSION_SECURE_COOKIE=true di file .env.'],
            ['label' => 'Backup terenkripsi 2 hari terakhir', 'ok' => $backup && $backup > now()->subDays(2)->getTimestamp(), 'saran' => 'Jalankan "php artisan resto:backup", atau jadwalkan "php artisan schedule:run" tiap menit (Task Scheduler Windows / cron).'],
            ['label' => 'Kunci backup terpisah', 'ok' => filled(config('resto.backup.kunci')), 'saran' => 'Isi BACKUP_KEY di .env (minimal 16 karakter) dan simpan salinannya di tempat lain, supaya backup tetap bisa dibuka bila .env hilang.'],
            ['label' => 'QR di struk & laporan', 'ok' => class_exists(Writer::class), 'saran' => 'Jalankan "composer require bacon/bacon-qr-code" supaya kode verifikasi juga tampil sebagai QR. Tanpa itu, kode & tautan cek keaslian tetap tercetak.'],
        ];
    }
}
