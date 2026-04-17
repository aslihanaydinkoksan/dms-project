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
    // --- GÜVENLİK ZIRHI (İZOLASYON) ---
    public function scopeVisibleTo(Builder $query, $user)
    {
        // 1. Sistem Yöneticileri (ID:1 veya Roller) HER ŞEYİ görür
        if ($user->id === 1 || $user->hasAnyRole(['Super Admin', 'Admin'])) {
            return $query;
        }

        // 2. "Tüm Belgeleri Gör" yetkisine sahip olanlar
        $hasViewAll = false;
        try {
            $hasViewAll = $user->hasPermissionTo('document.view_all');
        } catch (\Exception $e) {
        }

        if ($hasViewAll) {
            return $query;
        }

        // 3. ÇOKLU İZOLASYON KURALI (Magic Happens Here)
        return $query->where(function (Builder $q) use ($user) {
            // A) Hiçbir departmana bağlı olmayan (Global) klasörler
            $q->doesntHave('departments')
                // B) VEYA kullanıcının kendi departmanının eklendiği klasörler
                ->orWhereHas('departments', function (Builder $sq) use ($user) {
                    $sq->where('departments.id', $user->department_id);
                })
                // C) VEYA KULLANICIYA ÖZEL "GRANULAR" YETKİ VERİLMİŞ KLASÖRLER (ASLIHAN'I KURTARAN SATIR)
                ->orWhereHas('specificUsers', function (Builder $sq) use ($user) {
                    $sq->where('users.id', $user->id);
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
