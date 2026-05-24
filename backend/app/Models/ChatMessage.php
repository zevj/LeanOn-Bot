<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChatMessage extends Model
{
    protected $fillable = [
        'user_id',
        'conversation_id',
        'message',
        'reply',
        'is_crisis',
        'is_fallback',
    ];

    public function conversation()
    {
        return $this->belongsTo(Conversation::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function crisisAlert()
    {
        return $this->hasOne(CrisisAlert::class);
    }
}
