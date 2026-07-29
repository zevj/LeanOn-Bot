<?php

namespace App\Services;

use App\Helpers\DataFormatter;
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
            $currentConversations = Conversation::whereBetween('created_at', [$start, $end])->count();
            $crisisCount = CrisisAlert::whereBetween('created_at', [$start, $end])->count();
            $fallbackCount = $this->getFallbackCount($start, $end);

            // Previous period for comparison
            $prevConversations = Conversation::whereBetween('created_at', [$prevStart, $start])->count();

            // Growth calculations
            $convGrowth = $prevConversations > 0 ? round((($currentConversations - $prevConversations) / $prevConversations) * 100, 1) : 0;

            // Peak usage hour
            $peakHours = $this->getPeakUsageHours($start, $end);
            $peakHour = !empty($peakHours) ? $peakHours[0]['hour'] : null;

            // Crisis by severity (classified only)
            $crisisBySeverity = CrisisAlert::whereBetween('created_at', [$start, $end])
                ->where('is_classified', true)
                ->selectRaw('severity, COUNT(*) as count')
                ->groupBy('severity')
                ->pluck('count', 'severity')
                ->toArray();

            // Total registered users
            $totalUsers = User::where('role', 'student')->count();

            // Active users in the current period
            $activeUsersInPeriod = SessionLog::whereBetween('session_start', [$start, $end])
                ->distinct('user_id')
                ->count('user_id');

            // ── NEW: Three replacement stat cards ──────────────────
            // 1. Department with most users
            $topDeptUsers = User::where('role', 'student')
                ->whereNotNull('department')
                ->selectRaw('department, COUNT(*) as count')
                ->groupBy('department')
                ->orderByDesc('count')
                ->first();

            // 2. Department with most crisis alerts (all time, not period-limited,
            //    so the card always shows meaningful data even in short periods)
            $topDeptAlerts = CrisisAlert::where('is_classified', true)
                ->whereNotNull('department')
                ->selectRaw('department, COUNT(*) as count')
                ->groupBy('department')
                ->orderByDesc('count')
                ->first();

            // 3. Gender that uses the system most
            $topGender = User::where('role', 'student')
                ->whereNotNull('gender')
                ->selectRaw('gender, COUNT(*) as count')
                ->groupBy('gender')
                ->orderByDesc('count')
                ->first();

            // 4. Age range that uses the system most
            $topAgeRange = $this->getTopAgeRange();

            return [
                // Kept for backward compat (charts, export, etc.)
                'total_conversations'   => $currentConversations,
                'conversation_growth'   => $convGrowth,
                'peak_hour'             => $peakHour,
                'peak_usage_hours'      => $peakHours,
                'crisis_alert_count'    => $crisisCount,
                'crisis_by_severity'    => $crisisBySeverity,
                'fallback_count'        => $fallbackCount,
                'total_registered_users' => $totalUsers,
                'active_users_in_period' => $activeUsersInPeriod,
                'period'                => $period,
                'period_start'          => $start->toDateString(),
                'period_end'            => $end->toDateString(),

                // ── New replacement cards ──
                'top_department_users'  => $topDeptUsers?->department  ?? 'N/A',
                'top_department_users_count' => (int) ($topDeptUsers?->count ?? 0),
                'top_department_alerts' => $topDeptAlerts?->department ?? 'N/A',
                'top_department_alerts_count' => (int) ($topDeptAlerts?->count ?? 0),
                'top_gender'            => $topGender?->gender          ?? 'N/A',
                'top_gender_count'      => (int) ($topGender?->count    ?? 0),

                // ── Age range card ──
                'top_age_range'         => $topAgeRange['range']  ?? 'N/A',
                'top_age_range_count'   => (int) ($topAgeRange['count'] ?? 0),
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
     * Search students for the Student Insights picker.
     * Returns student identity data for admin picker/search.
     */
    public function searchStudents(string $q, int $limit = 15): array
    {
        $q = trim($q);
        if ($q === '') {
            return [];
        }

        $limit = min(max($limit, 1), 25);
        $query = User::where('role', 'student');

        $domainQuery = $q;
        if (str_contains($q, '@')) {
            $parts = explode('@', $q);
            $domainQuery = end($parts);
        }

        // Match by:
        // - name (first_name / last_name)
        // - email (full or domain part)
        // - department (optional convenience)
        $query->where(function ($qb) use ($q, $domainQuery) {
            $qb->where('first_name', 'like', '%' . $q . '%')
                ->orWhere('last_name', 'like', '%' . $q . '%')
                ->orWhere('email', 'like', '%' . $q . '%')
                // Domain-only search (e.g. "gmail.com")
                ->orWhere('email', 'like', '%@' . $domainQuery . '%')
                ->orWhere('department', 'like', '%' . $q . '%');

            if (preg_match('/^\d+$/', $q)) {
                $qb->orWhere('id', (int) $q);
            }
        });

        return $query->orderBy('id')
            ->limit($limit)
            ->get(['id', 'email', 'department', 'first_name', 'last_name'])
            ->map(fn (User $user) => $this->formatStudentSubject($user))
            ->values()
            ->toArray();
    }

    /**
     * Per-student dashboard stats (live queries — never uses school-wide snapshots).
     */
    public function getStudentDashboardStats(int $userId, string $period = '7d'): ?array
    {
        $user = User::where('id', $userId)->where('role', 'student')->first();
        if (!$user) {
            return null;
        }

        $cacheKey = "analytics:student:{$userId}:dashboard:{$period}";
        // Bump version because student identity payload has changed (picker no longer uses anonymized labels).
        $cacheKey .= ':v2';
        $cacheTtl = 900; // 15 minutes

        return Cache::remember($cacheKey, $cacheTtl, function () use ($user, $userId, $period) {
            $end = Carbon::now();
            $start = $this->periodToStart($period, $end);
            $prevStart = $this->periodToStart($period, $start);

            $currentConversations = Conversation::where('user_id', $userId)
                ->whereBetween('created_at', [$start, $end])
                ->count();

            $prevConversations = Conversation::where('user_id', $userId)
                ->whereBetween('created_at', [$prevStart, $start])
                ->count();

            $convGrowth = $prevConversations > 0
                ? round((($currentConversations - $prevConversations) / $prevConversations) * 100, 1)
                : 0;

            $crisisCount = CrisisAlert::where('user_id', $userId)
                ->whereBetween('created_at', [$start, $end])
                ->count();

            $crisisBySeverity = CrisisAlert::where('user_id', $userId)
                ->whereBetween('created_at', [$start, $end])
                ->where('is_classified', true)
                ->selectRaw('severity, COUNT(*) as count')
                ->groupBy('severity')
                ->pluck('count', 'severity')
                ->toArray();

            $peakHours = $this->getPeakUsageHours($start, $end, $userId);
            $peakHour = !empty($peakHours) ? $peakHours[0]['hour'] : null;

            $sessionCount = SessionLog::where('user_id', $userId)
                ->whereBetween('session_start', [$start, $end])
                ->count();

            $messageCount = ChatMessage::where('user_id', $userId)
                ->whereBetween('created_at', [$start, $end])
                ->count();

            return [
                'student'               => $this->formatStudentSubject($user),
                'total_conversations'   => $currentConversations,
                'conversation_growth'   => $convGrowth,
                'peak_hour'             => $peakHour,
                'peak_usage_hours'      => $peakHours,
                'crisis_alert_count'    => $crisisCount,
                'crisis_by_severity'    => $crisisBySeverity,
                'fallback_count'        => $this->getFallbackCount($start, $end, $userId),
                'session_count'         => $sessionCount,
                'message_count'         => $messageCount,
                'had_activity'          => ($currentConversations + $sessionCount + $messageCount + $crisisCount) > 0,
                'period'                => $period,
                'period_start'          => $start->toDateString(),
                'period_end'            => $end->toDateString(),
            ];
        });
    }

    /**
     * Per-student emotion / usage trends.
     */
    public function getStudentTrends(int $userId, string $period = '30d'): ?array
    {
        $user = User::where('id', $userId)->where('role', 'student')->first();
        if (!$user) {
            return null;
        }

        $cacheKey = "analytics:student:{$userId}:trends:{$period}";
        $cacheTtl = 900;

        return Cache::remember($cacheKey, $cacheTtl, function () use ($user, $userId, $period) {
            $end = Carbon::now();
            $start = $this->periodToStart($period, $end);

            return [
                'student'              => $this->formatStudentSubject($user),
                'emotion_distribution' => $this->getEmotionDistribution($start, $end, $userId),
                'sentiment_over_time'  => $this->getSentimentOverTime($start, $end, $userId),
                'peak_usage_hours'     => $this->getPeakUsageHours($start, $end, $userId),
                'period_start'         => $start->toDateString(),
                'period_end'           => $end->toDateString(),
            ];
        });
    }

    /**
     * Student subject payload for admin UI.
     */
    private function formatStudentSubject(User $user): array
    {
        return [
            'id'           => $user->id,
            'display'      => trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')) ?: 'Student',
            'email'        => $user->email,
            'domain_email' => $user->email ? explode('@', $user->email, 2)[1] : null,
            'department'   => $user->department,
        ];
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
     *
     * IMPORTANT: Keep this payload COMPACT to minimize Gemini token usage.
     * No raw messages, no names, no emails, no full conversations.
     *
     * @param  string $period  Legacy period label (weekly/monthly/daily) — kept for backward compat.
     * @param  int    $days    Explicit day window: 7 or 60. Overrides $period when provided.
     */
    public function buildAnonymizedStatsForAI(string $period = 'weekly', int $days = 0): array
    {
        $end = Carbon::now();

        // If an explicit day window is given, use it; otherwise fall back to period string.
        if ($days > 0) {
            $start = $end->copy()->subDays($days);
        } else {
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
        }

        $emotionDist    = $this->getEmotionDistribution($start, $end);
        $sentimentScores = $this->getSentimentScores($start, $end);

        // ── Dashboard-level stats ──────────────────────────────────────────
        $totalConversations = Conversation::whereBetween('created_at', [$start, $end])->count();
        $fallbackCount      = $this->getFallbackCount($start, $end);
        $peakHours          = $this->getPeakUsageHours($start, $end);
        $peakHour           = !empty($peakHours) ? $peakHours[0]['hour'] : null;
        $avgSessionMinutes  = $this->getAvgSessionMinutes($start, $end);
        $activeUsers        = SessionLog::whereBetween('session_start', [$start, $end])
                                ->distinct('user_id')->count('user_id');

        // ── Department / gender breakdowns ────────────────────────────────
        $topDeptUsers = User::where('role', 'student')
            ->whereNotNull('department')
            ->selectRaw('department, COUNT(*) as count')
            ->groupBy('department')
            ->orderByDesc('count')
            ->first();

        $topDeptAlerts = CrisisAlert::where('is_classified', true)
            ->whereNotNull('department')
            ->selectRaw('department, COUNT(*) as count')
            ->groupBy('department')
            ->orderByDesc('count')
            ->first();

        $topGender = User::where('role', 'student')
            ->whereNotNull('gender')
            ->selectRaw('gender, COUNT(*) as count')
            ->groupBy('gender')
            ->orderByDesc('count')
            ->first();

        // ── Crisis counts ─────────────────────────────────────────────────
        $totalFlagged    = CrisisAlert::whereBetween('created_at', [$start, $end])->count();
        $totalClassified = CrisisAlert::whereBetween('created_at', [$start, $end])
            ->where('is_classified', true)->count();

        $crisisBySeverity = CrisisAlert::where('is_classified', true)
            ->whereBetween('created_at', [$start, $end])
            ->selectRaw('severity, COUNT(*) as count')
            ->groupBy('severity')
            ->pluck('count', 'severity')
            ->toArray();

        // ── Emotional Trends: summarized (not raw per-week rows) ─────────────
        $emotionTypes = ['positive', 'sad', 'anxious', 'stressed'];
        $weeksToShow = min(6, (int) ceil(($days > 0 ? $days : 7) / 7));
        $emotionTotals = array_fill_keys($emotionTypes, 0);
        $weekCount = 0;

        for ($i = $weeksToShow - 1; $i >= 0; $i--) {
            $wStart = Carbon::now()->subWeeks($i)->startOfWeek(1); // 1 = Monday
            $wEnd   = Carbon::now()->subWeeks($i)->endOfWeek(0);   // 0 = Sunday

            if ($wStart->lt($start)) $wStart = $start->copy();
            if ($wEnd->gt($end))     $wEnd   = $end->copy();

            $wCounts = EmotionLog::whereBetween('created_at', [$wStart, $wEnd])
                ->selectRaw('emotion, COUNT(*) as count')
                ->groupBy('emotion')
                ->pluck('count', 'emotion')
                ->toArray();

            $wTotal = array_sum($wCounts);
            if ($wTotal > 0) {
                foreach ($emotionTypes as $emo) {
                    $emotionTotals[$emo] += round((($wCounts[$emo] ?? 0) / $wTotal) * 100, 1);
                }
                $weekCount++;
            }
        }

        // Average % across weeks
        $avgEmotionBreakdown = [];
        foreach ($emotionTypes as $emo) {
            $avgEmotionBreakdown[$emo] = $weekCount > 0 ? round($emotionTotals[$emo] / $weekCount, 1) : 0;
        }

        // ── Referral-style emotion totals ─────────────────────────────────
        $totalEmotionLogs  = EmotionLog::whereBetween('created_at', [$start, $end])->count();
        $thisWeekEmotions  = EmotionLog::whereBetween('created_at', [
            Carbon::now()->startOfWeek(1), // 1 = Monday
            Carbon::now()->endOfWeek(0),   // 0 = Sunday
        ])->count();

        // ── COMPACT payload — only aggregated numbers, no PII ─────────────
        return [
            'period'                      => $start->toDateString() . ' to ' . $end->toDateString(),
            'days_window'                 => $days > 0 ? $days : ($period === 'monthly' ? 30 : 7),
            'report_type'                 => $period,

            // Dashboard stats
            'total_conversations'         => $totalConversations,
            'active_users_in_period'      => $activeUsers,
            'avg_session_minutes'         => $avgSessionMinutes,
            'peak_hour'                   => $peakHour,
            'fallback_count'              => $fallbackCount,
            'total_registered_students'   => User::where('role', 'student')->count(),

            // Department / gender / age
            'top_department'              => $topDeptUsers?->department  ?? 'N/A',
            'top_gender'                  => $topGender?->gender          ?? 'N/A',
            'department_with_most_alerts' => $topDeptAlerts?->department ?? 'N/A',
            'top_age_range'               => $this->getTopAgeRange()['range'] ?? 'N/A',

            // Crisis
            'total_flagged_alerts'        => $totalFlagged,
            'total_classified_alerts'     => $totalClassified,
            'crisis_by_severity'          => $crisisBySeverity,

            // Emotional trends (averaged, not raw per-week rows)
            'top_emotions'                => array_slice($emotionDist, 0, 5, true),
            'sentiment_scores'            => $sentimentScores,
            'avg_emotion_breakdown'       => $avgEmotionBreakdown,
            'total_emotion_logs_in_period'=> $totalEmotionLogs,
            'emotion_logs_this_week'      => $thisWeekEmotions,
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

    /**
     * Get dashboard stats for a custom date range (used by export endpoint).
     */
    public function getDashboardStatsByRange(Carbon $start, Carbon $end): array
    {
        $currentConversations = Conversation::whereBetween('created_at', [$start, $end])->count();
        $crisisCount = CrisisAlert::whereBetween('created_at', [$start, $end])->count();
        $fallbackCount = $this->getFallbackCount($start, $end);

        $peakHours = $this->getPeakUsageHours($start, $end);
        $peakHour = !empty($peakHours) ? $peakHours[0]['hour'] : null;

        $crisisBySeverity = CrisisAlert::whereBetween('created_at', [$start, $end])
            ->where('is_classified', true)
            ->selectRaw('severity, COUNT(*) as count')
            ->groupBy('severity')
            ->pluck('count', 'severity')
            ->toArray();

        $totalUsers = User::where('role', 'student')->count();

        $topDeptAlerts = CrisisAlert::where('is_classified', true)
            ->whereNotNull('department')
            ->selectRaw('department, COUNT(*) as count')
            ->groupBy('department')
            ->orderByDesc('count')
            ->first();

        return [
            'total_conversations'        => $currentConversations,
            'conversation_growth'        => 0,
            'peak_hour'                  => $peakHour,
            'peak_usage_hours'           => $peakHours,
            'crisis_alert_count'         => $crisisCount,
            'crisis_by_severity'         => $crisisBySeverity,
            'fallback_count'             => $fallbackCount,
            'total_registered_users'     => $totalUsers,
            'period_start'               => $start->toDateString(),
            'period_end'                 => $end->toDateString(),
            'top_department_alerts'      => $topDeptAlerts?->department ?? 'N/A',
            'top_department_alerts_count'=> (int) ($topDeptAlerts?->count ?? 0),
        ];
    }

    /**
     * Get trend data for a custom date range (used by export endpoint).
     */
    public function getTrendsByRange(Carbon $start, Carbon $end): array
    {
        return [
            'emotion_distribution' => $this->getEmotionDistribution($start, $end),
            'sentiment_over_time'  => $this->getSentimentOverTime($start, $end),
            'peak_usage_hours'     => $this->getPeakUsageHours($start, $end),
            'weekly_comparison'    => $this->getWeeklyComparison(),
            'period_start'         => $start->toDateString(),
            'period_end'           => $end->toDateString(),
        ];
    }

    /**
     * Get historical snapshots for a custom date range (used by export endpoint).
     */
    public function getSnapshotsByRange(Carbon $start, Carbon $end): array
    {
        return AnalyticsSnapshot::whereBetween('snapshot_date', [$start->toDateString(), $end->toDateString()])
            ->orderBy('snapshot_date')
            ->get()
            ->toArray();
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

    private function getPeakUsageHours(Carbon $start, Carbon $end, ?int $userId = null): array
    {
        $driver = DB::connection()->getDriverName();
        $query = ChatMessage::whereBetween('created_at', [$start, $end]);
        if ($userId) {
            $query->where('user_id', $userId);
        }

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

    private function getEmotionDistribution(Carbon $start, Carbon $end, ?int $userId = null): array
    {
        $query = EmotionLog::whereBetween('created_at', [$start, $end]);
        if ($userId) {
            $query->where('user_id', $userId);
        }

        return $query->selectRaw('emotion, COUNT(*) as count')
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

    private function getFallbackCount(Carbon $start, Carbon $end, ?int $userId = null): int
    {
        $query = ChatMessage::whereBetween('created_at', [$start, $end])
            ->where('is_fallback', true);
        if ($userId) {
            $query->where('user_id', $userId);
        }

        return $query->count();
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

    private function getSentimentOverTime(Carbon $start, Carbon $end, ?int $userId = null): array
    {
        $driver = DB::connection()->getDriverName();

        switch ($driver) {
            case 'pgsql':
                $dateExpr = "created_at::date";
                break;
            case 'sqlite':
                $dateExpr = "date(created_at)";
                break;
            default:
                $dateExpr = "DATE(created_at)";
                break;
        }

        $query = EmotionLog::whereBetween('created_at', [$start, $end]);
        if ($userId) {
            $query->where('user_id', $userId);
        }

        $emotionCounts = $query->selectRaw("$dateExpr as date, emotion, COUNT(*) as count")
            ->groupByRaw("$dateExpr, emotion")
            ->get()
            ->groupBy('date')
            ->map(function ($items) {
                return $items->pluck('count', 'emotion')->toArray();
            })
            ->toArray();

        $positiveEmotions = ['positive', 'hopeful'];
        $negativeEmotions = ['sad', 'angry', 'lonely'];

        $weeks = [];
        $current = $start->copy()->startOfWeek(1); // Monday

        while ($current->lt($end)) {
            $weekEnd = $current->copy()->endOfWeek(7); // Sunday
            if ($weekEnd->gt($end)) $weekEnd = $end->copy();

            $pos = 0;
            $neg = 0;
            $neu = 0;

            $currentDay = $current->copy();
            while ($currentDay->lte($weekEnd)) {
                $dayStr = $currentDay->toDateString();
                if (isset($emotionCounts[$dayStr])) {
                    foreach ($emotionCounts[$dayStr] as $emo => $cnt) {
                        if (in_array($emo, $positiveEmotions)) {
                            $pos += $cnt;
                        } elseif (in_array($emo, $negativeEmotions)) {
                            $neg += $cnt;
                        } else {
                            $neu += $cnt;
                        }
                    }
                }
                $currentDay->addDay();
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
        $start = Carbon::now()->subWeeks(3)->startOfWeek(1); // Monday of W1
        $end = Carbon::now()->endOfWeek(7); // Sunday of W4
        $driver = DB::connection()->getDriverName();

        switch ($driver) {
            case 'pgsql':
                $dateExpr = "created_at::date";
                $sessionDateExpr = "session_start::date";
                break;
            case 'sqlite':
                $dateExpr = "date(created_at)";
                $sessionDateExpr = "date(session_start)";
                break;
            default:
                $dateExpr = "DATE(created_at)";
                $sessionDateExpr = "DATE(session_start)";
                break;
        }

        // 1. Fetch conversations count by date
        $convCounts = Conversation::whereBetween('created_at', [$start, $end])
            ->selectRaw("$dateExpr as date, COUNT(*) as count")
            ->groupByRaw("$dateExpr")
            ->pluck('count', 'date')
            ->toArray();

        // 2. Fetch chat messages count by date
        $msgCounts = ChatMessage::whereBetween('created_at', [$start, $end])
            ->selectRaw("$dateExpr as date, COUNT(*) as count")
            ->groupByRaw("$dateExpr")
            ->pluck('count', 'date')
            ->toArray();

        // 3. Fetch active users per date (distinct user_ids per day)
        $userSessions = SessionLog::whereBetween('session_start', [$start, $end])
            ->selectRaw("$sessionDateExpr as date, user_id")
            ->groupByRaw("$sessionDateExpr, user_id")
            ->get()
            ->groupBy('date')
            ->map(function ($rows) {
                return $rows->pluck('user_id')->unique()->toArray();
            })
            ->toArray();

        $results = [];

        for ($i = 3; $i >= 0; $i--) {
            $weekStart = Carbon::now()->subWeeks($i)->startOfWeek(1); // Monday
            $weekEnd = Carbon::now()->subWeeks($i)->endOfWeek(7); // Sunday

            $convSum = 0;
            $msgSum = 0;
            $weekUsers = [];

            $currentDay = $weekStart->copy();
            while ($currentDay->lte($weekEnd)) {
                $dayStr = $currentDay->toDateString();
                $convSum += $convCounts[$dayStr] ?? 0;
                $msgSum += $msgCounts[$dayStr] ?? 0;
                if (isset($userSessions[$dayStr])) {
                    $weekUsers = array_merge($weekUsers, $userSessions[$dayStr]);
                }
                $currentDay->addDay();
            }

            $results[] = [
                'label'         => 'W' . (4 - $i),
                'week_start'    => $weekStart->toDateString(),
                'conversations' => $convSum,
                'messages'      => $msgSum,
                'active_users'  => count(array_unique($weekUsers)),
            ];
        }

        return $results;
    }

    private function ensureSnapshotsExist(int $days): void
    {
        // Backfill missing snapshots for the last N days (max 7 per call to avoid heavy load)
        $backfillLimit = min($days, 7);
        $datesToCheck = [];
        for ($i = 1; $i <= $backfillLimit; $i++) {
            $datesToCheck[] = Carbon::now()->subDays($i)->toDateString();
        }

        $existingDates = AnalyticsSnapshot::whereIn('snapshot_date', $datesToCheck)
            ->pluck('snapshot_date')
            ->toArray();

        foreach ($datesToCheck as $dateStr) {
            if (!in_array($dateStr, $existingDates)) {
                try {
                    $this->computeDailySnapshot(Carbon::parse($dateStr));
                } catch (\Exception $e) {
                    Log::warning("Failed to compute snapshot for {$dateStr}: " . $e->getMessage());
                }
            }
        }
    }

    /**
     * Bucket registered students into age ranges and return the most common one.
     * Buckets: Under 18 | 18–20 | 21–23 | 24–26 | 27+
     */
    private function getTopAgeRange(): array
    {
        $result = User::where('role', 'student')
            ->whereNotNull('age')
            ->selectRaw("
                COUNT(CASE WHEN age <= 17 THEN 1 END) as under_17,
                COUNT(CASE WHEN age BETWEEN 18 AND 20 THEN 1 END) as age_18_20,
                COUNT(CASE WHEN age BETWEEN 21 AND 23 THEN 1 END) as age_21_23,
                COUNT(CASE WHEN age BETWEEN 24 AND 26 THEN 1 END) as age_24_26,
                COUNT(CASE WHEN age >= 27 THEN 1 END) as age_27_plus
            ")
            ->first();

        $counts = [
            'Under 18' => (int) ($result->under_17 ?? 0),
            '18–20'    => (int) ($result->age_18_20 ?? 0),
            '21–23'    => (int) ($result->age_21_23 ?? 0),
            '24–26'    => (int) ($result->age_24_26 ?? 0),
            '27+'      => (int) ($result->age_27_plus ?? 0),
        ];

        if (empty($counts) || array_sum($counts) === 0) {
            return ['range' => 'N/A', 'count' => 0];
        }

        $topLabel = array_key_first(array_filter($counts, fn($v) => $v === max($counts)));

        return [
            'range' => $topLabel ?? 'N/A',
            'count' => $counts[$topLabel] ?? 0,
        ];
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
