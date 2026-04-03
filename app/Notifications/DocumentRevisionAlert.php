<?php

namespace App\Notifications;

use App\Models\Document;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\HtmlString;

class DocumentRevisionAlert extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Document $document,
        public string $actionType, // 'checked_out' (Kilitlendi), 'checked_in' (Yeni Versiyon Yüklendi)
        public string $actorName // İşlemi Yapan Kişi
    ) {}

    public function via(object $notifiable): array
    {
        $prefs = $notifiable->notification_preferences ?? [];
        $channels = ['database']; // Sistem içi (Zil) her zaman aktiftir

        // SENİN YAZDIĞIN HARİKA HELPER METODU KULLANIYORUZ!
        $globalSettings = \App\Models\SystemSetting::getByKey('global_notifications', []);

        // 1. Sistem şalteri AÇIK MI? (Yoksa varsayılan olarak açık kabul et)
        $isGlobalMailEnabled = $globalSettings['mail_enabled'] ?? true;

        // 2. Kullanıcının Bireysel Tercihi AÇIK MI? (Yoksa varsayılan açık kabul et)
        $isUserMailEnabled = $prefs['workflow_action']['mail'] ?? true;

        // İki şart da sağlanıyorsa e-posta kuyruğuna (Mail kanalına) ekle
        if ($isGlobalMailEnabled && $isUserMailEnabled) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        $actionUrl = route('documents.show', $this->document->id);

        // İşlem tipine göre doğru şablonları veritabanından çek (Yoksa varsayılanı kullan)
        if ($this->actionType === 'checked_out') {
            $subjectTemplate = \App\Models\SystemSetting::getByKey('mail_subject_checked_out', '🔒 Belge Kilitlendi: {document_name}');
            $bodyTemplate = \App\Models\SystemSetting::getByKey('mail_body_checked_out', "Sahibi olduğunuz {document_code} kodlu belge üzerinde bir işlem yapıldı.\n\n{actor_name} isimli kullanıcı bu belgeyi revize etmek üzere KİLİTLEDİ. İşlem bitene kadar belge salt-okunurdur.\n\n{action_url}\n\nSistem güvenliği için tarafınıza bilgi verilmiştir.");
        } else {
            $subjectTemplate = \App\Models\SystemSetting::getByKey('mail_subject_checked_in', '⬆️ Yeni Versiyon Yüklendi: {document_name}');
            $bodyTemplate = \App\Models\SystemSetting::getByKey('mail_body_checked_in', "Sahibi olduğunuz {document_code} kodlu belge üzerinde bir işlem yapıldı.\n\n{actor_name} isimli kullanıcı belgeye YENİ BİR VERSİYON yükledi ve kilidi kaldırdı.\n\n{action_url}\n\nSistem güvenliği için tarafınıza bilgi verilmiştir.");
        }

        // 3. Değişkenleri Gerçek Verilerle Değiştir
        $search = ['{user_name}', '{document_name}', '{document_code}', '{actor_name}', '{action_url}'];
        $replace = [
            $notifiable->name,
            $this->document->title,
            $this->document->document_number,
            $this->actorName,
            $actionUrl
        ];

        $subject = str_replace($search, $replace, $subjectTemplate);
        $body = str_replace($search, $replace, $bodyTemplate);

        return (new MailMessage)
            ->subject($subject)
            ->greeting("Merhaba {$notifiable->name},")
            ->line(new HtmlString(nl2br(e($body))))
            ->action('Belgeyi İncele', $actionUrl);
    }

    public function toArray(object $notifiable): array
    {
        $isCheckout = $this->actionType === 'checked_out';

        return [
            'document_id' => $this->document->id,
            'title' => $isCheckout ? 'Belge Kilitlendi' : 'Yeni Versiyon Yüklendi',
            'message' => $isCheckout
                ? "{$this->actorName}, belgenizi revize etmek için kilitledi."
                : "{$this->actorName}, belgenize yeni bir versiyon yükledi.",
            'icon' => $isCheckout ? '🔒' : '⬆️',
            'url' => route('documents.show', $this->document->id)
        ];
    }
}
