<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue; // Kuyruk mimarisi için kritik!
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\Document;
use Illuminate\Support\HtmlString;

class DocumentExpiringNotification extends Notification implements ShouldQueue
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

    /**
     * E-posta şablonu ve içeriği.
     */
   public function toMail(object $notifiable): MailMessage
    {
        $actionUrl = route('documents.show', $this->document->id);
        
        // Dinamik Şablonları Çek
        $subjectTemplate = \App\Models\SystemSetting::getByKey('mail_subject_expiration', '⚠️ UYARI: {document_name} Belgesinin Süresi Dolmak Üzere ({remaining_days} Gün Kaldı)');
        $bodyTemplate = \App\Models\SystemSetting::getByKey('mail_body_expiration', "Sayın {user_name},\n\nSorumlusu olduğunuz '{document_name}' isimli belgenin geçerlilik süresinin dolmasına {remaining_days} gün kalmıştır.\n\nLütfen belgenin güncelliğini kontrol ediniz ve gerekiyorsa yeni bir revizyon başlatınız.\n\n{action_url}\n\nSistem Yönetimi");

        // Değişkenleri Değiştir
        $search = ['{user_name}', '{document_name}', '{remaining_days}', '{action_url}'];
        $replace = [$notifiable->name, $this->document->title, $this->daysLeft, $actionUrl];

        $subject = str_replace($search, $replace, $subjectTemplate);
        $body = str_replace($search, $replace, $bodyTemplate);

        return (new MailMessage)
            ->subject($subject)
            ->greeting('Merhaba,')
            ->line(new HtmlString(nl2br(e($body))))
            ->action('Belgeyi Görüntüle', $actionUrl);
    }

    /**
     * Veritabanı (Çan İkonu / Dropdown) içeriği.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'document_id' => $this->document->id,
            'title' => 'Süre Sonu Yaklaşıyor',
            'message' => "'{$this->document->title}' başlıklı belgenin geçerlilik süresinin dolmasına {$this->daysLeft} gün kaldı.",
            'icon' => '⏰',
            'url' => route('documents.show', $this->document->id)
        ];
    }
}
