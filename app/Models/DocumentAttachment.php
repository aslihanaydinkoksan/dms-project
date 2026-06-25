<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentAttachment extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'document_id',
        'original_name',
        'file_path',
        'file_size',
        'mime_type',
        'uploaded_by',
    ];

    /**
     * Ekin bağlı olduğu ana doküman
     */
    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    /**
     * Eki sisteme yükleyen personel
     */
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
