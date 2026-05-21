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
    /**
     * Fiziksel evrak devri başlatma iş mantığını (Business Logic) yönetir
     */
    public function handleInitiation(Document $document, int $senderId, array $data): string
    {
        if ($data['action'] !== 'initiate') {
            throw new Exception('Geçersiz işlem türü.');
        }

        $receivers = $data['receiver_ids'];

        // İş Kuralı: Seçilen kişi sayısı 1'den fazlaysa Rota (Routing Slip) başlat
        if (count($receivers) > 1) {
            $this->startRoutingSlip($document, $senderId, $receivers, $data['location_details'] ?? null, $data['comment'] ?? '');
            return 'Sıralı Posta Rotası (Routing Slip) başarıyla başlatıldı. Evrak ilk sıradaki kişiye yönlendirildi.';
        }

        // İş Kuralı: Tek kişi seçildiyse normal devir başlat
        $this->initiateTransfer($document, $senderId, $receivers[0], $data['location_details'] ?? null, $data['comment'] ?? '');
        return 'Fiziksel evrak devri başlatıldı. Karşı tarafın onayı bekleniyor.';
    }

    /**
     * Gelen fiziksel evrak yanıtını işler
     */
    public function handleResponse(DocumentPhysicalMovement $movement, array $data): string
    {
        if ($data['action'] === 'accept') {
            $this->acceptTransfer($movement, $data['comment'] ?? '', $data['location_details'] ?? null);
            return 'Evrak başarıyla teslim alındı.';
        } 
        
        if ($data['action'] === 'reject') {
            $this->rejectTransfer($movement, $data['comment'] ?? '');
            return 'Evrak teslimi reddedildi ve göndericiye iade edildi.';
        }

        throw new Exception('Bilinmeyen yanıt türü.');
    }
}
