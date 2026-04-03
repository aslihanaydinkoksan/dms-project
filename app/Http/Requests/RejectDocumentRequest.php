<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RejectDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Reddetme işleminde "comment" (Sebep) zorunludur!
            'comment' => ['required', 'string', 'min:3', 'max:1000']
        ];
    }
}
