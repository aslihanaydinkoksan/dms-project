<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DocumentPhysicalMovement extends Model
{
    protected $fillable = ['document_id', 'sender_id', 'receiver_id', 'status', 'location_details', 'comment', 'action_at'];
    protected $casts = ['action_at' => 'datetime'];

    public function document()
    {
        return $this->belongsTo(Document::class);
    }
    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }
    public function receiver()
    {
        return $this->belongsTo(User::class, 'receiver_id');
    }
}
