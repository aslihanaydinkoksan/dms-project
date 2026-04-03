<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class DocumentReadLog extends Model
{
    protected $fillable = ['document_id', 'user_id', 'duration_seconds', 'ip_address'];
    /**
     * Bu log kaydı bir kullanıcıya aittir.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Bu log kaydı bir dokümana aittir. 
     */
    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }
}
