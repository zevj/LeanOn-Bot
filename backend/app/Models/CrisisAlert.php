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
        'admin_email_sent_at',
        'admin_email_notified',
        'appointment_date',
        'appointment_time',
        'appointment_status',
    ];

    protected $casts = [
        'detected_keywords'    => 'array',
        'is_classified'        => 'boolean',
        'admin_email_sent_at'  => 'datetime',
        'admin_email_notified' => 'boolean',
        'appointment_date'     => 'date:Y-m-d',
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
