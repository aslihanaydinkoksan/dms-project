<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FolderController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\DocumentApprovalController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PermissionSettingsController;
use App\Http\Controllers\SystemSettingsController;
use App\Http\Controllers\MailSettingsController;
use App\Http\Controllers\LanguageController;
use App\Http\Controllers\FavoriteController;
use App\Http\Controllers\FolderPermissionController;
use App\Http\Controllers\SudoController;
use App\Http\Controllers\DocumentPermissionController;
use App\Http\Controllers\DelegationController;
use App\Http\Controllers\ReportEngineController;
use App\Http\Controllers\Auth\PasswordResetController;
use Illuminate\Support\Facades\Http;
use App\Http\Controllers\SsoController;

Route::get('/debug-google-models', function () {
    $apiKey = config('ai.providers.google.api_key');

    // app()->isLocal() kontrolü ile sadece yerel ortamda SSL doğrulamasını esnetiyoruz.
    $response = Illuminate\Support\Facades\Http::withOptions([
        'verify' => !app()->isLocal()
    ])->get("https://generativelanguage.googleapis.com/v1/models?key={$apiKey}");

    return $response->json();
});

// Sadece Super Adminlerin erişebileceği Kara Kutu Rotası
Route::middleware(['auth', 'role:Super Admin'])->group(function () {
    Route::get('/system-logs', [\App\Http\Controllers\SystemLogController::class, 'index'])->name('system.logs.index');
    // Ana Ekran Rotası
    Route::get('/settings/user-permissions-explorer', [\App\Http\Controllers\Settings\UserPermissionExplorerController::class, 'index'])
        ->name('settings.permissions.explorer');

    // AJAX Veri Çekme Rotası
    Route::get('/settings/users/{user}/permission-details', [\App\Http\Controllers\Settings\UserPermissionExplorerController::class, 'getUserDetails'])
        ->name('settings.users.permission_details');
});

// ==========================================================================
// 1. ZİYARETÇİ & SİSTEM GİRİŞ ROTALARI (GUEST)
// ==========================================================================

// KYS'nin atacağı her türlü (GET/POST/OPTIONS) isteği yakalayan "Sünger" Rota
Route::any('/', function (\Illuminate\Http\Request $request) {
    if (Auth::check()) {
        if (Auth::user()->hasRole('Yönetim Kurulu')) {
            return redirect()->route('executive.cockpit');
        }
        return redirect()->route('dashboard');
    }

    // KYS ekibi buraya POST ile token/veri gönderiyorsa, 
    // bu verileri alıp sso.login rotasına GET parametresi olarak fırlatıyoruz!
    if ($request->isMethod('post') || $request->all()) {
        return redirect()->route('sso.login', $request->all());
    }

    // Normal gelenleri login'e yolla
    return redirect()->route('login');
});

Route::get('/language/{locale}', [LanguageController::class, 'switch'])->name('language.switch');

if (app()->environment('local')) {
    // LOKAL ORTAM
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.post');

    Route::get('forgot-password', [PasswordResetController::class, 'showLinkRequestForm'])->name('password.request');
    Route::post('forgot-password', [PasswordResetController::class, 'sendResetLinkEmail'])->name('password.email');
    Route::get('reset-password/{token}', [PasswordResetController::class, 'showResetForm'])->name('password.reset');
    Route::post('reset-password', [PasswordResetController::class, 'reset'])->name('password.update');
} else {
    // CANLI ORTAM (Production)
    Route::any('/login', function (\Illuminate\Http\Request $request) {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        // KYS /login adresine POST atarsa, yine veriyi alıp SSO'ya yönlendir
        if ($request->isMethod('post')) {
            return redirect()->route('sso.login', $request->all());
        }

        return redirect('https://kys.koksan.com/merkezi_yonetim_sistemi');
    })->name('login');
}

Route::post('/logout', function () {
    Auth::logout();

    if (app()->environment('local')) {
        return redirect()->route('login');
    }

    return redirect('https://kys.koksan.com/merkezi_yonetim_sistemi');
})->name('logout');

// DMS SSO İÇİN YENİ ROTALAR (Dokunmadık)
Route::controller(\App\Http\Controllers\Auth\SsoController::class)->prefix('sso')->name('sso.')->group(function () {
    Route::get('/login', 'login')->name('login');
    Route::get('/basvuru', 'basvuruFormu')->name('basvuru_formu');
    Route::post('/basvuru', 'basvuruKaydet')->name('basvuru_kaydet');
    Route::get('/onay-bekliyor', 'onayBekliyor')->name('onay_bekliyor');
});
// ==========================================================================


