<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserDelegation extends Model
{
    protected $fillable = ['delegator_id', 'proxy_id', 'start_date', 'end_date', 'is_active', 'reason'];
    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'is_active' => 'boolean'
    ];

    public function delegator()
    {
        return $this->belongsTo(User::class, 'delegator_id');
    }
    public function proxy()
    {
        return $this->belongsTo(User::class, 'proxy_id');
    }
}
