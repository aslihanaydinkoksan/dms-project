<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\AnalyticsService;
use App\Models\User;

class AnalyticsController extends Controller
{
    protected AnalyticsService $analyticsService;

    public function __construct(AnalyticsService $analyticsService)
    {
        $this->analyticsService = $analyticsService;
    }

    public function index(Request $request)
    {
        /** @var User $user */
        $user = $request->user();

        // YENİ GÜVENLİK ZIRHI: Sadece 'menu.analytics' yetkisi olanlar veya Super Admin görebilir
        if (!$user->hasRole('Super Admin') && !$user->can('menu.analytics')) {
            abort(403, 'Sistem Analitiği ekranını görüntüleme yetkiniz bulunmuyor.');
        }
        // Güvenlik Zırhı
        \Illuminate\Support\Facades\Gate::authorize('viewAny', \App\Models\Document::class);

        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');

        // Tüm karmaşık işi Servis'e devrediyoruz!
        $analyticsData = $this->analyticsService->getExecutiveData($startDate, $endDate);

        return view('analytics.index', compact('analyticsData', 'startDate', 'endDate'));
    }
}
