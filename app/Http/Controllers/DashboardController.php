<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\DocumentApproval;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    // Arama kelimesini yakalamak için Request parametresini ekledik
    public function index(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        // --- VEKALET KONTROLÜ ---
        $proxyForIds = $user->getActiveDelegatorIds() ?? [];
        $allIdsToCheck = array_merge([$user->id], $proxyForIds);

        // 1. ACİL AKSİYONLAR: Bekleyen Onaylar
        $allMyPendingApprovals = DocumentApproval::with(['document.currentVersion', 'user'])
            ->whereIn('user_id', $allIdsToCheck)
            ->where('status', 'pending')
            ->whereHas('document', function ($query) {
                $query->whereNotIn('status', ['archived', 'rejected']);
            })
            ->get();

        $pendingApprovals = $allMyPendingApprovals->filter(function ($approval) {
            $unapprovedPrevious = DocumentApproval::where('document_id', $approval->document_id)
                ->where('step_order', '<', $approval->step_order)
                ->where('status', '!=', 'approved')
                ->exists();
            return !$unapprovedPrevious;
        });

        // 2. ACİL AKSİYONLAR: Fiziksel Teslimat Bekleyenler
        $pendingPhysicalReceipts = Document::with('currentVersion')
            ->where('delivered_to_user_id', $user->id)
            ->where('physical_receipt_status', 'pending')
            ->get();

        // Toplam Bekleyen İşlem Sayısı (Rozet ve Karşılama mesajı için gerçek sayı)
        $totalPendingTasks = $pendingApprovals->count() + $pendingPhysicalReceipts->count();

        // Arayüzü şişirmemek için sadece en eski/acil 5 tanesini ekrana gönderiyoruz
        $displayPendingApprovals = $pendingApprovals->take(5);
        $displayPhysicalReceipts = $pendingPhysicalReceipts->take(5);

        // 3. ÜZERİMDEKİ BELGELER: Revize için kilitlediğim belgeler
        $allLockedDocs = Document::with('currentVersion')
            ->where('is_locked', true)
            ->where('locked_by', $user->id)
            ->get();

        $totalLockedCount = $allLockedDocs->count();
        $myLockedDocuments = $allLockedDocs->take(5);

        // 4. SON AKTİVİTELERİM: Sadece kendi başlattığım/yüklediğim son belgeler
        $myRecentUploads = Document::with(['currentVersion'])
            ->whereHas('versions', function ($q) use ($user) {
                $q->where('created_by', $user->id);
            })
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();

        // 5. YAKLAŞAN SÖZLEŞMELER (Kritik! Sadece Hukuk ve Yöneticiler)
        $expiringContracts = collect();
        if ($user->hasAnyRole(['Super Admin', 'Admin', 'Hukuk'])) {
            $expiringContracts = Document::whereNotNull('expire_at')
                ->where('expire_at', '<=', Carbon::now()->addDays(30))
                ->whereNotIn('status', ['archived', 'rejected'])
                ->orderBy('expire_at', 'asc')
                ->take(5)
                ->get();
        }

        // 6. HIZLI BAKIŞ İSTATİSTİKLERİ
        $totalAccessible = Document::authorizedForUser($user)->count();
        $totalArchived = Document::authorizedForUser($user)->where('status', 'archived')->count();
        $myDrafts = Document::whereHas('versions', function ($q) use ($user) {
            $q->where('created_by', $user->id);
        })->whereIn('status', ['draft', 'rejected'])->count();

        // 7. YENİ EKLENEN: FAVORİ BELGELERİM VE SMART SEARCH
        $keyword = $request->input('fav_search');

        $favoritesQuery = $user->favorites()
            ->with(['documentType', 'currentVersion']) // Eager Loading (N+1 engeller)
            ->searchInFavorites($keyword) // Modelde yazdığımız Scope
            ->latest('document_user_favorites.created_at');

        // Policy Filtresi: Favoriye eklendikten sonra yetkisi geri alınmış belgeleri listeden gizle!
        $favoriteDocuments = $favoritesQuery->get()->filter(function ($document) use ($user) {
            return $user->can('view', $document);
        });
        if ($request->ajax()) {
            return view('dashboard.partials.favorites-list', compact('favoriteDocuments', 'keyword'))->render();
        }

        // DİKKAT: Carbon::setLocale('tr') satırını sildik! 
        // Çünkü Middleware içinde bunu dinamik yaptık (İngilizce seçildiğinde April yazması için)
        $currentDate = Carbon::now()->translatedFormat('d F Y, l');

        return view('dashboard', compact(
            'displayPendingApprovals',
            'displayPhysicalReceipts',
            'totalPendingTasks',
            'myLockedDocuments',
            'totalLockedCount',
            'myRecentUploads',
            'expiringContracts',
            'totalAccessible',
            'totalArchived',
            'myDrafts',
            'currentDate',
            'favoriteDocuments', // YENİ
            'keyword'            // YENİ
        ));
    }
}