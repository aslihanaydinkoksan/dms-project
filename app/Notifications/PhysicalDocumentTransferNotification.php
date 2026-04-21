<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\DocumentPhysicalMovement;

class PhysicalDocumentTransferNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public DocumentPhysicalMovement $movement;
    public string $status;

    /**
     * Observer'dan gelen verileri (2 parametreyi) burada karşılıyoruz.
     */
    public function __construct(DocumentPhysicalMovement $movement, string $status)
    {
        $this->movement = $movement;
        $this->status = $status;
    }

    /**
     * Bildirimin hangi kanallardan gönderileceğini seçiyoruz (Örn: Sadece sistem içi bildirim veya Mail)
     */
    public function via(object $notifiable): array
    {
        // Eğer mail de gitmesini istersen ['database', 'mail'] yapabilirsin
        return ['database'];
    }

    /**
     * Eğer Mail gönderilecekse, içeriği nasıl olacak?
     */
    public function toMail(object $notifiable): MailMessage
    {
        $docNumber = $this->movement->document->document_number;
        $senderName = $this->movement->sender->name;

        $subject = 'Fiziksel Evrak İşlemi: ' . $docNumber;
        $message = '';

        if ($this->status === 'pending') {
            $message = "{$senderName} size fiziksel bir evrak teslim etmek istiyor.";
        } elseif ($this->status === 'accepted') {
            $message = "Gönderdiğiniz {$docNumber} numaralı fiziksel evrak teslim alındı.";
        } elseif ($this->status === 'rejected') {
            $message = "Gönderdiğiniz {$docNumber} numaralı fiziksel evrak REDDEDİLDİ ve size iade edildi.";
        }

        return (new MailMessage)
            ->subject($subject)
            ->greeting('Merhaba ' . $notifiable->name . ',')
            ->line($message)
            ->line('Açıklama/Not: ' . $this->movement->comment)
            ->action('Evrak Detayına Git', route('documents.show', $this->movement->document_id))
            ->line('Sistemimizi kullandığınız için teşekkürler.');
    }

    /**
     * Veritabanına (Çan ikonuna) kaydedilecek bildirim içeriği
     */
    public function toArray(object $notifiable): array
    {
        $docNumber = $this->movement->document->document_number;
        $senderName = $this->movement->sender->name;
        $title = '';
        $message = '';

        if ($this->status === 'pending') {
            $title = 'Yeni Evrak Teslimatı';
            $message = "{$senderName} size fiziksel bir evrak teslim etmek istiyor.";
        } elseif ($this->status === 'accepted') {
            $title = 'Evrak Teslim Alındı';
            $message = "{$docNumber} numaralı fiziksel evrakınız teslim alındı.";
        } elseif ($this->status === 'rejected') {
            $title = 'Evrak Reddedildi';
            $message = "{$docNumber} numaralı fiziksel evrakınız reddedildi.";
        }

        return [
            'document_id' => $this->movement->document_id,
            'title' => $title,
            'message' => $message,
            'icon' => $this->status === 'accepted' ? 'check-circle' : ($this->status === 'rejected' ? 'x-circle' : 'inbox'),
            'type' => 'physical_movement',
            'url' => route('documents.show', $this->movement->document_id)
        ];
    }
}
