<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class DocumentAttachment extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'document_id',
        'title',          // YENİ: Metadata
        'description',    // YENİ: Metadata
        'uploaded_by',
    ];

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    // YENİ İLİŞKİ: Tüm Versiyonlar
    public function versions(): HasMany
    {
        return $this->hasMany(DocumentAttachmentVersion::class);
    }

    // YENİ İLİŞKİ: Sadece Aktif Olan Güncel Versiyon (Performans İçin)
    public function currentVersion(): HasOne
    {
        return $this->hasOne(DocumentAttachmentVersion::class)->where('is_current', true);
    }
}