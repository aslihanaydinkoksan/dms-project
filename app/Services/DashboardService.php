<?php

namespace App\Services;

use App\Models\Document;
use App\Models\DocumentApproval;
use App\Models\User;
use Illuminate\Support\Collection;
use Carbon\Carbon;

class DashboardService
{
    /**
     * Kullanıcının ve vekalet ettiklerinin bekleyen onaylarını getirir
     */
    public function getPendingApprovals(array $userIds): Collection
    {
        $allPending = DocumentApproval::with(['document.currentVersion', 'user'])
            ->whereIn('user_id', $userIds)
            ->where('status', 'pending')
            ->whereHas('document', function ($query) {
                $query->whereNotIn('status', ['archived', 'rejected']);
            })
            ->get();

        // Sadece sırası gelmiş (önceki adımları onaylanmış) olanları filtrele
        return $allPending->filter(function ($approval) {
            $unapprovedPrevious = DocumentApproval::where('document_id', $approval->document_id)
                ->where('step_order', '<', $approval->step_order)
                ->where('status', '!=', 'approved')
                ->exists();
            return !$unapprovedPrevious;
        });
    }

    /**
     * Kullanıcıya teslim edilecek ıslak imzalı belgeleri getirir
     */
    public function getPendingPhysicalReceipts(int $userId): Collection
    {
        return Document::with('currentVersion')
            ->where('delivered_to_user_id', $userId)
            ->where('physical_receipt_status', 'pending')
            ->get();
    }

    /**
     * Kullanıcı tarafından kilitlenmiş (Checkout) belgeleri getirir
     */
    public function getLockedDocuments(int $userId): Collection
    {
        return Document::with('currentVersion')
            ->where('is_locked', true)
            ->where('locked_by', $userId)
            ->get();
    }

    /**
     * Kullanıcının sisteme yüklediği son 5 belgeyi getirir
     */
    public function getRecentUploads(int $userId): Collection
    {
        return Document::with(['currentVersion'])
            ->whereHas('versions', function ($q) use ($userId) {
                $q->where('created_by', $userId);
            })
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();
    }

    /**
     * Yaklaşan belgeleri yetkiye göre filtreleyip getirir
     * DİKKAT: P1132 Uyarıları (User $user) Type-Hint eklenerek çözüldü.
     */
    public function getExpiringDocuments(User $user): Collection
    {
        $today = Carbon::today();
        $thirtyDaysLater = Carbon::today()->addDays(30);

        $query = Document::whereNotNull('expire_at')
            ->whereDate('expire_at', '>=', $today)
            ->whereDate('expire_at', '<=', $thirtyDaysLater)
            ->whereNotIn('status', ['archived', 'rejected']);

        // Eğer üst düzey yetkili değilse, sadece kendi eklediği belgelerin alarmını görsün
        if (!$user->hasAnyRole(['Super Admin', 'Admin', 'Hukuk'])) {
            $query->whereHas('versions', function ($q) use ($user) {
                $q->where('created_by', $user->id);
            });
        }

        return $query->orderBy('expire_at', 'asc')->take(7)->get();
    }

    /**
     * Dashboard üstündeki özet sayıları (Card) hesaplar
     */
    public function getQuickStats(User $user): array
    {
        return [
            'accessible' => Document::authorizedForUser($user)->count(),
            'archived' => Document::authorizedForUser($user)->where('status', 'archived')->count(),
            'drafts' => Document::whereHas('versions', function ($q) use ($user) {
                $q->where('created_by', $user->id);
            })->whereIn('status', ['draft', 'rejected'])->count()
        ];
    }

    /**
     * Kullanıcının favorilerini (arama filtresiyle birlikte) yetki kontrolünden geçirerek getirir
     */
    public function getFavoriteDocuments(User $user, ?string $keyword): Collection
    {
        $favorites = $user->favorites()
            ->with(['documentType', 'currentVersion'])
            ->searchInFavorites($keyword)
            ->latest('document_user_favorites.created_at')
            ->get();

        // Kullanıcının favoriye eklediği ama sonradan yetkisinin alındığı belgeleri listeden çıkar
        return $favorites->filter(function ($document) use ($user) {
            return $user->can('view', $document);
        });
    }
}
