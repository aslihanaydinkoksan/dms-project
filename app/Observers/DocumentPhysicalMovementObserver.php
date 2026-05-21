<?php

namespace App\Observers;

use App\Models\DocumentPhysicalMovement;
use App\Models\AuditLog;
use App\Notifications\PhysicalDocumentTransferNotification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class DocumentPhysicalMovementObserver
{
    /**
     * @var Request
     */
    protected Request $request;

    /**
     * Bağımlılık Enjeksiyonu (Dependency Injection)
     * Laravel'in IoC Container'ı Request nesnesini otomatik çözümleyerek buraya enjekte eder.
     */
    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    /**
     * Yeni bir fiziksel hareket başlatıldığında çalışır (Insert)
     */
    public function created(DocumentPhysicalMovement $movement): void
    {
        // 1. BİLDİRİM (Notification)
        if ($movement->receiver) {
            $movement->receiver->notify(new PhysicalDocumentTransferNotification($movement, 'pending'));
        }

        // 2. AUDIT LOG (İzlenebilirlik)
        $userId = Auth::check() ? Auth::id() : $movement->sender_id;
        $isSystemAutomation = str_contains($movement->comment, '[Sistem Otomasyonu]');
        $eventName = $isSystemAutomation ? 'physical_route_auto_advanced' : 'physical_transfer_initiated';

        AuditLog::create([
            'user_id' => $userId,
            'event' => $eventName,
            'auditable_type' => DocumentPhysicalMovement::class,
            'auditable_id' => $movement->id,
            'old_values' => null,
            'new_values' => [
                'document_id' => $movement->document_id,
                'receiver_id' => $movement->receiver_id,
                'status' => 'pending',
                'location' => $movement->location_details
            ],
            // CLI/Job üzerinden tetiklenirse IP ve User Agent boş döneceğinden varsayılan değerleri atıyoruz
            'ip_address' => $this->request->ip() ?? '127.0.0.1',
            'user_agent' => $this->request->userAgent() ?? 'System',
        ]);
    }

    /**
     * Bir fiziksel hareket güncellendiğinde çalışır (Update)
     */
    public function updated(DocumentPhysicalMovement $movement): void
    {
        // Sadece 'status' alanı değiştiyse tetikle (Gereksiz DB yükünü engellemek için)
        if ($movement->wasChanged('status')) {

            // 1. BİLDİRİM (Notification)
            if ($movement->sender) {
                $movement->sender->notify(new PhysicalDocumentTransferNotification($movement, $movement->status));
            }

            // 2. AUDIT LOG (İzlenebilirlik)
            $oldStatus = $movement->getOriginal('status');
            $newStatus = $movement->status;

            $eventName = $newStatus === 'accepted' ? 'physical_transfer_accepted' : 'physical_transfer_rejected';

            AuditLog::create([
                'user_id' => Auth::id() ?? $movement->receiver_id,
                'event' => $eventName,
                'auditable_type' => DocumentPhysicalMovement::class,
                'auditable_id' => $movement->id,
                'old_values' => ['status' => $oldStatus],
                'new_values' => [
                    'status' => $newStatus,
                    'comment' => $movement->comment,
                    'location' => $movement->location_details
                ],
                // CLI/Job üzerinden tetiklenirse IP ve User Agent boş döneceğinden varsayılan değerleri atıyoruz
                'ip_address' => $this->request->ip() ?? '127.0.0.1',
                'user_agent' => $this->request->userAgent() ?? 'System',
            ]);
        }
    }
}
