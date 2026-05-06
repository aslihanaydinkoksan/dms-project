<?php

namespace App\Policies;

use Spatie\Permission\Exceptions\PermissionDoesNotExist;
use App\Models\Document;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class DocumentPolicy
{
    /**
     * YARDIMCI METOT: Sistem Ayarlarınden kullanıcının kategorik iznini sorgular.
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
     * Kullanıcı bu belgeyi görebilir mi? (Listeleme Kalkanı ile %100 Senkronize Policy)
     */
    public function view(User $user, Document $document): bool
    {
        /** @var \App\Models\User $user */
        $delegatorIds = $user->getActiveDelegatorIds();
        $allUserIds = array_merge([$user->id], $delegatorIds);

        // =========================================================
        // 1. İSTİSNALAR VE GLOBAL BYPASS (MUTLAK GÜÇLER)
        // =========================================================

        // A. Sistem Yöneticisi Zırhı
        if ($user->hasAnyRole(['Super Admin', 'Admin'])) {
            return true;
        }

        // B. GOD-MODE READ-ONLY (Yönetim Kurulu Asistanı / Denetçi Kalkanı)
        try {
            if ($user->hasPermissionTo('document.view_all')) {
                return true;
            }
        } catch (PermissionDoesNotExist $e) {
        }

        // C. Vekalet verenler arasında Admin veya God Mode var mı? 
        if (!empty($delegatorIds)) {
            $delegators = User::with('roles', 'permissions')->whereIn('id', $delegatorIds)->get();
            $hasAdminOrGod = $delegators->contains(function (User $d) {
                $hasViewAll = false;
                try {
                    $hasViewAll = $d->hasPermissionTo('document.view_all');
                } catch (\Exception $e) {
                }
                return $d->hasAnyRole(['Super Admin', 'Admin']) || $hasViewAll;
            });

            if ($hasAdminOrGod) return true;
        }

        // =========================================================
        // 2. SÜREÇ KATILIMI (Sahiplik, Granular, Onaycı)
        // =========================================================

        // Sahiplik Kontrolü (Kendisinin veya vekilinin yüklediği belge)
        $isOwner = $document->versions()->whereIn('created_by', $allUserIds)->exists();
        if ($isOwner) return true;

        // Granular Access Kontrolü (Özel İzinli Kullanıcı)
        if ($document->specificUsers()->whereIn('user_id', $allUserIds)->exists()) return true;

        // Onaycı Kontrolü (Sürece Dahil Edilen Kullanıcı)
        if ($document->approvals()->whereIn('user_id', $allUserIds)->exists()) return true;

        // =========================================================
        // 3. KURUMSAL GİZLİLİK VE KALITIM MANTIĞI (Yayınlanmış Belgeler)
        // =========================================================

        // Eğer belge "Yayında, Onaylanmış veya Arşivlenmiş" DEĞİLSE, 
        // ve kullanıcı da belgenin sahibi/onaycısı/admini değilse taslakları göremez!
        if (!in_array($document->status, ['published', 'approved', 'archived'])) {
            return false;
        }

        // A) HERKESE AÇIK (PUBLIC) DUVAR DELİCİ
        if ($document->privacy_level === 'public') {
            return true;
        }

        // B) ÇOK GİZLİ (STRICTLY CONFIDENTIAL) KALKANI
        if ($document->privacy_level === 'strictly_confidential') {
            $hasStrictClearance = false;
            try {
                $hasStrictClearance = $user->hasPermissionTo('document.view_strictly_confidential');
            } catch (\Exception $e) {
            }

            // Vekillerinde bu yetki var mı?
            if (!$hasStrictClearance && !empty($delegatorIds)) {
                $hasStrictClearance = $delegators->contains(function (User $d) {
                    try {
                        return $d->hasPermissionTo('document.view_strictly_confidential');
                    } catch (\Exception $e) {
                        return false;
                    }
                });
            }

            // Çok Gizli belgede yetkisi yoksa anında reddet!
            if (!$hasStrictClearance) return false;
        }

        // C) İZOLASYON DUVARI (Departman/Klasör Uyumu VEYA Matris Yetkisi)

        // 1. LEGAL DMS 3D MATRİS KONTROLÜ
        if ($document->document_type_id && $document->documentType) {
            if ($this->hasMatrixPermission($user, $document->documentType->name, 'can_view')) {
                return true;
            }
        }

        // 2. KLASÖR VE DEPARTMAN UYUMU (Listeleme Scope'u ile Aynı Kural)
        $folder = $document->folder;
        $userDeptIds = array_filter(array_merge([$user->department_id], User::whereIn('id', $delegatorIds)->pluck('department_id')->toArray()));

        if ($folder) {
            // Klasörün hiç departmanı yoksa (Departmansız Ana Klasör), Departmana Özel olsa dahi herkese açıktır.
            if ($folder->departments->isEmpty()) {
                return true;
            }

            // Klasörün departmanlarından biri, kullanıcının (veya vekilinin) departmanı mı?
            if ($folder->departments->whereIn('id', $userDeptIds)->isNotEmpty()) {
                return true;
            }
        } else {
            // Klasörsüz (kök dizin) yüklenmişse belgenin departmanına bakılır
            if (!$document->related_department_id || in_array($document->related_department_id, $userDeptIds)) {
                return true;
            }
        }

        // Hiçbir şartı sağlayamayan Aslıhanları kapıda durdur :)
        return false;
    }

    /**
     * Kullanıcı yeni belge yükleyebilir mi?
     */
    public function create(User $user): bool
    {
        if ($user->hasAnyRole(['Super Admin', 'Admin'])) {
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
        if ($document->document_type_id && $document->documentType && $this->hasMatrixPermission($user, $document->documentType->name, 'can_edit')) {
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
        // 1. YÖNETİCİ ZIRHI: Super Admin ve Admin her zaman silebilir.
        if ($user->hasAnyRole(['Super Admin', 'Admin'])) {
            return true;
        }

        // 2. KİLİTLİ DURUMLAR: Onaylı, Reddedilmiş veya Yayınlanmış belgeleri SAHİBİ DAHİL kimse silemez!
        if (in_array($document->status, ['approved', 'published', 'rejected', 'archived'])) {
            return false;
        }

        // 3. SAHİPLİK HAKKI: Eğer belge henüz 'taslak' veya 'onay bekliyor' aşamasındaysa, sahibi silebilir.
        $isOwner = $document->currentVersion && $document->currentVersion->created_by === $user->id;
        if ($isOwner && in_array($document->status, ['draft', 'pending_approval', 'pending'])) {
            return true;
        }

        // 4. 3D MATRİS: Bu kategoride "İmha (can_delete)" yetkisi var mı?
        if ($document->document_type_id && $document->documentType && $this->hasMatrixPermission($user, $document->documentType->name, 'can_delete')) {
            return true;
        }

        // 5. KLASİK YETKİ ZIRHI
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
