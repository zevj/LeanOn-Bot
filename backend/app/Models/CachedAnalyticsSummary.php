<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CachedAnalyticsSummary extends Model
{
    protected $fillable = [
        'cache_key',
        'summary_type',
        'payload',
        'generated_at',
        'expires_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'generated_at' => 'datetime',
        'expires_at' => 'datetime',
    ];
}
