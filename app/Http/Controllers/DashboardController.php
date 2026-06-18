<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\Services\DashboardService;
use App\Models\User;

class DashboardController extends Controller
{
    /**
     * @var DashboardService
     */
    protected DashboardService $dashboardService;

    /**
     * Bağımlılık Enjeksiyonu (Dependency Injection)
     */
    public function __construct(DashboardService $dashboardService)
    {
        $this->dashboardService = $dashboardService;
    }

    /**
     * Dashboard ana sayfasını yükler
     */
    public function index(Request $request)
    {
        /** @var User $user */
        $user = Auth::user();

        // 1. KYS/SSO VIP INTERCEPTOR (Yönetim Kurulu Yönlendirmesi)
        // Eğer kullanıcı Cockpit yetkisine sahipse, Dashboard'u çizmeden VİP rotaya fırlat.
        if ($user->hasRole('Yönetim Kurulu')) {
            return redirect()->route('executive.cockpit');
        }

        // 2. STANDART GÜVENLİK KALKANI (Rotadan buraya taşındı)
        // Eğer Super Admin değilse ve normal Dashboard menü yetkisi de yoksa izinsiz giriş uyarısı ver.
        if (!$user->hasRole('Super Admin') && !$user->can('menu.dashboard')) {
            abort(403, 'Ana sayfayı (Dashboard) görüntüleme yetkiniz bulunmuyor.');
        }

        // Favori arama (AJAX kontrolünü en başta yaparak gereksiz DB sorgularından kaçınıyoruz)
        $keyword = $request->input('fav_search');

        if ($request->ajax()) {
            $favoriteDocuments = $this->dashboardService->getFavoriteDocuments($user, $keyword);
            return view('dashboard.partials.favorites-list', compact('favoriteDocuments', 'keyword'))->render();
        }

        // Vekalet verilen kullanıcıların ID'lerini topla
        $proxyForIds = $user->getActiveDelegatorIds() ?? [];
        $allIdsToCheck = array_merge([$user->id], $proxyForIds);

        // Servisten verileri çekiyoruz
        $pendingApprovals = $this->dashboardService->getPendingApprovals($allIdsToCheck);
        $pendingPhysicalReceipts = $this->dashboardService->getPendingPhysicalReceipts($user->id);
        $lockedDocuments = $this->dashboardService->getLockedDocuments($user->id);
        $recentUploads = $this->dashboardService->getRecentUploads($user->id);
        $expiringDocuments = $this->dashboardService->getExpiringDocuments($user);
        $stats = $this->dashboardService->getQuickStats($user);
        $favoriteDocuments = $this->dashboardService->getFavoriteDocuments($user, $keyword);

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
            'expiringDocuments' => $expiringDocuments,

            // İstatistikler
            'totalAccessible' => $stats['accessible'],
            'totalArchived' => $stats['archived'],
            'myDrafts' => $stats['drafts'],

            // Diğer
            'currentDate' => Carbon::now()->translatedFormat('d F Y, l'),
            'favoriteDocuments' => $favoriteDocuments,
            'keyword' => $keyword
        ]);
    }
}
