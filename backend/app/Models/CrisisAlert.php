<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CrisisAlert extends Model
{
    protected $fillable = [
        'user_id',
        'chat_message_id',
        'department',
        'gender',
        'message',
        'severity',
        'detected_keywords',
        'flag_reason',
        'status',
        'is_classified',
    ];

    protected $casts = [
        'detected_keywords' => 'array',
        'is_classified'     => 'boolean',
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
