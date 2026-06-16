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
        $groupColumn = $groupConfig['col'];

        $start = $startDate ? Carbon::parse($startDate)->startOfDay() : Carbon::now()->subDays(30)->startOfDay();
        $end = $endDate ? Carbon::parse($endDate)->endOfDay() : Carbon::now()->endOfDay();

        // 1. Dinamik Sorgu Oluşturma
        $query = $modelClass::whereBetween($dateColumn, [$start, $end]);

        // İlişki (Relation) varsa N+1'i önlemek için Eager Load yap
        if (isset($groupConfig['relation'])) {
            $query->with($groupConfig['relation']);
        }

        // 2. Gruplama ve Sayma
        $results = $query->select($groupColumn, DB::raw('count(*) as total'))
            ->groupBy($groupColumn)
            ->orderByDesc('total')
            ->get();

        // 3. Veriyi Frontend'in istediği formata (Labels ve Data) çevir
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
            'archived' => 'Arşivlendi / Yayından Kaldırıldı'
        ];

        foreach ($results as $item) {
            $labelName = 'Tanımsız';

            if (isset($groupConfig['relation'])) {
                // İlişkili tablo varsa (Örn: process_template_id -> Şablon Adı)
                $relationName = $groupConfig['relation'];
                $displayCol = $groupConfig['display_col'];

                if ($item->$relationName) {
                    $labelName = $item->$relationName->$displayCol;
                }
            } else {
                // 1. ZIRH: Ham veriyi al
                $rawVal = $item->getRawOriginal($groupColumn) ?? $item->$groupColumn;

                // 2. ZIRH: Eşleşme garantisi için boşlukları silip küçük harfe çevirelim
                $searchKey = is_string($rawVal) ? trim(strtolower($rawVal)) : $rawVal;

                // 3. Önce Config'de map var mı diye bak
                if (isset($groupConfig['map']) && isset($groupConfig['map'][$searchKey])) {
                    $labelName = $groupConfig['map'][$searchKey];
                }
                // 4. Config inat edip yüklenmediyse DOĞRUDAN SERVİS SÖZLÜĞÜNE BAK
                elseif (isset($universalMap[$searchKey])) {
                    $labelName = $universalMap[$searchKey];
                }
                // 5. Hiçbir yerde yoksa makyajla ver (Örn: pending_closure_approval -> Pending closure approval)
                else {
                    $labelName = ucfirst(str_replace('_', ' ', $rawVal ?? 'Bilinmiyor'));
                }
            }

            $labels[] = $labelName;
            $data[] = (int) $item->total;
        }

        return [
            'labels' => $labels,
            'data' => $data
        ];
    }
}
