<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ScheduledReport;
use App\Models\Document;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use App\Mail\ScheduledReportMail;
use Carbon\Carbon;

class SendScheduledReports extends Command
{
    // Komutumuz parametre alacak (daily, weekly, monthly)
    protected $signature = 'reports:send {frequency}';
    protected $description = 'Zamanlanmış raporları oluşturur ve e-posta ile gönderir.';

    public function handle()
    {
        $frequency = $this->argument('frequency');

        // Sadece aktif ve sıklığı eşleşen raporları bul
        $reports = ScheduledReport::where('is_active', true)
            ->where('frequency', $frequency)
            ->get();

        foreach ($reports as $report) {
            /** @var \App\Models\ScheduledReport $report */
            $this->info("Rapor oluşturuluyor: {$report->report_name}");

            // 1. Veriyi Çek
            $data = $this->fetchData($report->module, $report->date_range);

            // 2. CSV/Excel Dosyasını Oluştur (Storage içine geçici olarak)
            $fileName = 'reports/' . time() . '_rapor.csv';
            $this->generateCSV($data, $fileName);

            // 3. E-Postayı Gönder
            $recipients = array_map('trim', explode(',', $report->recipients));

            try {
                Mail::to($recipients)->send(new ScheduledReportMail($report->report_name, storage_path('app/private/' . $fileName)));

                // Gönderim tarihini güncelle
                $report->update(['last_sent_at' => now()]);
                $this->info("Başarıyla gönderildi!");
            } catch (\Exception $e) {
                $this->error("Gönderim hatası: " . $e->getMessage());
            }
        }
    }

    // --- YARDIMCI METOTLAR ---

    private function fetchData($module, $dateRange)
    {
        // Tarih filtresi
        $query = Document::query();
        if ($dateRange === 'last_24_hours') $query->where('created_at', '>=', Carbon::now()->subDay());
        if ($dateRange === 'last_7_days') $query->where('created_at', '>=', Carbon::now()->subDays(7));
        if ($dateRange === 'last_30_days') $query->where('created_at', '>=', Carbon::now()->subDays(30));

        // Modüle göre veri (Şimdilik örnek olarak Dokümanları çekiyoruz)
        if ($module === 'documents') {
            return $query->select('document_number', 'title', 'category', 'privacy_level', 'created_at')
                ->get()->toArray();
        }

        return [];
    }

    private function generateCSV($data, $fileName)
    {
        // UTF-8 BOM ekleyerek Excel'in Türkçe karakterleri düzgün okumasını sağlıyoruz
        Storage::put($fileName, "\xEF\xBB\xBF");

        $stream = fopen(storage_path('app/private/' . $fileName), 'a');

        // Sütun Başlıkları
        if (count($data) > 0) {
            fputcsv($stream, array_keys($data[0]));
        }

        // Veriler
        foreach ($data as $row) {
            fputcsv($stream, $row);
        }

        fclose($stream);
    }
}
