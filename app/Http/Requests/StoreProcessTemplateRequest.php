<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class StoreProcessTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Yetki kontrolünü Controller/Policy'de yapacağımız için burayı true bırakıyoruz.
        return true;
    }

    protected function prepareForValidation()
    {
        // HTML checkbox'lar işaretlenmediğinde veri göndermez. 
        // Bunu %100 boolean formata zorluyoruz.
        $this->merge([
            'requires_document_on_closure' => $this->boolean('requires_document_on_closure'),
        ]);
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'department_id' => 'required|exists:departments,id',
            'requires_document_on_closure' => 'boolean',
            'mandatory_user_group_id' => 'nullable|exists:user_groups,id',
            'allow_ad_hoc_members' => 'nullable|boolean',

            // JSON (No-Code Form) Validasyonu: Array gelmeli ve içindeki her elemanın asgari alanları olmalı
            'fields' => 'nullable|array',
            'fields.*.name' => 'required_with:fields|string|max:100', // Sütun key'i (Örn: plaka_no)
            'fields.*.label' => 'required_with:fields|string|max:255', // Görünen ad (Örn: Araç Plakası)
            'fields.*.type' => 'required_with:fields|string|in:text,number,date,textarea,select',
            'fields.*.required' => 'nullable|boolean',
        ];
    }
}
