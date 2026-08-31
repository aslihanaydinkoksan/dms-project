<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\AuditLog;
use Illuminate\View\View;
use Illuminate\Support\Facades\DB;

class SystemLogController extends Controller
{
    /**
     * Sistem "Kara Kutu" loglarını ve bildirim geçmişini getirir.
     */
    public function index(Request $request): View
    {
        // 1. Filtre Girdileri
        $userName = $request->query('user_name');
        $docName = $request->query('document_name');
        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');

        // ==========================================
        // TAB 1: BELGE VE SİSTEM İZLERİ (AUDIT LOGS)
        // ==========================================
        // Hem silinmiş kullanıcıları hem de silinmiş objeleri (Document, Folder vb.) getirebilmek için withTrashed() ekliyoruz.
        $auditQuery = AuditLog::with([
            'user' => fn($q) => $q->withTrashed(),
            'auditable' => fn($q) => $q->withTrashed()
        ])->latest();

        // 1. Kullanıcı Adına Göre Arama (Silinmiş kullanıcılar dahil)
        if ($userName) {
            $auditQuery->whereHas('user', function ($q) use ($userName) {
                $q->withTrashed()->where('name', 'like', "%{$userName}%");
            });
        }

        // 2. Tarih Filtreleri
        if ($startDate) {
            $auditQuery->whereDate('created_at', '>=', $startDate);
        }
        if ($endDate) {
            $auditQuery->whereDate('created_at', '<=', $endDate);
        }

        // 3. Obje Adına (Belge, Klasör) Göre Arama - Polimorfik Arama
        if ($docName) {
            $auditQuery->where(function ($q) use ($docName) {
                // Belge isimlerinde ara
                $q->whereHasMorph('auditable', [\App\Models\Document::class], function ($query) use ($docName) {
                    $query->withTrashed()->where('title', 'like', "%{$docName}%");
                })
                    // Klasör isimlerinde ara (Artık klasörleri de loglayacağımız için)
                    ->orWhereHasMorph('auditable', [\App\Models\Folder::class], function ($query) use ($docName) {
                        $query->withTrashed()->where('name', 'like', "%{$docName}%");
                    });
            });
        }

        $auditLogs = $auditQuery->paginate(15, ['*'], 'audit_page')->withQueryString();

        // ==========================================
        // TAB 2: BİLDİRİM GEÇMİŞİ (DISPATCH LOGS)
        // ==========================================
        // Sistemin kendi notifications tablosunu kullanarak "Okundu" bilgisini yakalıyoruz
        $dispatchQuery = DB::table('notifications')
            ->join('users', 'notifications.notifiable_id', '=', 'users.id')
            ->select('notifications.*', 'users.name as receiver_name', 'users.email as receiver_email')
            ->where('notifications.notifiable_type', 'App\Models\User')
            ->orderBy('notifications.created_at', 'desc');

        if ($userName) {
            $dispatchQuery->where('users.name', 'like', "%{$userName}%");
        }
        if ($docName) {
            $dispatchQuery->where('notifications.data', 'like', "%{$docName}%");
        }
        if ($startDate) {
            $dispatchQuery->whereDate('notifications.created_at', '>=', $startDate);
        }
        if ($endDate) {
            $dispatchQuery->whereDate('notifications.created_at', '<=', $endDate);
        }

        $dispatchLogs = $dispatchQuery->paginate(15, ['*'], 'dispatch_page')->withQueryString();

        // JSON verisini Blade'de kolay okumak için çözüyoruz
        $dispatchLogs->getCollection()->transform(function ($log) {
            $log->data = json_decode($log->data, true);
            return $log;
        });

        return view('system.logs.index', compact('auditLogs', 'dispatchLogs', 'userName', 'docName', 'startDate', 'endDate'));
    }
}
