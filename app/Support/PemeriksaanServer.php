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
        $uji = BackupTerenkripsi::hasilUjiTerakhir();
        $salinan = BackupTerenkripsi::folderSalinan();
        $salinanBaru = $salinan && collect(File::glob($salinan.'/resto-*.backup'))->contains(fn ($f) => File::lastModified($f) > now()->subDays(2)->getTimestamp());
        $https = $request->isSecure() || str_starts_with((string) config('app.url'), 'https://');

        return [
            ['label' => 'APP_KEY terisi', 'ok' => strlen(KunciAplikasi::biner(KunciAplikasi::sekarang())) >= 32, 'saran' => 'Jalankan "php artisan key:generate". Jangan pernah membagikan atau meng-commit file .env ke Git.'],
            ['label' => 'Isi sesi terenkripsi', 'ok' => (bool) config('session.encrypt'), 'saran' => 'Ubah SESSION_ENCRYPT=true di file .env, lalu "php artisan config:clear". Semua pengguna perlu masuk ulang sekali.'],
            ['label' => 'Mode debug mati', 'ok' => ! config('app.debug'), 'saran' => 'Ubah APP_DEBUG=false di file .env supaya pesan error tidak membocorkan isi kode & konfigurasi.'],
            ['label' => 'Mode produksi', 'ok' => app()->environment('production'), 'saran' => 'Ubah APP_ENV=production di file .env saat sudah dipakai sungguhan.'],
            ['label' => 'HTTPS aktif', 'ok' => $https, 'saran' => 'Pasang sertifikat SSL (mis. Let\'s Encrypt) dan ubah APP_URL ke https://. Kamera & GPS di HP/PC lain hanya jalan lewat HTTPS.'],
            ['label' => 'Cookie sesi hanya lewat HTTPS', 'ok' => (bool) config('session.secure'), 'saran' => 'Setelah HTTPS aktif, ubah APP_URL ke https:// (cookie aman aktif otomatis) atau tambahkan SESSION_SECURE_COOKIE=true di file .env.'],
            ['label' => 'Backup terenkripsi 2 hari terakhir', 'ok' => $backup && $backup > now()->subDays(2)->getTimestamp(), 'saran' => 'Jalankan "php artisan resto:backup", atau jadwalkan "php artisan schedule:run" tiap menit (Task Scheduler Windows / cron).'],
            ['label' => 'Salinan backup di lokasi lain', 'ok' => $salinanBaru, 'saran' => $salinan
                ? "Folder salinan {$salinan} belum berisi backup 2 hari terakhir. Pastikan folder/flashdisk tersambung lalu jalankan \"php artisan resto:backup\"."
                : 'Isi BACKUP_SALINAN_FOLDER di .env dengan folder lain (flashdisk, NAS, atau folder OneDrive/Google Drive), contoh BACKUP_SALINAN_FOLDER="D:\\Backup-Resto".'],
            ['label' => 'Uji pemulihan backup 7 hari terakhir', 'ok' => $uji && $uji['ok'] && now()->subDays(7)->lt($uji['waktu']), 'saran' => $uji && ! $uji['ok']
                ? 'Uji terakhir GAGAL: '.$uji['pesan']
                : 'Jalankan "php artisan resto:uji-backup" (otomatis tiap malam bila scheduler berjalan). Database utama tidak disentuh.'],
            ['label' => 'Kunci backup terpisah dari APP_KEY', 'ok' => BackupTerenkripsi::kunciTerpisah(), 'saran' => 'Jalankan "php artisan resto:kunci-backup", tempel hasilnya (BACKUP_KEY=...) ke .env, dan simpan salinan kuncinya di luar server.'],
            ['label' => 'Tanda tangan Ed25519 untuk laporan', 'ok' => TandaTanganDigital::ed25519Tersedia(), 'saran' => 'Aktifkan ekstensi sodium: di php.ini hapus tanda ; di depan "extension=sodium", lalu jalankan ulang server. Tanpa itu laporan hanya memakai kode HMAC.'],
            ['label' => 'QR di struk & laporan', 'ok' => class_exists(Writer::class), 'saran' => 'Jalankan "composer require bacon/bacon-qr-code" supaya kode verifikasi juga tampil sebagai QR. Tanpa itu, kode & tautan cek keaslian tetap tercetak.'],
        ];
    }
}
