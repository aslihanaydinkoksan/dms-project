<?php

namespace App\Notifications;

use App\Models\Document;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\HtmlString;

// 1. KURAL: Kesinlikle ShouldQueue eklenmeli (Ekranda donma olmasın)
class PhysicalDocumentAssigned extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * MİMARİ DOKUNUŞ: Tip Güvenliği (Type Safety)
     * P1132 hatasını çözmek için 'Document' tipini açıkça (explicitly) belirtiyoruz.
     */
    public Document $document;

    public function __construct(Document $document)
    {
        $this->document = $document;
    }

    /**
     * 2. KURAL: Kullanıcının tercihine göre kanal (Mail/DB) belirle
     * Not: Laravel Notification yapısına tam uyum için parametre tipi 'mixed' olarak güncellendi.
     */
    public function via(mixed $notifiable): array
    {
        // IDE'ye bu değişkenin bir User modeli olduğunu bildirerek olası hataları önlüyoruz
        /** @var \App\Models\User $notifiable */

        $prefs = $notifiable->notification_preferences ?? [];
        $channels = ['database']; // Sistem içi (Zil) her zaman aktiftir

        // Dinamik ayarları çekiyoruz (Hard-coded yasağına tam uyum)
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

    public function toMail(mixed $notifiable): MailMessage
    {
        /** @var \App\Models\User $notifiable */

        $actionUrl = route('documents.show', $this->document->id);

        // Dinamik Şablonları Çek
        $subjectTemplate = \App\Models\SystemSetting::getByKey('mail_subject_physical', 'Fiziksel Evrak Teslimatı: {document_name}');
        $bodyTemplate = \App\Models\SystemSetting::getByKey('mail_body_physical', "Sayın {user_name},\n\n{document_code} kodlu '{document_name}' isimli belgenin ıslak imzalı orijinal kopyası size zimmetlenmiştir.\n\nLütfen evrakı fiziksel olarak teslim aldığınızda sisteme girerek arşiv konumunu belirterek onaylayınız.\n\n{action_url}\n\nİyi çalışmalar.");

        // Değişkenleri Değiştir
        $search = ['{user_name}', '{document_name}', '{document_code}', '{action_url}'];
        $replace = [$notifiable->name, $this->document->title, $this->document->document_number, $actionUrl];

        $subject = str_replace($search, $replace, $subjectTemplate);
        $body = str_replace($search, $replace, $bodyTemplate);

        return (new MailMessage)
            ->subject($subject)
            ->greeting('Merhaba,')
            ->line(new HtmlString(nl2br(e($body))))
            ->action('Evrakı Teslim Al', $actionUrl);
    }

    public function toArray(mixed $notifiable): array
    {
        return [
            'document_id' => $this->document->id,
            'title' => 'Evrak Zimmeti Bekliyor',
            'message' => $this->document->title . ' isimli belgenin ıslak imzalı kopyası size zimmetlendi.',
            'icon' => '📥',
            'url' => route('documents.show', $this->document->id)
        ];
    }
}
