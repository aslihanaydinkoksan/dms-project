<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue; // Arka planda kuyruk (queue) desteği için
use Illuminate\Queue\SerializesModels;
use Illuminate\Notifications\Messages\MailMessage;
use App\Models\Task;
use App\Models\User;

class TaskClosureRequested extends Notification implements ShouldQueue
{
    use Queueable, SerializesModels;

    public Task $task;
    public User $requester;

    public function __construct(Task $task, User $requester)
    {
        $this->task = $task;
        $this->requester = $requester;
    }

    /**
     * Bildirimin hangi kanallarla gideceğini belirliyoruz.
     * Artık hem veritabanına (zil) hem de e-postaya (mail) aynı anda gidecek!
     */
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    /**
     * KÖKSAN Veritabanı (Zil) Bildirim Formatı
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'title' => '🛑 Kapanış Onayı Bekliyor',
            'message' => "{$this->requester->name}, TASK-{$this->task->id} numaralı '{$this->task->title}' görevini tamamladı ve onayınızı bekliyor.",
            'icon' => 'info',
            'url' => route('tasks.index') . '?view=kanban'
        ];
    }

    /**
     * KÖKSAN Kurumsal E-Posta Bildirim Formatı (YENİ EKLENDİ)
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("KÖKSAN BPM - Kapanış Onayı Talebi: TASK-{$this->task->id}")
            ->greeting("Merhaba " . $notifiable->name . ",")
            ->line("Sorumlusu veya yöneticisi olduğunuz bir iş akışında yeni bir onay talebi bulunmaktadır.")
            ->line("**Görevi Kapatmak İsteyen:** " . $this->requester->name)
            ->line("**Görev Numarası:** TASK-" . str_pad($this->task->id, 4, '0', STR_PAD_LEFT))
            ->line("**Görev Başlığı:** " . $this->task->title)
            ->line("**Personel Kapanış Notu:** " . ($this->task->closure_note ?? 'Açıklama belirtilmedi.'))
            ->action('Görevi İncele & Onayla', route('tasks.index') . '?view=kanban')
            ->line('Eğer süreç tasarımında "Evrak Zorunluluğu" tanımlanmışsa, yukarıdaki butona tıklayarak personelin yüklediği kanıt belgesini de inceleyebilirsiniz.')
            ->salutation('KÖKSAN Süreç Yönetim Sistemi (BPM)');
    }
}
