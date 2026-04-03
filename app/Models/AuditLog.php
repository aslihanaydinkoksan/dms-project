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
}
