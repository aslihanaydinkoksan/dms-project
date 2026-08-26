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
        'document.manage_versions',   // YENİ: Versiyon Yönetimi (Edit/Delete)
        'document.force_unlock',      // Kilitli belgeleri zorla açma
        'notify.global',              // Tüm departmanlara bildirim gönderebilme
        'document.manage_attachments', // Ek belgeleri ve versiyonlarını yönetme

        // --- BPM (SÜREÇ/GÖREV) YETKİLERİ ---
        'task' => [
            'label' => 'Süreç Yönetimi (BPM)',
            'permissions' => [
                'task.view_all' => 'Tüm Şirket Süreçlerini Görüntüleme (Global Departman Kalkanı Bypass Yetkisi)',
                'task.create'   => 'Yeni Süreç Başlatma Yetkisi',
                'task.edit'     => 'Proje Bilgilerini Düzenleme Yetkisi',
                'task.delete'   => 'Süreç Silme / Kalıcı Arşivleme Yetkisi',
                'task.restrict_department' => 'Süreçleri SADECE kendi departmanıyla sınırlandırma',
            ]
        ],
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
            'pdf',
            'doc',
            'docx',
            'xls',
            'xlsx',
            'png',
            'jpg',
            'jpeg',
            'zip',
            'rar'
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

    /*
    |--------------------------------------------------------------------------
    | Yetki Arayüzü (UI Metadata)
    |--------------------------------------------------------------------------
    | Blade dosyalarında spagetti kod oluşmasını engellemek için, 
    | sistemdeki çekirdek yetkilerin renk, ikon ve etiket tanımları burada tutulur.
    */
    'permissions_ui' => [
        // DOKÜMAN YETKİLERİ
        'document.view_strictly_confidential' => ['icon' => '🕵️', 'color' => '#b91c1c', 'label' => '"ÇOK GİZLİ" ERİŞİMİ'],
        'document.view_all'                   => ['icon' => '🌍', 'color' => '#1d4ed8', 'label' => 'TÜM BELGELERİ GÖRÜNTÜLEME'],
        'document.manage_all'                 => ['icon' => '👑', 'color' => '#047857', 'label' => 'TÜM BELGELERİ YÖNETME'],
        'document.manage_versions'            => ['icon' => '⏳', 'color' => '#d97706', 'label' => 'VERSİYON YÖNETİMİ'], // YENİ EKLENDİ
        'document.force_unlock'               => ['icon' => '⚠️', 'color' => '#b45309', 'label' => 'KİLİT AÇMA YETKİSİ'],
        'notify.global'                       => ['icon' => '🌐', 'color' => '#0284c7', 'label' => 'KÜRESEL BİLDİRİM YETKİSİ'],
        'document.manage_attachments' => ['icon' => '📎', 'color' => '#059669', 'label' => 'EK BELGE YÖNETİMİ'],

        // SÜREÇ (BPM) YETKİLERİ
        'task.view_all'                       => ['icon' => '👁️', 'color' => '#6366f1', 'label' => 'TÜM SÜREÇLERİ GÖRME (GOD MODE)'],
        'task.create'                         => ['icon' => '✨', 'color' => '#10b981', 'label' => 'YENİ SÜREÇ BAŞLATMA'],
        'task.edit'                           => ['icon' => '✏️', 'color' => '#f59e0b', 'label' => 'SÜREÇLERİ DÜZENLEME'],
        'task.delete'                         => ['icon' => '🗑️', 'color' => '#ef4444', 'label' => 'SÜREÇ SİLME / İPTAL'],
        'task.restrict_department'            => ['icon' => '🏢', 'color' => '#475569', 'label' => 'SADECE KENDİ DEPARTMANI'],
    ],

];
