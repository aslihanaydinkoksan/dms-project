<?php

namespace App\Services;

use App\Models\Task;
use App\Models\ProcessTemplate;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class TaskService
{
    /**
     * Dinamik verilerle yeni bir iş (Task) yaratır ve Ad-Hoc ekibi kurar.
     */
    public function createTask(array $validatedData, User $creator): Task
    {
        return DB::transaction(function () use ($validatedData, $creator) {

            $template = ProcessTemplate::findOrFail($validatedData['process_template_id']);

            // a) Kanban'ın İLK sütununu (aşamasını) bul (Eğer aşama varsa)
            $firstStage = $template->stages()->orderBy('sort_order', 'asc')->first();

            // b) İşin oluşturulması (custom_data otomatik JSON'a çevrilir)
            $task = Task::create([
                'process_template_id' => $template->id,
                'current_stage_id'    => $firstStage ? $firstStage->id : null,
                'creator_id'          => $creator->id,
                'title'               => $validatedData['title'],
                'custom_data'         => $validatedData['custom_data'] ?? [],
                'status'              => 'active',
            ]);

            // c) Ad-Hoc Proje Ekibini Senkronize Et (Pivot Tablo)
            $syncData = [];

            if (!empty($validatedData['team_members'])) {
                foreach ($validatedData['team_members'] as $member) {
                    $syncData[$member['user_id']] = ['role' => $member['role']];
                }
            }

            // Kurucu (Creator) otomatik olarak 'manager' rolüyle ekibe dahil edilir (Opsiyonel ama kurumsal mantıkta faydalıdır)
            if (!isset($syncData[$creator->id])) {
                $syncData[$creator->id] = ['role' => 'manager'];
            }

            // Pivot tabloya yaz (task_user)
            $task->users()->sync($syncData);

            // Gerekirse ileride buraya Mail/Bildirim tetikleyicileri (Events/Observers) eklenecek

            return $task;
        });
    }
}
