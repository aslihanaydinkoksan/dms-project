<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;

class DocumentType extends Model
{
    protected $fillable = ['name', 'slug', 'category', 'description', 'is_active', 'custom_fields'];
    protected $casts = [
        'custom_fields' => 'array', // Veritabanından çıkarken diziye çevirir
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
                /** @var \Spatie\Permission\Models\Permission|null $permission */
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
}
