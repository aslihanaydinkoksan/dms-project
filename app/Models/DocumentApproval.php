<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentApproval extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'document_id',
        'user_id',
        'status',
        'step_order',
        'comment',
        'action_date'
    ];

    protected $casts = [
        'action_date' => 'datetime',
        'step_order' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }
    /**
     * SANAL NİTELİK: İş akışı onay durumunu (status) Türkçe ve okunaklı hale getirir.
     * Kullanımı: $approval->status_text
     */
    public function getStatusTextAttribute(): string
    {
        return match ($this->status) {
            'pending' => 'Bekliyor',
            'approved' => 'Onaylandı',
            'rejected' => 'Reddedildi',
            default => 'Bilinmiyor (' . $this->status . ')',
        };
    }
}
