<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use App\Models\Task;

class TaskDeadlineWarning extends Notification
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

    public function via(object $notifiable): array
    {
        return ['database']; // KÖKSAN bildirim merkezine (veritabanı) gönderiyoruz
    }

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
