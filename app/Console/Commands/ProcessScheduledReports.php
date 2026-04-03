<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use App\Models\Document;
use App\Mail\ScheduledReportMail;
use Carbon\Carbon;

class ProcessScheduledReports extends Command
{
    /**
     * Komutun terminalde çağrılma adı
     */
    protected $signature = 'reports:process';

    /**
     * Komutun açıklaması
     */
    protected $description = 'Zamanlanmış raporları (scheduled_reports) kontrol eder, CSV üretir ve e-posta ile gönderir.';

    public function handle()
    {
        $this->info('Rapor motoru çalıştırıldı. Bekleyen raporlar kontrol ediliyor...');

        // 1. O gün çalışması gereken raporları çek
        // Örneğin: frequency 'daily' olan ve bugün henüz çalışmamış olanlar.
        $reports = DB::table('scheduled_reports')
            ->where('is_active', 1)
            ->where('frequency', 'daily') // Eğer weekly, monthly varsa burayı orWhere ile genişletebilirsin
            ->where(function ($query) {
                $query->whereNull('last_run_at')
                    ->orWhereDate('last_run_at', '<', Carbon::today());
            })
            ->get();

        if ($reports->isEmpty()) {
            $this->info('Şu an çalıştırılacak rapor bulunamadı.');
            return;
        }

        // Raporları tutacağımız klasörün varlığından emin olalım
        $reportsDir = storage_path('app/reports');
        if (!is_dir($reportsDir)) {
            mkdir($reportsDir, 0755, true);
        }

        foreach ($reports as $report) {
            $this->info("ID: {$report->id} numaralı {$report->module} raporu işleniyor...");

            // Dosya adını ve yolunu dinamik belirle
            $fileName = $report->module . '_raporu_' . now()->format('Ymd_His') . '.csv';
            $filePath = $reportsDir . '/' . $fileName;

            // 2. CSV Dosyasını Oluştur ve Aç
            $file = fopen($filePath, 'w');

            // BOM Eklemesi: Microsoft Excel'de Türkçe karakterlerin (Ş, Ğ, İ vb.) bozulmaması için KRİTİK!
            fputs($file, chr(0xEF) . chr(0xBB) . chr(0xBF));

            // 3. Modüle Göre Veri Çek ve Yaz
            if ($report->module === 'documents') {
                // Sütun Başlıkları (Noktalı virgül kullanarak Excel'de sütunların düzgün ayrılmasını sağlıyoruz)
                fputcsv($file, ['ID', 'Doküman Kodu', 'Başlık', 'Statü', 'Gizlilik', 'Yüklenme Tarihi'], ';');

                // Veritabanını yormamak için verileri 200'erli paketler (chunk) halinde çekiyoruz
                Document::chunk(200, function ($documents) use ($file) {
                    foreach ($documents as $doc) {
                        fputcsv($file, [
                            $doc->id,
                            $doc->document_number,
                            $doc->title,
                            strtoupper($doc->status),
                            strtoupper($doc->privacy_level),
                            $doc->created_at->format('d.m.Y H:i')
                        ], ';');
                    }
                });
            } else {
                // Eğer farklı bir modül gelirse hata vermesin, boş rapor dönsün
                fputcsv($file, ['Bu modül (' . $report->module . ') için raporlama henüz tanımlanmadı.'], ';');
            }

            // Dosyayı kaydet ve kapat
            fclose($file);

            // 4. Mailleri Parçala ve Gönder
            // Virgülle ayrılmış mailleri diziye çevirip, etrafındaki olası boşlukları (trim) temizliyoruz
            $recipients = array_map('trim', explode(',', $report->recipients));
            $recipients = array_filter($recipients); // Boş elemanları çıkar

            if (!empty($recipients)) {
                try {
                    // ScheduledReportMail sınıfının construct metoduna $filePath aldığını varsayıyoruz
                    Mail::to($recipients)->send(new ScheduledReportMail($filePath, $report->module));
                    $this->info("✅ Rapor başarıyla gönderildi: " . implode(', ', $recipients));

                    // Başarılı olursa DB'de son çalışma tarihini güncelle
                    DB::table('scheduled_reports')->where('id', $report->id)->update(['last_run_at' => now()]);
                } catch (\Exception $e) {
                    $this->error("❌ Mail gönderim hatası: " . $e->getMessage());
                }
            } else {
                $this->warn("⚠️ Geçerli bir alıcı (mail) adresi bulunamadı.");
            }

            // 5. Sunucuyu yormamak için CSV dosyasını temizle
            if (file_exists($filePath)) {
                unlink($filePath);
                $this->info("🗑️ Geçici CSV dosyası silindi.");
            }
        }

        $this->info('Tüm raporlama işlemleri tamamlandı.');
    }
}
