<?php

namespace App\Services;

use App\Models\AnalyticsSnapshot;
use App\Models\ChatMessage;
use App\Models\Conversation;
use App\Models\CrisisAlert;
use App\Models\EmotionLog;
use App\Models\SessionLog;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Analytics Service
 *
 * Computes and caches anonymized analytics from existing tables.
 * All methods return aggregated statistics only — never PII.
 *
 * Supports PostgreSQL (production), MySQL (local), and SQLite (testing).
 */
class AnalyticsService
{
    /**
     * Get extended dashboard statistics for a given period.
     */
    public function getDashboardStats(string $period = '7d'): array
    {
        $cacheKey = "analytics:dashboard:{$period}";
        $cacheTtl = 3600; // 1 hour

        return Cache::remember($cacheKey, $cacheTtl, function () use ($period) {
            $end = Carbon::now();
            $start = $this->periodToStart($period, $end);
            $prevStart = $this->periodToStart($period, $start);

            // Current period metrics
            $currentDau = $this->getDailyActiveUsers($start, $end);
            $currentConversations = Conversation::whereBetween('created_at', [$start, $end])->count();
            $currentMessages = ChatMessage::whereBetween('created_at', [$start, $end])->count();
            $avgSession = $this->getAvgSessionMinutes($start, $end);
            $crisisCount = CrisisAlert::whereBetween('created_at', [$start, $end])->count();
            $fallbackCount = $this->getFallbackCount($start, $end);

            // Previous period for comparison
            $prevDau = $this->getDailyActiveUsers($prevStart, $start);
            $prevConversations = Conversation::whereBetween('created_at', [$prevStart, $start])->count();
            $prevMessages = ChatMessage::whereBetween('created_at', [$prevStart, $start])->count();

            // Growth calculations
            $dauGrowth = $prevDau > 0 ? round((($currentDau - $prevDau) / $prevDau) * 100, 1) : 0;
            $convGrowth = $prevConversations > 0 ? round((($currentConversations - $prevConversations) / $prevConversations) * 100, 1) : 0;
            $msgGrowth = $prevMessages > 0 ? round((($currentMessages - $prevMessages) / $prevMessages) * 100, 1) : 0;

            // Peak usage hour
            $peakHours = $this->getPeakUsageHours($start, $end);
            $peakHour = !empty($peakHours) ? $peakHours[0]['hour'] : null;

            // Crisis by severity
            $crisisBySeverity = CrisisAlert::whereBetween('created_at', [$start, $end])
                ->selectRaw('severity, COUNT(*) as count')
                ->groupBy('severity')
                ->pluck('count', 'severity')
                ->toArray();

            // Total registered users
            $totalUsers = User::where('role', 'student')->count();

            return [
                'daily_active_users'    => $currentDau,
                'dau_growth'            => $dauGrowth,
                'total_conversations'   => $currentConversations,
                'conversation_growth'   => $convGrowth,
                'total_messages'        => $currentMessages,
                'message_growth'        => $msgGrowth,
                'avg_session_minutes'   => $avgSession,
                'peak_hour'             => $peakHour,
                'peak_usage_hours'      => $peakHours,
                'crisis_alert_count'    => $crisisCount,
                'crisis_by_severity'    => $crisisBySeverity,
                'fallback_count'        => $fallbackCount,
                'total_registered_users' => $totalUsers,
                'period'                => $period,
                'period_start'          => $start->toDateString(),
                'period_end'            => $end->toDateString(),
            ];
        });
    }

    /**
     * Get emotion/sentiment trend data over a period.
     */
    public function getTrends(string $period = '30d'): array
    {
        $cacheKey = "analytics:trends:{$period}";
        $cacheTtl = 3600;

        return Cache::remember($cacheKey, $cacheTtl, function () use ($period) {
            $end = Carbon::now();
            $start = $this->periodToStart($period, $end);

            return [
                'emotion_distribution' => $this->getEmotionDistribution($start, $end),
                'sentiment_over_time'  => $this->getSentimentOverTime($start, $end),
                'peak_usage_hours'     => $this->getPeakUsageHours($start, $end),
                'weekly_comparison'    => $this->getWeeklyComparison(),
                'period_start'         => $start->toDateString(),
                'period_end'           => $end->toDateString(),
            ];
        });
    }

