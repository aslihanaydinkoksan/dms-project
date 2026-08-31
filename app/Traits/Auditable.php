<?php

namespace App\Traits;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Arr;

/**
 * @mixin \Illuminate\Database\Eloquent\Model
 * @property int $id
 * @method static void registerModelEvent(string $event, \Closure|string|array $callback)
 */
trait Auditable
{
    /**
     * Trait model tarafından çağrıldığında otomatik tetiklenir (Boot).
     */
    public static function bootAuditable(): void
    {
        // 1. Yeni kayıt oluşturulduğunda
        static::registerModelEvent('created', function ($model) {
            $model->logAudit('create');
        });

        // 2. Mevcut kayıt güncellendiğinde
        static::registerModelEvent('updated', function ($model) {
            if (!empty(Arr::except($model->getChanges(), ['updated_at']))) {
                $model->logAudit('update');
            }
        });

        // 3. Kayıt silindiğinde
        static::registerModelEvent('deleted', function ($model) {
            $model->logAudit('delete');
        });

        // 4. Kayıt geri getirildiğinde (SoftDeletes kullanan modeller için)
        if (method_exists(static::class, 'restored')) {
            static::registerModelEvent('restored', function ($model) {
                $model->logAudit('restore');
            });
        }
    }

    /**
     * İşlemi AuditLog tablosuna yazar.
     * 
     * @param string $event
     */
    protected function logAudit(string $event): void
    {
        $oldValues = [];
        $newValues = [];

        if ($event === 'update') {
            $newValues = Arr::except($this->getChanges(), ['updated_at']);
            $oldValues = Arr::only($this->getOriginal(), array_keys($newValues));
        } elseif ($event === 'create') {
            $newValues = Arr::except($this->getAttributes(), ['created_at', 'updated_at']);
        } elseif ($event === 'delete') {
            $oldValues = Arr::except($this->getAttributes(), ['created_at', 'updated_at']);
        }

        AuditLog::create([
            'user_id' => Auth::id(),
            'event' => $event,
            'auditable_type' => static::class,
            'auditable_id' => $this->id,
            'old_values' => empty($oldValues) ? null : $oldValues,
            'new_values' => empty($newValues) ? null : $newValues,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
}
