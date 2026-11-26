<?php

use App\Support\PemeriksaanServer;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Backup data terenkripsi tiap malam (butuh "php artisan schedule:work" atau cron/Task Scheduler yang menjalankan schedule:run)
// penanda scheduler hidup: Pemeriksaan Server memperingatkan bila cron hosting berhenti (backup ikut berhenti)
Schedule::call(fn () => PemeriksaanServer::catatSchedulerJalan())->everyMinute()->name('penanda-scheduler');

Schedule::command('resto:backup')->dailyAt('23:45');
// uji pulihkan backup terbaru ke database sementara: backup yang tidak bisa dipulihkan ketahuan sebelum dibutuhkan
Schedule::command('resto:uji-backup')->dailyAt('23:55');
// email ringkasan kejadian keamanan 24 jam terakhir ke Owner (hanya bila ada kejadian)
Schedule::command('resto:ringkasan-keamanan')->dailyAt('07:05');
// hapus data pribadi yang melewati masa simpan (lihat config/resto.php bagian retensi)
Schedule::command('resto:bersihkan-data-pribadi')->dailyAt('02:15');
// periksa rantai hash log aktivitas tiap jam (hasilnya tampil di Log Aktivitas & Pemeriksaan Server)
Schedule::command('resto:cek-log')->hourly();
