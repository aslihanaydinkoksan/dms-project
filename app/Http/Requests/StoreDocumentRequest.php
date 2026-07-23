<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\DocumentType;

class StoreDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', \App\Models\Document::class);
    }

    public function rules(): array
    {
        $rules = [
            // =========================================================
            // 1. GLOBAL BELGE BİLGİLERİ (Tüm seçili dosyalar için ortak)
            // =========================================================
            'folder_id' => ['required', 'exists:folders,id'],
            'privacy_level' => ['required', 'string', 'in:public,confidential,strictly_confidential'],

            // --- KURUMSAL METADATA VE İMHA POLİTİKASI ---
            'related_department_id' => ['nullable', 'integer', 'exists:departments,id'],
            'system_article_no' => ['nullable', 'string', 'max:255'],

            // --- ETİKETLER ---
            'tags' => ['nullable', 'array'],

            // --- DİNAMİK ONAY AKIŞI (WORKFLOW) VE BİLDİRİMLER ---
            'approvers' => ['nullable', 'array'],
            'approvers.*.user_id' => ['required_with:approvers', 'exists:users,id'],
            'approvers.*.step_order' => ['required_with:approvers', 'integer', 'min:1'],
            'notified_user_ids' => ['nullable', 'array'],

            // --- SMART FORM / GEÇİCİ KONTROL BAYRAĞI ---
            'is_form_based_submission' => ['nullable', 'boolean'],

            // =========================================================
            // 2. ÇOKLU DOSYA (BATCH UPLOAD) KONTROLLERİ
            // =========================================================
            // Akıllı form tabanlı gönderimlerde (is_form_based_submission = true) fiziksel dosya zorunluluğu kalkar
            'files' => ['required_unless:is_form_based_submission,true', 'array', 'min:1'],
            'files.*' => [
                'required_unless:is_form_based_submission,true',
                'file',
                'mimes:pdf,doc,docx,jpg,jpeg,png,html',
                'mimetypes:application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document,image/jpeg,image/png,text/html',
                'max:20480'
            ],

            // Eski 'title', 'document_type_id', 'expire_at', 'metadata' 
            // alanları her bir dosya için özel olduğundan array içine alındı
            'documents' => ['required', 'array'],
            'documents.*.title' => ['required', 'string', 'max:255'],
            'documents.*.document_type_id' => ['required', 'exists:document_types,id'],
            'documents.*.is_indefinite' => ['nullable'], // Checkbox değeri
            'documents.*.validity_description' => ['nullable', 'string', 'max:1000'], //Güvenlik için 1000 karakter sınırı var
            // KORUNAN KRİTİK İŞ MANTIĞI: Belge tipine göre dinamik tarih kontrolü yapan fonksiyon
            'documents.*.expire_at' => [
                'nullable', // Checkbox seçiliyse null gelebilir
                'date',
                function ($attribute, $value, $fail) {
                    // $attribute = 'documents.0.expire_at' şeklinde gelir. İndeksi yakalıyoruz:
                    $index = explode('.', $attribute)[1];

                    $docTypeId = $this->input("documents.{$index}.document_type_id");
                    $isIndefinite = $this->boolean("documents.{$index}.is_indefinite");

                    if ($docTypeId && !$isIndefinite && empty($value)) {
                        $docType = DocumentType::find($docTypeId);
                        // Eğer belge tipi tarih gerektiriyorsa ve Süresiz seçilmemişse, tarih boş olamaz
                        if ($docType && $docType->requires_expiration_date) {
                            $fail('Belge tipi süreli olduğu için geçerlilik tarihi girmeli VEYA "Süresiz" seçeneğini işaretlemelisiniz.');
                        }
                    }
                }
            ],
            'documents.*.metadata' => ['nullable', 'array'],
        ];

        // =========================================================
        // 3. SMART FORMS / DİNAMİK METADATA VALİDASYON MOTORU
        // =========================================================
        if ($this->has('documents') && is_array($this->input('documents'))) {
            foreach ($this->input('documents') as $index => $doc) {
                $typeId = $doc['document_type_id'] ?? null;
                if (!$typeId) continue;

                // İlgili doküman tipinin custom_fields şablon mimarisini çekiyoruz
                $documentType = DocumentType::find($typeId);

                // Eğer döküman tipi form tabanlıysa ve içinde tanımlanmış dinamik alanlar varsa kuralları enjekte et
                if ($documentType && $documentType->is_form_based && !empty($documentType->custom_fields)) {
                    $fields = is_string($documentType->custom_fields)
                        ? json_decode($documentType->custom_fields, true)
                        : $documentType->custom_fields;

                    foreach ($fields as $field) {
                        $fieldName = $field['name'] ?? null;
                        if (!$fieldName) continue;

                        $dynamicRules = [];

                        // Alanın zorunluluk (Required) kontrolü şablondan okunur
                        if (!empty($field['required'])) {
                            $dynamicRules[] = 'required';
                        } else {
                            $dynamicRules[] = 'nullable';
                        }

                        // Veri Tipi Eşleştirmesi (Data Type Strategy Mapping)
                        switch ($field['type'] ?? 'text') {
                            case 'number':
                            case 'integer':
                                $dynamicRules[] = 'numeric';
                                break;
                            case 'date':
                                $dynamicRules[] = 'date';
                                break;
                            case 'boolean':
                                $dynamicRules[] = 'boolean';
                                break;
                            default:
                                $dynamicRules[] = 'string';
                                $dynamicRules[] = 'max:65000';
                        }

                        // Noktasal array dot-notation hedeflemesi ile kural anahtarı oluşturulur:
                        // Örn: rules['documents.0.metadata.vardiya'] = ['required', 'string']
                        $rules["documents.{$index}.metadata.{$fieldName}"] = $dynamicRules;
                    }
                }
            }
        }

        // =========================================================
        // 1. GLOBAL BELGE BİLGİLERİ (Eklenen Yeni Alanlar)
        // =========================================================
        $rules['external_emails'] = ['nullable', 'array'];
        $rules['external_emails.*'] = ['required_with:external_emails', 'email'];
        $rules['external_note'] = ['nullable', 'string', 'max:1000'];
        $rules['external_expires_at'] = ['nullable', 'date', 'after:now'];

        return $rules;
    }

    public function messages(): array
    {
        return [
            // Eski mesajların korundu, array yapısına uyarlandı
            'document_number.unique' => 'Bu Evrak Kayıt No sistemde zaten mevcut. Lütfen değiştirin.',
            'files.required' => 'Lütfen sisteme yüklenecek en az bir dosya seçin.',
            'files.*.max' => 'Yükleyeceğiniz dosya boyutu 20MB sınırını aşamaz.',
            'files.*.mimes' => 'Sisteme sadece PDF, Word, JPG, PNG ve HTML formatında belgeler yüklenebilir.',
            'files.*.mimetypes' => 'Dosya uzantısı değiştirilmiş zararlı bir içerik tespit edildi. İşlem reddedildi.',

            'documents.*.document_type_id.required' => 'Lütfen eklediğiniz tüm belgelerin tipini (Prosedür, Talimat vb.) seçiniz.',
            'documents.*.title.required' => 'Eklediğiniz tüm belgeler için bir başlık girmek zorunludur.',

            // 'department_retention_years.min' => 'Bölümde saklama süresi 0\'dan küçük olamaz.',
            // 'archive_retention_years.min' => 'Arşivde saklama süresi 0\'dan küçük olamaz.',
        ];
    }
}
