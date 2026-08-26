<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentAttachmentVersion extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'document_attachment_id',
        'version_number',
        'original_name',
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

    public function attachment(): BelongsTo
    {
        return $this->belongsTo(DocumentAttachment::class, 'document_attachment_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}