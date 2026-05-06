<?php

namespace App\Observers;

use App\Models\FolderRolePermission;
use App\Models\FolderUserPermission;
use App\Models\User;
use App\Notifications\FolderPermissionChanged;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class FolderPermissionObserver
{
    /**
     * Tüm 'created' (oluşturma) olaylarını yakalar.
     */
    public function created(mixed $model): void
    {
        Log::info('FolderPermissionObserver tetiklendi: ' . get_class($model));

        if ($model instanceof FolderUserPermission) {
            $this->notifyUser($model->user_id, $model->folder_id, $model->access_level);
        }

        if ($model instanceof FolderRolePermission) {
            $this->notifyRelevantManagers($model);
        }
    }

    /**
     * Tüm 'updated' (güncelleme) olaylarını yakalar.
     */
    public function updated(mixed $model): void
    {
        if ($model instanceof FolderUserPermission) {
            $this->notifyUser($model->user_id, $model->folder_id, $model->access_level);
        }

        if ($model instanceof FolderRolePermission) {
            $this->notifyRelevantManagers($model);
        }
    }

    protected function notifyUser(int|string $userId, int|string $folderId, string $level): void
    {
        $user = User::find($userId);
        $folder = \App\Models\Folder::find($folderId);
        $assigner = Auth::user();

        if ($user && $folder && $assigner && $user->id !== $assigner->id) {
            $user->notify(new FolderPermissionChanged($folder, $level, $assigner));
            Log::info('Granular bildirim gönderildi: ' . $user->email);
        }
    }

    protected function notifyRelevantManagers(FolderRolePermission $permission): void
    {
        /** @var \App\Models\User|null $assigner */
        $assigner = Auth::user();
        if (!$assigner) return;

        // ÇÖZÜM: Intelephense'in en sevdiği standart Laravel Collection PHPDoc formatı:
        /** @var \Illuminate\Database\Eloquent\Collection|\App\Models\User[] $users */
        $users = User::role($permission->role_id)->get();

        $folder = \App\Models\Folder::find($permission->folder_id);

        if (!$folder) return;

        foreach ($users as $user) {
            /** @var \App\Models\User $validUser */
            $validUser = $user;

            if ($validUser->id !== $assigner->id) {
                $level = $permission->can_manage ? 'manage' : ($permission->can_upload ? 'upload' : 'read');
                $validUser->notify(new FolderPermissionChanged($folder, $level, $assigner));
            }
        }
    }
}
