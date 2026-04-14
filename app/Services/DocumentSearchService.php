<?php

namespace App\Services;

use App\Models\Document;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;

class DocumentSearchService
{
    /**
     * Zeki Eloquent Motoru ile yetkilendirilmiş belge araması yapar.
     */
    public function searchDocuments($keyword, $user, $perPage = 15, $status = null, $privacy = null, $startDate = null, $endDate = null)
    {
        // 1. ZIRH: authorizedForUser sorgusunu function($q) içine hapsediyoruz.
        $query = Document::where(function ($q) use ($user) {
            $q->authorizedForUser($user);
        })->with(['folder', 'currentVersion.createdBy']);

        // 2. Metin Araması
        if (isset($keyword) && trim($keyword) !== '') {
            $query->advancedSearch($keyword);
        }

        // 3. Statü Filtresi
        if (!empty($status)) {
            $searchStatus = $status === 'pending' ? 'pending_approval' : $status;
            $query->where('status', $searchStatus);
        }

        // 4. Gizlilik Filtresi
        if (!empty($privacy)) {
            // YENİ: Hızlı karttan 'secret' gelirse iki gizli durumu da kapsayacak şekilde filtrele
            if ($privacy === 'secret') {
                $query->whereIn('privacy_level', ['confidential', 'strictly_confidential']);
            } else {
                $query->where('privacy_level', $privacy);
            }
        }

        // 5. YENİ: Başlangıç Tarihi Filtresi
        if (!empty($startDate)) {
            $query->whereDate('created_at', '>=', $startDate);
        }

        // 6. YENİ: Bitiş Tarihi Filtresi
        if (!empty($endDate)) {
            $query->whereDate('created_at', '<=', $endDate);
        }

        // Zaten latest() olduğu için daima en yeniler en üstte gelecek!
        return $query->latest()->paginate($perPage);
    }
}
