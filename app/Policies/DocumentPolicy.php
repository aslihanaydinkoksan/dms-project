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
     * Kullanıcı bu belgeyi görebilir mi? (Hem Klasik Zırh Hem 3D Matris Hem Dinamik Kalkan)
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
        // Bu yetki en üstte olduğu için aşağıdaki Gizlilik Kalkanı veya Matrislere HİÇ BAKMADAN doğrudan erişim verir.
        try {
            if ($user->hasPermissionTo('document.view_all')) {
                return true;
            }
        } catch (PermissionDoesNotExist $e) {
        }

        // C. Vekalet verenler arasında Admin var mı? 
        if (!empty($delegatorIds)) {
            $hasAdminDelegator = User::whereIn('id', $delegatorIds)
                ->role(['Super Admin', 'Admin'])
                ->exists();

            if ($hasAdminDelegator) {
                return true;
            }
        }

        // Granular Access Kontrolü (Vekalet Entegreli)
        if ($document->specificUsers()->whereIn('user_id', $allUserIds)->exists()) {
            return true;
        }

        // Onaycı Kontrolü (Vekalet Entegreli)
        if ($document->approvals()->whereIn('user_id', $allUserIds)->exists()) {
            return true;
        }

        // =========================================================
        // 2. DİNAMİK GİZLİLİK SEVİYESİ KALKANI
        // =========================================================
        if (!empty($document->privacy_level) && $document->privacy_level !== 'public') {
            $isOwner = $document->currentVersion && $document->currentVersion->created_by === $user->id;
            $hasClearance = false;

            try {
                $hasClearance = $user->hasPermissionTo('document.view_' . $document->privacy_level);
            } catch (PermissionDoesNotExist $e) {
            }

            if (!$isOwner && !$hasClearance) {
                return false;
            }
        }

        // =========================================================
        // 3. LEGAL DMS 3D MATRİS KONTROLÜ
        // =========================================================
        if ($document->document_type_id && $document->documentType) {
            $hasMatrixView = $this->hasMatrixPermission($user, $document->documentType->name, 'can_view');
            $isOwner = $document->currentVersion && $document->currentVersion->created_by === $user->id;

            return $hasMatrixView || $isOwner;
        }

        // =========================================================
        // 4. STANDART KONTROL (Kategorisiz public belgeler)
        // =========================================================
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
