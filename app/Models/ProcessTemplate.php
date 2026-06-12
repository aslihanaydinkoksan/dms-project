<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProcessTemplate extends Model
{
    protected $fillable = [
        'name',
        'department_id',
        'fields',
        'requires_document_on_closure',
        'mandatory_user_group_id',
        'allow_ad_hoc_members',
    ];

    // KRİTİK: fields sütununu otomatik array/object olarak işlemek için cast ediyoruz
    protected $casts = [
        'fields' => 'array',
        'requires_document_on_closure' => 'boolean'
    ];

    /**
     * Şablonun ait olduğu departman
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * Şablona ait Kanban aşamaları (Sütunlar)
     */
    public function stages(): HasMany
    {
        return $this->hasMany(ProcessStage::class)->orderBy('sort_order');
    }

    /**
     * Bu şablon kullanılarak üretilen tüm görevler / işler
     */
    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }
    public function mandatoryGroup()
    {
        return $this->belongsTo(UserGroup::class, 'mandatory_user_group_id');
    }
}
