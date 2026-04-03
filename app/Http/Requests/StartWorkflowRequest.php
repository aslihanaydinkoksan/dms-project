<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StartWorkflowRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Sadece belge oluşturma veya yönetme yetkisi olanlar akış başlatabilir.
        return true;
    }

    public function rules(): array
    {
        return [
            // approvers dizisi zorunludur ve en az 1 kişi olmalıdır.
            'approvers' => ['required', 'array', 'min:1'],
            'approvers.*.user_id' => ['required', 'integer', 'exists:users,id'],
            'approvers.*.step_order' => ['required', 'integer', 'min:1'],
        ];
    }
    
    public function messages(): array
    {
        return [
            'approvers.required' => 'Onay akışını başlatmak için en az bir onaycı seçmelisiniz.',
            'approvers.*.user_id.exists' => 'Seçilen onaycılardan biri sistemde bulunamadı.',
        ];
    }
}