    /**
     * Compute and store a daily snapshot for a given date.
     */
    public function computeDailySnapshot(Carbon $date): AnalyticsSnapshot
    {
        $dayStart = $date->copy()->startOfDay();
        $dayEnd = $date->copy()->endOfDay();

        $data = [
            'snapshot_date'        => $date->toDateString(),
            'daily_active_users'   => $this->getDailyActiveUsers($dayStart, $dayEnd),
            'total_conversations'  => Conversation::whereBetween('created_at', [$dayStart, $dayEnd])->count(),
            'new_conversations'    => Conversation::whereBetween('created_at', [$dayStart, $dayEnd])->count(),
            'total_messages'       => ChatMessage::whereBetween('created_at', [$dayStart, $dayEnd])->count(),
            'avg_session_minutes'  => $this->getAvgSessionMinutes($dayStart, $dayEnd),
            'peak_usage_hours'     => $this->getPeakUsageHours($dayStart, $dayEnd),
            'emotion_distribution' => $this->getEmotionDistribution($dayStart, $dayEnd),
            'topic_frequency'      => $this->getTopicFrequency($dayStart, $dayEnd),
            'crisis_alert_count'   => CrisisAlert::whereBetween('created_at', [$dayStart, $dayEnd])->count(),
            'fallback_count'       => $this->getFallbackCount($dayStart, $dayEnd),
            'sentiment_scores'     => $this->getSentimentScores($dayStart, $dayEnd),
            'trend_metrics'        => $this->getTrendMetrics($dayStart, $dayEnd),
        ];

        return AnalyticsSnapshot::updateOrCreate(
            ['snapshot_date' => $date->toDateString()],
            $data
        );
    }

    /**
     * Get historical snapshots for charting.
     */
    public function getSnapshots(int $days = 30): array
    {
        $cacheKey = "analytics:snapshots:{$days}";

        return Cache::remember($cacheKey, 3600, function () use ($days) {
            $start = Carbon::now()->subDays($days);

            // Ensure recent snapshots exist
            $this->ensureSnapshotsExist($days);

            return AnalyticsSnapshot::where('snapshot_date', '>=', $start->toDateString())
                ->orderBy('snapshot_date')
                ->get()
                ->toArray();
        });
    }

    /**
     * Build anonymized stats payload for AI insights prompt.
     * This is the ONLY data that ever reaches the AI provider.
     */
    public function buildAnonymizedStatsForAI(string $period = 'weekly'): array
    {
        $end = Carbon::now();

        switch ($period) {
            case 'daily':
                $start = $end->copy()->subDay();
                break;
            case 'monthly':
                $start = $end->copy()->subMonth();
                break;
            case 'weekly':
            default:
                $start = $end->copy()->subWeek();
                break;
        }

        $prevEnd = $start->copy();
        $prevStart = $this->periodToStart($period === 'daily' ? '1d' : ($period === 'monthly' ? '30d' : '7d'), $prevEnd);

        $currentDau = $this->getDailyActiveUsers($start, $end);
        $prevDau = $this->getDailyActiveUsers($prevStart, $prevEnd);
        $currentConv = Conversation::whereBetween('created_at', [$start, $end])->count();
        $prevConv = Conversation::whereBetween('created_at', [$prevStart, $prevEnd])->count();

        $emotionDist = $this->getEmotionDistribution($start, $end);
        $prevEmotionDist = $this->getEmotionDistribution($prevStart, $prevEnd);

        // Calculate emotion changes
        $emotionChanges = [];
        foreach ($emotionDist as $emotion => $count) {
            $prevCount = $prevEmotionDist[$emotion] ?? 0;
            $change = $prevCount > 0 ? round((($count - $prevCount) / $prevCount) * 100, 1) : 0;
            $emotionChanges[$emotion] = [
                'current' => $count,
                'previous' => $prevCount,
                'change_percent' => $change,
            ];
        }

        $peakHours = $this->getPeakUsageHours($start, $end);
        $topPeakHours = array_slice(array_column($peakHours, 'hour'), 0, 5);

        $trendMetrics = $this->getTrendMetrics($start, $end);

        return [
            'period'                      => $start->toDateString() . ' to ' . $end->toDateString(),
            'report_type'                 => $period,
            'daily_active_users_avg'      => $currentDau,
            'dau_change'                  => $prevDau > 0 ? round((($currentDau - $prevDau) / $prevDau) * 100, 1) . '%' : 'N/A',
            'total_conversations'         => $currentConv,
            'conversation_change'         => $prevConv > 0 ? round((($currentConv - $prevConv) / $prevConv) * 100, 1) . '%' : 'N/A',
            'total_messages'              => ChatMessage::whereBetween('created_at', [$start, $end])->count(),
            'avg_session_minutes'         => $this->getAvgSessionMinutes($start, $end),
            'emotion_distribution'        => $emotionDist,
            'emotion_changes'             => $emotionChanges,
            'peak_usage_hours'            => $topPeakHours,
            'crisis_alerts_total'         => CrisisAlert::whereBetween('created_at', [$start, $end])->count(),
            'crisis_by_severity'          => CrisisAlert::whereBetween('created_at', [$start, $end])
                                                ->selectRaw('severity, COUNT(*) as count')
                                                ->groupBy('severity')
                                                ->pluck('count', 'severity')
                                                ->toArray(),
            'fallback_rate'               => $this->getFallbackCount($start, $end),
            'sentiment_scores'            => $this->getSentimentScores($start, $end),
            'total_registered_students'   => User::where('role', 'student')->count(),
            'trend_metrics'               => $trendMetrics,
        ];
    }

