<?php

namespace App\Policies;

use Spatie\Permission\Exceptions\PermissionDoesNotExist;
use App\Models\Document;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class DocumentPolicy
{
    /**
     * YARDIMCI METOT: 3D Yetki Matrisinden kullanıcının kategorik iznini sorgular.
     */
    private function hasMatrixPermission(User $user, ?string $category, string $action): bool
    {
        // Belgenin kategorisi yoksa (Eski/Normal belge ise) matris devreye girmez.
        if (!$category) {
            return false;
        }

        // Kullanıcının sahip olduğu tüm rolleri al
        $roleIds = $user->roles->pluck('id');
        if ($roleIds->isEmpty()) {
            return false;
        }

        // Veritabanı Pivot Tablomuzdan (role_category_permissions) kontrol et
        return DB::table('role_category_permissions')
            ->whereIn('role_id', $roleIds)
            ->where('category', $category)
            ->where($action, 1)
            ->exists();
    }

    /**
     * Kullanıcı bu belgeyi indirebilir mi? (View ile aynı mantıkta çalışır)
     */
    public function download(User $user, Document $document): bool
    {
        $isApprover = $document->approvals()->where('user_id', $user->id)->exists();
        if ($isApprover) {
            return true;
        }
        return $this->view($user, $document);
    }

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true; // Listeleme ekranında (Index) arama yapabilmeleri için açık bırakıyoruz, filtrelemeyi Scope yapıyor.
    }

    /**
     * Kullanıcı bu belgeyi görebilir mi? (Hem Klasik Zırh Hem 3D Matris)
     */
    public function view(User $user, Document $document): bool
    {
        // 1. Super Admin her şeyi görebilir
        if ($user->hasRole('Super Admin')) {
            return true;
        }
        if ($document->specificUsers()->where('user_id', $user->id)->exists()) {
            return true;
        }
        $isApprover = $document->approvals()->where('user_id', $user->id)->exists();
        if ($isApprover) {
            return true;
        }
        // 2. ÇOK GİZLİ (Strictly Confidential) KALKANI - En üst düzey zırh
        if ($document->privacy_level === 'strictly_confidential') {
            $isOwner = $document->currentVersion && $document->currentVersion->created_by === $user->id;
            $hasSpecialClearance = false;

            try {
                $hasSpecialClearance = $user->hasPermissionTo('document.view_strictly_confidential');
            } catch (PermissionDoesNotExist $e) {
            }

            // Çok gizli bir belgeyse, matriste "can_view" olsa bile Özel İzin veya Sahibi olması ŞARTTIR!
            if (!$isOwner && !$hasSpecialClearance) {
                return false;
            }
        }

        // 3. LEGAL DMS 3D MATRİS KONTROLÜ (Belgenin bir kategorisi varsa)
        if ($document->category) {
            $hasMatrixView = $this->hasMatrixPermission($user, $document->category, 'can_view');
            $isOwner = $document->currentVersion && $document->currentVersion->created_by === $user->id;

            return $hasMatrixView || $isOwner;
        }

        // 4. KLASİK YETKİ KONTROLÜ (Kategorisi olmayan eski/normal belgeler için)
        $hasViewAll = false;
        try {
            $hasViewAll = $user->hasPermissionTo('document.view_all');
        } catch (PermissionDoesNotExist $e) {
        }

        if ($hasViewAll) {
            return true;
        }
        if (empty($document->category) && $document->privacy_level === 'public') {
            return true;
        }

        // Sadece kendi yüklediği belgeleri görebilsin
        return $document->currentVersion && $document->currentVersion->created_by === $user->id;
    }

    /**
     * Kullanıcı yeni belge yükleyebilir mi?
     */
    public function create(User $user): bool
    {
        if ($user->hasRole('Super Admin')) {
            return true;
        }

        // Matriste HERHANGİ BİR kategoride belge yükleme (can_create) yetkisi var mı?
        $roleIds = $user->roles->pluck('id');
        if ($roleIds->isNotEmpty()) {
            $hasAnyMatrixCreate = DB::table('role_category_permissions')
                ->whereIn('role_id', $roleIds)
                ->where('can_create', 1)
                ->exists();

            if ($hasAnyMatrixCreate) {
                return true;
            }
        }

        // Matris yetkisi yoksa klasik create yetkisine bak
        try {
            if ($user->hasPermissionTo('document.create')) return true;
        } catch (PermissionDoesNotExist $e) {
        }

        return false;
    }

    /**
     * Kullanıcı belgeyi kilitleyebilir/güncelleyebilir mi? (Check-out / Check-in)
     */
    public function update(User $user, Document $document): bool
    {
        if ($user->hasRole('Super Admin')) {
            return true;
        }
        // Kullanıcıya bu belge için özel olarak "edit" (Düzenleme) yetkisi verilmişse, her şeyi ezer
        $granularPermission = $document->specificUsers()->where('user_id', $user->id)->first();
        if ($granularPermission && $granularPermission->pivot->access_level === 'edit') {
            return true;
        }

        // 1. 3D MATRİS: Bu kategoride "Revize Etme (can_edit)" yetkisi var mı?
        if ($document->category && $this->hasMatrixPermission($user, $document->category, 'can_edit')) {
            return true;
        }

        // 2. KLASİK YETKİ (Yönetici Zırhı)
        try {
            if ($user->hasPermissionTo('document.manage_all')) return true;
        } catch (PermissionDoesNotExist $e) {
        }

        // 3. SADECE SAHİBİ GÜNCELLEYEBİLİR
        return $document->currentVersion && $document->currentVersion->created_by === $user->id;
    }

    /**
     * Belge kilidini zorla açma yetkisi (Force Unlock)
     */
    public function forceUnlock(User $user, Document $document): bool
    {
        if ($user->hasRole('Super Admin')) {
            return true;
        }

        try {
            return $user->hasPermissionTo('document.force_unlock');
        } catch (PermissionDoesNotExist $e) {
            return false;
        }
    }

    /**
     * Belgeyi silme yetkisi (İmha Politikası)
     */
    public function delete(User $user, Document $document): bool
    {
        if ($user->hasRole('Super Admin')) {
            return true;
        }

        // 1. 3D MATRİS: Bu kategoride "İmha (can_delete)" yetkisi var mı?
        if ($document->category && $this->hasMatrixPermission($user, $document->category, 'can_delete')) {
            return true;
        }

        // 2. KLASİK YETKİ ZIRHI
        try {
            return $user->hasPermissionTo('document.delete');
        } catch (PermissionDoesNotExist $e) {
            return false;
        }
    }

    public function restore(User $user, Document $document): bool
    {
        return false;
    }
    public function forceDelete(User $user, Document $document): bool
    {
        return false;
    }
}
