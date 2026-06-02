<?php

namespace App\Services;

use App\Models\Document;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AnalyticsService
{
    /**
     * Tüm Dashboard verilerini filtrelerle birlikte toplayıp JSON'a hazır hale getirir.
     */
    public function getExecutiveData(?string $startDate, ?string $endDate): array
    {
        // Varsayılan tarih: Son 30 Gün
        $start = $startDate ? Carbon::parse($startDate)->startOfDay() : Carbon::now()->subDays(29)->startOfDay();
        $end = $endDate ? Carbon::parse($endDate)->endOfDay() : Carbon::now()->endOfDay();

        return [
            'summary' => $this->getSummaryCards($start, $end),
            'trend' => $this->getThirtyDaysTrend($start, $end),
            'status' => $this->getStatusDistribution($start, $end),
            'types' => $this->getDocumentTypeDistribution($start, $end),
            'departments' => $this->getDepartmentActivity($start, $end),
            'top_users' => $this->getTopUsers($start, $end),
        ];
    }

    private function getSummaryCards(Carbon $start, Carbon $end): array
    {
        $baseQuery = Document::whereBetween('created_at', [$start, $end]);

        return [
            'total' => (clone $baseQuery)->count(),
            'approved' => (clone $baseQuery)->whereIn('status', ['approved', 'published'])->count(),
            'rejected' => (clone $baseQuery)->where('status', 'rejected')->count(),
            'pending' => (clone $baseQuery)->whereIn('status', ['pending', 'pending_approval', 'draft'])->count(),
        ];
    }

    private function getThirtyDaysTrend(Carbon $start, Carbon $end): array
    {
        // Sadece tarih (Y-m-d) bazında gruplama yapıp sayıyoruz (Performanslı SQL)
        $trend = Document::select(DB::raw('DATE(created_at) as date'), DB::raw('count(*) as total'))
            ->whereBetween('created_at', [$start, $end])
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // ApexCharts'ın datetime formatı için Timestamp (milisaniye) ve değer eşleşmesi
        return $trend->map(function ($item) {
            return [
                Carbon::parse($item->date)->timestamp * 1000,
                $item->total
            ];
        })->toArray();
    }

    private function getStatusDistribution(Carbon $start, Carbon $end): array
    {
        $statuses = Document::select('status', DB::raw('count(*) as total'))
            ->whereBetween('created_at', [$start, $end])
            ->groupBy('status')
            ->get();

        $approved = $statuses->whereIn('status', ['approved', 'published'])->sum('total');
        $rejected = $statuses->where('status', 'rejected')->sum('total');
        $pending = $statuses->whereIn('status', ['pending', 'pending_approval', 'draft'])->sum('total');

        return [
            'series' => [$approved, $pending, $rejected], // Onaylı, Bekleyen, Reddedilen sırasıyla
            'labels' => ['Onaylı / Yayında', 'Bekleyen / Taslak', 'Reddedilen']
        ];
    }

    private function getDocumentTypeDistribution(Carbon $start, Carbon $end): array
    {
        // Relation kullanarak Type ismini alıyoruz ve grupluyoruz
        $types = Document::with('documentType:id,name')
            ->select('document_type_id', DB::raw('count(*) as total'))
            ->whereBetween('created_at', [$start, $end])
            ->groupBy('document_type_id')
            ->orderByDesc('total')
            ->get();

        return [
            'series' => $types->pluck('total')->toArray(),
            'labels' => $types->map(fn($t) => $t->documentType ? $t->documentType->name : 'Tanımsız')->toArray()
        ];
    }

    private function getDepartmentActivity(Carbon $start, Carbon $end): array
    {
        $departments = Document::with('relatedDepartment:id,name')
            ->select('related_department_id', DB::raw('count(*) as total'))
            ->whereBetween('created_at', [$start, $end])
            ->groupBy('related_department_id')
            ->orderByDesc('total')
            ->take(10) // İlk 10 Departman
            ->get();

        return [
            'series' => [['name' => 'Üretilen Belge', 'data' => $departments->pluck('total')->toArray()]],
            'categories' => $departments->map(fn($d) => $d->relatedDepartment ? $d->relatedDepartment->name : 'Genel')->toArray()
        ];
    }

    private function getTopUsers(Carbon $start, Carbon $end): array
    {
        // Kullanıcıları yükledikleri belge sayılarına göre sıralayan ZEKİ BİR SORGULAMA (WithCount)
        $topUsers = User::select('id', 'name')
            ->withCount(['documentVersions as docs_count' => function ($query) use ($start, $end) {
                // Sadece belirtilen tarih aralığındaki ilk (orijinal) yüklemeleri sayalım
                $query->whereBetween('created_at', [$start, $end]);
            }])
            ->orderByDesc('docs_count')
            ->take(7) // Liderlik tablosu (İlk 7)
            ->get()
            ->filter(fn($u) => $u->docs_count > 0); // Hiç yüklemeyenleri listeden at

        return [
            'series' => [['name' => 'Yüklenen Belge', 'data' => $topUsers->pluck('docs_count')->toArray()]],
            'categories' => $topUsers->pluck('name')->toArray()
        ];
    }
}
