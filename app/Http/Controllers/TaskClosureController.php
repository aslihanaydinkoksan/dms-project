<?php

namespace App\Http\Controllers;

use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;


class TaskClosureController extends Controller
{
    /**
     * 1. Personelin Görevi Kapatma ve Onaya Sunma Talebi
     */
    public function requestClosure(Request $request, Task $task)
    {
        $rules = ['closure_note' => 'nullable|string|max:1000'];

        // Şablonda "Belge Zorunlu" işaretlendiyse Validasyon kurallarını dinamik olarak sıkılaştır!
        if ($task->template->requires_document_on_closure) {
            $rules['closure_document'] = 'required|file|mimes:pdf,doc,docx,jpg,jpeg,png,html|max:10240'; // Max 10 MB
        } else {
            $rules['closure_document'] = 'nullable|file|mimes:pdf,doc,docx,jpg,jpeg,png,html|max:10240';
        }

        $validated = $request->validate($rules);

        // Zararlı betikleri önleyen güvenli dosya yükleme (Public olmayan klasöre)
        $path = $task->closure_document_path;
        if ($request->hasFile('closure_document')) {
            $path = $request->file('closure_document')->store('documents/tasks', 'local');
        }

        $task->update([
            'status' => 'pending_closure_approval',
            'closure_note' => $validated['closure_note'] ?? null,
            'closure_document_path' => $path
        ]);
        $managers = $task->managers()->get(); // Task modelindeki managers() scope'unu kullanıyoruz
        if ($managers->isNotEmpty()) {
            /** @var \App\Models\User $user */
            $user = $request->user();

            \Illuminate\Support\Facades\Notification::send($managers, new \App\Notifications\TaskClosureRequested($task, $user));
        }

        return back()->with('success', '✅ Kapatma talebiniz başarıyla oluşturuldu ve yönetici onayına sunuldu.');
    }

    /**
     * 2. Proje Yöneticisinin Talebi Onaylaması (Kalıcı Kapanış)
     */
    public function approveClosure(Task $task)
    {
        $this->authorizeManager($task);

        $lastStage = $task->template->stages()->orderBy('sort_order', 'desc')->first();

        $updateData = ['status' => 'completed'];

        // Eğer bir son aşama tanımlıysa, görevi otomatik olarak oraya konumlandır
        if ($lastStage) {
            $updateData['current_stage_id'] = $lastStage->id;
        }

        $task->update($updateData);

        return back()->with('success', '🏆 Süreç onaylandı, son sütuna taşındı ve başarıyla arşive kaldırıldı.');
    }

    /**
     * 3. Proje Yöneticisinin Talebi Reddetmesi (Aktife Geri Dönüş)
     */
    public function rejectClosure(Task $task)
    {
        $this->authorizeManager($task);

        $task->update([
            'status' => 'active',
            // İsteğe bağlı: Kapanış evraklarını sıfırlayabilirsin -> 'closure_document_path' => null
        ]);

        return back()->with('success', '⛔ Kapatma talebi reddedildi. Süreç tekrar Kanban tahtasına (Aktif) alındı.');
    }

    /**
     * 4. Kanıt Niteliğindeki Evrakı Güvenli İndirme/Görüntüleme
     */
    public function downloadDocument(Task $task)
    {
        // Yetki: İşi görebilenler indirebilir (İleride Policy bağlanabilir)
        if (!$task->closure_document_path || !Storage::disk('local')->exists($task->closure_document_path)) {
            abort(404, 'Belge sunucuda bulunamadı.');
        }

        return Storage::disk('local')->download($task->closure_document_path);
    }

    /**
     * Helper: İşlemi yapan kişi bu Ad-Hoc ekibin Yöneticisi (Manager) mi?
     */
    private function authorizeManager(Task $task)
    {
        $isManager = $task->managers()->where('user_id', Auth::id())->exists();

        if (!$isManager && !Auth::user()->hasRole('Super Admin')) {
            abort(403, 'Güvenlik İhlali: Bu işi sadece projenin atanan Yöneticisi onaylayabilir veya reddedebilir.');
        }
    }
    /**
     * GÖREVİ YENİDEN AÇMA (RE-OPEN) MEKANİZMASI
     */
    public function reopenTask(Request $request, Task $task)
    {
        // 1. Zaten var olan kendi metodumuzu kullanıyoruz (DRY Prensibi)
        $this->authorizeManager($task);

        // 2. Sistemsel Mantık Kontrolü (Sadece arşivdekiler açılabilir)
        if ($task->status !== 'completed') {
            return back()->with('error', 'Sadece tamamlanmış ve arşivlenmiş görevler yeniden açılabilir.');
        }

        // 3. Durum Değişimi ve İlk Aşamaya Döndürme
        $oldStatus = $task->status;
        $firstStage = $task->template->stages()->orderBy('sort_order', 'asc')->first();

        $task->update([
            'status' => 'active',
            'current_stage_id' => $firstStage ? $firstStage->id : $task->current_stage_id,
        ]);

        // 4. Denetim İzi (Audit Log) - Yalan Söylemez Kurumsal Ayak İzi
        \App\Models\TaskLog::create([
            'task_id' => $task->id,
            'user_id' => Auth::id(), // SOLID: Global auth() yerine $request->user()->id de yazılabilir
            'action' => 'task_reopened',
            'description' => "Süreç Yönetici (<strong>" . Auth::user()->name . "</strong>) tarafından arşivden çıkarılarak yeniden aktifleştirildi.",
            'old_data' => ['status' => $oldStatus],
            'new_data' => ['status' => 'active', 'stage_id' => $task->current_stage_id],
            'ip_address' => $request->ip()
        ]);

        return back()->with('success', '🔄 Süreç başarıyla arşivden çıkarıldı ve Kanban tahtasına geri alındı.');
    }
}
