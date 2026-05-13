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

        // 2. MUTLAK GÜÇ
        $isAdmin = $user->id === 1 || $user->hasAnyRole(['Super Admin', 'Admin']) ||
            $delegators->contains(function (\App\Models\User $d) {
                return $d->id === 1 || $d->hasAnyRole(['Super Admin', 'Admin']);
            });

        if ($isAdmin) {
            return $query;
        }

        // 3. "Tüm Belgeleri Gör" yetkisi
        $hasViewAll = $user->hasPermissionTo('document.view_all') ||
            $delegators->contains(function (\App\Models\User $d) {
                return $d->hasPermissionTo('document.view_all');
            });

        if ($hasViewAll) {
            return $query;
        }

        $allRoleIds = array_merge($user->roles->pluck('id')->toArray(), $delegators->flatMap->roles->pluck('id')->toArray());

        // 4. KUSURSUZ İZOLASYON KURALI (Granular VEYA (Departman VE Matris))
        return $query->where(function (Builder $q) use ($allUserIds, $allDeptIds, $allRoleIds) {

            // KURAL A: Kullanıcıya VEYA Vekiline ÖZEL (Granular) İzin Verilmişse her zaman göster
            $q->whereHas('specificUsers', function (Builder $sq) use ($allUserIds) {
                $sq->whereIn('users.id', $allUserIds);
            })

                // KURAL B: DEPARTMAN VE MATRİS KESİŞİMİ (AND)
                ->orWhere(function (Builder $subQ) use ($allDeptIds, $allRoleIds) {

                    // ŞART 1: Departman İzolasyonu (ZORUNLU)
                    $subQ->where(function ($deptQ) use ($allDeptIds) {
                        $deptQ->doesntHave('departments') // Global klasör
                            ->orWhereHas('departments', function (Builder $dq) use ($allDeptIds) {
                                $dq->whereIn('departments.id', $allDeptIds); // Kendi departmanı
                            });
                    })

                        // ŞART 2: Matris Kontrolü (ZORUNLU KESİŞİM)
                        ->where(function ($matrixQ) use ($allRoleIds) {
                            $matrixQ->doesntHave('rolePermissions') // Matris boşsa geç
                                ->orWhereHas('rolePermissions', function (Builder $roleQ) use ($allRoleIds) {
                                    $roleQ->whereIn('role_id', $allRoleIds)->where('can_view', true); // Matris varsa rolden geçmeli
                                });
                        });
                });
        });
    }

    public function getBreadcrumbs()
    {
        $breadcrumbs = collect([]);
        $current = $this;
        while ($current) {
            $breadcrumbs->prepend($current);
            $current = $current->parent;
        }
        return $breadcrumbs;
    }

    public function rolePermissions()
    {
        return $this->hasMany(FolderRolePermission::class);
    }

    public function specificUsers()
    {
        return $this->belongsToMany(User::class, 'folder_user_permissions')
            ->using(FolderUserPermission::class)
            ->withPivot('access_level')
            ->withTimestamps();
    }
}