// ==========================================================================
// 2. OTURUM AÇMIŞ KULLANICI ROTALARI (AUTH)
// ==========================================================================
Route::middleware(['auth'])->group(function () {

    // Güvenli Belge İndirme Rotası
    Route::get('/tasks/attachments/{attachment}/download', [\App\Http\Controllers\TaskController::class, 'downloadAttachment'])
        ->name('tasks.attachments.download')
        ->middleware('auth');

    Route::get('/notifications/{id}/read', [App\Http\Controllers\ProfileController::class, 'readAndRedirect'])->name('notifications.read');

    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // YENİ: Dashboard'a Menü Kalkanı eklendi
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::post('/assistant/chat', [\App\Http\Controllers\AssistantController::class, 'chat'])->name('assistant.chat');

    // --- AKILLI ASİSTAN (BOT) YÖNETİMİ ---
    Route::prefix('settings')->name('settings.')->group(function () {
        Route::get('/intents', [\App\Http\Controllers\BotIntentController::class, 'index'])->name('intents.index');
        Route::post('/intents', [\App\Http\Controllers\BotIntentController::class, 'store'])->name('intents.store');
        Route::delete('/intents/{intent}', [\App\Http\Controllers\BotIntentController::class, 'destroy'])->name('intents.destroy');
    });

    // YENİ: Analitik sayfasına Menü Kalkanı eklendi
    Route::get('/analytics', [\App\Http\Controllers\AnalyticsController::class, 'index'])->name('analytics.index')->middleware('can:menu.analytics');
    Route::get('/analytics', [\App\Http\Controllers\AnalyticsController::class, 'index'])->name('analytics.index');
    Route::post('/analytics/generate', [\App\Http\Controllers\AnalyticsController::class, 'getChartData'])->name('analytics.generate');

    // --- KULLANICI & PROFİL YÖNETİMİ ---
    Route::prefix('profile')->name('profile.')->group(function () {
        Route::get('/', function () {
            // redirect()->away() dış linklere güvenli ve hızlı yönlendirme (302) yapar
            return redirect()->away('https://kys.koksan.com/merkezi_yonetim_sistemi/profile');
        })->name('edit');
        Route::post('/', [ProfileController::class, 'update'])->name('update');
        Route::get('/show/{id?}', [ProfileController::class, 'show'])->name('show');
        Route::get('/vault-security', [ProfileController::class, 'edit'])->name('vault.security');
        Route::put('/vault-password', [ProfileController::class, 'updateVaultPassword'])->name('vault-password.update');
        Route::delete('/vault-password', [ProfileController::class, 'resetVaultPassword'])->name('vault-password.destroy');

        Route::get('/notifications', [ProfileController::class, 'notificationSettings'])->name('notifications');
        Route::post('/notifications', [ProfileController::class, 'updateNotificationSettings'])->name('notifications.update');

        Route::get('/delegations', [DelegationController::class, 'index'])->name('delegations');
        Route::post('/delegations', [DelegationController::class, 'store'])->name('delegations.store');
        Route::delete('/delegations/{delegation}', [DelegationController::class, 'destroy'])->name('delegations.destroy');
    });

    // --- BİLDİRİMLER ---
    Route::prefix('notifications')->name('notifications.')->group(function () {
        Route::post('/mark-all-read', [ProfileController::class, 'markAllNotificationsRead'])->name('mark-all-read');
        Route::post('/clear-all', [ProfileController::class, 'clearAllNotifications'])->name('clear-all');
        Route::delete('/{id}/delete', [ProfileController::class, 'deleteNotification'])->name('destroy');
        Route::get('/history', [ProfileController::class, 'notificationsHistory'])->name('history');
        Route::get('/check', [ProfileController::class, 'checkUnreadNotifications'])->name('check');
    });

    // --- KLASÖRLER (FOLDERS) ---
    // YENİ: Folders resource rotasına Menü Kalkanı eklendi
    Route::resource('folders', FolderController::class)->middleware('can:menu.folders');
    // Klasör İçi Özel ACL (Normal Kullanıcılar için, Policy ile korunur)
    Route::post('/folders/{folder}/permissions', [FolderPermissionController::class, 'store'])->name('folders.permissions.store');
    Route::delete('/folders/{folder}/permissions/{user}', [FolderPermissionController::class, 'destroy'])->name('folders.permissions.destroy');

    // --- FAVORİLER ---
    Route::get('/favorites/sidebar', [FavoriteController::class, 'sidebar'])->name('favorites.sidebar');

    // --- API DİNAMİK FORM ALANLARI ---
    Route::get('/api/document-types/{id}/fields', [DocumentController::class, 'getCustomFields'])->name('api.document-types.fields');

    // --- RAPORLAR ---
    // YENİ: Raporlar sayfasına Menü Kalkanı eklendi
    Route::get('/reports', [ReportEngineController::class, 'index'])->name('reports.index')->middleware('can:menu.reports');
    Route::post('/reports/store', [ReportEngineController::class, 'store'])->name('reports.store');

    // ---  FİZİKSEL EVRAK YÖNETİMİ ---
    Route::post('/documents/{document}/physical', [\App\Http\Controllers\DocumentPhysicalController::class, 'store'])->name('physical.store');
    Route::put('/physical-movements/{movement}', [\App\Http\Controllers\DocumentPhysicalController::class, 'update'])->name('physical.update');

    // --- DOKÜMAN YÖNETİMİ ---
    Route::prefix('documents')->name('documents.')->group(function () {
        // YENİ: Sadece documents.index sayfasına Menü Kalkanı eklendi (Direkt link erişimlerini bozmamak için)
        Route::get('/', [DocumentController::class, 'index'])->name('index')->middleware('can:menu.documents');
        Route::get('/create', [DocumentController::class, 'create'])->name('create');
        Route::post('/', [DocumentController::class, 'store'])->name('store');
        Route::get('/{document}/edit', [DocumentController::class, 'edit'])->name('edit');
        Route::put('/{document}', [DocumentController::class, 'update'])->name('update');
        Route::delete('/{document}', [DocumentController::class, 'destroy'])->name('destroy');
        Route::patch('/{document}/move', [DocumentController::class, 'move'])->name('move');

        // Çok Gizli Kasa (Vault)
        Route::get('/{document}/vault', [SudoController::class, 'showVault'])->name('vault');
        Route::post('/{document}/vault', [SudoController::class, 'unlockVault'])->name('vault.unlock');

        // Versiyonlama ve Kilitleme
        Route::post('/{document}/checkout', [DocumentController::class, 'checkout'])->name('checkout');
        Route::post('/{document}/checkin', [DocumentController::class, 'checkin'])->name('checkin');
        Route::post('/{document}/force-unlock', [DocumentController::class, 'forceUnlock'])->name('force-unlock');

        // İş Akışı ve Onay
        Route::post('/{document}/start-workflow', [DocumentApprovalController::class, 'start'])->name('workflow.start');
        Route::post('/{document}/approve', [DocumentApprovalController::class, 'approve'])->name('approve');
        Route::post('/{document}/reject', [DocumentApprovalController::class, 'reject'])->name('reject');

        // Fiziksel Arşiv
        Route::post('/{document}/assign-physical', [DocumentController::class, 'assignPhysicalCopy'])->name('assign-physical');
        Route::post('/{document}/confirm-physical', [DocumentController::class, 'confirmPhysicalReceipt'])->name('confirm-physical');

        // Belge Özel Yetkileri
        Route::post('/{document}/permissions', [DocumentPermissionController::class, 'store'])->name('permissions.store');
        Route::delete('/{document}/permissions/{user}', [DocumentPermissionController::class, 'destroy'])->name('permissions.destroy');

        // Favori ve Log
        Route::post('/{document}/favorite', [FavoriteController::class, 'toggle'])->name('favorite');
        Route::post('/{document}/favorite-note', [FavoriteController::class, 'updateNote'])->name('favorite.note');
        Route::post('/{document}/log-time', [DocumentController::class, 'logTime'])->name('log-time');

        // Hassas Görüntüleme (Middleware Korumalı)
        Route::middleware(['sensitive'])->group(function () {
            Route::get('/{document}', [DocumentController::class, 'show'])->name('show');
            Route::get('/download/{document}', [DocumentController::class, 'download'])->name('download');
        });
        // Dış Paydaş Paylaşım Rotası
        Route::post('/{document}/share', [DocumentController::class, 'shareExternal'])->name('share');

        //Doküman Yapay Zeka Chat Rotası 
        Route::post('/chat', [\App\Http\Controllers\AI\DocumentChatController::class, 'chat'])->name('chat');
    });

    // --- BELGE EKLERİ (ATTACHMENTS) ROTALARI ---
    Route::post('/documents/{document}/attachments', [\App\Http\Controllers\DocumentAttachmentController::class, 'store'])->name('document-attachments.store');
    Route::get('/document-attachments/{attachment}/download', [\App\Http\Controllers\DocumentAttachmentController::class, 'download'])->name('document-attachments.download');
    Route::delete('/document-attachments/{attachment}', [\App\Http\Controllers\DocumentAttachmentController::class, 'destroy'])->name('document-attachments.destroy');
    // ==========================================================================
    // 3. SİSTEM YÖNETİCİSİ & KULLANICI ROTALARI
    // ==========================================================================

    // YENİ: Kullanıcılar menüsü kalkanı ('can:menu.users') eklendi. (user.manage yetkisine ek olarak)
    Route::get('/users/onay-bekleyenler', [UserController::class, 'onayBekleyenler'])
        ->name('users.onay_bekleyenler')
        ->middleware(['can:user.manage', 'can:menu.users']);

    Route::post('/users/{id}/onayla', [UserController::class, 'basvuruOnayla'])
        ->name('r_yonetim_basvuru_onayla')
        ->middleware(['can:user.manage', 'can:menu.users']);
    Route::delete('/users/{id}/reddet', [UserController::class, 'basvuruReddet'])
        ->name('r_yonetim_basvuru_reddet')
        ->middleware(['can:user.manage', 'can:menu.users']);
    Route::resource('users', UserController::class)->middleware(['can:user.manage', 'can:menu.users']);

    // ==========================================================================
    // --- KÖKSAN BPM: SÜREÇ TASARIM MERKEZİ ---
    // ==========================================================================
    Route::middleware(['can:menu.process_templates'])->group(function () {
        Route::resource('process-templates', \App\Http\Controllers\ProcessTemplateController::class);
        Route::post('process-templates/{template}/stages', [\App\Http\Controllers\ProcessStageController::class, 'store'])->name('process-stages.store');
        Route::patch('process-templates/{template}/stages/reorder', [\App\Http\Controllers\ProcessStageController::class, 'updateOrder'])->name('process-stages.reorder');
        Route::delete('process-stages/{stage}', [\App\Http\Controllers\ProcessStageController::class, 'destroy'])->name('process-stages.destroy');
    });

    // ==========================================================================
    // --- KÖKSAN BPM: GÖREV (TASK) YÖNETİMİ VE AD-HOC EKİPLER (STANDART PERSONEL) ---
    // ==========================================================================
    Route::middleware(['can:menu.tasks'])->group(function () {
        Route::get('/api/users/search', [\App\Http\Controllers\TaskController::class, 'searchUsers'])->name('api.users.search');
        Route::get('/api/process-templates/{id}/fields', [\App\Http\Controllers\TaskController::class, 'getTemplateFields'])->name('api.process-templates.fields');
        Route::patch('tasks/{task}/stage', [\App\Http\Controllers\TaskController::class, 'updateStage'])->name('tasks.updateStage');
        Route::post('tasks/{task}/request-closure', [\App\Http\Controllers\TaskClosureController::class, 'requestClosure'])->name('tasks.request-closure');
        Route::post('tasks/{task}/approve-closure', [\App\Http\Controllers\TaskClosureController::class, 'approveClosure'])->name('tasks.approve-closure');
        Route::post('tasks/{task}/reject-closure', [\App\Http\Controllers\TaskClosureController::class, 'rejectClosure'])->name('tasks.reject-closure');
        Route::get('tasks/{task}/closure-document', [\App\Http\Controllers\TaskClosureController::class, 'downloadDocument'])->name('tasks.closure-document');
        Route::resource('tasks', \App\Http\Controllers\TaskController::class);
        Route::post('tasks/{task}/reopen', [\App\Http\Controllers\TaskClosureController::class, 'reopenTask'])
            ->name('tasks.reopen')
            ->middleware('can:menu.tasks');
    });

    // İŞ ARŞİVİ ROTASI
    Route::get('tasks/archive/completed', [\App\Http\Controllers\TaskController::class, 'archive'])
        ->name('tasks.archive')
        ->middleware('can:menu.tasks_archive');

    // ==========================================================================
    // --- SİSTEM AYARLARI ROTALARI ---
    // ==========================================================================
    // YENİ: Ayarlar ana menüsü için 'can:menu.settings' eklendi.
    Route::middleware(['role:Super Admin|Admin', 'can:menu.settings'])->prefix('settings')->name('settings.')->group(function () {

        // İzinler, Roller ve Gizlilik
        Route::get('/permissions', [PermissionSettingsController::class, 'index'])->name('permissions');
        Route::post('/permissions', [PermissionSettingsController::class, 'update'])->name('permissions.update');
        Route::post('/roles', [PermissionSettingsController::class, 'storeRole'])->name('roles.store');
        Route::put('/roles/{role}', [PermissionSettingsController::class, 'updateRole'])->name('roles.update');
        Route::delete('/roles/{role}', [PermissionSettingsController::class, 'destroyRole'])->name('roles.destroy');
        Route::post('/privacy-levels', [PermissionSettingsController::class, 'storePrivacyLevel'])->name('privacy-levels.store');
        Route::delete('/privacy-levels/{key}', [PermissionSettingsController::class, 'destroyPrivacyLevel'])->name('privacy-levels.destroy');

        // Klasör AJAX Matrisi 
        Route::get('/folders/{folder}/permissions', [FolderPermissionController::class, 'getPermissions'])->name('folders.permissions.get');
        Route::post('/folders/{folder}/permissions', [FolderPermissionController::class, 'sync'])->name('folders.permissions.sync');

        // Departmanlar
        Route::post('/departments', [PermissionSettingsController::class, 'storeDepartment'])->name('departments.store');
        Route::put('/departments/{department}', [PermissionSettingsController::class, 'updateDepartment'])->name('departments.update');
        Route::delete('/departments/{department}', [PermissionSettingsController::class, 'destroyDepartment'])->name('departments.destroy');
        Route::patch('/departments/{department}/toggle-approval', [PermissionSettingsController::class, 'toggleDepartmentApproval'])->name('departments.toggle-approval');

        // Doküman Tipleri
        Route::post('/document-types', [PermissionSettingsController::class, 'storeDocumentType'])->name('document-types.store');
        Route::put('/document-types/{documentType}', [PermissionSettingsController::class, 'updateDocumentType'])->name('document-types.update');
        Route::delete('/document-types/{documentType}', [PermissionSettingsController::class, 'destroyDocumentType'])->name('document-types.destroy');

        // Sistem Ayarları
        Route::get('/notifications', [SystemSettingsController::class, 'notificationSettings'])->name('notifications');
        Route::post('/notifications', [SystemSettingsController::class, 'updateNotificationSettings'])->name('notifications.update');
        Route::get('/mail', [MailSettingsController::class, 'index'])->name('mail');
        Route::match(['post', 'put'], '/mail', [MailSettingsController::class, 'update'])->name('mail.update');

        // ZORUNLU KULLANICI GRUPLARI
        Route::resource('user-groups', \App\Http\Controllers\UserGroupController::class)->except(['create', 'show', 'edit'])->middleware('can:menu.user_groups');
        Route::post('user-groups/{userGroup}/sync', [\App\Http\Controllers\UserGroupController::class, 'syncMembers'])->name('user-groups.sync')->middleware('can:menu.user_groups');
    });
});


// Middleware ile hem giriş yapılmış olması hem de yetki kontrolü sağlanır.
Route::middleware(['auth', 'can:menu.executive_cockpit'])->group(function () {
    Route::get('/executive-cockpit', [\App\Http\Controllers\ExecutiveCockpitController::class, 'index'])->name('executive.cockpit');
});
// ==========================================================================
// KÖKSAN SECURE GUEST TOKEN - DIŞ PAYLAŞIM ROTALARI
// ==========================================================================
Route::get('/shared-document/{token}', [\App\Http\Controllers\SharedDocumentController::class, 'show'])
    ->name('shared.document.show');
// YENİ EKLENEN: Sadece token ile çalışan dış indirme tüneli
Route::get('/shared-document/{token}/download', [\App\Http\Controllers\SharedDocumentController::class, 'download'])
    ->name('shared.document.download');
Route::get('/shared-document/{token}/attachment/{attachment}/download', [\App\Http\Controllers\SharedDocumentController::class, 'downloadAttachment'])
    ->name('shared.document.download-attachment');
