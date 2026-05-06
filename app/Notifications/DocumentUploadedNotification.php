<?php

namespace App\Notifications;

use App\Models\Document;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class DocumentUploadedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected Document $document;
    protected User $uploader;

    public function __construct(Document $document, User $uploader)
    {
        $this->document = $document;
        $this->uploader = $uploader;
    }

    public function via(mixed $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(mixed $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Yeni Belge Bilgilendirmesi: ' . $this->document->document_number)
            ->greeting('Sayın ' . $notifiable->name . ',')
            ->line($this->uploader->name . " tarafından '{$this->document->title}' isimli yeni bir belge yüklendi ve bilgilerinize sunuldu.")
            ->action('Belgeyi İncele', route('documents.show', $this->document->id))
            ->line('İyi çalışmalar.');
    }

    public function toArray(mixed $notifiable): array
    {
        return [
            'document_id' => $this->document->id,
            'title' => $this->document->title,
            'message' => "{$this->uploader->name} tarafından '{$this->document->title}' başlıklı yeni belge yüklendi.",
            'action_url' => route('documents.show', $this->document->id),
            'type' => 'document_info_alert'
        ];
    }
}
