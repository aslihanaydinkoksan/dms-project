<?php

namespace App\Http\Controllers;

use App\Models\ProcessTemplate;
use App\Models\Department;
use App\Http\Requests\StoreProcessTemplateRequest;
use App\Http\Requests\UpdateProcessTemplateRequest;
use Illuminate\Http\Request;

class ProcessTemplateController extends Controller
{
    /**
     * Şablonların listelendiği ana ekran
     */
    public function index()
    {
        // N+1 önlemi ile departmanları eager load ediyoruz.
        $templates = ProcessTemplate::with('department')->latest()->get();
        return view('process-templates.index', compact('templates'));
    }

    /**
     * Yeni şablon oluşturma formu (No-Code Form Builder)
     */
    public function create()
    {
        $departments = Department::orderBy('name')->get();
        return view('process-templates.create', compact('departments'));
    }

    /**
     * Şablonu veritabanına kaydetme işlemi
     */
    public function store(StoreProcessTemplateRequest $request)
    {
        // JSON cast işlemi Model'de ayarlandığı için doğrudan kaydediyoruz!
        ProcessTemplate::create($request->validated());

        return redirect()->route('process-templates.index')
            ->with('success', 'Süreç Şablonu başarıyla oluşturuldu.');
    }

    /**
     * Şablon ve Aşama (Stage) düzenleme ekranı
     */
    public function edit(ProcessTemplate $processTemplate)
    {
        $departments = Department::orderBy('name')->get();
        return view('process-templates.edit', compact('processTemplate', 'departments'));
    }

    /**
     * Şablon bilgilerini güncelleme işlemi
     */
    public function update(UpdateProcessTemplateRequest $request, ProcessTemplate $processTemplate)
    {
        $processTemplate->update($request->validated());

        return redirect()->route('process-templates.index')
            ->with('success', 'Süreç Şablonu başarıyla güncellendi.');
    }

    /**
     * Şablonu sistemden silme işlemi
     */
    public function destroy(ProcessTemplate $processTemplate)
    {
        try {
            $processTemplate->delete();
            return redirect()->route('process-templates.index')
                ->with('success', 'Süreç Şablonu silindi.');
        } catch (\Illuminate\Database\QueryException $e) {
            // Faz 1'deki "restrict" kuralı sayesinde, devam eden işi olan şablon silinmek istendiğinde bu exception fırlatılır.
            return back()->with('error', '⛔ Bu süreç şablonu silinemez! Çünkü bu şablon kullanılarak oluşturulmuş aktif veya geçmiş görevler/işler bulunuyor.');
        }
    }
}
