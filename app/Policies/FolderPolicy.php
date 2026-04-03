<?php

namespace App\Policies;

use App\Models\Folder;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class FolderPolicy
{
    use HandlesAuthorization;

    // Sadece Super Admin ve Admin silebilir
    public function delete(User $user, Folder $folder): bool
    {
        return $user->hasAnyRole(['Super Admin', 'Admin']);
    }
}
