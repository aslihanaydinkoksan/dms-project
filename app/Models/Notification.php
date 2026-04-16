<?php

namespace App\Models;

use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Database\Eloquent\SoftDeletes;

class Notification extends DatabaseNotification
{
    use SoftDeletes; // İşte sihir burada! Artık bu model silinmek yerine çöp kutusuna gidecek.
}
