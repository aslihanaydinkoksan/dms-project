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
        'physical_route',
        'delivered_to_user_id',
        'physical_receipt_status',
        'title',
        'document_number',
        'privacy_level',
        'is_locked',
        'locked_by',
        'status',
        'document_type_id',
        'contract_party',
        'contract_amount',
        'contract_duration',
        'system_article_no',
        'related_department_id',
        'department_retention_years',
        'archive_retention_years',
        'metadata',
        'expire_at'
    ];

    protected $casts = [
        'is_locked' => 'boolean',
        'metadata' => 'array',
        'physical_route' => 'array',
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

    // --- LİSTELEME GÜVENLİK ZIRHI (3D MATRİS VE VEKALET ENTEGRELİ) ---
    public function scopeAuthorizedForUser(Builder $query, User $user)
    {
        $delegatorIds = $user->getActiveDelegatorIds();
        $allUserIds = array_merge([$user->id], $delegatorIds);
        $delegators = User::with('roles')->whereIn('id', $delegatorIds)->get();

        // 1. ADMİN Mİ?
        $isAdmin = $user->id === 1 || $user->hasAnyRole(['Super Admin', 'Admin']) ||
            $delegators->contains(function (User $d) {
                return $d->id === 1 || $d->hasAnyRole(['Super Admin', 'Admin']);
            });

        // 2. GOD-MODE (Tüm Belgeleri Görüntüleme) YETKİSİ VAR MI?
        $hasViewAll = $user->hasPermissionTo('document.view_all') ||
            $delegators->contains(function (User $d) {
                return $d->hasPermissionTo('document.view_all');
            });

        // EĞER ADMİN VEYA SÜPER OKUYUCU İSE, HİÇBİR KISITLAMA UYGULAMADAN TÜM SORGULARI VER!
        if ($isAdmin || $hasViewAll) {
            return $query;
        }

        // Matris Rollere Vekilleri de Ekle
        $allRoleIds = array_merge($user->roles->pluck('id')->toArray(), $delegators->flatMap->roles->pluck('id')->toArray());

        $allowedDocumentTypeNames = \Illuminate\Support\Facades\DB::table('role_category_permissions')
            ->whereIn('role_id', $allRoleIds)
            ->where('can_view', 1)
            ->pluck('category')
            ->toArray();

        $hasStrictlyConfidential = $user->hasPermissionTo('document.view_strictly_confidential') ||
            $delegators->contains(function (User $d) {
                return $d->hasPermissionTo('document.view_strictly_confidential');
            });

        // DİKKAT: "use" kısmından $hasViewAll'u silebiliriz artık, çünkü yukarıda erken çıkış yaptık.
        return $query->where(function ($q) use ($allUserIds, $allowedDocumentTypeNames, $hasStrictlyConfidential, $user) {

            // KURAL A: Kendisinin VEYA Vekilinin Yüklediği Belgeler (Taslak dahil her şeyi görür)
            $q->whereHas('versions', function ($versionQuery) use ($allUserIds) {
                $versionQuery->whereIn('created_by', $allUserIds);
            })
                // KURAL B: Kendisinin VEYA Vekilinin ONAY ZİNCİRİNDE Olduğu Belgeler
                ->orWhereHas('approvals', function ($approvalQuery) use ($allUserIds) {
                    $approvalQuery->whereIn('user_id', $allUserIds);
                })
                ->orWhereHas('specificUsers', function ($s) use ($allUserIds) {
                    $s->whereIn('users.id', $allUserIds);
                })
                // KURAL C: Matris, Klasör ve Gizlilik Kuralları
                ->orWhere(function ($subQ) use ($allowedDocumentTypeNames, $hasStrictlyConfidential, $user) {

                    // Çok gizli (strictly_confidential) yetkisi yoksa o belgeleri direkt kalkanın dışında bırak
                    if (!$hasStrictlyConfidential) {
                        $subQ->where('privacy_level', '!=', 'strictly_confidential');
                    }

                    $subQ->where(function ($accessQ) use ($allowedDocumentTypeNames, $user) {
                        // (Artık burada if($hasViewAll) kontrolüne gerek kalmadı, yukarıda hallettik)

                        // 1. Kategori Matrisi Yetkisi
                        $accessQ->whereHas('documentType', function ($typeQ) use ($allowedDocumentTypeNames) {
                            $typeQ->whereIn('name', $allowedDocumentTypeNames);
                        })
                            // 2. Kategori Yoksa ve Public ise
                            ->orWhere(function ($publicQ) {
                                $publicQ->whereNull('document_type_id')->where('privacy_level', 'public');
                            })
                            // 3. KLASÖR VE DEPARTMAN BAZLI ERİŞİM
                            ->orWhere(function ($folderLogicQ) use ($user) {
                                // Sadece yayınlanmış/onaylanmış/arşivlenmiş belgeleri görebilir (Başkasının taslaklarını göremez)
                                $folderLogicQ->whereIn('status', ['published', 'approved', 'archived'])
                                    ->whereHas('folder', function ($folderQ) use ($user) {
                                        $folderQ->where(function ($q) use ($user) {
                                            // DURUM 1: Klasörün hiç departmanı yoksa (Yani GLOBAL / Herkese Açık klasörse)
                                            $q->doesntHave('departments');

                                            // DURUM 2: VEYA Klasör kullanıcının kendi departmanına atanmışsa
                                            if ($user->department_id) {
                                                $q->orWhereHas('departments', function ($depQ) use ($user) {
                                                    $depQ->where('departments.id', $user->department_id);
                                                });
                                            }
                                        });
                                    });
                            });
                    });
                });
        });
    }
    /**
     * Zeki Arama Motoru (İlişkisel Tablolar ve Türkçe Çeviri Dahil)
     */
    // public function scopeAdvancedSearch($query, $term)
    // {
    //     if (empty($term)) {
    //         return $query;
    //     }

    //     $searchTerm = "%{$term}%";
    //     $termLower = mb_strtolower($term, 'UTF-8');

    //     // --- TÜRKÇE STATÜ / GİZLİLİK ÇEVİRMENİ ---
    //     $mappedTerms = [];

    //     // Statü Çevirileri
    //     if (str_contains($termLower, 'onay')) array_push($mappedTerms, 'pending_approval', 'approved', 'pending');
    //     if (str_contains($termLower, 'red')) array_push($mappedTerms, 'rejected');
    //     if (str_contains($termLower, 'taslak')) array_push($mappedTerms, 'draft');
    //     if (str_contains($termLower, 'yay')) array_push($mappedTerms, 'published');
    //     if (str_contains($termLower, 'arşiv')) array_push($mappedTerms, 'archived');

    //     // Gizlilik Çevirileri
    //     if (str_contains($termLower, 'genel')) array_push($mappedTerms, 'public');
    //     if (str_contains($termLower, 'özel') || str_contains($termLower, 'gizli')) array_push($mappedTerms, 'confidential', 'strictly_confidential');

    //     return $query->where(function ($q) use ($searchTerm, $mappedTerms) {

    //         // 1. FİZİKSEL KOLONLARDA ARAMA (Başlık, Numara vb.)
    //         $q->where('title', 'like', $searchTerm)
    //             ->orWhere('document_number', 'like', $searchTerm)
    //             ->orWhere('category', 'like', $searchTerm)
    //             ->orWhere('contract_party', 'like', $searchTerm);

    //         // 2. ÇEVİRİLMİŞ STATÜ/GİZLİLİK ARAMASI
    //         if (!empty($mappedTerms)) {
    //             $q->orWhereIn('status', $mappedTerms)
    //                 ->orWhereIn('privacy_level', $mappedTerms);
    //         } else {
    //             $q->orWhere('status', 'like', $searchTerm)
    //                 ->orWhere('privacy_level', 'like', $searchTerm);
    //         }

    //         // 3. İLİŞKİSEL TABLOLARDA (KLASÖR ADI) ARAMA
    //         $q->orWhereHas('folder', function ($subQ) use ($searchTerm) {
    //             $subQ->where('name', 'like', $searchTerm);
    //         })

    //             // 4. İLİŞKİSEL TABLOLARDA (BELGE SAHİBİ/YÜKLEYEN) ARAMA
    //             ->orWhereHas('versions', function ($vQ) use ($searchTerm) {
    //                 $vQ->where('is_current', true)
    //                     ->whereHas('createdBy', function ($uQ) use ($searchTerm) {
    //                         $uQ->where('name', 'like', $searchTerm);
    //                     });
    //             });
    //     });
    // }
    /**
     * SADECE metinsel verilerde arama yapar.
     * KULLANIM: $query->advancedSearch($keyword);
     */
    public function scopeAdvancedSearch(Builder $query, string $term)
    {
        // Eğer arama kelimesi boşsa veya sadece boşluklardan oluşuyorsa sorguyu bozma, aynen geri döndür.
        if (empty(trim($term))) {
            return $query;
        }

        $term = '%' . trim($term) . '%';

        // LIKE aramalarını grupluyoruz
        return $query->where(function ($q) use ($term) {
            $q->where('title', 'LIKE', $term)
                ->orWhere('document_number', 'LIKE', $term)

                // YENİ: Doküman Tipi adında arama (Kategori kaldırıldığı için relation kullanıyoruz)
                ->orWhereHas('documentType', function ($typeQuery) use ($term) {
                    $typeQuery->where('name', 'LIKE', $term);
                })

                // Klasör adında arama
                ->orWhereHas('folder', function ($folderQuery) use ($term) {
                    $folderQuery->where('name', 'LIKE', $term);
                })

                // YENİ ve ÖNEMLİ: Belgeyi yükleyen KİŞİNİN adında arama (Aslıhan Aydın araması burada çalışır)
                ->orWhereHas('currentVersion.createdBy', function ($userQuery) use ($term) {
                    $userQuery->where('name', 'LIKE', $term);
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
        // Sabit match yerine, controller'da yaptığın gibi veritabanından çekiyoruz
        $privacyLevels = \App\Models\SystemSetting::getByKey('privacy_levels', [
            'public' => 'Herkese Açık',
            'confidential' => 'Hizmete Özel',
            'strictly_confidential' => 'Çok Gizli'
        ]);

        // Key eşleşiyorsa veritabanındaki adını al, eşleşmiyorsa Bilinmiyor de
        return $privacyLevels[$this->privacy_level] ?? 'Bilinmiyor (' . $this->privacy_level . ')';
    }
    public function relatedDepartment()
    {
        return $this->belongsTo(Department::class, 'related_department_id');
    }
    /**
     * Dinamik Statü Rengi Accessor'ı
     */
    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'published', 'approved' => 'theme-success',
            'rejected' => 'theme-danger',
            'pending', 'pending_approval' => 'theme-warning',
            'draft', 'archived' => 'theme-secondary',
            default => 'theme-secondary',
        };
    }

    /**
     * Dinamik Gizlilik Rengi Accessor'ı
     */
    public function getPrivacyColorAttribute(): string
    {
        return match ($this->privacy_level) {
            'public' => 'theme-info',
            'department' => 'theme-primary',
            'confidential', 'strictly_confidential' => 'theme-warning',
            default => 'theme-secondary',
        };
    }
    public function documentType(): BelongsTo
    {
        return $this->belongsTo(DocumentType::class);
    }
    public function favoritedBy()
    {
        return $this->belongsToMany(User::class, 'document_user_favorites')
            ->withPivot('note')
            ->withTimestamps();
    }
    public function scopeSearchInFavorites(Builder $query, string $keyword)
    {
        if (empty($keyword)) return $query;

        return $query->where(function ($q) use ($keyword) {
            $q->where('title', 'like', "%{$keyword}%")
                ->orWhere('document_number', 'like', "%{$keyword}%");
        });
    }
    // Fiziksel Hareket Geçmişi
    public function physicalMovements()
    {
        return $this->hasMany(DocumentPhysicalMovement::class)->latest('action_at');
    }

    // En Güncel Fiziksel Hareket
    public function latestPhysicalMovement()
    {
        return $this->hasOne(DocumentPhysicalMovement::class)->latestOfMany('action_at');
    }

    // Fiziksel Durum Özelliği (Sanal Kolon)
    public function getCurrentPhysicalStatusAttribute()
    {
        return $this->latestPhysicalMovement ? $this->latestPhysicalMovement->status : 'none';
    }
}
