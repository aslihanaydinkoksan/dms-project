<?php

namespace App\Notifications;

use App\Models\Document;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\HtmlString;

class WorkflowActionRequired extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Document $document,
        public string $actionType, // 'pending_your_approval', 'approved', 'rejected'
        public ?string $comment = null // Red durumu için zorunlu sebep
    ) {}

    /**
     * Tercih Motoru Kontrolü
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
     * E-Posta Şablonu
     */
    public function toMail(object $notifiable): MailMessage
    {
        $actionUrl = route('documents.show', $this->document->id);

        if ($this->actionType === 'pending_your_approval') {
            // SADECE ONAY BEKLEYENLERİ DİNAMİK YAPTIK
            $subjectTemplate = \App\Models\SystemSetting::getByKey('mail_subject_workflow', 'Eylem Gerekiyor: {document_name} Onayınızı Bekliyor');
            $bodyTemplate = \App\Models\SystemSetting::getByKey('mail_body_workflow', "Sayın {user_name},\n\n{document_code} kodlu '{document_name}' isimli belge iş akışı kapsamında onayınızı beklemektedir.\n\nBelgeyi incelemek ve kararınızı iletmek için aşağıdaki bağlantıya tıklayabilirsiniz:\n{action_url}\n\nİyi çalışmalar dileriz.");

            $search = ['{user_name}', '{document_name}', '{document_code}', '{action_url}'];
            $replace = [$notifiable->name, $this->document->title, $this->document->document_number, $actionUrl];

            $subject = str_replace($search, $replace, $subjectTemplate);
            $body = str_replace($search, $replace, $bodyTemplate);

            return (new MailMessage)
                ->subject($subject)
                ->greeting('Merhaba,')
                ->line(new HtmlString(nl2br(e($body))))
                ->action('Belgeyi İncele ve Onayla', $actionUrl);
        } elseif ($this->actionType === 'approved') {
            return (new MailMessage)
                ->subject("DMS İş Akışı: {$this->document->document_number}")
                ->greeting("Tebrikler {$notifiable->name},")
                ->line("Yüklediğiniz '**{$this->document->title}**' başlıklı belge tüm onaycılardan geçmiş ve yayına alınmıştır.")
                ->action('Belgeyi Görüntüle', $actionUrl);
        } elseif ($this->actionType === 'rejected') {
            return (new MailMessage)
                ->subject("DMS İş Akışı: {$this->document->document_number}")
                ->greeting("Merhaba {$notifiable->name},")
                ->line("Yüklediğiniz '**{$this->document->title}**' başlıklı belge maalesef REDDEDİLMİŞTİR.")
                ->line("**Red Sebebi:** {$this->comment}")
                ->action('Taslağı Düzenle', $actionUrl)
                ->error();
        }
        return (new MailMessage)
            ->subject("DMS Sistem Bildirimi: {$this->document->document_number}")
            ->greeting("Merhaba {$notifiable->name},")
            ->line("Sahibi olduğunuz '**{$this->document->title}**' başlıklı belge üzerinde bir işlem yapıldı.")
            ->action('Sisteme Git ve İncele', $actionUrl);
    }

    /**
     * Sistem İçi (Zil İkonu) Şablonu
     */
    public function toArray(object $notifiable): array
    {
        $title = match ($this->actionType) {
            'pending_your_approval' => "İşlem Bekleyen Belge",
            'approved' => "Belge Onaylandı",
            'rejected' => "Belge Reddedildi",
            default => "İş Akışı Bildirimi",
        };

        // MİMARİ DOKUNUŞ: Artık veritabanına değişkenlerle çevrilmiş statik metni değil,
        // Blade üzerinde anlık çevrilecek şablonları (key) gönderiyoruz.
        $messageKey = match ($this->actionType) {
            'pending_your_approval' => ":doc_title onayınızı bekliyor.",
            'approved' => ":doc_title tüm onaylardan geçti.",
            'rejected' => ":doc_title reddedildi. Sebep: :comment",
            default => "İşlem yapıldı: :doc_title",
        };

        $icon = match ($this->actionType) {
            'pending_your_approval' => "⚡",
            'approved' => "✅",
            'rejected' => "❌",
            default => "🔄",
        };

        return [
            'document_id' => $this->document->id,
            'title' => $title, // Başlıklar sabit metin olduğu için Blade'de direkt __() ile çevrilebilir.
            'message_key' => $messageKey, // Çeviri motoru için anahtar
            'message_params' => [         // Çeviri motoru için dinamik değişkenler
                'doc_title' => $this->document->title,
                'comment' => $this->comment ?? '',
            ],
            // Eski bildirimler hata vermesin diye statik metni yedek olarak tutmaya devam ediyoruz (Geriye Dönük Uyumluluk)
            'message' => match ($this->actionType) {
                'pending_your_approval' => "{$this->document->title} onayınızı bekliyor.",
                'approved' => "{$this->document->title} tüm onaylardan geçti.",
                'rejected' => "{$this->document->title} reddedildi. Sebep: {$this->comment}",
                default => "İşlem yapıldı: {$this->document->title}",
            },
            'icon' => $icon,
            'url' => route('documents.show', $this->document->id)
        ];
    }
}
