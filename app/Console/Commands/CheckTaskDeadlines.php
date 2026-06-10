<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Task;
use App\Notifications\TaskDeadlineWarning;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;

class CheckTaskDeadlines extends Command
{
    protected $signature = 'bpm:check-deadlines';
    protected $description = 'BPM sistemindeki aktif görevlerin dinamik tarih alanlarını kontrol eder ve yaklaşanları ekibe bildirir.';

    public function handle()
    {
        $this->info('Dinamik tarih kontrolleri başlatılıyor...');

        // Sadece devam eden (active ve onaya sunulmuş) görevleri ve ekiplerini çek
        $tasks = Task::with('template', 'users')->whereIn('status', ['active', 'pending_closure_approval'])->get();

        $notificationCount = 0;

        foreach ($tasks as $task) {
            /** @var Task $task */
            $fields = $task->template->fields ?? [];
            
            // İLERİYE DÖNÜK ESNEKLİK: 3 günü hard-coded yazmak yerine, şablonda JSON içine 
            // "warning_days" adında gizli bir ayar eklenebilir. Yoksa default 3 gün kabul et.
            $warningDays = $task->template->warning_days ?? 3;

            foreach ($fields as $field) {
                // Şablondaki alan tipi 'date' ise ve formda doldurulmuşsa
                if (($field['type'] ?? '') === 'date' && !empty($task->custom_data[$field['name']])) {
                    
                    $deadlineStr = $task->custom_data[$field['name']];
                    
                    try {
                        $deadline = Carbon::parse($deadlineStr)->startOfDay();
                        $now = Carbon::today();
                        
                        // Tarih geçmediyse ve uyarı eşiğine (Örn: 3 gün) girdiyse
                        $diffInDays = $now->diffInDays($deadline, false);
                        
                        if ($diffInDays >= 0 && $diffInDays <= $warningDays) {
                            // Görevin Ad-Hoc Ekibindeki herkese bildirimi fırlat!
                            Notification::send($task->users, new TaskDeadlineWarning($task, (int)$diffInDays, $field['label']));
                            $notificationCount++;
                        }
                    } catch (\Exception $e) {
                        // Yanlış tarih formatı girilmişse atla
                        continue;
                    }
                }
            }
        }

        $this->info("Kontrol tamamlandı. Toplam {$notificationCount} bildirim fırlatıldı.");
    }
}