    public function getPeriodBounds(string $period): array
    {
        $end = Carbon::now();

        $start = match ($period) {
            'daily' => $end->copy()->subDay(),
            'monthly' => $end->copy()->subMonth(),
            default => $end->copy()->subWeek(),
        };

        return [$start, $end];
    }

    // ─── Private Helper Methods ──────────────────────────────

    private function getDailyActiveUsers(Carbon $start, Carbon $end): int
    {
        return SessionLog::whereBetween('session_start', [$start, $end])
            ->distinct('user_id')
            ->count('user_id');
    }

    private function getAvgSessionMinutes(Carbon $start, Carbon $end): float
    {
        $driver = DB::connection()->getDriverName();
        $query = SessionLog::whereNotNull('session_end')
            ->whereBetween('session_start', [$start, $end]);

        switch ($driver) {
            case 'pgsql':
                $query->selectRaw('AVG(ABS(EXTRACT(EPOCH FROM (session_end - session_start)) / 60)) as avg_minutes');
                break;
            case 'sqlite':
                $query->selectRaw('AVG(ABS((julianday(session_end) - julianday(session_start)) * 1440)) as avg_minutes');
                break;
            case 'mysql':
            default:
                $query->selectRaw('AVG(ABS(TIMESTAMPDIFF(MINUTE, session_start, session_end))) as avg_minutes');
                break;
        }

        $result = $query->value('avg_minutes');
        return round($result ?? 0, 1);
    }

    private function getPeakUsageHours(Carbon $start, Carbon $end): array
    {
        $driver = DB::connection()->getDriverName();
        $query = ChatMessage::whereBetween('created_at', [$start, $end]);

        switch ($driver) {
            case 'pgsql':
                $query->selectRaw('EXTRACT(HOUR FROM created_at)::int as hour, COUNT(*) as count');
                break;
            case 'sqlite':
                $query->selectRaw('CAST(strftime(\'%H\', created_at) AS INTEGER) as hour, COUNT(*) as count');
                break;
            case 'mysql':
            default:
                $query->selectRaw('HOUR(created_at) as hour, COUNT(*) as count');
                break;
        }

        return $query->groupByRaw('hour')
            ->orderByDesc('count')
            ->get()
            ->map(fn($row) => ['hour' => (int) $row->hour, 'count' => (int) $row->count])
            ->toArray();
    }

    private function getEmotionDistribution(Carbon $start, Carbon $end): array
    {
        return EmotionLog::whereBetween('created_at', [$start, $end])
            ->selectRaw('emotion, COUNT(*) as count')
            ->groupBy('emotion')
            ->pluck('count', 'emotion')
            ->toArray();
    }

