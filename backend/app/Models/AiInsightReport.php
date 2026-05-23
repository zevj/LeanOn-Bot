<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiInsightReport extends Model
{
    protected $fillable = [
        'analytics_snapshot_id',
        'report_type',
        'period_start',
        'period_end',
        'analytics_snapshot',
        'insights',
        'recommendations',
        'trends',
        'wellness_summary',
        'anomalies',
        'ai_provider',
        'status',
        'error_message',
        'request_count',
        'generated_at',
        'cache_expires_at',
        'metadata',
    ];

    protected $casts = [
        'period_start'    => 'date',
        'period_end'      => 'date',
        'analytics_snapshot' => 'array',
        'insights'        => 'array',
        'recommendations' => 'array',
        'trends'          => 'array',
        'anomalies'       => 'array',
        'metadata'        => 'array',
        'generated_at'    => 'datetime',
        'cache_expires_at' => 'datetime',
    ];

    public function analyticsSnapshot()
    {
        return $this->belongsTo(AnalyticsSnapshot::class);
    }
}
