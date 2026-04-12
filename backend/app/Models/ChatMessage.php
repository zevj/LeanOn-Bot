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
    ];

    public function conversation()
    {
        return $this->belongsTo(Conversation::class);
    }
}
