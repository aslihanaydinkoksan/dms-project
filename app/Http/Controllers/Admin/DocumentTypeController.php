<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\DocumentType;
use App\Models\Department;
use Illuminate\Support\Str;
use Illuminate\Http\RedirectResponse;

class DocumentTypeController extends Controller
{
    /**
     * Doküman tipleri listesini ve ilişkili departmanları getirir.
     */
    public function index()
    {
        $documentTypes = DocumentType::with('department')->orderBy('name')->get();
        $departments = Department::orderBy('name')->get();

        return view('admin.document-types.index', compact('documentTypes', 'departments'));
    }

    /**
     * Yeni doküman tipi ve dinamik form şablonunu kaydeder.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:document_types,name',
            'department_id' => 'nullable|integer|exists:departments,id',
            'custom_fields' => 'nullable|array',
        ]);

        $fields = $this->processCustomFields($request->input('custom_fields'));

        DocumentType::create([
            'name' => $validated['name'],
            'department_id' => $validated['department_id'] ?? null,
            'custom_fields' => empty($fields) ? null : $fields,
            'requires_expiration_date' => $request->boolean('requires_expiration_date'),
            'is_form_based' => $request->boolean('is_form_based')
        ]);

        return back()->with('success', '📄 Yeni doküman tipi ve form şablonu başarıyla oluşturuldu.');
    }

    /**
     * Mevcut doküman tipini günceller.
     */
    public function update(Request $request, DocumentType $documentType): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:document_types,name,' . $documentType->id,
            'department_id' => 'nullable|integer|exists:departments,id',
            'custom_fields' => 'nullable|array',
        ]);

        $fields = $this->processCustomFields($request->input('custom_fields'));

        $documentType->update([
            'name' => $validated['name'],
            'department_id' => $validated['department_id'] ?? null,
            'custom_fields' => empty($fields) ? null : $fields,
            'requires_expiration_date' => $request->boolean('requires_expiration_date'),
            'is_form_based' => $request->boolean('is_form_based')
        ]);

        return back()->with('success', 'Doküman tipi ve form alanları başarıyla güncellendi.');
    }

    /**
     * Doküman tipini siler (İlişkili yetkiler Observer ile otomatik temizlenecek).
     */
    public function destroy(DocumentType $documentType): RedirectResponse
    {
        $documentType->delete();
        return back()->with('success', 'Doküman tipi ve buna bağlı tüm sistem yetkileri kalıcı olarak silindi.');
    }

    /**
     * Arayüzden gelen karmaşık dinamik form verisini temizler ve JSON'a hazırlar.
     */
    private function processCustomFields(?array $customFields): array
    {
        $fields = [];
        if ($customFields) {
            foreach ($customFields as $field) {
                if (!empty($field['label']) && !empty($field['name'])) {
                    $fields[] = [
                        'label' => $field['label'],
                        'name' => Str::slug($field['name'], '_'),
                        'type' => $field['type'] ?? 'text',
                        'placeholder' => $field['placeholder'] ?? '',
                        'required' => isset($field['required']) ? true : false,
                    ];
                }
            }
        }
        return $fields;
    }
}
