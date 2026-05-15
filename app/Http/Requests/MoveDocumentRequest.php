<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class MoveDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::check();
    }

    public function rules(): array
    {
        return [
            // Hedef klasör zorunludur ve veritabanında var olmalıdır.
            'target_folder_id' => 'required|integer|exists:folders,id',
        ];
    }

    public function messages(): array
    {
        return [
            'target_folder_id.required' => 'Hedef klasör seçimi zorunludur.',
            'target_folder_id.exists' => 'Belirtilen hedef klasör sistemde bulunamadı.',
        ];
    }
}
