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
    public function searchDocuments($query, $user, $perPage = 15)
    {
        // Scout'un Document::search($query) kullanımını KADIRDIK!
        // Yerine yazdığımız Kapsamlı Arama (Advanced Search) scope'unu zincirledik.

        return Document::advancedSearch($query)
            ->authorizedForUser($user) // Kullanıcının görme yetkisi olanları filtrele (3D Matris)
            ->with(['folder', 'currentVersion.createdBy']) // N+1 Sorgu problemini engelle
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }
}
