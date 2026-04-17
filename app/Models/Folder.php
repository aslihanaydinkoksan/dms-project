<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

class Folder extends Model
{
    use SoftDeletes;

    protected $fillable = ['name', 'parent_id', 'created_by', 'prefix'];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Folder::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Folder::class, 'parent_id');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }
    public function departments()
    {
        return $this->belongsToMany(Department::class, 'department_folder');
    }
    /**
     * N-Derinlikte tüm alt klasörleri getiren Recursive (Özyinelemeli) İlişki.
     * Bu yapı Frontend'deki TreeView bileşenleri için kusursuz bir JSON üretir.
     */
    public function childrenRecursive(): HasMany
    {
        return $this->children()->with('childrenRecursive');
    }
    // --- GÜVENLİK ZIRHI (İZOLASYON VE VEKALET ENTEGRELİ) ---
    public function scopeVisibleTo(Builder $query, User $user)
    {
        // 1. KİMLİK GENİŞLETMESİ (Vekalet Edenleri Bul)
        $delegatorIds = $user->getActiveDelegatorIds();
        $allUserIds = array_merge([$user->id], $delegatorIds);
        $delegators = \App\Models\User::with('roles')->whereIn('id', $delegatorIds)->get();

        // Vekillerin departmanlarını da listeye ekle
        $allDeptIds = array_filter(array_merge([$user->department_id], $delegators->pluck('department_id')->toArray()));

        // 2. MUTLAK GÜÇ (Kullanıcı veya Vekalet Edenlerden Biri Admin mi?)
        $isAdmin = $user->id === 1 || $user->hasAnyRole(['Super Admin', 'Admin']) ||
            $delegators->contains(function (\App\Models\User $d) {
                return $d->id === 1 || $d->hasAnyRole(['Super Admin', 'Admin']);
            });

        if ($isAdmin) {
            return $query;
        }

        // 3. "Tüm Belgeleri Gör" yetkisi (Kullanıcı veya Vekillerinde var mı?)
        $hasViewAll = $user->hasPermissionTo('document.view_all') ||
            $delegators->contains(function (\App\Models\User $d) {
                return $d->hasPermissionTo('document.view_all');
            });

        if ($hasViewAll) {
            return $query;
        }

        // 4. ÇOKLU İZOLASYON KURALI (Miras, Granular ve Vekalet Entegreli)
        return $query->where(function (Builder $q) use ($allUserIds, $allDeptIds) {
            $q->doesntHave('departments') // A) Global klasörler
                ->orWhereHas('departments', function (Builder $sq) use ($allDeptIds) {
                    $sq->whereIn('departments.id', $allDeptIds); // B) Kendisinin VEYA Vekalet Edenlerin Departmanları
                })
                ->orWhereHas('specificUsers', function (Builder $sq) use ($allUserIds) {
                    $sq->whereIn('users.id', $allUserIds); // C) Kendisine VEYA Vekalet Edenlere özel tanımlanmış
                });
        });
    }

    // --- BREADCRUMB (YOL İZİ) ÜRETİCİ ---
    public function getBreadcrumbs()
    {
        $breadcrumbs = collect([]);
        $current = $this;
        while ($current) {
            $breadcrumbs->prepend($current);
            $current = $current->parent;
        }
        return $breadcrumbs; // Örn: [Ana Dizin, İK, 2026 Raporları]
    }
    public function rolePermissions()
    {
        return $this->hasMany(FolderRolePermission::class);
    }
    // Klasöre özel tanımlanmış istisna kullanıcılar
    public function specificUsers()
    {
        return $this->belongsToMany(User::class, 'folder_user_permissions')
            ->withPivot('access_level')
            ->withTimestamps();
    }
}
