<?php

namespace App\Observers;

use App\Models\Document;
use App\Models\Folder;
use Illuminate\Support\Facades\Request;
use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;


class DocumentObserver
{
    /**
     * Belge veritabanına KAYDEDİLMEDEN HEMEN ÖNCE çalışır.
     * Klasörden Departman Kalıtımını (Inheritance) Pivot tablodan çekerek zorunlu kılar.
     */
    public function saving(Document $document): void
    {
        if ($document->folder_id) {
            // Klasörü, pivot tablosundaki departmanlarıyla birlikte çek
            $folder = Folder::with('departments')->find($document->folder_id);

            // Klasöre atanmış en az 1 departman varsa, ilkini alıp belgenin meta verisine kalıtımla aktar
            if ($folder && $folder->departments->isNotEmpty()) {
                $firstDepartment = $folder->departments->first();
                $document->related_department_id = $firstDepartment->id;
            } else {
                // Eğer "Departmansız Ana Klasör" ise belge de departmansız (Tüm şirkete açık) kalsın
                $document->related_department_id = null;
            }
        }
    }
    /**
     * Handle the Document "created" event.
     */
    public function created(Document $document): void
    {
        //
    }

    /**
     * Belge güncellendiğinde tetiklenir (Field-Level Tracking)
     */
    public function updated(Document $document): void
    {
        // Belgede gerçekten bir alan değişti mi?
        if ($document->isDirty()) {

            // Yeni ve eski değerleri yakala
            $changed = $document->getDirty();
            $original = array_intersect_key($document->getOriginal(), $changed);

            // 'updated_at' gibi sistemin otomatik güncellediği, log kalabalığı yapacak alanları çıkarıyoruz
            unset($changed['updated_at']);
            unset($original['updated_at']);

            // Eğer updated_at dışında gerçekten değişen bir veri varsa logla
            if (count($changed) > 0) {
                AuditLog::create([
                    'auditable_type' => Document::class,
                    'auditable_id'   => $document->id,
                    'user_id' => Auth::id() ?? null,
                    'event' => 'document_updated',
                    'ip_address' => request()->ip() ?? '127.0.0.1',
                    'old_values' => json_encode($original, JSON_UNESCAPED_UNICODE),
                    'new_values' => json_encode($changed, JSON_UNESCAPED_UNICODE),
                ]);
            }
        }
    }

    /**
     * Handle the Document "deleted" event.
     */
    public function deleted(Document $document): void
    {
        //
    }

    /**
     * Handle the Document "restored" event.
     */
    public function restored(Document $document): void
    {
        //
    }

    /**
     * Handle the Document "force deleted" event.
     */
    public function forceDeleted(Document $document): void
    {
        //
    }
}
