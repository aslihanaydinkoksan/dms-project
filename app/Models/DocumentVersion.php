<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentVersion extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'document_id',
        'version_number',
        'file_path',
        'mime_type',
        'file_size',
        'created_by',
        'is_current',
        'revision_reason'
    ];

    protected $casts = [
        'is_current' => 'boolean',
        'file_size' => 'integer',
    ];

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
