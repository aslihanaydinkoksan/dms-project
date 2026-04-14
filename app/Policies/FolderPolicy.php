<?php

namespace App\Policies;

use App\Models\Folder;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class FolderPolicy
{
    use HandlesAuthorization;

    /**
     * Mutlak Güç: Super Admin ve Admin her türlü engeli aşar.
     */
    public function before(User $user, $ability)
    {
        if ($user->hasAnyRole(['Super Admin', 'Admin'])) {
            return true;
        }
    }

    /**
     * Özel Yardımcı Metot: Kullanıcının rollerini Folder Role matrisinde arar.
     */
    private function checkMatrix(User $user, Folder $folder, string $permissionColumn): bool
    {
        $userRoleIds = $user->roles->pluck('id')->toArray();

        // 1. ZIRH: Bu klasöre tanımlanmış herhangi bir "Rol Kısıtlaması" var mı?
        $hasRoleRestrictions = $folder->rolePermissions()->exists();

        if ($hasRoleRestrictions) {
            // Eğer klasörde özel bir kısıtlama (matris) varsa, kullanıcının rolü bu kısıtlamadan geçmek ZORUNDADIR!
            return $folder->rolePermissions()
                ->whereIn('role_id', $userRoleIds)
                ->where($permissionColumn, true)
                ->exists();
        }

        // 2. MANTIKLI KALITIM (Sorunu Çözen Kısım):
        // Klasörün özel bir kısıtlaması (matrisi) YOKSA (Örn: yeni açılmış düz bir departman klasörüyse),
        // Klasörü görebilen herkes (zaten kendi departmanıdır) "Görüntüleyebilir" ve "Belge Yükleyebilir".
        if (in_array($permissionColumn, ['can_view', 'can_upload'])) {
            return true;
        }

        // Ancak "Klasör Silme/Düzenleme (manage)" ve "Alt Klasör Açma" gibi KRİTİK işlemlere otomatik izin VERME.
        return false;
    }

    public function view(User $user, Folder $folder): bool
    {
        return $this->checkMatrix($user, $folder, 'can_view');
    }

    public function uploadDocument(User $user, Folder $folder): bool
    {
        // 1. YENİ İSTİSNA SİSTEMİ: Kullanıcıya özel "upload" veya "manage" yetkisi atanmışsa her şeyi ezer!
        $specificPermission = $folder->specificUsers()->where('user_id', $user->id)->first();
        if ($specificPermission && in_array($specificPermission->pivot->access_level, ['upload', 'manage'])) {
            return true;
        }

        // 2. Eski Sistem: Rol matrisine veya Varsayılan İzinlere bak
        return $this->checkMatrix($user, $folder, 'can_upload');
    }

    public function createSubfolder(User $user, Folder $folder): bool
    {
        // Özel yetki (manage) varsa delebilir
        $specificPermission = $folder->specificUsers()->where('user_id', $user->id)->first();
        if ($specificPermission && $specificPermission->pivot->access_level === 'manage') {
            return true;
        }

        return $this->checkMatrix($user, $folder, 'can_create_subfolder');
    }

    public function update(User $user, Folder $folder): bool
    {
        // Özel yetki "manage" ise izin ver
        $specificPermission = $folder->specificUsers()->where('user_id', $user->id)->first();
        if ($specificPermission && $specificPermission->pivot->access_level === 'manage') {
            return true;
        }

        return $this->checkMatrix($user, $folder, 'can_manage');
    }

    public function delete(User $user, Folder $folder): bool
    {
        // Özel yetki "manage" ise izin ver
        $specificPermission = $folder->specificUsers()->where('user_id', $user->id)->first();
        if ($specificPermission && $specificPermission->pivot->access_level === 'manage') {
            return true;
        }

        return $this->checkMatrix($user, $folder, 'can_manage');
    }
}
