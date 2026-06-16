<?php

namespace App\Observers;

use App\Models\Task;
use App\Models\TaskLog;
use App\Models\ProcessStage;
use Illuminate\Support\Facades\Auth;


class TaskObserver
{
    /**
     * Görev her güncellendiğinde burası otomatik tetiklenir (Sürükle bırak, kapatma vb.)
     */
    public function updated(Task $task): void
    {
        $userId = Auth::id(); // Cron'da veya sistem arka planında ise null döner
        $ipAddress = request()->ip();

        // 1. KANBAN AŞAMASI (SÜTUN) DEĞİŞTİYSE
        if ($task->isDirty('current_stage_id')) {
            $oldStageId = $task->getOriginal('current_stage_id');
            $newStageId = $task->current_stage_id;

            $oldStage = $oldStageId ? ProcessStage::find($oldStageId)->name ?? 'Bilinmiyor' : 'Yok';
            $newStage = $newStageId ? ProcessStage::find($newStageId)->name ?? 'Bilinmiyor' : 'Yok';

            TaskLog::create([
                'task_id' => $task->id,
                'user_id' => $userId,
                'action' => 'stage_changed',
                'description' => "Süreç aşaması '{$oldStage}' sütunundan '{$newStage}' sütununa taşındı.",
                'old_data' => ['stage_id' => $oldStageId, 'stage_name' => $oldStage],
                'new_data' => ['stage_id' => $newStageId, 'stage_name' => $newStage],
                'ip_address' => $ipAddress
            ]);
        }

        // 2. SÜREÇ DURUMU (STATUS) DEĞİŞTİYSE (Örn: Onaya Sunuldu, Tamamlandı)
        if ($task->isDirty('status')) {
            $oldStatus = $task->getOriginal('status');
            $newStatus = $task->status;

            $statusLabels = [
                'active' => 'Devam Ediyor',
                'pending_closure_approval' => 'Kapatma Onayı Bekliyor',
                'completed' => 'Tamamlandı (Kapatıldı)'
            ];

            TaskLog::create([
                'task_id' => $task->id,
                'user_id' => $userId,
                'action' => 'status_changed',
                'description' => "Süreç durumu '{$statusLabels[$oldStatus]}' statüsünden '{$statusLabels[$newStatus]}' statüsüne geçirildi.",
                'old_data' => ['status' => $oldStatus],
                'new_data' => ['status' => $newStatus],
                'ip_address' => $ipAddress
            ]);
        }
    }
}
