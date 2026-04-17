<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use Notifiable, SoftDeletes, HasRoles;

    protected $fillable = [
        'name',
        'email',
        'password',
        'vault_password',
        'department_id',
        'is_active',
        'last_login_at',
        'can_manage_acl',
        'locale',
    ];

    protected $hidden = [
        'password',
        'vault_password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'is_active' => 'boolean',
        'last_login_at' => 'datetime',
    ];

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function approvals(): HasMany
    {
        return $this->hasMany(DocumentApproval::class);
    }
    // Benim başkalarına verdiğim vekaletler
    public function givenDelegations()
    {
        return $this->hasMany(UserDelegation::class, 'delegator_id');
    }

    // Başkalarının bana verdiği vekaletler
    public function receivedDelegations()
    {
        return $this->hasMany(UserDelegation::class, 'proxy_id');
    }

    // ŞU ANDA KİMLERİN YERİNE İMZA ATABİLİRİM? 
    public function getActiveDelegatorIds(): array
    {
        return $this->receivedDelegations()
            ->where('is_active', true)
            ->where('start_date', '<=', now())
            ->where('end_date', '>=', now())
            ->whereHas('delegator', function ($query) {
                $query->where('is_active', true)->whereNull('deleted_at');
            })
            ->pluck('delegator_id')
            ->toArray();
    }
    /**
     * Şifre sıfırlama bildirimini ezer (Override) ve kendi Türkçe şablonumuzu yollar.
     */
    public function sendPasswordResetNotification($token)
    {
        $this->notify(new \App\Notifications\CustomResetPasswordNotification($token));
    }
    //  Kullanıcının özel yetkisi olduğu klasörler
    public function specificFolders()
    {
        return $this->belongsToMany(Folder::class, 'folder_user_permissions')
            ->withPivot('access_level')
            ->withTimestamps();
    }
    public function favorites()
    {
        return $this->belongsToMany(Document::class, 'document_user_favorites')
            ->withPivot('note')
            ->withTimestamps();
    }
    /**
     * Laravel'in varsayılan bildirim modelini kendi Soft Delete özellikli modelimizle eziyoruz.
     */
    public function notifications()
    {
        return $this->morphMany(\App\Models\Notification::class, 'notifiable')->latest();
    }
}
