<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProcessStage extends Model
{
    protected $fillable = [
        'process_template_id',
        'name',
        'color',
        'sort_order'
    ];

    protected $casts = [
        'sort_order' => 'integer'
    ];

    /**
     * Aşamanın bağlı olduğu süreç şablonu
     */
    public function template(): BelongsTo
    {
        return $this->belongsTo(ProcessTemplate::class, 'process_template_id');
    }

    /**
     * Şu an bu aşamada (sütunda) bulunan görevler
     */
    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class, 'current_stage_id');
    }
}