    private function getTopicFrequency(Carbon $start, Carbon $end): array
    {
        // Derive topic frequency from crisis alert keywords and emotion logs
        $topics = [];

        // From crisis alerts (detected keywords are stored as JSON arrays)
        $alerts = CrisisAlert::whereBetween('created_at', [$start, $end])
            ->whereNotNull('detected_keywords')
            ->pluck('detected_keywords');

        foreach ($alerts as $keywords) {
            $kwArray = is_array($keywords) ? $keywords : json_decode($keywords, true);
            if (is_array($kwArray)) {
                foreach ($kwArray as $kw) {
                    $category = $this->categorizeKeyword($kw);
                    $topics[$category] = ($topics[$category] ?? 0) + 1;
                }
            }
        }

        // From emotion logs — map emotions to broader topic categories
        $emotions = EmotionLog::whereBetween('created_at', [$start, $end])
            ->selectRaw('emotion, COUNT(*) as count')
            ->groupBy('emotion')
            ->pluck('count', 'emotion')
            ->toArray();

        $emotionTopicMap = [
            'stressed'     => 'academic_stress',
            'anxious'      => 'anxiety',
            'overwhelmed'  => 'burnout',
            'sad'          => 'sadness',
            'lonely'       => 'loneliness',
            'angry'        => 'frustration',
            'positive'     => 'positive_wellbeing',
            'hopeful'      => 'positive_wellbeing',
        ];

        foreach ($emotions as $emotion => $count) {
            $topic = $emotionTopicMap[$emotion] ?? $emotion;
            $topics[$topic] = ($topics[$topic] ?? 0) + $count;
        }

        arsort($topics);
        return $topics;
    }

    private function categorizeKeyword(string $keyword): string
    {
        $keyword = strtolower($keyword);

        $categories = [
            'self_harm'       => ['suicide', 'kill myself', 'end my life', 'self harm', 'cut myself', 'magpakamatay', 'saktan ang sarili'],
            'hopelessness'    => ['hopeless', 'worthless', 'no reason to live', 'want to die', 'better off dead', 'wala nang kwenta'],
            'emotional_crisis'=> ['i\'m done', 'give up', 'don\'t want to live', 'disappear', 'i wish i was gone', 'ayoko na', 'gusto ko nang mawala'],
            'burnout'         => ['pagod na ako', 'hindi ko na kaya'],
        ];

        foreach ($categories as $category => $keywords) {
            if (in_array($keyword, $keywords)) {
                return $category;
            }
        }

        return 'other_crisis';
    }

    private function getSentimentScores(Carbon $start, Carbon $end): array
    {
        $emotions = EmotionLog::whereBetween('created_at', [$start, $end])
            ->selectRaw('emotion, COUNT(*) as count')
            ->groupBy('emotion')
            ->pluck('count', 'emotion')
            ->toArray();

        $positive = ($emotions['positive'] ?? 0) + ($emotions['hopeful'] ?? 0);
        $negative = ($emotions['sad'] ?? 0) + ($emotions['angry'] ?? 0) + ($emotions['lonely'] ?? 0);
        $neutral = ($emotions['stressed'] ?? 0) + ($emotions['anxious'] ?? 0) + ($emotions['overwhelmed'] ?? 0);

        $total = $positive + $negative + $neutral;

        return [
            'positive' => $total > 0 ? round(($positive / $total) * 100, 1) : 0,
            'negative' => $total > 0 ? round(($negative / $total) * 100, 1) : 0,
            'neutral'  => $total > 0 ? round(($neutral / $total) * 100, 1) : 0,
            'total'    => $total,
        ];
    }

    private function getFallbackCount(Carbon $start, Carbon $end): int
    {
        // Count messages where the bot replied with the mental health redirect
        return ChatMessage::whereBetween('created_at', [$start, $end])
            ->where('reply', 'like', '%support mental health%')
            ->count();
    }

    private function getTrendMetrics(Carbon $start, Carbon $end): array
    {
        $topicFrequency = $this->getTopicFrequency($start, $end);
        $emotionDistribution = $this->getEmotionDistribution($start, $end);
        $peakHours = $this->getPeakUsageHours($start, $end);

        return [
            'top_topics' => array_slice($topicFrequency, 0, 5, true),
            'top_emotions' => array_slice($emotionDistribution, 0, 5, true),
            'peak_usage_window' => $this->summarizePeakUsageWindow($peakHours),
            'message_to_conversation_ratio' => $this->getConversationMessageRatio($start, $end),
        ];
    }

