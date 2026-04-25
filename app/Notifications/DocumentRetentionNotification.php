<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue; // Kuyruk mimarisi için kritik!
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\Document;
use Illuminate\Support\HtmlString;

class DocumentRetentionNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * PHP 8 Constructor Property Promotion (Clean Code)
     */
    public function __construct(
        public Document $document,
        public int $daysLeft
    ) {}

    /**
     * Bildirimin gönderileceği kanallar.
     */
    public function via(object $notifiable): array
    {
        $prefs = $notifiable->notification_preferences ?? [];
        $channels = ['database']; // Sistem içi (Zil) her zaman aktiftir

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

    /**
     * E-posta şablonu ve içeriği.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $actionUrl = route('documents.show', $this->document->id);

        // Dinamik Şablonları Çek (Eğer yoksa default olanı kullan)
        $defaultSubject = $this->daysLeft === 0
            ? '⚠️ DİKKAT: {document_name} Belgesinin Yasal Saklama Süresi Doldu!'
            : 'ℹ️ BİLGİ: {document_name} Belgesinin Saklama Süresi Dolmak Üzere ({remaining_days} Gün Kaldı)';

        $defaultBody = "Sayın {user_name},\n\nSorumlusu olduğunuz '{document_name}' isimli belgenin sistemdeki yasal saklama/arşiv süresi ";
        $defaultBody .= $this->daysLeft === 0 ? "bugün itibarıyla **dolmuştur**." : "**{remaining_days} gün sonra** dolacaktır.";
        $defaultBody .= "\n\nLütfen kurum politikaları gereği belgenin imha veya arşivden kaldırma prosedürlerini uygulayınız.\n\n{action_url}\n\nSistem Yönetimi";

        $subjectTemplate = \App\Models\SystemSetting::getByKey('mail_subject_retention', $defaultSubject);
        $bodyTemplate = \App\Models\SystemSetting::getByKey('mail_body_retention', $defaultBody);

        // Değişkenleri Değiştir
        $search = ['{user_name}', '{document_name}', '{remaining_days}', '{action_url}'];
        $replace = [$notifiable->name, $this->document->title, $this->daysLeft, $actionUrl];

        $subject = str_replace($search, $replace, $subjectTemplate);
        $body = str_replace($search, $replace, $bodyTemplate);

        return (new MailMessage)
            ->subject($subject)
            ->greeting('Merhaba,')
            ->line(new HtmlString(nl2br($body))) // e() fonksiyonunu kaldırdık ki ** (bold) markdown veya HTML tagleri çalışabilsin
            ->action('Belgeyi İncele', $actionUrl);
    }

    /**
     * Veritabanı (Çan İkonu / Dropdown) içeriği.
     */
    public function toArray(object $notifiable): array
    {
        $statusText = $this->daysLeft === 0 ? "tamamen doldu." : "dolmasına {$this->daysLeft} gün kaldı.";

        return [
            'document_id' => $this->document->id,
            'title' => 'Arşiv Süresi Alarmı',
            'message' => "'{$this->document->title}' başlıklı belgenin yasal saklama süresinin {$statusText}",
            'icon' => 'archive', // Lucide icon ismi (Çan menüsünde güzel görünür)
            'url' => route('documents.show', $this->document->id)
        ];
    }
}
