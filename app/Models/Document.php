<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Builder;
use Laravel\Scout\Searchable;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Spatie\Permission\Exceptions\PermissionDoesNotExist;

class Document extends Model
{
    use SoftDeletes, Searchable;

    protected $fillable = [
        'folder_id',
        'physical_location',
        'delivered_to_user_id',
        'physical_receipt_status',
        'title',
        'document_number',
        'privacy_level',
        'is_locked',
        'locked_by',
        'status',
        'document_type',
        'category',
        'sub_category',
        'contract_party',
        'contract_amount',
        'contract_duration',
        'system_article_no',
        'related_department_id',
        'department_retention_years',
        'archive_retention_years',
        'metadata'
    ];

    protected $casts = [
        'is_locked' => 'boolean',
        'metadata' => 'array',
    ];

    public function folder(): BelongsTo
    {
        return $this->belongsTo(Folder::class);
    }

    public function lockedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'locked_by');
    }

    // Tüm versiyon geçmişi
    public function versions(): HasMany
    {
        return $this->hasMany(DocumentVersion::class);
    }

    // Sadece aktif olan versiyonu getiren özel ilişki (Performans için)
    public function currentVersion(): HasOne
    {
        return $this->hasOne(DocumentVersion::class)->where('is_current', true);
    }

    public function approvals(): HasMany
    {
        return $this->hasMany(DocumentApproval::class);
    }
    /**
     * SCOUT: Arama motoruna gönderilecek (indexlenecek) verilerin haritası.
     * Sadece arama yapılacak alanları buraya ekleyerek arama motorunun şişmesini engelliyoruz.
     */
    public function toSearchableArray(): array
    {
        // İlişkileri yükleyerek arama dizinine dahil ediyoruz
        $this->loadMissing(['currentVersion.createdBy', 'folder']);

        return [
            'id' => $this->id,
            'title' => $this->title,
            'document_number' => $this->document_number,
            'status' => $this->status,
            'privacy_level' => $this->privacy_level,
            // Belgeyi yükleyenin ismini de aramaya dahil ediyoruz
            'owner_name' => $this->currentVersion?->createdBy?->name,
            'folder_name' => $this->folder?->name,
            // 'tags' => $this->tags->pluck('name')->toArray(), // İleride etiket tablosu eklediğimizde
        ];
    }

    // --- LİSTELEME GÜVENLİK ZIRHI (SQL SCOPE) - 3D MATRİS ENTEGRELİ ---
    public function scopeAuthorizedForUser($query, $user)
    {
        if ($user->id === 1 || $user->hasAnyRole(['Super Admin', 'Admin'])) {
            return $query;
        }

        $roleIds = $user->roles->pluck('id');
        $allowedCategories = \Illuminate\Support\Facades\DB::table('role_category_permissions')
            ->whereIn('role_id', $roleIds)
            ->where('can_view', 1)
            ->pluck('category')
            ->toArray();

        $hasStrictlyConfidential = false;
        $hasViewAll = false;
        try {
            $hasStrictlyConfidential = $user->hasPermissionTo('document.view_strictly_confidential');
            $hasViewAll = $user->hasPermissionTo('document.view_all');
        } catch (\Exception $e) {
        }

        return $query->where(function ($q) use ($user, $allowedCategories, $hasStrictlyConfidential, $hasViewAll) {

            // KURAL A: Kendi Yüklediği Belgeler
            $q->whereHas('versions', function ($versionQuery) use ($user) {
                $versionQuery->where('created_by', $user->id);
            })

                // --- YENİ EKLENEN KURAL B: ONAY ZİNCİRİNDE OLDUĞU BELGELER ---
                ->orWhereHas('approvals', function ($approvalQuery) use ($user) {
                    $approvalQuery->where('user_id', $user->id);
                })
                // -----------------------------------------------------------

                // KURAL C: Başkalarının Yüklediği Belgeler (3D Matris ve Gizlilik Kuralları)
                ->orWhere(function ($subQ) use ($allowedCategories, $hasStrictlyConfidential, $hasViewAll) {

                    if (!$hasStrictlyConfidential) {
                        $subQ->where('privacy_level', '!=', 'strictly_confidential');
                    }

                    $subQ->where(function ($accessQ) use ($allowedCategories, $hasViewAll) {
                        if ($hasViewAll) {
                            $accessQ->whereNotNull('id');
                        } else {
                            $accessQ->whereIn('category', $allowedCategories)
                                ->orWhere(function ($publicQ) {
                                    $publicQ->whereNull('category')->where('privacy_level', 'public');
                                });
                        }
                    });
                });
        });
    }
    /**
     * Zeki Arama Motoru (İlişkisel Tablolar ve Türkçe Çeviri Dahil)
     */
    public function scopeAdvancedSearch($query, $term)
    {
        if (empty($term)) {
            return $query;
        }

        $searchTerm = "%{$term}%";
        $termLower = mb_strtolower($term, 'UTF-8');

        // --- TÜRKÇE STATÜ / GİZLİLİK ÇEVİRMENİ ---
        $mappedTerms = [];

        // Statü Çevirileri
        if (str_contains($termLower, 'onay')) array_push($mappedTerms, 'pending_approval', 'approved', 'pending');
        if (str_contains($termLower, 'red')) array_push($mappedTerms, 'rejected');
        if (str_contains($termLower, 'taslak')) array_push($mappedTerms, 'draft');
        if (str_contains($termLower, 'yay')) array_push($mappedTerms, 'published');
        if (str_contains($termLower, 'arşiv')) array_push($mappedTerms, 'archived');

        // Gizlilik Çevirileri
        if (str_contains($termLower, 'genel')) array_push($mappedTerms, 'public');
        if (str_contains($termLower, 'özel') || str_contains($termLower, 'gizli')) array_push($mappedTerms, 'confidential', 'strictly_confidential');

        return $query->where(function ($q) use ($searchTerm, $mappedTerms) {

            // 1. FİZİKSEL KOLONLARDA ARAMA (Başlık, Numara vb.)
            $q->where('title', 'like', $searchTerm)
                ->orWhere('document_number', 'like', $searchTerm)
                ->orWhere('category', 'like', $searchTerm)
                ->orWhere('contract_party', 'like', $searchTerm);

            // 2. ÇEVİRİLMİŞ STATÜ/GİZLİLİK ARAMASI
            if (!empty($mappedTerms)) {
                $q->orWhereIn('status', $mappedTerms)
                    ->orWhereIn('privacy_level', $mappedTerms);
            } else {
                $q->orWhere('status', 'like', $searchTerm)
                    ->orWhere('privacy_level', 'like', $searchTerm);
            }

            // 3. İLİŞKİSEL TABLOLARDA (KLASÖR ADI) ARAMA
            $q->orWhereHas('folder', function ($subQ) use ($searchTerm) {
                $subQ->where('name', 'like', $searchTerm);
            })

                // 4. İLİŞKİSEL TABLOLARDA (BELGE SAHİBİ/YÜKLEYEN) ARAMA
                ->orWhereHas('versions', function ($vQ) use ($searchTerm) {
                    $vQ->where('is_current', true)
                        ->whereHas('createdBy', function ($uQ) use ($searchTerm) {
                            $uQ->where('name', 'like', $searchTerm);
                        });
                });
        });
    }
    /**
     * Belgeye ait etiketleri getirir.
     */
    public function tags(): MorphToMany
    {
        return $this->morphToMany(Tag::class, 'taggable');
    }
    /**
     * Teslim alan kişinin ismini ekrana basabilmek için ilişki
     * @return BelongsTo<User, Document>
     */
    public function deliveredToUser()
    {
        return $this->belongsTo(User::class, 'delivered_to_user_id');
    }
    /**
     * Belgeye özel istisnai yetki verilmiş kullanıcılar (Granular Access)
     */
    public function specificUsers()
    {
        return $this->belongsToMany(User::class, 'document_user_permissions')
            ->withPivot('access_level')
            ->withTimestamps();
    }
    /**
     * SANAL NİTELİK: Belgenin durumunu (status) Türkçe ve okunaklı hale getirir.
     * Kullanımı: $document->status_text
     */
    public function getStatusTextAttribute(): string
    {
        return match ($this->status) {
            'draft' => 'Taslak',
            'pending', 'pending_approval' => 'Onay Bekliyor',
            'published' => 'Yayında',
            'rejected' => 'Reddedildi',
            'archived' => 'Arşivlendi',
            default => 'Bilinmiyor (' . $this->status . ')',
        };
    }

    /**
     * SANAL NİTELİK: Gizlilik seviyesini (privacy_level) Türkçe ve okunaklı hale getirir.
     * Kullanımı: $document->privacy_level_text
     */
    public function getPrivacyLevelTextAttribute(): string
    {
        return match ($this->privacy_level) {
            'public' => 'Herkese Açık',
            'department' => 'Departmana Özel',
            'confidential' => 'Hizmete Özel',
            'strictly_confidential' => 'Çok Gizli',
            default => 'Bilinmiyor (' . $this->privacy_level . ')',
        };
    }
    public function relatedDepartment()
    {
        return $this->belongsTo(Department::class, 'related_department_id');
    }
}
