<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\ProcessTemplate;

class StoreTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Yetki Policy veya Controller'da kontrol edilecek
    }

    public function rules(): array
    {
        // 1. Sabit Kurallar (Görev başlığı, şablon ID ve Ekip Üyeleri)
        $rules = [
            'process_template_id' => 'required|exists:process_templates,id',
            'title'               => 'required|string|max:255',
            'team_members'        => 'nullable|array',
            'team_members.*.user_id' => 'required_with:team_members|exists:users,id',
            'team_members.*.role' => 'required_with:team_members|in:manager,member',
            'custom_data'         => 'nullable|array',
        ];

        // 2. DİNAMİK KURALLAR (No-Code Form Validation)
        $templateId = $this->input('process_template_id');

        if ($templateId) {
            $template = ProcessTemplate::find($templateId);

            if ($template && is_array($template->fields)) {
                foreach ($template->fields as $field) {
                    $key = 'custom_data.' . $field['name']; // Örn: custom_data.arac_plakasi
                    $fieldRules = [];

                    // Zorunluluk Kontrolü
                    if (isset($field['required']) && $field['required']) {
                        $fieldRules[] = 'required';
                    } else {
                        $fieldRules[] = 'nullable';
                    }

                    // Veri Tipi Kontrolü (Text, Number, Date vs.)
                    if ($field['type'] === 'number') {
                        $fieldRules[] = 'numeric';
                    } elseif ($field['type'] === 'date') {
                        $fieldRules[] = 'date';
                    } else {
                        $fieldRules[] = 'string';
                    }

                    $rules[$key] = implode('|', $fieldRules);
                }
            }
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'custom_data.*.required' => 'Bu dinamik alanın doldurulması zorunludur.',
            'custom_data.*.numeric'  => 'Bu alana sadece rakam girilebilir.',
        ];
    }
}
