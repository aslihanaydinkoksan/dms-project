<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BotIntent extends Model
{
    protected $fillable = [
        'intent_name',
        'keywords',
        'response_text',
        'action_route',
        'action_button_text'
    ];
    // JSON kolonunu otomatik diziye çevirir (Mutator/Casting)
    protected $casts = [
        'keywords' => 'array'
    ];
}
