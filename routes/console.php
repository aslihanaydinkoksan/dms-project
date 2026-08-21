<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// =========================================================================
// GÜNLÜK SABAH OPERASYONLARI (PIPELINE)
// =========================================================================

// 1. Önce raporları hazırla
Schedule::command('reports:process')->dailyAt('07:00');

// 2. Hazırlanan günlük raporları gönder
Schedule::command('reports:send daily')->dailyAt('07:30');

// 3. Yasal saklama (Fiziksel İmha) sürelerini denetle
Schedule::command('dms:check-retention')->dailyAt('08:00');

// 4. Arşiv süresi dolanları kaldır
Schedule::command('dms:archive-expired')->dailyAt('08:15');

// 5. Sözleşme bitiş sürelerini denetle
Schedule::command('dms:check-expiring-documents')->dailyAt('08:30');

// 6. Son olarak BPM Görev / Süreç bitiş tarihlerini denetle
Schedule::command('bpm:check-deadlines')->dailyAt('09:00');


// =========================================================================
// PERİYODİK RAPOR GÖNDERİMLERİ (HAFTALIK / AYLIK)
// =========================================================================

// HAFTALIK Raporlar (Pazartesi günleri saat 08:00'da çalışır)
Schedule::command('reports:send weekly')->weeklyOn(1, '8:00');

// AYLIK Raporlar (Her ayın 1'inde saat 08:00'da çalışır)
Schedule::command('reports:send monthly')->monthlyOn(1, '8:00');

// Her gece saat 02:00'da MYS ile otomatik eşitlenir
Schedule::command('mys:sync')->dailyAt('02:00');
