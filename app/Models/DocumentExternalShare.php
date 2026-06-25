<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentExternalShare extends Model
{
    protected $fillable = [
        'document_id',
        'email',
        'token',
        'personal_note',
        'expires_at',
        'created_by'
    ];

    protected $casts = [
        'expires_at' => 'datetime'
    ];

    /**
     * Paylaşılan ana doküman ilişkisi
     */
    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    /**
     * Paylaşımı oluşturan iç kullanıcı ilişkisi
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
    public function isExpired(): bool
    {
        // Eğer expires_at null ise süresizdir (Kurumsal karara göre kısıtlanabilir)
        if (is_null($this->expires_at)) {
            return false;
        }
        return $this->expires_at->isPast();
    }
}
