<?php

namespace App\Listeners;

use Illuminate\Notifications\Events\NotificationSent;
use App\Models\TaskLog;

class NotificationSentListener
{
    public function handle(NotificationSent $event): void
    {
        $notification = $event->notification;
        $notifiable = $event->notifiable; // Bildirimin gittiği kişi (User)

        // Eğer fırlatılan bildirim bizim BPM bildirimlerimizden biriyse
        if (
            $notification instanceof \App\Notifications\TaskDeadlineWarning ||
            $notification instanceof \App\Notifications\TaskClosureRequested
        ) {

            TaskLog::create([
                'task_id' => $notification->task->id,
                'user_id' => null, // Sistem tarafından gönderildi
                'action' => 'notification_sent',
                'description' => "Sistem tarafından {$notifiable->name} (E-Posta: {$notifiable->email}) adlı kullanıcıya uyarı/onay bildirimi başarıyla iletildi. (Kanal: {$event->channel})",
                'ip_address' => '127.0.0.1'
            ]);
        }
    }
}
