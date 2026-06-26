<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class UniversalAnalyticsService
{
    /**
     * Frontend'deki Dropdown'ları doldurmak için konfigürasyonu döner
     */
    public function getAvailableModules(): array
    {
        return config('analytics.modules', []);
    }

    /**
     * Dinamik Grafik Verisi Üretici
     */
    public function generateChartData(string $moduleKey, string $groupKey, ?string $startDate, ?string $endDate): array
    {
        $config = config("analytics.modules.{$moduleKey}");
        if (!$config) abort(400, "Modül bulunamadı.");

        $groupConfig = $config['groupings'][$groupKey] ?? null;
        if (!$groupConfig) abort(400, "Gruplama kriteri bulunamadı.");

        $modelClass = $config['model'];
        $dateColumn = $config['date_column'];

        // Eğer kullanıcı (nested) ilişkisi ise gruplamayı farklı yakalamamız gerekir.
        // Güvenlik: Standart kolon mu yoksa özel bir ilişki mi?
        $isNestedUser = ($groupKey === 'user' && $moduleKey === 'documents');
        $groupColumn = $isNestedUser ? 'created_by' : $groupConfig['col'];

        $start = $startDate ? Carbon::parse($startDate)->startOfDay() : Carbon::now()->subDays(30)->startOfDay();
        $end = $endDate ? Carbon::parse($endDate)->endOfDay() : Carbon::now()->endOfDay();

        $query = $modelClass::whereBetween($dateColumn, [$start, $end]);

        // 1. Gruplama mantığı (Nested User için özel join veya alt sorgu gerekebilir, 
        // ancak en temizi veriyi çekip collection üzerinde gruplamaktır)
        if ($isNestedUser) {
            $query->with('currentVersion.createdBy');
            $rawRecords = $query->get();

            $grouped = $rawRecords->groupBy(function ($doc) {
                return $doc->currentVersion?->createdBy?->name ?? 'Bilinmeyen Kullanıcı';
            })->map->count()->sortByDesc(function ($count) {
                return $count;
            })->take(10); // En çok yükleyen 10 kişi

            return [
                'labels' => $grouped->keys()->toArray(),
                'data' => $grouped->values()->toArray()
            ];
        }

        // 2. Standart Gruplama Mantığı (Mevcut kodun)
        if (isset($groupConfig['relation'])) {
            $query->with($groupConfig['relation']);
        }

        $results = $query->select($groupColumn, DB::raw('count(*) as total'))
            ->groupBy($groupColumn)
            ->orderByDesc('total')
            ->get();

        $labels = [];
        $data = [];

        $universalMap = [
            'active' => 'Aktif Süreçler',
            'pending_closure_approval' => 'Kapanış Onayı Bekliyor',
            'completed' => 'Tamamlandı (Arşiv)',
            'draft' => 'Taslak',
            'pending_approval' => 'Onay Bekliyor',
            'approved' => 'Onaylandı (Yürürlükte)',
            'rejected' => 'Reddedildi',
            'archived' => 'Arşivlendi'
        ];

        foreach ($results as $item) {
            $labelName = 'Tanımsız';

            if (isset($groupConfig['relation'])) {
                $relationName = $groupConfig['relation'];
                $displayCol = $groupConfig['display_col'];

                if ($item->$relationName) {
                    $labelName = $item->$relationName->$displayCol;
                }
            } else {
                $rawVal = $item->getRawOriginal($groupColumn) ?? $item->$groupColumn;
                $searchKey = is_string($rawVal) ? trim(strtolower($rawVal)) : $rawVal;

                if (isset($groupConfig['map']) && isset($groupConfig['map'][$searchKey])) {
                    $labelName = $groupConfig['map'][$searchKey];
                } elseif (isset($universalMap[$searchKey])) {
                    $labelName = $universalMap[$searchKey];
                } else {
                    $labelName = ucfirst(str_replace('_', ' ', $rawVal ?? 'Bilinmiyor'));
                }
            }

            $labels[] = __($labelName);
            $data[] = (int) $item->total;
        }

        return [
            'labels' => $labels,
            'data' => $data
        ];
    }
}
