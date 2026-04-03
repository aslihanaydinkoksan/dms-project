<?php

namespace App\Notifications;

use App\Models\UserDelegation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue; // Ekranda donmayı engelleyen sihir!
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Carbon\Carbon;

class NewDelegationAssigned extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * @var UserDelegation
     */
    public $delegation;

    /**
     * Constructor Injection
     */
    public function __construct(UserDelegation $delegation)
    {
        $this->delegation = $delegation;
    }

    /**
     * Bildirimin gönderileceği kanallar.
     */
    public function via(object $notifiable): array
    {
        // Sistem içi zil ikonu (database) ve E-posta (mail)
        return ['database', 'mail'];
    }

    /**
     * KÖKSAN Kurumsal E-posta Şablonu
     */
    public function toMail(object $notifiable): MailMessage
    {
        // Tarihleri Türkçe okunaklı formata çeviriyoruz
        $delegatorName = $this->delegation->delegator->name;
        $startDate = Carbon::parse($this->delegation->start_date)->format('d.m.Y H:i');
        $endDate = Carbon::parse($this->delegation->end_date)->format('d.m.Y H:i');
        $reason = $this->delegation->reason ?? 'Belirtilmedi';

        return (new MailMessage)
            ->subject("KÖKSAN DYS - Yeni Vekalet Ataması: {$delegatorName}")
            ->greeting("Merhaba {$notifiable->name},")
            ->line("Sistem üzerinden **{$delegatorName}** tarafından size yeni bir vekalet (delegation) tanımlanmıştır.")
            ->line("**Başlangıç:** {$startDate}")
            ->line("**Bitiş:** {$endDate}")
            ->line("**Sebep / Açıklama:** {$reason}")
            ->line("Bu tarihler arasında, vekalet verenin onayında bekleyen tüm iş akışlarına ve belgelere kendi profiliniz (Dashboard) üzerinden erişebilir ve vekaleten işlem yapabilirsiniz.")
            ->action('Vekaletlerimi Görüntüle', route('profile.delegations'))
            ->line('İyi çalışmalar dileriz.');
    }

    /**
     * Sistem İçi (Zil İkonu) Bildirim İçeriği
     */
    public function toArray(object $notifiable): array
    {
        $delegatorName = $this->delegation->delegator->name;
        $startDate = Carbon::parse($this->delegation->start_date)->format('d.m.Y');
        $endDate = Carbon::parse($this->delegation->end_date)->format('d.m.Y');

        return [
            'delegation_id' => $this->delegation->id,
            'title' => 'Yeni Vekalet Ataması',
            'message' => "📝 {$delegatorName} size {$startDate} - {$endDate} tarihleri arasında vekalet vermiştir.",
            'icon' => '🤝',
            'url' => route('profile.delegations')
        ];
    }
}
