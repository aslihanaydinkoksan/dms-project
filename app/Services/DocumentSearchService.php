<?php

namespace App\Services;

use App\Models\Document;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator; // Somut sınıf yerine Interface (Contract) kullanıyoruz
use Illuminate\Database\Eloquent\Builder;

class DocumentSearchService
{
    /**
     * Zeki Eloquent Motoru ile yetkilendirilmiş belge araması yapar.
     * 
     * MİMARİ DOKUNUŞ: %100 Tip Güvenliği (Strict Types) sağlandı.
     * Parametrelerin alabileceği değerler (string, int, null) açıkça belirtildi.
     */
    public function searchDocuments(
        ?string $keyword,
        User $user,
        int $perPage = 15,
        ?string $status = null,
        ?string $privacy = null,
        ?string $startDate = null,
        ?string $endDate = null
    ): LengthAwarePaginator {

        // 1. ZIRH: authorizedForUser sorgusunu function($q) içine hapsediyoruz.
        // Closure içine tip garantisi (Builder) ekliyoruz.
        $query = Document::where(function (Builder $q) use ($user) {
            $q->authorizedForUser($user);
        })->with(['folder', 'currentVersion.createdBy']);

        // 2. Metin Araması
        if (!empty($keyword) && trim($keyword) !== '') {
            $query->advancedSearch($keyword);
        }

        // 3. Statü Filtresi
        if (!empty($status)) {
            $searchStatus = $status === 'pending' ? 'pending_approval' : $status;
            $query->where('status', $searchStatus);
        }

        // 4. Gizlilik Filtresi
        if (!empty($privacy)) {
            // Hızlı karttan 'secret' gelirse iki gizli durumu da kapsayacak şekilde filtrele
            if ($privacy === 'secret') {
                $query->whereIn('privacy_level', ['confidential', 'strictly_confidential']);
            } else {
                $query->where('privacy_level', $privacy);
            }
        }

        // 5. Başlangıç Tarihi Filtresi
        if (!empty($startDate)) {
            $query->whereDate('created_at', '>=', $startDate);
        }

        // 6. Bitiş Tarihi Filtresi
        if (!empty($endDate)) {
            $query->whereDate('created_at', '<=', $endDate);
        }

        // Zaten latest() olduğu için daima en yeniler en üstte gelecek!
        return $query->latest()->paginate($perPage);
    }
}
