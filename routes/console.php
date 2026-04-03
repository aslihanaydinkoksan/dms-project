<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('dms:check-expiring-documents')->dailyAt('01:00');
Schedule::command('dms:archive-expired')->dailyAt('00:00');
// GÜNLÜK Raporlar (Her gün saat 18:00'da çalışır)
Schedule::command('reports:send daily')->dailyAt('18:00');

// HAFTALIK Raporlar (Pazartesi günleri saat 08:00'da çalışır)
Schedule::command('reports:send weekly')->weeklyOn(1, '8:00');

// AYLIK Raporlar (Her ayın 1'inde saat 08:00'da çalışır)
Schedule::command('reports:send monthly')->monthlyOn(1, '8:00');
// Rapor motorumuzu her sabah saat 08:00'da çalışacak şekilde kurduk
Schedule::command('reports:process')->dailyAt('08:00');
