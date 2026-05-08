<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Sistem ve Güvenlik Yetkileri (Permissions)
    |--------------------------------------------------------------------------
    | Arayüzden dinamik olarak eklenemeyen, sistemin çekirdeğine (koda) 
    | gömülü olan özel yetkilerdir.
    */
    'core_permissions' => [
        'document.view_all',          // Tüm belgeleri (gizlilik hariç) görme
        'document.manage_all',        // Tüm belgeleri yönetme
        'document.force_unlock',      // Kilitli belgeleri zorla açma
        'notify.global',              // Tüm departmanlara bildirim gönderebilme
    ],

    /*
    |--------------------------------------------------------------------------
    | Kritik Sistem Rolleri (Protected Roles)
    |--------------------------------------------------------------------------
    | Sistemden asla silinmemesi ve adı değiştirilmemesi gereken kök roller.
    */
    'security' => [
        'protected_roles' => [
            'Super Admin',
            'Admin',
        ],

        'core_privacy_levels' => [
            'public', 
            'confidential', 
            'strictly_confidential'
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Dosya Yükleme Limitleri ve Ayarları (Uploads)
    |--------------------------------------------------------------------------
    | Çoklu belge yükleme (Batch Upload) ve tekil yüklemeler için sistem sınırları.
    | Değerler Megabayt (MB) cinsindendir.
    */
    'uploads' => [
        'max_single_file_size' => 20, // Tek bir dosyanın maksimum boyutu (MB)
        'max_batch_total_size' => 40, // Çoklu yüklemede toplam paket boyutu (MB)
        'allowed_extensions' => [
            'pdf', 'doc', 'docx', 'xls', 'xlsx', 'png', 'jpg', 'jpeg', 'zip', 'rar'
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Audit Log (İz Sürme) Ayarları
    |--------------------------------------------------------------------------
    | Logların veritabanında ne kadar süre tutulacağını belirler.
    */
    'audit' => [
        'retention_days' => 365, // Loglar kaç gün saklanacak?
    ],

];