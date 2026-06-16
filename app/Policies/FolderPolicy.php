<?php

namespace App\Policies;

use App\Models\Folder;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class FolderPolicy
{
    use HandlesAuthorization;

    /**
     * ŞART 1: GLOBAL BYPASS (Kullanıcının Kendisi Super Admin veya Admin ise)
     */
    public function before(User $user, $ability)
    {
        if ($user->hasAnyRole(['Super Admin', 'Admin'])) {
            return true;
        }
    }

    /**
     * ANA ZIRH: Mantıksal OR Pipeline (Zincirleme Doğrulama ve Vekalet Mirası)
     */
    private function checkMatrix(User $user, Folder $folder, string $permissionColumn): bool
    {
        // 0. VEKALET KİMLİK KARTLARI VE RAM HAZIRLIĞI
        $delegatorIds = $user->getActiveDelegatorIds();
        $delegators = collect(); // Standartta boş koleksiyon

        if (!empty($delegatorIds)) {
            // N+1 Koruması: Vekalet verenleri rolleri ve izinleriyle birlikte RAM'e al
            $delegators = User::with('roles', 'permissions')->whereIn('id', $delegatorIds)->get();

            // =========================================================================
            // PIPELINE HALKASI 0: VEKALET MİRASI (DELEGATOR GLOBAL BYPASS)
            // =========================================================================
            $hasAdminOrGodDelegator = $delegators->contains(function (User $d) use ($permissionColumn) {
                $hasGlobalView = false;

                // Sadece görüntüleme (view) isteği varsa, vekalet verenin view_all yetkisine bak
                if ($permissionColumn === 'can_view') {
                    try {
                        $hasGlobalView = $d->hasPermissionTo('document.view_all');
                    } catch (\Exception $e) {
                        // Spatie izni henüz oluşturulmamışsa sessizce yut
                    }
                }

                // Vekalet veren kişi Admin ise (veya klasör görüntüleme için view_all varsa) TRUE dön
                return $d->hasAnyRole(['Super Admin', 'Admin']) || $hasGlobalView;
            });

            // Eğer vekalet veren kişi Tanrı/Admin modundaysa, aşağıdaki matris/departman
            // kontrolleriyle sunucuyu hiç yormadan DOĞRUDAN kapıları aç!
            if ($hasAdminOrGodDelegator) {
                return true;
            }
        }

        // =========================================================================
        // PIPELINE HAZIRLIK: Vekalet edenlerin departman ve rollerini havuza at
        // (Üstteki Admin bypassından geçemeyen STANDART personeller arası vekalet için)
        // =========================================================================
        $allDeptIds = array_filter(array_merge([$user->department_id], $delegators->pluck('department_id')->toArray()));
        $userRoleIds = array_merge($user->roles->pluck('id')->toArray(), $delegators->flatMap->roles->pluck('id')->toArray());

        // =========================================================================
        // PIPELINE HALKASI 1: İSTİSNA KULLANICI (GRANULAR ACL) DOĞRULAMASI
        // =========================================================================
        $accessLevelMap = [
            'can_view' => ['read', 'upload', 'manage'],
            'can_upload' => ['upload', 'manage'],
            'can_create_subfolder' => ['manage'],
            'can_manage' => ['manage']
        ];

        $specificPermission = $folder->specificUsers()->where('user_id', $user->id)->first();
        if ($specificPermission) {
            $allowedLevels = $accessLevelMap[$permissionColumn] ?? [];
            if (in_array($specificPermission->pivot->access_level, $allowedLevels)) {
                return true; // İstisna Kullanıcı tanımlanmış. Tüm kuralları ezer!
            }
        }

        // =========================================================================
        // PIPELINE HALKASI 2: DİNAMİK ROL MATRİSİ (OVERRIDE KALKANI)
        // =========================================================================
        $hasRoleRestrictions = $folder->rolePermissions()->exists();

        if ($hasRoleRestrictions) {
            $hasMatrixPermission = $folder->rolePermissions()
                ->whereIn('role_id', $userRoleIds)
                ->where($permissionColumn, true)
                ->exists();

            if ($hasMatrixPermission) {
                return true; // Matris Kalkanı Onayı: Ekrandan rolüne izin verilmiş, DEPARTMAN DUVARINI EZ!
            }

            // GÜVENLİK DUVARI: Sıkı Yönetim (Strict Policy)
            return false;
        }

        // =========================================================================
        // PIPELINE HALKASI 3: DEPARTMAN İZOLASYONU VE STANDART KALITIM
        // =========================================================================
        $isGlobalFolder = $folder->departments()->count() === 0;
        $isMyDepartment = $folder->departments()->whereIn('departments.id', $allDeptIds)->exists();

        if ($isGlobalFolder || $isMyDepartment) {
            if (in_array($permissionColumn, ['can_view', 'can_upload'])) {
                return true;
            }
        }

        // Hiçbir şart sağlanmadıysa erişim reddedilir (403)
        return false;
    }

    /**
     * YETKİ METOTLARI (Hepsi tertemiz ve tek satır!)
     */
    public function view(User $user, Folder $folder): bool
    {
        // Kullanıcının kendi (bireysel) bypass kontrolü
        try {
            if ($user->hasPermissionTo('document.view_all')) {
                return true;
            }
        } catch (\Spatie\Permission\Exceptions\PermissionDoesNotExist $e) {
        }

        // Vekalet ve diğer kontroller zırha devredildi
        return $this->checkMatrix($user, $folder, 'can_view');
    }

    public function uploadDocument(User $user, Folder $folder): bool
    {
        return $this->checkMatrix($user, $folder, 'can_upload');
    }

    public function createSubfolder(User $user, Folder $folder): bool
    {
        return $this->checkMatrix($user, $folder, 'can_create_subfolder');
    }

    public function update(User $user, Folder $folder): bool
    {
        return $this->checkMatrix($user, $folder, 'can_manage');
    }

    public function delete(User $user, Folder $folder): bool
    {
        return $this->checkMatrix($user, $folder, 'can_manage');
    }
}
