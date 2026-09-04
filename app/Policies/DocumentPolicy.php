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
        /** @var User $user */
        $delegatorIds = $user->getActiveDelegatorIds();
        $allUserIds = array_merge([$user->id], $delegatorIds);

        // =========================================================
        // 1. İSTİSNALAR VE GLOBAL BYPASS (MUTLAK GÜÇLER)
        // =========================================================
        if ($user->hasAnyRole(['Super Admin', 'Admin'])) {
            return true;
        }

        try {
            if ($user->hasPermissionTo('document.view_all')) {
                return true;
            }
        } catch (PermissionDoesNotExist $e) {
        }

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
        // 2. DİREKT İLİŞKİ KATILIMI (Zırh Delici Özellikler)
        // =========================================================

        // Sahiplik Kontrolü (Taslak olsa bile görür)
        $isOwner = $document->versions()->whereIn('created_by', $allUserIds)->exists();
        if ($isOwner) return true;

        // Granular Access Kontrolü (Özel İzinli Kullanıcı)
        if ($document->specificUsers()->whereIn('user_id', $allUserIds)->exists()) return true;

        // Onaycı Kontrolü (Sürece Dahil Edilen Kullanıcı)
        if ($document->approvals()->whereIn('user_id', $allUserIds)->exists()) return true;

        // =========================================================
        // 3. KESİN DEPARTMAN İZOLASYONU (En Önemli Katman)
        // =========================================================

        // Taslak ve Bekleyen belgeleri yukarıdaki direkt ilişkilere takılmayan HİÇ KİMSE göremez
        if (!in_array($document->status, ['published', 'approved', 'archived'])) {
            return false;
        }

        $folder = $document->folder;
        // RAM dostu Departman dizisi oluşturma (SQL N+1 olmadan)
        $userDeptIds = array_filter(array_merge([$user->department_id], User::whereIn('id', $delegatorIds)->pluck('department_id')->toArray()));
        $passesIsolation = false;

        if ($folder) {
            // Klasörün hiç departmanı yoksa (Global)
            if ($folder->departments->isEmpty()) {
                $passesIsolation = true;
            }
            // Klasörün departmanlarından biri kullanıcının departmanı ise
            elseif ($folder->departments->whereIn('id', $userDeptIds)->isNotEmpty()) {
                $passesIsolation = true;
            }
        } else {
            // Klasörsüz belgeler için (Global veya Kendi departmanı)
            if (!$document->related_department_id || in_array($document->related_department_id, $userDeptIds)) {
                $passesIsolation = true;
            }
        }

        // Kural İhlali: Eğer departman izolesini geçemiyorsa MATRİSE BAKMADAN reddet!
        if (!$passesIsolation) {
            return false;
        }

        // =========================================================
        // 4. KURUMSAL GİZLİLİK VE MATRİS MANTIĞI
        // =========================================================

        // YENİ: Eğer belge "Herkese Açık" veya "Departmana Özel" ise ve kod buraya kadar 
        // ulaştıysa (yani Adım 3'teki kesin departman duvarını aşabildiyse), 
        // ekstra bir Spatie yetkisi veya Matris yetkisi aramadan DOĞRUDAN İZİN VER!
        if (in_array($document->privacy_level, ['public', 'confidential'])) {
            return true;
        }

        // Sadece 'strictly_confidential' (Çok Gizli) vb. özel gizlilik seviyeleri için 
        // dinamik Spatie yetkisi (Örn: document.view_strictly_confidential) ara.
        if (!empty($document->privacy_level)) {

            $dynamicPermissionName = 'document.view_' . strtolower($document->privacy_level);
            $hasPrivacyClearance = false;

            // 1. Kullanıcının kendi bireysel iznini kontrol et
            try {
                $hasPrivacyClearance = $user->hasPermissionTo($dynamicPermissionName);
            } catch (\Exception $e) {
            }

            // 2. Kendisinde yoksa, vekalet verenlerin iznine bak
            if (!$hasPrivacyClearance && !empty($delegatorIds)) {
                if (!isset($delegators)) {
                    $delegators = User::whereIn('id', $delegatorIds)->get();
                }
                $hasPrivacyClearance = $delegators->contains(function (User $d) use ($dynamicPermissionName) {
                    try {
                        return $d->hasPermissionTo($dynamicPermissionName);
                    } catch (\Exception $e) {
                        return false;
                    }
                });
            }

            // Eğer dinamik gizlilik seviyesini aşamıyorsa: ANINDA RET!
            if (!$hasPrivacyClearance) {
                return false;
            }
        }

        // Son Kapı: 3D Matris Kontrolü (Sadece özel gizlilik gerektiren ama public/confidential olmayan belgeler için)
        if ($document->document_type_id && $document->documentType) {
            if ($this->hasMatrixPermission($user, $document->documentType->name, 'can_view')) {
                return true;
            }
        } elseif (!$document->document_type_id) {
            return true;
        }

        return false;
    }

    /**
     * Kullanıcı yeni belge yükleyebilir mi?
     */
    public function create(User $user): bool
    {
        // if ($user->hasAnyRole(['Super Admin', 'Admin'])) {
        //     return true;
        // }

        // // Matriste HERHANGİ BİR kategoride belge yükleme (can_create) yetkisi var mı?
        // $roleIds = $user->roles->pluck('id');
        // if ($roleIds->isNotEmpty()) {
        //     $hasAnyMatrixCreate = DB::table('role_category_permissions')
        //         ->whereIn('role_id', $roleIds)
        //         ->where('can_create', 1)
        //         ->exists();

        //     if ($hasAnyMatrixCreate) {
        //         return true;
        //     }
        // }

        // // Matris yetkisi yoksa klasik create yetkisine bak
        // try {
        //     if ($user->hasPermissionTo('document.create')) return true;
        // } catch (PermissionDoesNotExist $e) {
        // }

        return true; // Herkese açık bırakıyoruz, çünkü yükleme ekranında kategori seçimi yapılacak ve matris kontrolü orada yapılacak.
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
    /**
     * Kullanıcı spesifik bir versiyonu GÜNCELLEYEBİLİR mi?
     */
    public function updateVersion(User $user, Document $document, \App\Models\DocumentVersion $version): bool
    {
        if ($user->hasAnyRole(['Super Admin', 'Admin'])) return true;

        try { // YENİ: Global Spatie Yetkisi
            if ($user->hasPermissionTo('document.manage_versions')) return true;
        } catch (PermissionDoesNotExist $e) {
        }

        // YENİ: Matris Yetkisi (Artık can_edit değil, YENİ SÜTUNUMUZ can_manage_versions kontrol ediliyor)
        if ($document->document_type_id && $document->documentType && $this->hasMatrixPermission($user, $document->documentType->name, 'can_manage_versions')) {
            return true;
        }

        return $version->created_by === $user->id;
    }

    /**
     * Kullanıcı spesifik bir versiyonu SİLEBİLİR mi?
     */
    public function deleteVersion(User $user, Document $document, \App\Models\DocumentVersion $version): bool
    {
        if ($user->hasAnyRole(['Super Admin', 'Admin'])) return true;

        try { // YENİ: Global Spatie Yetkisi
            if ($user->hasPermissionTo('document.manage_versions')) return true;
        } catch (PermissionDoesNotExist $e) {
        }

        // YENİ: Matris Yetkisi (Artık can_delete değil, YENİ SÜTUNUMUZ can_manage_versions kontrol ediliyor)
        if ($document->document_type_id && $document->documentType && $this->hasMatrixPermission($user, $document->documentType->name, 'can_manage_versions')) {
            return true;
        }

        return $version->created_by === $user->id;
    }
    /**
     * Kullanıcı ana belgeye ek belge yükleyebilir mi veya mevcut bir ek belgeyi/versiyonunu yönetebilir mi?
     */
    public function manageAttachment(User $user, Document $document, ?\App\Models\DocumentAttachment $attachment = null): bool
    {
        // 1. Yönetici Zırhı
        if ($user->hasAnyRole(['Super Admin', 'Admin'])) return true;

        // 2. Global Spatie İzni (config'e eklediğimiz yeni yetki)
        try {
            if ($user->hasPermissionTo('document.manage_attachments')) return true;
        } catch (PermissionDoesNotExist $e) {
        }

        // 3. Matris Yetkisi: Ana belgeyi "düzenleyebilen" (can_edit) ek belgeyi de yönetebilir
        if ($document->document_type_id && $document->documentType && $this->hasMatrixPermission($user, $document->documentType->name, 'can_edit')) {
            return true;
        }

        // 4. Sahiplik (Granular): İşlem yapılan spesifik bir ek belgeyse ve bu kişi yüklemişse
        if ($attachment && $attachment->uploaded_by === $user->id) {
            return true;
        }

        // 5. Ana Belge Sahipliği: Ana belgeyi yükleyen kişi, belgesinin tüm eklerini yönetebilir
        return $document->currentVersion && $document->currentVersion->created_by === $user->id;
    }
    /**
     * Kullanıcı belgeyi revize etmek üzere KİLİTLEYEBİLİR Mİ (Check-out)?
     */
    public function checkout(User $user, Document $document): bool
    {
        // 1. Yönetici Zırhı
        if ($user->hasAnyRole(['Super Admin', 'Admin'])) {
            return true;
        }

        // 2. Özel Granüler İzin (Kullanıcıya özel edit hakkı verilmişse her şeyi ezer geçer)
        $granularPermission = $document->specificUsers()->where('user_id', $user->id)->first();
        if ($granularPermission && $granularPermission->pivot->access_level === 'edit') {
            return true;
        }

        // 3. Belgenin Sahibi mi? (Sahibi her zaman revize edebilir)
        $isOwner = $document->currentVersion && $document->currentVersion->created_by === $user->id;
        if ($isOwner) {
            return true;
        }

        // 4. KESİN DEPARTMAN İZOLASYONU (Check-out için de View kadar sıkı olmalı)
        $delegatorIds = $user->getActiveDelegatorIds();
        $userDeptIds = array_filter(array_merge([$user->department_id], User::whereIn('id', $delegatorIds)->pluck('department_id')->toArray()));
        $passesIsolation = false;
        $folder = $document->folder;

        if ($folder) {
            if ($folder->departments->isEmpty()) {
                $passesIsolation = true;
            } elseif ($folder->departments->whereIn('id', $userDeptIds)->isNotEmpty()) {
                $passesIsolation = true;
            }
        } else {
            if (!$document->related_department_id || in_array($document->related_department_id, $userDeptIds)) {
                $passesIsolation = true;
            }
        }

        if (!$passesIsolation) {
            return false;
        }

        // 5. GİZLİLİK (PRIVACY LEVEL) KONTROLÜ
        if (!in_array($document->privacy_level, ['public', 'confidential'])) {
            if (!empty($document->privacy_level)) {
                $dynamicPermissionName = 'document.view_' . strtolower($document->privacy_level);
                $hasPrivacyClearance = false;

                try {
                    $hasPrivacyClearance = $user->hasPermissionTo($dynamicPermissionName);
                } catch (\Exception $e) {
                }

                if (!$hasPrivacyClearance && !empty($delegatorIds)) {
                    $delegators = User::whereIn('id', $delegatorIds)->get();
                    $hasPrivacyClearance = $delegators->contains(function (User $d) use ($dynamicPermissionName) {
                        try {
                            return $d->hasPermissionTo($dynamicPermissionName);
                        } catch (\Exception $e) {
                            return false;
                        }
                    });
                }

                if (!$hasPrivacyClearance) {
                    return false;
                }
            }
        }

        // 6. MATRİS KONTROLÜ (GÖRÜNTÜLEYEBİLİYOR, AMA DÜZENLEYEBİLİR Mİ?)
        // DİKKAT: Veritabanındaki tablonuzda sütun adının tam olarak 'can_edit' olduğundan emin olun. 
        // Eğer 'can_edi' olarak bozuk kaydolduysa, aşağıdaki stringi ona göre değiştirin!
        $editColumnName = 'can_edit';

        if ($document->document_type_id && $document->documentType) {
            if ($this->hasMatrixPermission($user, $document->documentType->name, $editColumnName)) {
                return true;
            }
        } elseif (!$document->document_type_id) {
            return true;
        }

        return false;
    }
}
