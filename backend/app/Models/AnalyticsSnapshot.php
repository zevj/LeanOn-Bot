<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AnalyticsSnapshot extends Model
{
    protected $fillable = [
        'snapshot_date',
        'daily_active_users',
        'total_conversations',
        'new_conversations',
        'total_messages',
        'avg_session_minutes',
        'peak_usage_hours',
        'emotion_distribution',
        'topic_frequency',
        'crisis_alert_count',
        'fallback_count',
        'sentiment_scores',
        'trend_metrics',
    ];

    protected $casts = [
        'snapshot_date'        => 'date',
        'avg_session_minutes'  => 'decimal:2',
        'peak_usage_hours'     => 'array',
        'emotion_distribution' => 'array',
        'topic_frequency'      => 'array',
        'sentiment_scores'     => 'array',
        'trend_metrics'        => 'array',
    ];

    public function aiInsightReports()
    {
        return $this->hasMany(AiInsightReport::class);
    }
}
