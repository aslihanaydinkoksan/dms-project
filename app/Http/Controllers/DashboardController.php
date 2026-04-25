<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\DocumentApproval;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Collection;

class DashboardController extends Controller
{
    /**
     * Dashboard ana sayfasını yükler
     */
    public function index(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        // Vekalet verilen kullanıcıların ID'lerini topla
        $proxyForIds = $user->getActiveDelegatorIds() ?? [];
        $allIdsToCheck = array_merge([$user->id], $proxyForIds);

        // 1. İş Yükü ve Görevler
        $pendingApprovals = $this->getPendingApprovals($allIdsToCheck);
        $pendingPhysicalReceipts = $this->getPendingPhysicalReceipts($user->id);

        // 2. Özel Listeler
        $lockedDocuments = $this->getLockedDocuments($user->id);
        $recentUploads = $this->getRecentUploads($user->id);

        // DEĞİŞEN KISIM: Değişken ve fonksiyon adı 'Documents' oldu
        $expiringDocuments = $this->getExpiringDocuments($user);

        // 3. İstatistikler
        $stats = $this->getQuickStats($user);

        // 4. Favoriler (AJAX destekli)
        $keyword = $request->input('fav_search');
        $favoriteDocuments = $this->getFavoriteDocuments($user, $keyword);

        // Eğer sadece favoriler aranıyorsa (AJAX), sadece o kısmı döndür
        if ($request->ajax()) {
            return view('dashboard.partials.favorites-list', compact('favoriteDocuments', 'keyword'))->render();
        }

        // Ana View'ı döndür
        return view('dashboard', [
            // Görevler
            'displayPendingApprovals' => $pendingApprovals->take(5),
            'displayPhysicalReceipts' => $pendingPhysicalReceipts->take(5),
            'totalPendingTasks' => $pendingApprovals->count() + $pendingPhysicalReceipts->count(),

            // Listeler
            'myLockedDocuments' => $lockedDocuments->take(5),
            'totalLockedCount' => $lockedDocuments->count(),
            'myRecentUploads' => $recentUploads,

            // DEĞİŞEN KISIM: Blade'e 'expiringDocuments' olarak gönderiyoruz
            'expiringDocuments' => $expiringDocuments,

            // İstatistikler (Destructuring)
            'totalAccessible' => $stats['accessible'],
            'totalArchived' => $stats['archived'],
            'myDrafts' => $stats['drafts'],

            // Diğer
            'currentDate' => Carbon::now()->translatedFormat('d F Y, l'),
            'favoriteDocuments' => $favoriteDocuments,
            'keyword' => $keyword
        ]);
    }

    /* ==========================================================================
     * PRIVATE METODLAR (Veri Çekme İşlemleri)
     * ========================================================================== */

    /**
     * Kullanıcının ve vekalet ettiklerinin bekleyen onaylarını getirir
     */
    private function getPendingApprovals(array $userIds): Collection
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
    private function getPendingPhysicalReceipts(int $userId): Collection
    {
        return Document::with('currentVersion')
            ->where('delivered_to_user_id', $userId)
            ->where('physical_receipt_status', 'pending')
            ->get();
    }

    /**
     * Kullanıcı tarafından kilitlenmiş (Checkout) belgeleri getirir
     */
    private function getLockedDocuments(int $userId): Collection
    {
        return Document::with('currentVersion')
            ->where('is_locked', true)
            ->where('locked_by', $userId)
            ->get();
    }

    /**
     * Kullanıcının sisteme yüklediği son 5 belgeyi getirir
     */
    private function getRecentUploads(int $userId): Collection
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
     * DEĞİŞEN KISIM: Yaklaşan belgeleri yetkiye göre filtreleyip getirir
     */
    private function getExpiringDocuments($user): Collection
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
    private function getQuickStats($user): array
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
    private function getFavoriteDocuments($user, ?string $keyword): Collection
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
