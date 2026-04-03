<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ApproveDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Service katmanında (DocumentApprovalService) kullanıcının 
        // sırası gelmiş mi diye zaten kontrol ediyoruz. Kapıyı açıyoruz:
        return true; 
    }

    public function rules(): array
    {
        return [
            // Onaylama işlemi için ekstra bir input beklemiyoruz.
        ];
    }
}