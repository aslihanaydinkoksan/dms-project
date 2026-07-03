<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DocumentChatRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Yetki kontrolünü Controller içerisinde Gate ile yapacağız, bu yüzden buradan true dönüyoruz.
        return true; 
    }

    public function rules(): array
    {
        return [
            'document_id' => ['required', 'integer', 'exists:documents,id'],
            'message'     => ['required', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'message.required' => 'Lütfen yapay zekaya bir soru yöneltin.',
            'message.max'      => 'Sorunuz çok uzun. Lütfen en fazla 1000 karakter girin.',
        ];
    }
}