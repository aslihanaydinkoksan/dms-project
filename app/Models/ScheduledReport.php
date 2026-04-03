<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ScheduledReport extends Model
{
    protected $fillable = [
        'report_name',
        'module',
        'frequency',
        'date_range',
        'format',
        'recipients',
        'last_sent_at',
        'is_active',
        'last_run_at',
    ];
}
