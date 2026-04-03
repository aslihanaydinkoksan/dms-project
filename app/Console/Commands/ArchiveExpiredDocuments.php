<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Document;
use App\Models\AuditLog;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class ArchiveExpiredDocuments extends Command
{
    /**
     * Komutun terminaldeki adı.
     */
    protected $signature = 'dms:archive-expired';

    /**
     * Komutun açıklaması.
     */
    protected $description = 'Bölümde saklama süresi (department_retention_years) dolan aktif belgeleri sonsuz arşive (Salt Okunur) kaldırır.';

    public function handle()
    {
        $this->info('Sonsuz Arşiv Motoru (Kütüphaneci) çalışıyor...');

        // Durumu yayında olan ve saklama süresi > 0 olan belgeleri getir
        $documents = Document::where('status', 'published')
            ->where('department_retention_years', '>', 0)
            ->get();

        $archivedCount = 0;

        foreach ($documents as $doc) {
            // Belgenin oluşturulma tarihine saklama yılını ekle
            $expirationDate = $doc->created_at->addYears($doc->department_retention_years);

            // Eğer bitiş tarihi (expire_at) varsa veya bölüm saklama süresi dolduysa
            $isExpiredByDate = $doc->expire_at && Carbon::parse($doc->expire_at)->isPast();
            $isExpiredByRetention = $expirationDate->isPast();

            if ($isExpiredByDate || $isExpiredByRetention) {
                // SİLME YOK! Sadece statüyü arşivlendi yap ve kilidi varsa aç
                $doc->update([
                    'status' => 'archived',
                    'is_locked' => false,
                    'locked_by' => null
                ]);

                // Sistem Audit Loglarına İşle
                AuditLog::create([
                    'user_id' => null, // Sistem (Cron) tarafından yapıldığı için null
                    'auditable_type' => Document::class,
                    'auditable_id' => $doc->id,
                    'event' => 'archived',
                    'ip_address' => '127.0.0.1'
                ]);

                Log::info("SİSTEM ARŞİVİ: Belge [{$doc->document_number}] saklama süresini doldurduğu için Salt Okunur (Read-Only) arşive kaldırıldı.");
                $archivedCount++;
            }
        }

        $this->info("İşlem tamamlandı. Toplam {$archivedCount} belge arşive kaldırıldı.");
    }
}
