<?php

namespace App\Observers;

use App\Models\DocumentPhysicalMovement;
use App\Notifications\PhysicalDocumentTransferNotification;

class DocumentPhysicalMovementObserver
{
    public function created(DocumentPhysicalMovement $movement)
    {
        // Zimmet başlatıldığında ALICIYA bildirim at
        if ($movement->receiver) {
            $movement->receiver->notify(new PhysicalDocumentTransferNotification($movement, 'pending'));
        }
    }

    public function updated(DocumentPhysicalMovement $movement)
    {
        // Eğer statü değiştiyse GÖNDERİCİYE bildirim at (Kabul edildi veya Reddedildi)
        if ($movement->wasChanged('status') && $movement->sender) {
            $movement->sender->notify(new PhysicalDocumentTransferNotification($movement, $movement->status));
        }
    }
}
