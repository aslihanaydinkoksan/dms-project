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

    public $document;

    public function __construct(Document $document)
    {
        $this->document = $document;
    }

    /**
     * 2. KURAL: Kullanıcının tercihine göre kanal (Mail/DB) belirle
     */
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

    public function toArray(object $notifiable): array
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