    private function summarizePeakUsageWindow(array $peakHours): ?string
    {
        if (empty($peakHours)) {
            return null;
        }

        $hours = array_slice(array_column($peakHours, 'hour'), 0, 3);
        sort($hours);

        return implode(', ', array_map(fn ($hour) => sprintf('%02d:00', $hour), $hours));
    }

    private function getConversationMessageRatio(Carbon $start, Carbon $end): float
    {
        $conversations = Conversation::whereBetween('created_at', [$start, $end])->count();
        $messages = ChatMessage::whereBetween('created_at', [$start, $end])->count();

        if ($conversations === 0) {
            return 0;
        }

        return round($messages / $conversations, 2);
    }

    private function getSentimentOverTime(Carbon $start, Carbon $end): array
    {
        $driver = DB::connection()->getDriverName();

        // Group by week
        $positiveEmotions = ['positive', 'hopeful'];
        $negativeEmotions = ['sad', 'angry', 'lonely'];
        $neutralEmotions = ['stressed', 'anxious', 'overwhelmed'];

        $weeks = [];
        $current = $start->copy()->startOfWeek(1); // Monday

        while ($current->lt($end)) {
            $weekEnd = $current->copy()->endOfWeek(7); // Sunday
            if ($weekEnd->gt($end)) $weekEnd = $end->copy();

            $emotions = EmotionLog::whereBetween('created_at', [$current, $weekEnd])
                ->selectRaw('emotion, COUNT(*) as count')
                ->groupBy('emotion')
                ->pluck('count', 'emotion')
                ->toArray();

            $pos = 0;
            $neg = 0;
            $neu = 0;
            foreach ($emotions as $emo => $cnt) {
                if (in_array($emo, $positiveEmotions)) $pos += $cnt;
                elseif (in_array($emo, $negativeEmotions)) $neg += $cnt;
                else $neu += $cnt;
            }

            $weeks[] = [
                'week_start' => $current->toDateString(),
                'positive'   => $pos,
                'negative'   => $neg,
                'neutral'    => $neu,
            ];

            $current->addWeek();
        }

        return $weeks;
    }

    private function getWeeklyComparison(): array
    {
        $results = [];

        for ($i = 3; $i >= 0; $i--) {
            $weekStart = Carbon::now()->subWeeks($i)->startOfWeek(1); // Monday
            $weekEnd = Carbon::now()->subWeeks($i)->endOfWeek(7); // Sunday

            $results[] = [
                'label'         => 'W' . (4 - $i),
                'week_start'    => $weekStart->toDateString(),
                'conversations' => Conversation::whereBetween('created_at', [$weekStart, $weekEnd])->count(),
                'messages'      => ChatMessage::whereBetween('created_at', [$weekStart, $weekEnd])->count(),
                'active_users'  => SessionLog::whereBetween('session_start', [$weekStart, $weekEnd])
                                    ->distinct('user_id')
                                    ->count('user_id'),
            ];
        }

        return $results;
    }

    private function ensureSnapshotsExist(int $days): void
    {
        // Backfill missing snapshots for the last N days (max 7 per call to avoid heavy load)
        $backfillLimit = min($days, 7);

        for ($i = 1; $i <= $backfillLimit; $i++) {
            $date = Carbon::now()->subDays($i);
            $exists = AnalyticsSnapshot::where('snapshot_date', $date->toDateString())->exists();

            if (!$exists) {
                try {
                    $this->computeDailySnapshot($date);
                } catch (\Exception $e) {
                    Log::warning("Failed to compute snapshot for {$date->toDateString()}: " . $e->getMessage());
                }
            }
        }
    }

    private function periodToStart(string $period, Carbon $end): Carbon
    {
        return match ($period) {
            '1d', 'daily'   => $end->copy()->subDay(),
            '7d', 'weekly'  => $end->copy()->subDays(7),
            '14d'           => $end->copy()->subDays(14),
            '30d', 'monthly'=> $end->copy()->subDays(30),
            '90d'           => $end->copy()->subDays(90),
            default         => $end->copy()->subDays(7),
        };
    }
}
