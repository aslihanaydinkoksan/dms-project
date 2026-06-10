<?php

namespace App\Http\Controllers;

use App\Models\ProcessStage;
use App\Models\ProcessTemplate;
use Illuminate\Http\Request;

class ProcessStageController extends Controller
{
    /**
     * Yeni bir Kanban sütunu/aşaması ekler
     */
    public function store(Request $request, ProcessTemplate $template)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'color' => 'nullable|string|max:20'
        ]);

        // Otomatik sıralama: Mevcutların en büyüğü + 1
        $maxOrder = $template->stages()->max('sort_order') ?? 0;

        $template->stages()->create([
            'name' => $validated['name'],
            'color' => $validated['color'],
            'sort_order' => $maxOrder + 1
        ]);

        return back()->with('success', 'Yeni aşama eklendi.');
    }

    /**
     * Sürükle-Bırak sonrası aşamaların sırasını topluca günceller (Fetch API ile tetiklenecek)
     */
    public function updateOrder(Request $request, ProcessTemplate $template)
    {
        $validated = $request->validate([
            'ordered_ids' => 'required|array',
            'ordered_ids.*' => 'exists:process_stages,id'
        ]);

        // Gelen ID'lerin sırasına göre DB'deki sort_order değerini güncelliyoruz
        foreach ($validated['ordered_ids'] as $index => $stageId) {
            ProcessStage::where('id', $stageId)
                ->where('process_template_id', $template->id)
                ->update(['sort_order' => $index + 1]);
        }

        return response()->json(['success' => true, 'message' => 'Sıralama güncellendi.']);
    }

    public function destroy(ProcessStage $stage)
    {
        // Eğer bu aşamada bekleyen bir görev varsa silinmesini engelleyelim
        if ($stage->tasks()->exists()) {
            return back()->with('error', 'Bu aşamada bekleyen görevler varken sütunu silemezsiniz.');
        }

        $stage->delete();
        return back()->with('success', 'Aşama başarıyla silindi.');
    }
}
