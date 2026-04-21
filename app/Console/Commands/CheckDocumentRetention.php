<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Document;
use Carbon\Carbon;

class CheckDocumentRetention extends Command
{
    protected $signature = 'dms:check-retention';
    protected $description = 'Saklama süresi dolan belgeleri tespit eder ve uyarır.';

    public function handle()
    {
        // 10 yıl = 3650 gün (Departman/Arşiv yılları DB'den geliyorsa ona göre dinamik yapılır)
        $documents = Document::whereNotIn('status', ['archived'])
            ->where('created_at', '<=', Carbon::now()->subYears(10))
            ->get();

        foreach ($documents as $document) {
            // Sahibine veya departman yöneticisine bildirim gönder
            // $document->creator->notify(new RetentionExpiredNotification($document));
            $this->info("Evrak süresi doldu: {$document->document_number}");
        }
    }
}
