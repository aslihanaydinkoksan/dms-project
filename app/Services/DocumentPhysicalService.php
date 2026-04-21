<?php

namespace App\Services;

use App\Models\Document;
use App\Models\DocumentPhysicalMovement;
use Illuminate\Support\Facades\DB;
use Exception;

class DocumentPhysicalService
{
    /**
     * Yeni bir fiziksel evrak zimmeti başlatır.
     */
    public function initiateTransfer(Document $document, int $senderId, int $receiverId, ?string $location, string $comment): DocumentPhysicalMovement
    {
        if ($document->physical_receipt_status === 'pending') {
            throw new Exception('Bu evrak için zaten bekleyen bir devir işlemi var.');
        }

        return DB::transaction(function () use ($document, $senderId, $receiverId, $location, $comment) {
            $movement = DocumentPhysicalMovement::create([
                'document_id' => $document->id,
                'sender_id' => $senderId,
                'receiver_id' => $receiverId,
                'status' => 'pending',
                'location_details' => $location,
                'comment' => $comment,
                'action_at' => now(),
            ]);

            // Ana belgeyi kilitle ve beklemeye al
            $document->update([
                'physical_receipt_status' => 'pending',
                'delivered_to_user_id' => $receiverId
            ]);

            return $movement;
        });
    }
    /**
     * Çoklu Posta Rotası (Routing Slip) Başlatır
     */
    public function startRoutingSlip(Document $document, int $senderId, array $receiverIds, ?string $location, string $comment): void
    {
        // 1. Pusulayı Evraka Kaydet (Örn: [2, 5, 8] ve şu an 0. indeksteyiz)
        $document->update([
            'physical_route' => [
                'path' => $receiverIds,
                'current_step' => 0
            ]
        ]);

        // 2. Rotanın İLK adımını başlat (Senden -> Listedeki ilk kişiye)
        $this->initiateTransfer($document, $senderId, $receiverIds[0], $location, $comment);
    }

    /**
     * Evrağı teslim alan kişi onaylar.
     */
    public function acceptTransfer(DocumentPhysicalMovement $movement, string $comment, ?string $location): void
    {
        DB::transaction(function () use ($movement, $comment, $location) {
            $movement->update([
                'status' => 'accepted',
                'comment' => $movement->comment . "\n[Kabul Notu]: " . $comment,
                'action_at' => now(),
            ]);

            $document = $movement->document;
            $document->update([
                'physical_receipt_status' => 'received',
                'physical_location' => $location ?? $movement->location_details
            ]);

            // === POSTA ROTASI (ROUTING SLIP) OTOMASYONU ===
            $route = $document->physical_route;

            if ($route && isset($route['path'])) {
                $nextStep = $route['current_step'] + 1; // Sıradaki kişiye geç

                if (isset($route['path'][$nextStep])) {
                    $nextReceiverId = $route['path'][$nextStep];

                    // Rotayı bir adım ilerlet
                    $route['current_step'] = $nextStep;
                    $document->update(['physical_route' => $route]);

                    // Otomatik olarak sıradaki kişiye devri başlat!
                    $this->initiateTransfer(
                        $document,
                        $movement->receiver_id, // Artık gönderici, evrakı teslim alan bu kişi
                        $nextReceiverId,
                        $location,
                        "📦 [Sistem Otomasyonu]: Posta Rotası (Routing Slip) gereği evrak otomatik olarak size devredildi."
                    );
                } else {
                    // Rota başarıyla tamamlandıysa pusulayı temizle
                    $document->update(['physical_route' => null]);
                }
            }
        });
    }

    /**
     * Evrağı teslim almayı reddeder.
     */
    public function rejectTransfer(DocumentPhysicalMovement $movement, string $comment): void
    {
        DB::transaction(function () use ($movement, $comment) {
            $movement->update([
                'status' => 'rejected',
                'comment' => $movement->comment . "\n[Ret Nedeni]: " . $comment,
                'action_at' => now(),
            ]);

            $movement->document->update([
                'physical_receipt_status' => null,
                'delivered_to_user_id' => $movement->sender_id,
                'physical_route' => null // Biri reddederse tüm zincir (rota) iptal olur!
            ]);
        });
    }
}
