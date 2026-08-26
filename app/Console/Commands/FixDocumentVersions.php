<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Document;
use Illuminate\Support\Facades\DB;

class FixDocumentVersions extends Command
{
    protected $signature = 'dms:fix-versions';

    protected $description = 'Bozulmuş belge versiyon numaralarını (string sorting hatası) yaratılma sırasına göre onarır.';

    public function handle()
    {
        $this->info('Versiyon numaraları analiz ediliyor ve düzeltiliyor...');

        $documentCount = 0;
        $fixedVersionCount = 0;

        // Performans için chunk ile belgeleri çekiyoruz
        Document::chunk(100, function ($documents) use (&$documentCount, &$fixedVersionCount) {
            foreach ($documents as $document) {
                if (! $document instanceof Document) {
                    $document = Document::withTrashed()->find($document->id ?? null);
                }

                if (! $document) {
                    continue;
                }

                // Her belgenin tüm versiyonlarını, eklenme sırasına (id) göre alıyoruz
                $versions = $document->versions()->withTrashed()->orderBy('id', 'asc')->get();

                $counter = 1;
                foreach ($versions as $version) {

                    // storeDocument metodun ilk versiyonu "1.0" attığı için, 
                    // tutarlılığı bozmamak adına ilk kaydı 1.0, sonrakileri 2, 3, 4 şeklinde veriyoruz.
                    $correctVersionNumber = ($counter === 1) ? '1.0' : (string) $counter;

                    if ($version->version_number !== $correctVersionNumber) {
                        // updateQuietly() kullanarak model eventlerinin tetiklenmesini ve updated_at tarihinin bozulmasını engelliyoruz.
                        $version->updateQuietly(['version_number' => $correctVersionNumber]);
                        $fixedVersionCount++;
                    }

                    $counter++;
                }
                $documentCount++;
            }
        });

        $this->info("İşlem Tamamlandı!");
        $this->line("Taranan Toplam Belge: {$documentCount}");
        $this->line("Onarılan Bozuk Versiyon Sayısı: {$fixedVersionCount}");
    }
}
