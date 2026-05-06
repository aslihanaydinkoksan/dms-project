<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class FolderUserPermission extends Pivot
{
    protected $table = 'folder_user_permissions';
    public $incrementing = true; // sync eventlerini tetiklemek için önemli
}
