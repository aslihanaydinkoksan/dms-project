<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class AuditLog extends Model
{
    protected $fillable = [
        'user_id',
        'event',
        'auditable_type',
        'auditable_id',
        'old_values',
        'new_values',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
    ];

    // Polymorphic ilişki (Hangi modele ait olduğunu bulur: Document, Folder, vb.)
    public function auditable(): MorphTo
    {
        return $this->morphTo();
    }

    // İşlemi yapan kullanıcı
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
    //logları tersten çekebilmek için şu metot
    // public function auditLogs()
    // {
    //     return $this->morphMany(AuditLog::class, 'auditable');
    // }
    /**
     * SANAL NİTELİK: İşlem tipini (event) Türkçe ve okunaklı hale getirir.
     * Gelecekte eklenen ve haritada olmayan logları otomatik formatlar (Örn: "NEW_ACTION" -> "New Action")
     */
    public function getEventTextAttribute(): string
    {
        $map = [
            'create' => 'Yeni Kayıt Oluşturuldu',
            'update' => 'Kayıt Güncellendi',
            'delete' => 'Kayıt Silindi (Soft Delete)',
            'restore' => 'Kayıt Geri Getirildi',
            'document_updated' => 'Belge Detayları Güncellendi',
            'attachment_version_added' => 'Yeni Ek / Versiyon Eklendi',
            'workflow_started' => 'İş Akışı / Onay Süreci Başlatıldı',
            'workflow_approved' => 'Onay Verildi',
            'workflow_rejected' => 'Onay Reddedildi',
            'status_changed' => 'Durum Değiştirildi',
            'download' => 'Belge İndirildi',
            'view' => 'Belge Görüntülendi',
        ];

        $eventLower = strtolower($this->event);

        // Haritada varsa Türkçesini ver, YOKSA alt çizgileri silip kelimelerin baş harfini büyüt
        return $map[$eventLower] ?? ucwords(str_replace('_', ' ', $eventLower));
    }

    /**
     * SANAL NİTELİK: İşlem tipine göre dinamik renk paleti (UI için)
     */
    public function getEventThemeAttribute(): array
    {
        $eventLower = strtolower($this->event);
        
        return match (true) {
            in_array($eventLower, ['create', 'restore', 'workflow_started', 'workflow_approved']) 
                => ['bg' => '#f0fdf4', 'text' => '#16a34a', 'border' => '#bbf7d0'], // Yeşil
            
            in_array($eventLower, ['update', 'document_updated', 'status_changed']) 
                => ['bg' => '#eff6ff', 'text' => '#2563eb', 'border' => '#bfdbfe'], // Mavi
            
            in_array($eventLower, ['delete', 'workflow_rejected']) 
                => ['bg' => '#fef2f2', 'text' => '#dc2626', 'border' => '#fecaca'], // Kırmızı
            
            in_array($eventLower, ['attachment_version_added']) 
                => ['bg' => '#fdf4ff', 'text' => '#c026d3', 'border' => '#f5d0fe'], // Mor
            
            default 
                => ['bg' => '#eef2ff', 'text' => '#4f46e5', 'border' => '#c7d2fe'], // Varsayılan İndigo
        };
    }
}
