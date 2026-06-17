<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;
use App\Models\Task;
use App\Models\SystemSetting;

class TaskDeadlineWarning extends Notification implements ShouldQueue
{
    use Queueable;

    public Task $task;
    public int $daysLeft;
    public string $dateFieldLabel;

    public function __construct(Task $task, int $daysLeft, string $dateFieldLabel)
    {
        $this->task = $task;
        $this->daysLeft = $daysLeft;
        $this->dateFieldLabel = $dateFieldLabel;
    }

    /**
     * Tercih Motoru (Preference Engine): Kullanıcının ve Sistemin İzinlerini Kontrol Eder
     */
    public function via(object $notifiable): array
    {
        $channels = ['database']; // Zil (DB) bildirimi her zaman atılır

        // 1. Sistem Genel Email Ayarı Açık Mı?
        $globalNotifications = SystemSetting::getByKey('global_notifications', ['mail_enabled' => true]);

        // 2. Kullanıcının Bireysel Email Tercihi (Kapatmamışsa varsayılan olarak TRUE döner)
        $preferences = is_string($notifiable->notification_preferences)
            ? json_decode($notifiable->notification_preferences, true)
            : ($notifiable->notification_preferences ?? []);

        $userMailPref = $preferences['task_deadline_warning']['mail'] ?? true;

        // Hem sistem hem de kullanıcı email gönderimine izin veriyorsa, Mail kanalını ekle
        if (!empty($globalNotifications['mail_enabled']) && $userMailPref) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    /**
     * E-Posta Gönderimi ve Dinamik Şablon Yönetimi
     */
    public function toMail(object $notifiable): MailMessage
    {
        $urgency = $this->daysLeft === 0 ? 'BUGÜN DOLUYOR!' : "Son {$this->daysLeft} Gün!";

        // 1. Dinamik E-Posta Şablonlarını Veritabanından Çek (Yoksa varsayılanı kullan)
        $defaultSubject = "⚠️ Süreç Uyarısı: {task_title} ({urgency})";
        $defaultBody = "Sayın {user_name},\n\nDahil olduğunuz 'TASK-{task_id}' numaralı '{task_title}' sürecindeki '{date_label}' tarihinin dolmasına {urgency}.\n\nLütfen sürecin durumunu kontrol edip gerekli aksiyonları alınız.\n\nSistem Yönetimi";

        $subjectTemplate = SystemSetting::getByKey('mail_subject_task_deadline', $defaultSubject);
        $bodyTemplate = SystemSetting::getByKey('mail_body_task_deadline', $defaultBody);

        $actionUrl = route('tasks.show', $this->task->id);

        // 2. Değişkenleri (Placeholder) Gerçek Verilerle Değiştir
        $replacements = [
            '{user_name}'  => $notifiable->name,
            '{task_title}' => $this->task->title,
            '{task_id}'    => $this->task->id,
            '{date_label}' => $this->dateFieldLabel,
            '{urgency}'    => $urgency,
            '{action_url}' => $actionUrl,
        ];

        $subject = str_replace(array_keys($replacements), array_values($replacements), $subjectTemplate);
        $body = str_replace(array_keys($replacements), array_values($replacements), $bodyTemplate);

        // 3. Laravel MailMessage Objesini Oluştur
        $mailMessage = (new MailMessage)->subject($subject);

        // Şablon içeriğini satır satır böl ve ekle
        $lines = explode("\n", $body);
        foreach ($lines as $line) {
            if (trim($line) !== '') {
                $mailMessage->line($line);
            }
        }

        // En alta şık bir aksiyon butonu ekle
        $mailMessage->action('Süreci Görüntüle', $actionUrl);

        return $mailMessage;
    }

    /**
     * Çan / Zil Bildirimi (Orijinal Kod)
     */
    public function toDatabase(object $notifiable): array
    {
        $urgency = $this->daysLeft === 0 ? 'BUGÜN DOLUYOR!' : "Son {$this->daysLeft} Gün!";

        return [
            'title' => "⚠️ Süre Daralıyor: {$urgency}",
            'message' => "TASK-{$this->task->id} numaralı '{$this->task->title}' görevinin '{$this->dateFieldLabel}' tarihi yaklaştı.",
            'icon' => 'calendar-clock',
            'url' => route('tasks.show', $this->task->id)
        ];
    }
}
