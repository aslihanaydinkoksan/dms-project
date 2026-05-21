<?php

namespace App\Services;

use App\Models\User;
use App\Models\Document;
use App\Models\DocumentVersion;
use Illuminate\Support\Facades\DB;

class ProfileService
{
    /**
     * Kullanıcının bildirim tercihlerini günceller
     */
    public function updateNotificationPreferences(User $user, array $data): void
    {
        $prefs = [
            'physical_assigned' => [
                'mail' => isset($data['physical_assigned_mail']),
                'database' => isset($data['physical_assigned_db']),
            ],
            'workflow_action' => [
                'mail' => isset($data['workflow_action_mail']),
                'database' => isset($data['workflow_action_db']),
            ],
            'document_revision' => [
                'mail' => isset($data['document_revision_mail']),
                'database' => isset($data['document_revision_db']),
            ]
        ];

        $user->update(['notification_preferences' => $prefs]);
    }

    /**
     * Hedef kullanıcının kişisel performans ve sistem kullanım istatistiklerini hesaplar
     */
    public function getUserPerformanceStats(User $targetUser): array
    {
        // Toplam yüklenen belge sayısı
        $totalDocs = Document::whereHas('versions', fn($q) => $q->where('created_by', $targetUser->id))->count();

        // Onaylanmış / Yayındaki belgeler
        $approvedDocs = Document::whereHas('versions', fn($q) => $q->where('created_by', $targetUser->id))
            ->whereIn('status', ['published', 'approved'])
            ->count();

        // Reddedilmiş belgeler
        $rejectedDocs = Document::whereHas('versions', fn($q) => $q->where('created_by', $targetUser->id))
            ->where('status', 'rejected')
            ->count();

        // Toplam yapılan revizyon (1.0 haricindeki version yüklemeleri)
        $totalRevisions = DocumentVersion::where('created_by', $targetUser->id)
            ->where('version_number', '!=', '1.0')
            ->count();

        // Hangi doküman tipinden ne kadar yüklemiş? (Grafik İçin)
        $docTypesChart = Document::whereHas('versions', fn($q) => $q->where('created_by', $targetUser->id))
            ->select('document_type_id', DB::raw('count(*) as total'))
            ->groupBy('document_type_id')
            ->orderByDesc('total')
            ->with('documentType')
            ->get();

        // Oran Hesaplamaları (Sıfıra bölünme hatası - Division by Zero önlemi)
        $approvalRate = $totalDocs > 0 ? round(($approvedDocs / $totalDocs) * 100) : 0;
        $rejectionRate = $totalDocs > 0 ? round(($rejectedDocs / $totalDocs) * 100) : 0;

        return compact(
            'targetUser',
            'totalDocs',
            'approvedDocs',
            'rejectedDocs',
            'totalRevisions',
            'docTypesChart',
            'approvalRate',
            'rejectionRate'
        );
    }
}
