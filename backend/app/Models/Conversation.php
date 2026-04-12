<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Conversation extends Model
{
    protected $fillable = [
        'user_id',
        'email',
        'title',
        'last_message',
        'is_saved'
    ];

    public function messages()
    {
        return $this->hasMany(ChatMessage::class);
    }
}
