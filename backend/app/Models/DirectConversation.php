<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DirectConversation extends Model
{
    use HasFactory;

    protected $fillable = [
        'admin_id',
        'student_id',
        'last_message',
        'last_message_at',
        'admin_unread_count',
        'student_unread_count',
    ];

    protected $casts = [
        'last_message_at'      => 'datetime',
        'admin_unread_count'   => 'integer',
        'student_unread_count' => 'integer',
    ];

    public function admin()
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    public function student()
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function messages()
    {
        return $this->hasMany(DirectMessage::class, 'conversation_id');
    }
}
