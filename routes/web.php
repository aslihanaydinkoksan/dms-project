<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FolderController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\DocumentApprovalController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PermissionSettingsController;
use App\Http\Controllers\SystemSettingsController;
use \App\Http\Controllers\MailSettingsController;

// --------------------------------------------------------------------------
// Ana Rota (Kök Dizin)
// --------------------------------------------------------------------------
Route::get('/', function () {
    // Kullanıcı giriş yapmışsa Gösterge Paneline, yapmamışsa Login'e yönlendir.
    return redirect()->route(Auth::check() ? 'dashboard' : 'login');
});
Route::get('/language/{locale}', [\App\Http\Controllers\LanguageController::class, 'switch'])->name('language.switch');

// SADECE YÖNETİCİLER (Super Admin & Admin)
// --------------------------------------------------------------------------
// Spatie'nin 'role' middleware'i ile rotaları güvenlik altına alıyoruz
Route::middleware(['role:Super Admin|Admin'])->group(function () {

    // Sistem Ayarları (Legal DMS) Rotaları
    Route::get('/settings/permissions', [PermissionSettingsController::class, 'index'])->name('settings.permissions');
    Route::post('/settings/permissions', [PermissionSettingsController::class, 'update'])->name('settings.permissions.update');
    Route::patch('/settings/departments/{department}/toggle-approval', [PermissionSettingsController::class, 'toggleDepartmentApproval'])->name('settings.departments.toggle-approval');
    Route::post('/settings/roles', [PermissionSettingsController::class, 'storeRole'])->name('settings.roles.store');
    // Mevcut rol rotalarının yanına ekle:
    Route::post('/settings/document-types', [PermissionSettingsController::class, 'storeDocumentType'])->name('settings.document-types.store');
    Route::put('/settings/document-types/{documentType}', [PermissionSettingsController::class, 'updateDocumentType'])->name('settings.document-types.update');
    Route::delete('/settings/document-types/{documentType}', [PermissionSettingsController::class, 'destroyDocumentType'])->name('settings.document-types.destroy');

    Route::get('/settings/notifications', [SystemSettingsController::class, 'notificationSettings'])->name('settings.notifications');
    Route::post('/settings/notifications', [SystemSettingsController::class, 'updateNotificationSettings'])->name('settings.notifications.update');
    Route::put('/settings/roles/{role}', [PermissionSettingsController::class, 'updateRole'])->name('settings.roles.update');
    Route::delete('/settings/roles/{role}', [PermissionSettingsController::class, 'destroyRole'])->name('settings.roles.destroy');
    Route::post('/settings/departments', [PermissionSettingsController::class, 'storeDepartment'])->name('settings.departments.store');
    Route::put('/settings/departments/{department}', [PermissionSettingsController::class, 'updateDepartment'])->name('settings.departments.update');
    Route::delete('/settings/departments/{department}', [PermissionSettingsController::class, 'destroyDepartment'])->name('settings.departments.destroy');

    Route::get('/settings/folders/{folder}/permissions', [\App\Http\Controllers\FolderPermissionController::class, 'getPermissions'])->name('settings.folders.permissions.get');
    Route::post('/settings/folders/{folder}/permissions', [\App\Http\Controllers\FolderPermissionController::class, 'sync'])->name('settings.folders.permissions.sync');
    // --- KLASÖR BAZLI ÖZEL YETKİLER (ACL) ---
    Route::post('/folders/{folder}/permissions', [\App\Http\Controllers\FolderPermissionController::class, 'store'])->name('folders.permissions.store');
    Route::delete('/folders/{folder}/permissions/{user}', [\App\Http\Controllers\FolderPermissionController::class, 'destroy'])->name('folders.permissions.destroy');
    // SİSTEM AYARLARI VE MAİL YÖNETİMİ
    Route::get('/settings/mail', [MailSettingsController::class, 'index'])->name('settings.mail');
    Route::match(['post', 'put'], '/settings/mail', [MailSettingsController::class, 'update'])->name('settings.mail.update');
    // GİZLİLİK SEVİYELERİ (DİNAMİK YÖNETİM)
    Route::post('/settings/privacy-levels', [PermissionSettingsController::class, 'storePrivacyLevel'])->name('settings.privacy-levels.store');
    Route::delete('/settings/privacy-levels/{key}', [PermissionSettingsController::class, 'destroyPrivacyLevel'])->name('settings.privacy-levels.destroy');
});

