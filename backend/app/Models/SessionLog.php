<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SessionLog extends Model
{
    protected $fillable = [
        'user_id',
        'session_start',
        'session_end',
    ];

    protected $casts = [
        'session_start' => 'datetime',
        'session_end'   => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
