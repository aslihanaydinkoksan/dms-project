<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;

class DocumentType extends Model
{
    protected $fillable = ['department_id', 'name', 'slug',  'description', 'is_active', 'custom_fields', 'requires_expiration_date', 'is_form_based',];
    protected $casts = [
        'custom_fields' => 'array', // Veritabanından çıkarken diziye çevirir
        'requires_expiration_date' => 'boolean',
        'is_active' => 'boolean',
        'is_form_based' => 'boolean',
    ];

    // İsim girildiğinde slug otomatik dolsun
    protected static function boot()
    {
        parent::boot();

        // 1. Kaydedilmeden Önce: Slug (URL dostu isim) oluştur
        static::saving(function ($model) {
            if (empty($model->slug)) {
                $model->slug = Str::slug($model->name);
            }
        });

        // 2. YENİ: Kaydedildikten Sonra (Observer): Spatie Yetkilerini Otomatik Üret!
        static::created(function ($documentType) {
            $actions = ['view', 'create', 'edit', 'delete'];
            foreach ($actions as $action) {
                Permission::firstOrCreate([
                    'name' => $documentType->slug . '.' . $action
                ]);
            }
        });

        // 3. YENİ: Silindiğinde (Observer): Çöpleri (Yetkileri) Temizle!
        static::deleted(function ($documentType) {
            $actions = ['view', 'create', 'edit', 'delete'];
            foreach ($actions as $action) {
                /** @var Permission|null $permission */
                $permission = Permission::where('name', $documentType->slug . '.' . $action)->first();
                if ($permission) {
                    $permission->delete();
                }
            }
        });
    }

    public function documents()
    {
        return $this->hasMany(Document::class);
    }
    // 2. Department İlişkisi
    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    // 3. Dinamik Yetki Kalkanı (Local Scope)
    /**
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param \App\Models\User $user
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeVisibleToUser($query, $user)
    {
        // Eğer kullanıcı Super Admin ise her şeyi görebilir (Opsiyonel Kurumsal Kural)
        if ($user && $user->hasRole('Super Admin')) {
            return $query;
        }

        // Global şablonlar (Null) VEYA kullanıcının kendi departmanına atanmış şablonlar
        return $query->where(function ($q) use ($user) {
            $q->whereNull('department_id');

            if ($user && $user->department_id) {
                $q->orWhere('department_id', $user->department_id);
            }
        });
    }
}