// --------------------------------------------------------------------------
// Ziyaretçi Rotaları (Sadece giriş yapmamışlar görebilir)
// --------------------------------------------------------------------------
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.post');
    Route::get('forgot-password', [\App\Http\Controllers\Auth\PasswordResetController::class, 'showLinkRequestForm'])->name('password.request');
    Route::post('forgot-password', [\App\Http\Controllers\Auth\PasswordResetController::class, 'sendResetLinkEmail'])->name('password.email');
    Route::get('reset-password/{token}', [\App\Http\Controllers\Auth\PasswordResetController::class, 'showResetForm'])->name('password.reset');
    Route::post('reset-password', [\App\Http\Controllers\Auth\PasswordResetController::class, 'reset'])->name('password.update');
});

// --------------------------------------------------------------------------
// Kimlik Doğrulaması Gerektiren Rotalar (Sisteme giriş yapmış kullanıcılar)
// --------------------------------------------------------------------------
Route::middleware(['auth'])->group(function () {

    // Çıkış Yap
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Favoriler
    Route::get('/favorites/sidebar', [\App\Http\Controllers\FavoriteController::class, 'sidebar'])->name('favorites.sidebar');
    Route::post('/documents/{document}/favorite-note', [\App\Http\Controllers\FavoriteController::class, 'updateNote'])->name('documents.favorite.note');

    // Klasörler
    Route::get('/folders', [FolderController::class, 'index'])->name('folders.index');
    Route::post('/folders', [FolderController::class, 'store'])->name('folders.store');
    Route::get('/folders/{folder}', [FolderController::class, 'show'])->name('folders.show');
    Route::get('/folders/{folder}/edit', [FolderController::class, 'edit'])->name('folders.edit');
    Route::put('/folders/{folder}', [FolderController::class, 'update'])->name('folders.update');
    Route::delete('/folders/{folder}', [FolderController::class, 'destroy'])->name('folders.destroy');

    // --- KULLANICI YÖNETİMİ (GÜVENLİK KİLİDİ EKLENDİ) ---
    // Artık sadece "user.manage" yetkisine sahip olanlar bu rotalara erişebilir.
    // URL'den manuel yazsalar bile 403 Forbidden hatası alacaklar!
    Route::resource('users', UserController::class)->middleware('can:user.manage');

    // Kullanıcı profil düzenleme
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::post('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/vault-password', [ProfileController::class, 'updateVaultPassword'])->name('profile.vault-password.update');
    Route::delete('/profile/vault-password', [ProfileController::class, 'resetVaultPassword'])->name('profile.vault-password.destroy');
    Route::get('/profile/show/{id?}', [App\Http\Controllers\ProfileController::class, 'show'])->name('profile.show');

    // Doküman Yönetimi (CRUD ve Listeleme)
    // --- ÇOK GİZLİ KASA KİLİDİ (SUDO MODE) ROTALARI ---
    Route::get('/documents/{document}/vault', [\App\Http\Controllers\SudoController::class, 'showVault'])->name('documents.vault');
    Route::post('/documents/{document}/vault', [\App\Http\Controllers\SudoController::class, 'unlockVault'])->name('documents.vault.unlock');
    Route::get('/documents', [DocumentController::class, 'index'])->name('documents.index');
    Route::get('/documents/create', [DocumentController::class, 'create'])->name('documents.create');
    Route::post('/documents', [DocumentController::class, 'store'])->name('documents.store');
    Route::get('/documents/{document}/edit', [App\Http\Controllers\DocumentController::class, 'edit'])->name('documents.edit');
    Route::put('/documents/{document}', [App\Http\Controllers\DocumentController::class, 'update'])->name('documents.update');
    Route::delete('/documents/{document}', [DocumentController::class, 'destroy'])->name('documents.destroy');
    Route::middleware(['sensitive'])->group(function () {
        Route::get('/documents/{document}', [DocumentController::class, 'show'])->name('documents.show');
        Route::get('/documents/download/{document}', [DocumentController::class, 'download'])->name('documents.download');
    });
    Route::post('/documents/{document}/log-time', [DocumentController::class, 'logTime'])->name('documents.log-time');
    Route::post('/documents/{document}/assign-physical', [DocumentController::class, 'assignPhysicalCopy'])->name('documents.assign-physical');
    Route::post('/documents/{document}/confirm-physical', [DocumentController::class, 'confirmPhysicalReceipt'])->name('documents.confirm-physical');
    Route::post('/documents/{document}/permissions', [\App\Http\Controllers\DocumentPermissionController::class, 'store'])->name('documents.permissions.store');
    Route::delete('/documents/{document}/permissions/{user}', [\App\Http\Controllers\DocumentPermissionController::class, 'destroy'])->name('documents.permissions.destroy');

    // Check-in / Check-out (Kilitleme ve Versiyonlama)
    Route::post('/documents/{document}/checkout', [DocumentController::class, 'checkout'])->name('documents.checkout');
    Route::post('/documents/{document}/checkin', [DocumentController::class, 'checkin'])->name('documents.checkin');
    Route::post('/documents/{document}/force-unlock', [DocumentController::class, 'forceUnlock'])->name('documents.force-unlock');

    // Onay Akışı (Workflow) Rotaları
    Route::post('/documents/{document}/start-workflow', [DocumentApprovalController::class, 'start'])->name('documents.workflow.start');
    Route::post('/documents/{document}/approve', [DocumentApprovalController::class, 'approve'])->name('documents.approve');
    Route::post('/documents/{document}/reject', [DocumentApprovalController::class, 'reject'])->name('documents.reject');

    //Favoriler
    Route::post('/documents/{document}/favorite', [\App\Http\Controllers\FavoriteController::class, 'toggle'])->name('documents.favorite');

    // --- DİNAMİK FORM (API) ROTALARI ---
    Route::get('/api/document-types/{id}/fields', [DocumentController::class, 'getCustomFields'])->name('api.document-types.fields');
    // --- BİLDİRİM VE TERCİH ROTALARI ---
    Route::get('/profile/notifications', [ProfileController::class, 'notificationSettings'])->name('profile.notifications');
    Route::post('/profile/notifications', [ProfileController::class, 'updateNotificationSettings'])->name('profile.notifications.update');
    Route::post('/notifications/mark-all-read', [ProfileController::class, 'markAllNotificationsRead'])->name('notifications.mark-all-read');
    Route::get('/notifications/history', [ProfileController::class, 'notificationsHistory'])->name('notifications.history');
    Route::get('/notifications/check', [ProfileController::class, 'checkUnreadNotifications'])->name('notifications.check');

    // --- VEKALET ROTALARI ---
    Route::get('/profile/delegations', [\App\Http\Controllers\DelegationController::class, 'index'])->name('profile.delegations');
    Route::post('/profile/delegations', [\App\Http\Controllers\DelegationController::class, 'store'])->name('profile.delegations.store');
    Route::delete('/profile/delegations/{delegation}', [\App\Http\Controllers\DelegationController::class, 'destroy'])->name('profile.delegations.destroy');

    // RAPOR YÖNETİMİ
    Route::get('/reports', [\App\Http\Controllers\ReportEngineController::class, 'index'])->name('reports.index');
    Route::post('/reports/store', [\App\Http\Controllers\ReportEngineController::class, 'store'])->name('reports.store');
});
