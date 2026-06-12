<?php

namespace App\Services;

use App\Models\Task;
use App\Models\ProcessTemplate;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;


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

            $syncData = [];

            // c) ANTI-BYPASS KALKANI: Esnek mod varsa ekibi al, yoksa imha et!
            if ($template->allow_ad_hoc_members) {
                if (!empty($validatedData['team_members'])) {
                    foreach ($validatedData['team_members'] as $member) {
                        $syncData[$member['user_id']] = ['role' => $member['role']];
                    }
                }
            } else {
                // Şablon SIKI MODDA! Gelen tüm Ad-Hoc verilerini güvenlik nedeniyle çöpe at.
                unset($validatedData['team_members']);
            }

            // d) Kurucu (Creator) otomatik olarak 'manager' rolüyle ekibe dahil edilir
            if (!isset($syncData[$creator->id])) {
                $syncData[$creator->id] = ['role' => 'manager'];
            }

            // e) ZORUNLU GRUP KONTROLÜ: Varsa sisteme zorla dahil et (Ad-Hoc'u ezer)
            if ($template->mandatory_user_group_id) {
                $mandatoryGroup = $template->mandatoryGroup()->with('members')->first();

                if ($mandatoryGroup && $mandatoryGroup->is_active) {
                    foreach ($mandatoryGroup->members as $mandatoryMember) {
                        $syncData[$mandatoryMember->id] = [
                            'role' => $mandatoryMember->pivot->role
                        ];
                    }
                }
            }

            // f) Tüm süzgeçlerden geçen temiz ve güvenli listeyi Pivot tabloya (task_user) yaz
            $task->users()->sync($syncData);

            return $task;
        });
    }
    /**
     * Mevcut bir işi (Task) dinamik veriler ve Ad-Hoc ekiplerle günceller.
     */
    public function updateTask(Task $task, array $validatedData): Task
    {
        return DB::transaction(function () use ($task, $validatedData) {

            // 1. Temel ve Dinamik Verileri Güncelle
            $task->update([
                'title'       => $validatedData['title'],
                'custom_data' => $validatedData['custom_data'] ?? [],
            ]);

            $syncData = [];

            // 2. ANTI-BYPASS KALKANI: Esnek mod varsa ekibi al, yoksa gelen veriyi reddet
            if ($task->template->allow_ad_hoc_members) {
                if (!empty($validatedData['team_members'])) {
                    foreach ($validatedData['team_members'] as $member) {
                        $syncData[$member['user_id']] = ['role' => $member['role']];
                    }
                }
            }

            // 3. Kurucuyu (Creator) yönetici olarak koru (Dışarıdan silinmesini engelle)
            if (!isset($syncData[$task->creator_id])) {
                $syncData[$task->creator_id] = ['role' => 'manager'];
            }

            // 4. ZORUNLU GRUP KONTROLÜ: Varsa sisteme zorla dahil et (Formdan gelenleri ezer)
            if ($task->template->mandatory_user_group_id) {
                $mandatoryGroup = $task->template->mandatoryGroup()->with('members')->first();

                if ($mandatoryGroup && $mandatoryGroup->is_active) {
                    foreach ($mandatoryGroup->members as $mandatoryMember) {
                        $syncData[$mandatoryMember->id] = [
                            'role' => $mandatoryMember->pivot->role
                        ];
                    }
                }
            }

            // 5. Temizlenmiş ve güvenli listeyi veritabanına yaz
            $task->users()->sync($syncData);
            \App\Models\TaskLog::create([
                'task_id' => $task->id,
                'user_id' => Auth::id(),
                'action' => 'task_updated',
                'description' => "Süreç form verileri veya proje ekibi güncellendi.",
                // Değişen verileri JSON olarak veritabanına gömüyoruz (İleride kim neyi değiştirmiş görmek için)
                'new_data' => [
                    'title' => $validatedData['title'],
                    'custom_data' => $validatedData['custom_data'] ?? [],
                    'team_members' => $syncData // Son haline karar verilmiş ekip listesi
                ],
                'ip_address' => request()->ip()
            ]);

            return $task;
        });
    }
}
