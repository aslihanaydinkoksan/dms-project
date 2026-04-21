<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CustomResetPasswordNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public $token;

    public function __construct($token)
    {
        $this->token = $token;
    }

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        // Şifre sıfırlama linkini dinamik olarak oluşturuyoruz
        $url = url(route('password.reset', [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ], false));

        return (new MailMessage)
            ->subject('KÖKSAN DMS - Şifre Sıfırlama Talebi')
            ->greeting("Merhaba {$notifiable->name},")
            ->line('Kurumsal Doküman Yönetim Sistemi (DMS) hesabınız için bir şifre sıfırlama talebi aldık.')
            ->line('Aşağıdaki butona tıklayarak güvenli bir şekilde yeni şifrenizi belirleyebilirsiniz.')
            ->action('Yeni Şifre Belirle', $url)
            ->line('Bu şifre sıfırlama bağlantısının süresi 60 dakika içinde dolacaktır.')
            ->line('Eğer bu talebi siz yapmadıysanız, herhangi bir işlem yapmanıza gerek yoktur. Hesabınız güvendedir.')
            ->salutation('İyi çalışmalar dileriz, KÖKSAN Opex Departmanı');
    }
}
