<?php

namespace App\Notifications;

use App\Models\Document;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

// E-posta gönderimi sayfa hızını yavaşlatmasın diye ShouldQueue kullanıyoruz
class ExternalDocumentSharedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected Document $document;
    protected string $token;
    protected ?string $note;

    /**
     * Sınıfın kurucu metodu (Constructor)
     */
    public function __construct(Document $document, string $token, ?string $note = null)
    {
        $this->document = $document;
        $this->token = $token;
        $this->note = $note;
    }

    /**
     * Bildirimin hangi kanallardan gideceğini belirler.
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * E-posta içeriğinin yapılandırılması.
     */
    public function toMail(object $notifiable): MailMessage
    {
        // 1. Dokümanı Yükleyen Kişi Bilgisi (Null Safe yaklaşımı ile)
        $uploaderName = $this->document->currentVersion?->createdBy?->name ?? __('Sistem / Bilinmeyen');

        // YENİ: Doküman Tipi Bilgisini Null-Safe Olarak Çekme
        $documentType = $this->document->documentType?->name ?? __('Belirtilmemiş');

        // 2. Tarih ve Saat Bilgisini Carbon ile ayrı ayrı formatlama
        $uploadDate = $this->document->created_at->format('d.m.Y');
        $uploadTime = $this->document->created_at->format('H:i');

        // 3. Ek Dosyaların (Attachments) Kontrolü
        $attachments = $this->document->attachments;
        $hasAttachments = $attachments->isNotEmpty();

        // 4. Mail Taslağını Oluşturma (Fluent API)
        $mailMessage = (new MailMessage)
            ->subject(__('Doküman Paylaşımı: ') . $this->document->title)
            ->greeting(__('Merhaba,'))
            ->line(__('Sizinle Köksan DMS sistemimiz üzerinden **:title** isimli doküman paylaşıldı.', ['title' => $this->document->title]));

        // Eğer dış paydaş için özel bir not girildiyse ekle
        if ($this->note) {
            $mailMessage->line('**' . __('Göndericinin Ek Notu:') . '** ' . $this->note);
        }

        // --- DOKÜMAN DETAYLARI BÖLÜMÜ ---
        $mailMessage->line('---')
            ->line('**📋 ' . __('Doküman Detayları') . ':**')
            ->line('- **' . __('Doküman Adı') . ':** ' . $this->document->title) // YENİ SATIR
            ->line('- **' . __('Doküman Tipi') . ':** ' . $documentType) // YENİ SATIR
            ->line('- **' . __('Yükleyen') . ':** ' . $uploaderName)
            ->line('- **' . __('Kayıt Tarihi') . ':** ' . $uploadDate)
            ->line('- **' . __('Kayıt Saati') . ':** ' . $uploadTime);

        // --- EK DOSYA BÖLÜMÜ ---
        if ($hasAttachments) {
            $mailMessage->line('- **' . __('Dokümana ait Ek Belgeler') . ' (' . $attachments->count() . ' ' . __('adet') . '):**');

            // Ekleri liste halinde yazdır
            foreach ($attachments as $attachment) {
                $sizeKb = number_format($attachment->file_size / 1024, 2);
                $mailMessage->line('  📎 ' . $attachment->original_name . ' (' . $sizeKb . ' KB)');
            }
        } else {
            $mailMessage->line('- **' . __('Ek Belgeler') . ':** ' . __('Bu dokümana ait ek dosya bulunmuyor.'));
        }

        // --- AKSİYON VE BİTİŞ ---
        $mailMessage->line('---')
            ->action(__('Dokümanı Görüntüle'), route('shared.document.show', $this->token))
            ->line(__('Bu bağlantı güvenlik nedeniyle sadece size özel üretilmiştir. Lütfen yetkisiz kişilerle paylaşmayınız.'))
            ->salutation(__('Saygılarımızla,') . "\n" . config('app.name'));

        return $mailMessage;
    }
}
