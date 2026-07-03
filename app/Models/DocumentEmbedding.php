<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Bu model sınıfı; embedding verilerimizin 
 * veritabanı tablosuyla olan etkileşimini, 
 * ilişkili olduğu tabloları (Doküman, Versiyon, Gizlilik)
 * ve veri tiplerini (Casting) güvenli bir şekilde yönetmemizi sağlar.
 */
class DocumentEmbedding extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Mass assignment koruması.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'document_id',
        'document_version_id',
        'department_id',
        'privacy_level', 
        'chunk_index',
        'chunk_text',
        'external_vector_id',
        'vector_data',
        'embedding_model',
        'token_count',
    ];

    /**
     * Veritabanı tiplerinin otomatik dönüşümü (Casting).
     *
     * @var array<string, string>
     */
    protected $casts = [
        'chunk_index' => 'integer',
        'token_count' => 'integer',
        'vector_data' => 'array',
    ];

    /**
     * Bu chunk'ın (parçanın) ait olduğu ana doküman.
     */
    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    /**
     * Bu chunk'ın ait olduğu spesifik doküman versiyonu.
     */
    public function version(): BelongsTo
    {
        return $this->belongsTo(DocumentVersion::class, 'document_version_id');
    }

    /**
     * RBAC: Yetkilendirme - İlgili departman kısıtlaması.
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }
}
