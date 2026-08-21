<?php

namespace App\Http\Controllers;

use App\Services\DepartmentSyncService;
use App\Services\UserSyncService;
use Illuminate\Http\Request;
use Exception;
use Illuminate\Support\Facades\Log;

class UserSyncController extends Controller
{
    protected DepartmentSyncService $departmentSyncService;
    protected UserSyncService $userSyncService;

    // Servisleri Dependency Injection ile içeri alıyoruz
    public function __construct(DepartmentSyncService $departmentSyncService, UserSyncService $userSyncService)
    {
        $this->departmentSyncService = $departmentSyncService;
        $this->userSyncService = $userSyncService;
    }

    /**
     * Butona tıklandığında tetiklenecek metod
     */
    public function syncFromMys(Request $request)
    {
        // Yetki kontrolü eklemeyi unutma (örn: Spatie permission kullanıyorsan)
        // $this->authorize('sync_users'); 

        try {
            // Aşama 1: Önce Departmanlar
            $deptCount = $this->departmentSyncService->sync();

            // Aşama 2: Sonra Kullanıcılar
            $userCount = $this->userSyncService->sync();

            return redirect()->back()->with('success', "Senkronizasyon başarılı! {$deptCount} departman ve {$userCount} kullanıcı MYS ile eşitlendi.");
        } catch (Exception $e) {
            // Hata loglaması yapalım ki olası bir kesintide sebebini görebilesin
            Log::error('MYS Senkronizasyon Hatası: ' . $e->getMessage());

            return redirect()->back()->with('error', 'Senkronizasyon sırasında bir hata oluştu: ' . $e->getMessage());
        }
    }
    /**
     * Sadece Departmanları Senkronize Eder (/settings/permissions sayfası için)
     */
    public function syncDepartmentsOnly()
    {
        try {
            $deptCount = $this->departmentSyncService->sync();
            return redirect()->back()->with('success', "MYS Senkronizasyonu Başarılı! Toplam {$deptCount} departman güncellendi.");
        } catch (Exception $e) {
            Log::error('Departman Senkronizasyon Hatası: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Senkronizasyon sırasında bir hata oluştu: ' . $e->getMessage());
        }
    }
}
