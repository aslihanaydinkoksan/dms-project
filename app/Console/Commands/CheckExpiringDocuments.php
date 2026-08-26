<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Document;
use App\Models\SystemSetting;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification; // On-Demand bildirim için eklendi
use App\Notifications\DocumentExpiringNotification;
use App\Notifications\ExternalDocumentExpiringNotification; // Yeni sınıfımız dahil edildi

class CheckExpiringDocuments extends Command
{
    protected $signature = 'dms:check-expiring-documents';

    protected $description = 'Süresi dolmaya yaklaşan sözleşmeleri tespit eder ve dinamik ayarlara göre iç/dış paydaşlara uyarı gönderir.';

    public function handle(): void
    {
        $this->info('Süresi dolan belgeler kontrol ediliyor...');

        // 1. Dinamik Ayarları Çek
        $warningDays = SystemSetting::getByKey('expiration_warning_days', [30, 15, 7]);

        $documentsFound = 0;
        $externalSharesNotified = 0; // Konsol istatistiği için eklendi

        foreach ($warningDays as $days) {
            $targetDate = Carbon::today()->addDays($days)->toDateString();

            // 2. N+1 SORGUSU DÜZELTİLDİ: externalShares ilişkisini with() ile eager load yapıyoruz.
            $expiringDocs = Document::with('externalShares')
                ->where('status', 'published')
                ->whereDate('expire_at', $targetDate)
                ->get();

            /** @var Document $document */
            foreach ($expiringDocs as $document) {

                // --- A) İÇ PAYDAŞ (BELGE SAHİBİ) BİLDİRİMİ ---
                $owner = $document->currentVersion?->createdBy;

                if ($owner) {
                    $owner->notify(new DocumentExpiringNotification($document, $days));
                    Log::channel('daily')->info("İÇ BİLDİRİM GÖNDERİLDİ: {$owner->email} adresine {$document->document_number} için {$days} gün uyarısı iletildi.");
                }

                // --- B) DIŞ PAYDAŞ (HARİCİ KİŞİLER) BİLDİRİMİ ---
                if ($document->externalShares->isNotEmpty()) {
                    foreach ($document->externalShares as $share) {

                        // EKSTRA GÜVENLİK: Eğer harici paylaşıma atanan özel erişim süresi zaten dolmuşsa, mail atmaya gerek yok.
                        if (!$share->isExpired()) {

                            // On-Demand (Anında) Notification Fırlatma
                            Notification::route('mail', $share->email)
                                ->notify(new ExternalDocumentExpiringNotification($document, $days, $share));

                            $externalSharesNotified++;
                            Log::channel('daily')->info("DIŞ BİLDİRİM GÖNDERİLDİ: {$share->email} adresine {$document->document_number} için {$days} gün uyarısı (Token linkli) iletildi.");
                        }
                    }
                }

                $documentsFound++;
            }
        }

        $this->info("Kontrol tamamlandı. Toplam {$documentsFound} belge için uyarı tetiklendi. {$externalSharesNotified} dış paydaşa uyarı iletildi.");
    }
}
