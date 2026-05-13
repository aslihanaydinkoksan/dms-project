<?php

namespace App\Policies;

use App\Models\Folder;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class FolderPolicy
{
    use HandlesAuthorization;

    public function before(User $user, $ability)
    {
        if ($user->hasAnyRole(['Super Admin', 'Admin'])) {
            return true;
        }
    }

    /**
     * Özel Yardımcı Metot: Kullanıcının departman izolasyonunu ve rol matrisini arar.
     */
    private function checkMatrix(User $user, Folder $folder, string $permissionColumn): bool
    {
        // 0. VEKALET KİMLİK KARTLARI
        $delegatorIds = $user->getActiveDelegatorIds();
        $delegators = User::with('roles')->whereIn('id', $delegatorIds)->get();

        $allDeptIds = array_filter(array_merge([$user->department_id], $delegators->pluck('department_id')->toArray()));
        $userRoleIds = array_merge($user->roles->pluck('id')->toArray(), $delegators->flatMap->roles->pluck('id')->toArray());

        // 1. MUTLAK İZOLASYON (DEPARTMAN KONTROLÜ)
        $isGlobalFolder = $folder->departments()->count() === 0;
        $isMyDepartment = $folder->departments()->whereIn('departments.id', $allDeptIds)->exists();

        // Eğer klasör ne Global ne de kullanıcının departmanına ait değilse anında RET!
        if (!$isGlobalFolder && !$isMyDepartment) {
            return false;
        }

        // 2. ZIRH: Bu klasöre tanımlanmış herhangi bir "Rol Kısıtlaması" var mı?
        $hasRoleRestrictions = $folder->rolePermissions()->exists();

        if ($hasRoleRestrictions) {
            // Departman doğru olsa bile Matris'ten geçmek ZORUNDADIR!
            return $folder->rolePermissions()
                ->whereIn('role_id', $userRoleIds)
                ->where($permissionColumn, true)
                ->exists();
        }

        // 3. MANTIKLI KALITIM
        // Matris yoksa ve departman uyuyorsa "Görüntüle" ve "Yükle" serbest.
        if (in_array($permissionColumn, ['can_view', 'can_upload'])) {
            return true;
        }

        return false;
    }

    public function view(User $user, Folder $folder): bool
    {
        try {
            if ($user->hasPermissionTo('document.view_all')) {
                return true;
            }
        } catch (\Spatie\Permission\Exceptions\PermissionDoesNotExist $e) {
        }

        return $this->checkMatrix($user, $folder, 'can_view');
    }

    public function uploadDocument(User $user, Folder $folder): bool
    {
        $specificPermission = $folder->specificUsers()->where('user_id', $user->id)->first();
        if ($specificPermission && in_array($specificPermission->pivot->access_level, ['upload', 'manage'])) {
            return true;
        }

        return $this->checkMatrix($user, $folder, 'can_upload');
    }

    public function createSubfolder(User $user, Folder $folder): bool
    {
        $specificPermission = $folder->specificUsers()->where('user_id', $user->id)->first();
        if ($specificPermission && $specificPermission->pivot->access_level === 'manage') {
            return true;
        }

        return $this->checkMatrix($user, $folder, 'can_create_subfolder');
    }

    public function update(User $user, Folder $folder): bool
    {
        $specificPermission = $folder->specificUsers()->where('user_id', $user->id)->first();
        if ($specificPermission && $specificPermission->pivot->access_level === 'manage') {
            return true;
        }

        return $this->checkMatrix($user, $folder, 'can_manage');
    }

    public function delete(User $user, Folder $folder): bool
    {
        $specificPermission = $folder->specificUsers()->where('user_id', $user->id)->first();
        if ($specificPermission && $specificPermission->pivot->access_level === 'manage') {
            return true;
        }

        return $this->checkMatrix($user, $folder, 'can_manage');
    }
}
