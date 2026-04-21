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

// ==========================================================================
// 1. ZİYARETÇİ & SİSTEM GİRİŞ ROTALARI (GUEST)
// ==========================================================================
Route::get('/', fn() => redirect()->route(Auth::check() ? 'dashboard' : 'login'));
Route::get('/language/{locale}', [LanguageController::class, 'switch'])->name('language.switch');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.post');
    Route::get('forgot-password', [PasswordResetController::class, 'showLinkRequestForm'])->name('password.request');
    Route::post('forgot-password', [PasswordResetController::class, 'sendResetLinkEmail'])->name('password.email');
    Route::get('reset-password/{token}', [PasswordResetController::class, 'showResetForm'])->name('password.reset');
    Route::post('reset-password', [PasswordResetController::class, 'reset'])->name('password.update');
});

// ==========================================================================
// 2. OTURUM AÇMIŞ KULLANICI ROTALARI (AUTH)
// ==========================================================================
Route::middleware(['auth'])->group(function () {

    Route::get('/notifications/{id}/read', [App\Http\Controllers\ProfileController::class, 'readAndRedirect'])->name('notifications.read');

    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::post('/assistant/chat', [\App\Http\Controllers\AssistantController::class, 'chat'])->name('assistant.chat');
    // --- AKILLI ASİSTAN (BOT) YÖNETİMİ ---
    Route::prefix('settings')->name('settings.')->group(function () {
        Route::get('/intents', [\App\Http\Controllers\BotIntentController::class, 'index'])->name('intents.index');
        Route::post('/intents', [\App\Http\Controllers\BotIntentController::class, 'store'])->name('intents.store');
        Route::delete('/intents/{intent}', [\App\Http\Controllers\BotIntentController::class, 'destroy'])->name('intents.destroy');
    });

    // --- KULLANICI & PROFİL YÖNETİMİ ---
    Route::prefix('profile')->name('profile.')->group(function () {
        Route::get('/', [ProfileController::class, 'edit'])->name('edit');
        Route::post('/', [ProfileController::class, 'update'])->name('update');
        Route::get('/show/{id?}', [ProfileController::class, 'show'])->name('show');

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
    Route::resource('folders', FolderController::class);
    // Klasör İçi Özel ACL (Normal Kullanıcılar için, Policy ile korunur)
    Route::post('/folders/{folder}/permissions', [FolderPermissionController::class, 'store'])->name('folders.permissions.store');
    Route::delete('/folders/{folder}/permissions/{user}', [FolderPermissionController::class, 'destroy'])->name('folders.permissions.destroy');

    // --- FAVORİLER ---
    Route::get('/favorites/sidebar', [FavoriteController::class, 'sidebar'])->name('favorites.sidebar');

    // --- API DİNAMİK FORM ALANLARI ---
    Route::get('/api/document-types/{id}/fields', [DocumentController::class, 'getCustomFields'])->name('api.document-types.fields');

    // --- RAPORLAR ---
    Route::get('/reports', [ReportEngineController::class, 'index'])->name('reports.index');
    Route::post('/reports/store', [ReportEngineController::class, 'store'])->name('reports.store');

    // ---  FİZİKSEL EVRAK YÖNETİMİ ---
    Route::post('/documents/{document}/physical', [\App\Http\Controllers\DocumentPhysicalController::class, 'store'])->name('physical.store');
    Route::put('/physical-movements/{movement}', [\App\Http\Controllers\DocumentPhysicalController::class, 'update'])->name('physical.update');

    // --- DOKÜMAN YÖNETİMİ ---
    Route::prefix('documents')->name('documents.')->group(function () {
        Route::get('/', [DocumentController::class, 'index'])->name('index');
        Route::get('/create', [DocumentController::class, 'create'])->name('create');
        Route::post('/', [DocumentController::class, 'store'])->name('store');
        Route::get('/{document}/edit', [DocumentController::class, 'edit'])->name('edit');
        Route::put('/{document}', [DocumentController::class, 'update'])->name('update');
        Route::delete('/{document}', [DocumentController::class, 'destroy'])->name('destroy');

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
    });

    // ==========================================================================
    // 3. SİSTEM YÖNETİCİSİ ROTALARI (Sadece Özel Yetki veya Süper Admin/Admin)
    // ==========================================================================

    // Sadece "user.manage" yetkisi olanlar girebilir (Daha önce yaptığımız VIP kalkanı)
    Route::resource('users', UserController::class)->middleware('can:user.manage');

    // Sadece Rolü Super Admin veya Admin Olanlar
    Route::middleware(['role:Super Admin|Admin'])->prefix('settings')->name('settings.')->group(function () {

        // İzinler, Roller ve Gizlilik
        Route::get('/permissions', [PermissionSettingsController::class, 'index'])->name('permissions');
        Route::post('/permissions', [PermissionSettingsController::class, 'update'])->name('permissions.update');
        Route::post('/roles', [PermissionSettingsController::class, 'storeRole'])->name('roles.store');
        Route::put('/roles/{role}', [PermissionSettingsController::class, 'updateRole'])->name('roles.update');
        Route::delete('/roles/{role}', [PermissionSettingsController::class, 'destroyRole'])->name('roles.destroy');
        Route::post('/privacy-levels', [PermissionSettingsController::class, 'storePrivacyLevel'])->name('privacy-levels.store');
        Route::delete('/privacy-levels/{key}', [PermissionSettingsController::class, 'destroyPrivacyLevel'])->name('privacy-levels.destroy');

        // Klasör AJAX Matrisi (Senin 404 Hatanı Veren Kısım Burasıydı, artık güvenli bir rotası var)
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
    });
});
