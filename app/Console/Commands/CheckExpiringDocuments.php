<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Document;
use App\Models\SystemSetting;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class CheckExpiringDocuments extends Command
{
    /**
     * Terminalde veya Cron'da çalıştırılacak komutun imzası.
     */
    protected $signature = 'dms:check-expiring-documents';

    /**
     * Komutun açıklaması.
     */
    protected $description = 'Süresi dolmaya yaklaşan sözleşmeleri tespit eder ve dinamik ayarlara göre uyarı gönderir.';

    public function handle(): void
    {
        $this->info('Süresi dolan belgeler kontrol ediliyor...');

        // 1. Dinamik Ayarları Çek (Eğer ayar yoksa varsayılan olarak 30, 15 ve 7 gün kala uyar)
        // Array formatında döneceğini varsayıyoruz: [30, 15, 7]
        $warningDays = SystemSetting::getByKey('expiration_warning_days', [30, 15, 7]);

        $documentsFound = 0;

        foreach ($warningDays as $days) {
            // Hedef tarihi hesapla (Bugün + X gün)
            $targetDate = Carbon::today()->addDays($days)->toDateString();

            // 2. O gün süresi dolacak "Yayındaki (Published)" belgeleri bul
            $expiringDocs = Document::where('status', 'published')
                ->whereDate('expire_at', $targetDate)
                ->get();

            // IDE'nin $document değişkenini tanıması için PHPDoc ekliyoruz
            /** @var \App\Models\Document $document */
            foreach ($expiringDocs as $document) {

                // Belgenin güncel versiyonunu yükleyen kişiyi (Sahibini) bul
                $owner = $document->currentVersion?->createdBy;

                if ($owner) {
                    // Kullanıcıya bildirimi yolla!
                    $owner->notify(new \App\Notifications\DocumentExpiringNotification($document, $days));

                    // Yine de sistem loglarında iz bırakmak iyidir
                    Log::channel('daily')->info("BİLDİRİM GÖNDERİLDİ: {$owner->email} adresine {$document->document_number} için {$days} gün uyarısı iletildi.");
                }

                $documentsFound++;
            }
        }

        $this->info("Kontrol tamamlandı. Toplam {$documentsFound} belge için uyarı tetiklendi.");
    }
}
