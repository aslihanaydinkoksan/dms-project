<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue; // Kuyruk mimarisi (Performans için kritik)
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\Document;
use App\Models\DocumentExternalShare;
use Illuminate\Support\HtmlString;

class ExternalDocumentExpiringNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * PHP 8 Constructor Property Promotion
     */
    public function __construct(
        public Document $document,
        public int $daysLeft,
        public DocumentExternalShare $share // Dış paydaşın token'ını almak için eklendi
    ) {}

    /**
     * Dış paydaşların sistemde bir User modeli (Çan ikonu vs.) olmadığı için SADECE mail kanalı döner.
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Dış paydaşa gidecek olan e-posta şablonu ve içeriği.
     */
    public function toMail(object $notifiable): MailMessage
    {
        // KRİTİK GÜVENLİK: İç sistem (documents.show) YERİNE, dış paydaşın kendine özel token'ı ile link oluşturuyoruz.
        $actionUrl = route('shared.document.show', $this->share->token);

        // Dinamik Şablonları Çek (Yoksa varsayılan kurumsal metni kullan)
        $subjectTemplate = \App\Models\SystemSetting::getByKey('mail_subject_external_expiration', '⚠️ BİLGİLENDİRME: {document_name} Belgesinin Süresi Dolmak Üzere ({remaining_days} Gün Kaldı)');

        $bodyTemplate = \App\Models\SystemSetting::getByKey('mail_body_external_expiration', "Sayın İlgili,\n\nDaha önce sizinle paylaşılmış olan '{document_name}' isimli belgenin geçerlilik süresinin dolmasına {remaining_days} gün kalmıştır.\n\nBelgeye olan erişiminiz belge süresinin bitimiyle sonlanabileceği için, ilgili dokümanı aşağıdaki bağlantıdan inceleyebilirsiniz.\n\n{action_url}\n\nKÖKSAN Sistem Yönetimi");

        // Değişkenleri Değiştir
        $search = ['{document_name}', '{remaining_days}', '{action_url}'];
        $replace = [$this->document->title, $this->daysLeft, $actionUrl];

        $subject = str_replace($search, $replace, $subjectTemplate);
        $body = str_replace($search, $replace, $bodyTemplate);

        return (new MailMessage)
            ->subject($subject)
            ->greeting('Merhaba,')
            ->line(new HtmlString(nl2br(e($body))))
            ->action('Belgeyi Görüntüle', $actionUrl);
    }
}
