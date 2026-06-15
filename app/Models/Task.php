<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Task extends Model
{
    protected $fillable = [
        'process_template_id',
        'current_stage_id',
        'creator_id',
        'title',
        'custom_data',
        'status',
        'closure_note',
        'closure_document_path'
    ];

    // KRİTİK: custom_data dinamik form verilerini array olarak işlemek için cast ediyoruz
    protected $casts = [
        'custom_data' => 'array'
    ];

    /**
     * İşin türediği süreç şablonu
     */
    public function template(): BelongsTo
    {
        return $this->belongsTo(ProcessTemplate::class, 'process_template_id');
    }

    /**
     * İşin Kanban board üzerindeki mevcut konumu / sütunu
     */
    public function stage(): BelongsTo
    {
        return $this->belongsTo(ProcessStage::class, 'current_stage_id');
    }

    /**
     * İşi başlatan kurumsal kullanıcı
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    /**
     * Ad-Hoc Proje Ekibi: Bu işe özel atanmış esnek kullanıcılar ve rolleri
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'task_user')
            ->withPivot('role') // member veya manager bilgisini pivot üzerinden yönetiyoruz
            ->withTimestamps();
    }

    /**
     * Yardımcı Metot (Scope): Sadece yönetici (kapatma yetkilisi) olan ekip üyelerini getirir
     */
    public function managers()
    {
        return $this->users()->wherePivot('role', 'manager');
    }
    public function logs()
    {
        return $this->hasMany(TaskLog::class);
    }
    // --- GÖREV DURUM YARDIMCILARI (UI İÇİN) ---

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isPendingApproval(): bool
    {
        return $this->status === 'pending_closure_approval';
    }

    public function isLocked(): bool
    {
        return $this->isCompleted() || $this->isPendingApproval();
    }
    /**
     * Local Scope: Gelişmiş Süreç ve JSON Veri Filtreleme Kalkanı
     */
    public function scopeFilter(\Illuminate\Database\Eloquent\Builder $query, array $filters): \Illuminate\Database\Eloquent\Builder
    {
        // 1. Title Veya JSON Sütunlarında Metin Araması (Arama Filtresi)
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    // custom_data sütunundaki JSON text veriler içinde de akıllı arama yapar
                    ->orWhere('custom_data', 'like', "%{$search}%");
            });
        }

        // 2. Başlangıç Tarihi Sınırı
        if (!empty($filters['date_start'])) {
            $query->whereDate('created_at', '>=', $filters['date_start']);
        }

        // 3. Bitiş Tarihi Sınırı
        if (!empty($filters['date_end'])) {
            $query->whereDate('created_at', '<=', $filters['date_end']);
        }

        return $query;
    }
}
