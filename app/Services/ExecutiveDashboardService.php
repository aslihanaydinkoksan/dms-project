<?php

namespace App\Services;

use App\Models\Task;
use App\Models\Document;
use App\Models\AuditLog;
use Carbon\Carbon;

class ExecutiveDashboardService
{
    public function getCockpitStats(): array
    {
        $criticalDays = config('settings.critical_document_days', 30);

        return [
            // DMS Metrikleri
            'published_docs' => Document::where('status', 'published')->count(),
            'expiring_docs' => Document::where('expire_at', '<=', Carbon::now()->addDays($criticalDays))
                ->where('expire_at', '>=', Carbon::now())->count(),

            // BPM / Görev Metrikleri
            'active_tasks' => Task::where('status', 'active')->count(),
            'pending_closure' => Task::where('status', 'pending_closure_approval')->count(),

            'recent_activities' => AuditLog::with('user')
                ->orderBy('created_at', 'desc')
                ->limit(10)->get()
        ];
    }
}
