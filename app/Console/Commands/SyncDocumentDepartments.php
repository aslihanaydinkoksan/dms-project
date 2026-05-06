<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Document;

class SyncDocumentDepartments extends Command
{
    protected $signature = 'dms:sync-departments';
    protected $description = 'Geçmişe dönük olarak belgelerin departmanlarını, bulundukları klasörlerin departmanlarıyla eşitler (Inheritance).';

    public function handle()
    {
        $this->info('Belge kalıtım senkronizasyonu başlatılıyor...');
        $count = 0;

        // RAM şişmesini önlemek için chunk(100) kullanıyoruz
        Document::with('folder')->chunkById(100, function ($documents) use (&$count) {
            foreach ($documents as $document) {
                /** @var \App\Models\Document $document */
                // Eğer belgenin klasörü varsa ve o klasör bir departmana bağlıysa
                if ($document->folder && $document->folder->department_id) {
                    // Belgenin departmanı, klasörden farklıysa GÜNCELLE
                    if ($document->related_department_id !== $document->folder->department_id) {
                        $document->related_department_id = $document->folder->department_id;

                        // saveQuietly() kullanarak Observer'ları ve eventleri atlıyoruz ki 
                        // geçmiş belgelerin güncellenme tarihleri veya bildirimleri tetiklenmesin.
                        $document->saveQuietly();
                        $count++;
                    }
                }
            }
        });

        $this->info("Senkronizasyon Tamamlandı!");
        $this->line("<fg=green>Başarıyla {$count} adet belgenin departmanı, klasör yapısına uygun olarak kalıtımla düzeltildi.</>");
    }
}
