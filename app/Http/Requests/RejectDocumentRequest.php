<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\DocumentApproval;
use Illuminate\Support\Facades\Auth;

class RejectDocumentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $document = $this->route('document');
        $user = Auth::user();

        // 1. Genişletilmiş Kimlik (Vekalet Kalkanı)
        $allIdsToCheck = array_merge([$user->id], $user->getActiveDelegatorIds());

        // 2. Bu belgede, bu kullanıcılardan birinin "bekleyen" bir onayı var mı?
        return DocumentApproval::where('document_id', $document->id)
            ->whereIn('user_id', $allIdsToCheck)
            ->where('status', 'pending')
            ->exists();
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'comment' => 'required|string|min:5|max:1000' // Reddetme sebebi zorunludur
        ];
    }

    public function messages(): array
    {
        return [
            'comment.required' => 'Lütfen belgeyi neden reddettiğinize dair bir açıklama yazın.',
            'comment.min' => 'Açıklama çok kısa, lütfen en az 5 karakter girin.',
        ];
    }
}
