<?php

namespace App\Services;

use App\Models\Document;
use App\Models\User;

class FavoriteService
{
    public function toggleFavorite(User $user, Document $document): array
    {
        // toggle() metodu attached/detached dizilerini döner
        $changes = $user->favorites()->toggle($document->id);
        $isFavorited = count($changes['attached']) > 0;

        return [
            'success' => true,
            'is_favorited' => $isFavorited,
            'message' => $isFavorited
                ? __('Belge favorilere eklendi.')
                : __('Belge favorilerden çıkarıldı.')
        ];
    }
}
