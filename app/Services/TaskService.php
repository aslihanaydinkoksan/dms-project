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

            //Zorunlu Kullanıcı Grubu Kontrolü: Eğer şablonda atanmış bir zorunlu grup varsa, o grubun üyelerini de ekibe dahil et (Grup kuralları override eder)
            if ($template->mandatory_user_group_id) {
                // Şablona atanmış zorunlu grubu ve üyelerini çek
                $mandatoryGroup = $template->mandatoryGroup()->with('members')->first();

                if ($mandatoryGroup && $mandatoryGroup->is_active) {
                    foreach ($mandatoryGroup->members as $mandatoryMember) {
                        // Eğer kullanıcı arayüzden bu kişiyi 'member' seçtiyse ama grupta 'manager' ise,
                        // Grup kuralı ezer (override). Sistem güvenliği sağlanır.
                        $syncData[$mandatoryMember->id] = [
                            'role' => $mandatoryMember->pivot->role
                        ];
                    }
                }
            }
            // Pivot tabloya yaz (task_user)
            $task->users()->sync($syncData);

            // Gerekirse ileride buraya Mail/Bildirim tetikleyicileri (Events/Observers) eklenecek

            return $task;
        });
    }
}
