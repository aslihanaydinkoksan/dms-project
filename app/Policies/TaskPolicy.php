<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Task;
use Illuminate\Auth\Access\HandlesAuthorization;

class TaskPolicy
{
    use HandlesAuthorization;

    /**
     * URL Kaçak Giriş Engelleme / Süreç Detay Görme Yetkisi
     */
    public function view(User $user, Task $task): bool
    {
        // Global Yetki Kontrolü
        if ($user->hasRole('Super Admin') || $user->can('task.view_all')) {
            return true;
        }

        // ABAC Bellek İçi Karar Doğrulama Mekanizması
        // N+1 kalkanı için ilişkileri eksikse yükle
        $task->loadMissing(['template.mandatoryGroup', 'users']);

        // a) Süreci başlatan kişi mi?
        $isCreator = $task->creator_id === $user->id;

        // b) Kullanıcı ile sürecin departmanı eşleşiyor mu?
        $isSameDepartment = $task->template && $task->template->department_id === $user->department_id;

        // c) Ad-Hoc proje ekibinde yer alıyor mu?
        $isAdHocMember = $task->users->contains($user->id);

        // d) Şablonun zorunlu çekirdek ekibinde / grubunda mı?
        $isInMandatoryGroup = false;
        if ($task->template && $task->template->mandatoryGroup) {
            $isInMandatoryGroup = $task->template->mandatoryGroup->members->contains($user->id);
        }

        // Karar Ağacı Doğrulaması
        return $isCreator || $isSameDepartment || $isAdHocMember || $isInMandatoryGroup;
    }
    /**
     * Yeni Süreç Başlatma Yetkisi
     */
    public function create(User $user): bool
    {
        return $user->can('task.create') || $user->hasAnyRole(['Super Admin', 'Admin']);
    }

    /**
     * Süreci (Görev Formunu) Düzenleme Yetkisi
     */
    public function update(User $user, Task $task): bool
    {
        // 1. İşin açık (active) olması şarttır!
        if ($task->status !== 'active') {
            return false;
        }

        // 2. Super Admin veya Admin bypass
        if ($user->hasAnyRole(['Super Admin', 'Admin'])) {
            return true;
        }

        // 3. İşi başlatan düzenleyebilir
        if ($task->creator_id === $user->id) {
            return true;
        }

        // 4. Proje Yöneticisi ('manager' rolüyle atanan ekip üyesi) düzenleyebilir
        $isManager = \Illuminate\Support\Facades\DB::table('task_user')
            ->where('task_id', $task->id)
            ->where('user_id', $user->id)
            ->where('role', 'manager')
            ->exists();

        return $isManager;
    }

    /**
     * Kanban Tahtasında Kartı Sürükleme (Move) Yetkisi
     */
    public function move(User $user, Task $task): bool
    {
        // Kanban taşıma kuralları genelde Update (Düzenleme) kurallarıyla aynıdır
        return $this->update($user, $task);
    }
}
