<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProcessTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Yetki kontrolünü Controller/Policy'de yapacağımız için burayı true bırakıyoruz.
        return true;
    }

    protected function prepareForValidation()
    {
        // 1. Dinamik Form Alanlarını (Fields) Yakala
        $fields = $this->input('fields', []);

        if (is_array($fields)) {
            foreach ($fields as $key => $field) {

                // Gelen 'required' checkbox'ını boolean'a zorla
                $fields[$key]['required'] = isset($field['required']) ? filter_var($field['required'], FILTER_VALIDATE_BOOLEAN) : false;

                // 2. AÇILIR MENÜ (SELECT) İŞLEME MANTIĞI
                if (isset($field['type']) && $field['type'] === 'select' && !empty($field['options_raw'])) {
                    // Virgülle ayır, boşlukları temizle (trim) ve boş değerleri filtrele
                    $optionsArray = array_filter(array_map('trim', explode(',', $field['options_raw'])));

                    // JSON'a kaydedilecek asıl dizi
                    $fields[$key]['options'] = array_values($optionsArray);

                    // Ham (string) veriyi yollamamıza gerek yok, temizleyelim
                    unset($fields[$key]['options_raw']);
                }
            }
        }

        // 3. HTML checkbox'larını ve güncellenmiş fields dizisini request'e ezerek (merge) yaz
        $this->merge([
            'requires_document_on_closure' => $this->boolean('requires_document_on_closure'),
            'allow_ad_hoc_members'         => $this->boolean('allow_ad_hoc_members'), // Sıkı mod checkbox garantisi
            'fields'                       => $fields,
        ]);
    }

    public function rules(): array
    {
        return [
            'name'                         => 'required|string|max:255',
            'department_id'                => 'required|exists:departments,id',
            'requires_document_on_closure' => 'boolean',
            'mandatory_user_group_id'      => 'nullable|exists:user_groups,id',
            'allow_ad_hoc_members'         => 'boolean',

            // JSON (No-Code Form) Validasyonu
            'fields'              => 'nullable|array',
            'fields.*.name'       => 'required_with:fields|string|max:100',
            'fields.*.label'      => 'required_with:fields|string|max:255',
            'fields.*.type'       => 'required_with:fields|string|in:text,number,date,textarea,select',
            'fields.*.required'   => 'boolean',

            // YENİ: Dönüştürdüğümüz Select options dizisi için doğrulama
            'fields.*.options'    => 'nullable|array',
            'fields.*.options.*'  => 'string|max:255',
        ];
    }
}
