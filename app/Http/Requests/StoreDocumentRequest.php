<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('document.create');
    }

    public function rules(): array
    {
        return [
            // --- 1. TEMEL BELGE BİLGİLERİ (KATI ZIRH EKLENDİ) ---
            'file' => [
                'required',
                'file',
                'mimes:pdf,doc,docx,jpg,jpeg,png,html',
                'mimetypes:application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document,image/jpeg,image/png,text/html',
                'max:20480'
            ],
            'title' => ['required', 'string', 'max:255'],
           // 'document_number' => ['required', 'string', 'max:100', 'unique:documents,document_number'],
            'folder_id' => ['required', 'exists:folders,id'],
            'privacy_level' => ['required', 'string', 'in:public,confidential,strictly_confidential'],
            'expire_at' => ['nullable', 'date'],

            // --- 2. KURUMSAL METADATA VE İMHA POLİTİKASI ---
            'document_type_id' => 'required|exists:document_types,id',
            'related_department_id' => 'nullable|integer|exists:departments,id',
            'system_article_no' => ['nullable', 'string', 'max:255'],
            'department_retention_years' => ['nullable', 'integer', 'min:0'],
            'archive_retention_years' => ['nullable', 'integer', 'min:0'],

            // --- 3. ETİKETLER ---
            'tags' => ['nullable', 'array'],

            // --- 4. DİNAMİK ONAY AKIŞI (WORKFLOW) ---
            'approvers' => ['nullable', 'array'],
            'approvers.*.user_id' => ['required_with:approvers', 'exists:users,id'],
            'approvers.*.step_order' => ['required_with:approvers', 'integer', 'min:1'],
            'metadata' => 'nullable|array', // Dinamik alanları kabul et
        ];
    }

    public function messages(): array
    {
        return [
            'document_number.unique' => 'Bu Evrak Kayıt No sistemde zaten mevcut. Lütfen değiştirin.',
            'file.max' => 'Yükleyeceğiniz dosya boyutu 20MB sınırını aşamaz.',
            'file.mimes' => 'Sisteme sadece PDF, Word, JPG, PNG ve HTML formatında belgeler yüklenebilir.',
            'file.mimetypes' => 'Dosya uzantısı değiştirilmiş zararlı bir içerik tespit edildi. İşlem reddedildi.',
            'document_type.required' => 'Lütfen belgenin tipini (Prosedür, Talimat vb.) seçiniz.',
            'department_retention_years.min' => 'Bölümde saklama süresi 0\'dan küçük olamaz.',
            'archive_retention_years.min' => 'Arşivde saklama süresi 0\'dan küçük olamaz.',
        ];
    }
}
