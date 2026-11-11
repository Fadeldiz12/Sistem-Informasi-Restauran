<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Backup data terenkripsi tiap malam (butuh "php artisan schedule:work" atau cron/Task Scheduler yang menjalankan schedule:run)
Schedule::command('resto:backup')->dailyAt('23:45');
