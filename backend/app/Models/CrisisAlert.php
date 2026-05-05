<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CrisisAlert extends Model
{
    protected $fillable = [
        'user_id',
        'chat_message_id',
        'message',
        'severity',
        'detected_keywords',
        'status',
    ];

    protected $casts = [
        'detected_keywords' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function chatMessage()
    {
        return $this->belongsTo(ChatMessage::class);
    }
}
