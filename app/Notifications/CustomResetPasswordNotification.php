<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CustomResetPasswordNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Şifre sıfırlama token'ı
     * DİKKAT: P1132 hatasını çözmek için 'string' tip belirtimi eklendi.
     */
    public string $token;

    /**
     * Bağımlılık Enjeksiyonu
     */
    public function __construct(string $token)
    {
        $this->token = $token;
    }

    /**
     * Bildirimin hangi kanallardan gönderileceğini belirler.
     * DİKKAT: Laravel standartlarına uygun olarak 'mixed' tipi eklendi.
     */
    public function via(mixed $notifiable): array
    {
        return ['mail'];
    }

    /**
     * E-posta şablonunu ve içeriğini oluşturur.
     */
    public function toMail(mixed $notifiable): MailMessage
    {
        // =========================================================
        // MİMARİ DOKUNUŞ: TİP KORUMASI (Type Guard / DocBlock)
        // =========================================================
        // IDE'ye $notifiable nesnesinin bir User modeli olduğunu bildiriyoruz.
        // Bu sayede ->name ve ->getEmailForPasswordReset() metotlarında hata vermeyecek.
        /** @var \App\Models\User $notifiable */

        // Şifre sıfırlama linkini dinamik olarak oluşturuyoruz
        $url = url(route('password.reset', [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ], false));

        // Enterprise Mimari Notu: 
        // Şimdilik metinler Türkçe olarak burada durabilir ancak ileride
        // LanguageController ile entegre çoklu dil desteği (Localization) 
        // sağlamak istersek bu metinleri __('dms.reset_password_subject') 
        // şeklinde dil dosyalarından (lang/tr/...) çekecek şekilde kurgulamalıyız.
        
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