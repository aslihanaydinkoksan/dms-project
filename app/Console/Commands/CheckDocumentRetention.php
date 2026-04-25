<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Document;
use App\Notifications\DocumentRetentionNotification;

class CheckDocumentRetention extends Command
{
    protected $signature = 'dms:check-retention';
    protected $description = 'Dinamik saklama sürelerini kontrol eder ve süresi dolan/yaklaşan belgeler için uyarır.';

    public function handle()
    {
        $this->info('Dinamik saklama süresi kontrolleri başlatılıyor...');

        Document::whereNotIn('status', ['archived', 'destroyed', 'rejected'])
            ->chunk(100, function ($documents) {
                foreach ($documents as $document) {

                    $totalYears = ($document->department_retention_years ?? 0) + ($document->archive_retention_years ?? 0);

                    if ($totalYears === 0) {
                        continue;
                    }

                    $expirationDate = $document->created_at->copy()->addYears($totalYears);
                    $daysLeft = now()->startOfDay()->diffInDays($expirationDate->startOfDay(), false);

                    // 1. DURUM: Sürenin dolmasına tam 30 gün kaldıysa (YAKLAŞIYOR)
                    if ($daysLeft === 30) {
                        $this->info("⏳ Süre Yaklaşıyor (30 Gün): {$document->document_number}");
                        $this->notifyStakeholders($document, $daysLeft);
                    }
                    // 2. DURUM: Süre tamamen dolduysa (BİTTİ)
                    elseif ($daysLeft === 0) {
                        $this->info("❌ Evrak Süresi Doldu: {$document->document_number}");
                        $this->notifyStakeholders($document, 0);
                    }
                }
            });

        $this->info('Kontroller başarıyla tamamlandı.');
    }

    /**
     * Belge Sahibine ve (varsa) Departman Müdürüne bildirimi ateşler
     */
    private function notifyStakeholders($document, $daysLeft)
    {
        $owner = $document->currentVersion?->createdBy;
        $departmentManager = $document->relatedDepartment?->manager;

        // 1. Belge Sahibine Gönder
        if ($owner) {
            $owner->notify(new DocumentRetentionNotification($document, $daysLeft));
            $this->line("-> Uyarı gönderildi: {$owner->name}");
        }

        // 2. Departman Müdürüne Gönder (Belge sahibi ile aynı kişi değilse)
        if ($departmentManager && $departmentManager->id !== $owner?->id) {
            $departmentManager->notify(new DocumentRetentionNotification($document, $daysLeft));
            $this->line("-> Uyarı gönderildi (Yönetici): {$departmentManager->name}");
        }
    }
}
