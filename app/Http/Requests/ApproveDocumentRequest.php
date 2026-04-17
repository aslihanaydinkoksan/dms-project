<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\DocumentApproval;
use Illuminate\Support\Facades\Auth;

class ApproveDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $document = $this->route('document');
        $user = Auth::user();

        // 1. Genişletilmiş Kimlik: Kendi ID'm + Vekili olduğum kişilerin ID'leri
        $allIdsToCheck = array_merge([$user->id], $user->getActiveDelegatorIds());

        // 2. Bu belgede, bu kullanıcılardan birinin "bekleyen" bir onayı var mı?
        return DocumentApproval::where('document_id', $document->id)
            ->whereIn('user_id', $allIdsToCheck)
            ->where('status', 'pending')
            ->exists();
    }

    public function rules(): array
    {
        return [
            // Onaylama işlemi için ekstra bir input beklemiyoruz.
        ];
    }
}
