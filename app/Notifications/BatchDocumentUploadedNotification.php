<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use App\Models\User;

class BatchDocumentUploadedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected array $documents;
    protected User $uploader;

    public function __construct(array $documents, User $uploader)
    {
        $this->documents = $documents;
        $this->uploader = $uploader;
    }

    public function via(mixed $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(mixed $notifiable): MailMessage
    {
        $count = count($this->documents);
        $firstDoc = $this->documents[0];
        $folderName = $firstDoc->folder ? $firstDoc->folder->name : 'Ana Dizin';

        return (new MailMessage)
            ->subject('Yeni Belge Bilgilendirmesi: ' . $count . ' Adet Belge')
            ->greeting('Sayın ' . $notifiable->name . ',')
            ->line($this->uploader->name . " tarafından '{$folderName}' klasörüne size bilgi amaçlı {$count} adet yeni belge yüklenmiştir.")->action('Klasörü Görüntüle', route('folders.show', $firstDoc->folder_id))
            ->line('İyi çalışmalar.');
    }

    public function toArray(mixed $notifiable): array
    {
        $count = count($this->documents);
        $firstDoc = $this->documents[0];
        $folderName = $firstDoc->folder ? $firstDoc->folder->name : 'Ana Dizin';

        return [
            'document_id' => $firstDoc->id,
            'title' => "{$count} Adet Yeni Belge",
            'message' => "{$this->uploader->name} tarafından '{$folderName}' klasörüne {$count} adet yeni belge yüklendi.",
            'action_url' => route('folders.show', $firstDoc->folder_id),
            'type' => 'batch_document_info_alert'
        ];
    }
}
