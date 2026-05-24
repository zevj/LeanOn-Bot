<?php

namespace App\Services;

use App\Contracts\AIProviderInterface;
use App\Models\AiInsightReport;
use App\Models\CachedAnalyticsSummary;
use App\Services\AI\GeminiInsightsProvider;
use App\Services\AI\MockInsightsProvider;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * AI Insights Service
 *
 * Orchestrates AI-powered insight generation using ONLY anonymized statistics.
 *
 * Privacy guarantee:
 * - Only aggregated counts, percentages, and distributions are sent to AI.
 * - No user IDs, names, emails, or raw conversation text are included.
 *
 * Free-tier guarantee:
 * - Dashboard/API reads never call Gemini.
 * - Scheduled generation is locked to one successful report per period per day.
 * - Quota failures activate a cooldown and fall back to stored reports.
 */
class AIInsightsService
{
    private AIProviderInterface $provider;
    private AnalyticsService $analytics;
    private int $cacheTtl;
    private int $cooldownTtl;

    public function __construct(AnalyticsService $analytics)
    {
        $this->analytics = $analytics;
        $this->cacheTtl = (int) config('services.ai_insights.cache_ttl', 86400);
        $this->cooldownTtl = (int) config('services.ai_insights.cooldown_ttl', 21600);

        $providerName = (string) (config('services.ai_insights.provider') ?? 'gemini');
        $this->provider = $this->resolveProvider($providerName);
    }

    /**
     * Get latest insights from Laravel cache, durable DB cache, report DB, or fallback.
     * This method NEVER calls Gemini.
     *
     * @param  string $period  weekly|daily|monthly
     * @param  int    $days    0 = use period default; 7 or 60 = explicit window
     */
    public function getLatestInsights(string $period = 'weekly', int $days = 0): array
    {
        $cacheKey = $this->cacheKey($period, $days);

        return Cache::remember($cacheKey, $this->cacheTtl, function () use ($period, $days, $cacheKey) {
            $durable = CachedAnalyticsSummary::where('cache_key', $cacheKey)
                ->where(function ($query) {
                    $query->whereNull('expires_at')->orWhere('expires_at', '>', now());
                })
                ->first();

            if ($durable) {
                Log::info('AI insights served from durable cache', [
                    'period' => $period,
                    'days' => $days,
                    'cache_key' => $cacheKey,
                ]);

                return $durable->payload;
            }

            // For non-default windows, look for a report tagged with the days window
            $report = $days > 0
                ? $this->latestReportByDays($period, $days)
                : $this->latestReport($period);

            if ($report) {
                $payload = $this->formatReport($report);
                $this->storeDurableCache($cacheKey, 'ai_insights', $payload);

                Log::info('AI insights served from database report', [
                    'period' => $period,
                    'days' => $days,
                    'report_id' => $report->id,
                ]);

                return $payload;
            }

            Log::info('AI insights served from static fallback; no generated report exists', [
                'period' => $period,
                'days' => $days,
            ]);

            return $this->getFallbackInsights($period);
        });
    }

    /**
     * Generate fresh AI insights. Intended for scheduler/Artisan only.
     *
     * @param  string $period  weekly|daily|monthly
     * @param  bool   $force   Bypass once-per-day guard
     * @param  int    $days    0 = use period default; 7 or 60 = explicit window
     */
    public function generateInsights(string $period = 'weekly', bool $force = false, int $days = 0): array
    {
        $today = now()->toDateString();
        $windowTag = $days > 0 ? "_{$days}d" : '';
        $dailyKey = "ai_insights:generated:{$period}{$windowTag}:{$today}";
        $lockKey = "ai_insights:lock:{$period}{$windowTag}:{$today}";
        $cooldownKey = 'ai_insights:cooldown';

        if (!$force && Cache::has($dailyKey)) {
            Log::info('AI insights generation skipped; daily generation already completed', [
                'period' => $period,
                'days' => $days,
                'date' => $today,
            ]);

            return $this->getLatestInsights($period, $days);
        }

        if (!$force && Cache::has($cooldownKey)) {
            Log::warning('AI insights generation skipped; Gemini cooldown is active', [
                'period' => $period,
                'cooldown_until' => Cache::get($cooldownKey),
            ]);

            return $this->markFallback($this->getLatestInsights($period, $days), 'AI quota cooldown is active. Showing the latest stored report.');
        }

        // When force=true (manual trigger), clear stale locks and cooldowns
        // so a previous failed attempt never blocks the admin
        if ($force) {
            Cache::forget($lockKey);
            Cache::forget($cooldownKey);
            Cache::forget($dailyKey);
        }

        $lock = Cache::lock($lockKey, 300);

        if (!$lock->get()) {
            Log::info('AI insights generation skipped; another generation is already running', [
                'period' => $period,
                'days' => $days,
                'date' => $today,
            ]);

            return $this->getLatestInsights($period, $days);
        }

        try {
            if (!$force && $this->reportExistsForToday($period, $days)) {
                Cache::put($dailyKey, true, $this->cacheTtl);
                return $this->getLatestInsights($period, $days);
            }

            return $this->performGeneration($period, $dailyKey, $days);
        } finally {
            optional($lock)->release();
        }
    }

    public function warmInsightCache(string $period = 'weekly', int $days = 0): array
    {
        $report = $days > 0
            ? $this->latestReportByDays($period, $days)
            : $this->latestReport($period);
        $payload = $report ? $this->formatReport($report) : $this->getFallbackInsights($period);
        $cacheKey = $this->cacheKey($period, $days);

        Cache::put($cacheKey, $payload, $this->cacheTtl);
        $this->storeDurableCache($cacheKey, 'ai_insights', $payload);

        return $payload;
    }

    private function performGeneration(string $period, string $dailyKey, int $days = 0): array
    {
        $stats = $this->analytics->buildAnonymizedStatsForAI($period, $days);
        $prompt = $this->buildInsightsPrompt($stats, $days);
        $maxRetries = max(1, (int) config('services.ai_insights.max_retries', 2));
        $result = null;
        $lastError = null;
        $requestCount = 0;

        for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
            try {
                $requestCount++;
                $result = $this->provider->generateInsights($prompt);
                break;
            } catch (\Exception $e) {
                $lastError = $e;
                $isQuota = $this->isQuotaError($e);

                Log::warning('AI insights generation attempt failed', [
                    'attempt' => $attempt,
                    'max_attempts' => $maxRetries,
                    'quota_related' => $isQuota,
                    'message' => $e->getMessage(),
                ]);

                if ($isQuota) {
                    $cooldownUntil = now()->addSeconds($this->cooldownTtl);
                    Cache::put('ai_insights:cooldown', $cooldownUntil->toIso8601String(), $this->cooldownTtl);
                    break;
                }

                if ($attempt < $maxRetries) {
                    sleep(min(8, 2 ** ($attempt - 1)));
                }
            }
        }

        if ($result === null) {
            Log::warning('AI insights generation failed; using latest cached report', [
                'error' => $lastError?->getMessage(),
                'request_count' => $requestCount,
            ]);

            return $this->markFallback(
                $this->getFallbackInsights($period),
                'AI generation failed. Showing the latest available stored report.'
            );
        }

        [$startDate, $end] = $this->analytics->getPeriodBounds($period);

        // If an explicit days window was given, recalculate the bounds
        if ($days > 0) {
            $end = now();
            $startDate = $end->copy()->subDays($days);
        }

        // Snapshot is best-effort — don't let it block report storage
        try {
            $snapshot = $this->analytics->computeDailySnapshot(now()->subDay());
            $snapshotId = $snapshot->id;
        } catch (\Throwable $e) {
            Log::warning('Snapshot computation failed inside performGeneration (non-fatal): ' . $e->getMessage());
            $snapshotId = null;
        }

        $cacheExpiresAt = now()->addSeconds($this->cacheTtl);

        // Use period_end + days_window suffix to prevent 7d and 60d reports
        // from overwriting each other when generated on the same day.
        $periodEndStored = $days > 0
            ? $end->toDateString() . "_d{$days}"
            : $end->toDateString();

        $report = AiInsightReport::updateOrCreate(
            [
                'report_type' => $period,
                'period_end'  => $periodEndStored,
            ],
            [
                'analytics_snapshot_id' => $snapshotId,
                'period_start' => $startDate->toDateString(),
                'analytics_snapshot' => $this->compactSnapshotForStorage($stats),
                'insights' => $result['insights'] ?? [],
                'recommendations' => $result['recommendations'] ?? [],
                'trends' => $result['trends'] ?? [],
                'wellness_summary' => $result['wellness_summary'] ?? '',
                'anomalies' => $result['anomalies'] ?? [],
                'ai_provider' => $this->provider->getProviderName(),
                'status' => 'completed',
                'error_message' => null,
                'request_count' => $requestCount,
                'generated_at' => now(),
                'cache_expires_at' => $cacheExpiresAt,
                'metadata' => [
                    'model' => config('services.ai_insights.gemini_model', 'gemini-2.5-flash'),
                    'privacy' => 'aggregated_anonymized_stats_only',
                    'prompt_bytes' => strlen($prompt),
                    'days_window'                 => $days > 0 ? $days : null,
                    'top_department'              => $result['top_department'] ?? null,
                    'top_gender'                  => $result['top_gender'] ?? null,
                    'department_with_most_alerts' => $result['department_with_most_alerts'] ?? null,
                ],
            ]
        );

        $payload = $this->formatReport($report);
        $cacheKey = $this->cacheKey($period, $days);
        Cache::put($cacheKey, $payload, $this->cacheTtl);
        Cache::put($dailyKey, true, $this->cacheTtl);
        $this->storeDurableCache($cacheKey, 'ai_insights', $payload);

        Log::info('AI insights generated and cached', [
            'period' => $period,
            'report_id' => $report->id,
            'request_count' => $requestCount,
            'generated_at' => $report->generated_at?->toIso8601String(),
        ]);

        return $payload;
    }

    private function buildInsightsPrompt(array $stats, int $days = 0): string
    {
        $windowLabel = $days > 0 ? "Last {$days} Days" : ucfirst($stats['report_type'] ?? 'weekly');

        // Only send the fields Gemini actually needs — drop verbose nested arrays
        $compact = [
            'period'                      => $stats['period'] ?? '',
            'days_window'                 => $stats['days_window'] ?? 7,
            'total_conversations'         => $stats['total_conversations'] ?? 0,
            'active_users_in_period'      => $stats['active_users_in_period'] ?? 0,
            'avg_session_minutes'         => $stats['avg_session_minutes'] ?? 0,
            'peak_hour'                   => $stats['peak_hour'] ?? null,
            'total_registered_students'   => $stats['total_registered_students'] ?? 0,
            'top_department'              => $stats['top_department'] ?? 'N/A',
            'top_gender'                  => $stats['top_gender'] ?? 'N/A',
            'department_with_most_alerts' => $stats['department_with_most_alerts'] ?? 'N/A',
            'total_flagged_alerts'        => $stats['total_flagged_alerts'] ?? 0,
            'total_classified_alerts'     => $stats['total_classified_alerts'] ?? 0,
            'crisis_by_severity'          => $stats['crisis_by_severity'] ?? [],
            'top_emotions'                => $stats['top_emotions'] ?? [],
            'sentiment_scores'            => $stats['sentiment_scores'] ?? [],
            'avg_emotion_breakdown'       => $stats['avg_emotion_breakdown'] ?? [],
            'total_emotion_logs_in_period'=> $stats['total_emotion_logs_in_period'] ?? 0,
        ];

        $statsJson = json_encode($compact, JSON_PRETTY_PRINT);

        return <<<PROMPT
You are a mental health analytics advisor for a university student wellness chatbot called LeanOn Bot.
Analyze the ANONYMIZED statistics below ({$windowLabel}) and return a SINGLE valid JSON object.

RULES (follow exactly):
1. Output ONLY raw JSON — no markdown, no code fences, no explanation text.
2. Every string value must be 15 words or fewer.
3. Include exactly: 2 insights, 1 recommendation, 1 trend, 0 anomalies.
4. Reference actual numbers from the data in your text values.
5. You MUST close every array and object. Incomplete JSON is not acceptable.

DATA:
{$statsJson}

REQUIRED JSON STRUCTURE (replace placeholder values):
{"wellness_summary":"one sentence summary","insights":[{"category":"usage","title":"title here","text":"observation with numbers","severity":"info"},{"category":"emotional","title":"title here","text":"observation with numbers","severity":"info"}],"recommendations":[{"priority":"medium","text":"actionable step"}],"trends":[{"metric":"metric name","direction":"stable","description":"short description"}],"anomalies":[],"top_department":"N/A","top_gender":"N/A","department_with_most_alerts":"N/A"}
PROMPT;
    }

    private function formatReport(AiInsightReport $report): array
    {
        $meta = $report->metadata ?? [];

        return [
            'id' => $report->id,
            'report_type' => $report->report_type,
            'period_start' => $report->period_start?->format('Y-m-d'),
            'period_end' => $report->period_end?->format('Y-m-d'),
            'insights' => $report->insights ?? [],
            'recommendations' => $report->recommendations ?? [],
            'trends' => $report->trends ?? [],
            'wellness_summary' => $report->wellness_summary ?? '',
            'anomalies' => $report->anomalies ?? [],
            'ai_provider' => $report->ai_provider,
            'generated_at' => ($report->generated_at ?? $report->created_at)->toIso8601String(),
            'cache_expires_at' => $report->cache_expires_at?->toIso8601String(),
            'metadata' => $meta,
            // Compact fields surfaced for dashboard display
            'days_window'                 => $meta['days_window'] ?? null,
            'top_department'              => $meta['top_department'] ?? null,
            'top_gender'                  => $meta['top_gender'] ?? null,
            'department_with_most_alerts' => $meta['department_with_most_alerts'] ?? null,
        ];
    }

    private function getFallbackInsights(string $period): array
    {
        $report = AiInsightReport::where('status', 'completed')
            ->orderByDesc('generated_at')
            ->orderByDesc('created_at')
            ->first();

        if ($report) {
            $payload = $this->formatReport($report);
            $payload['is_stale'] = true;
            $payload['stale_message'] = 'These insights are from a previous stored report. AI generation is temporarily unavailable.';
            return $payload;
        }

        return [
            'report_type'     => $period,
            'insights'        => [
                [
                    'category' => 'general',
                    'title'    => 'Analytics Ready',
                    'text'     => 'The analytics system is active and collecting anonymized data. Scheduled AI insights will appear after the next daily generation run.',
                    'severity' => 'info',
                ],
            ],
            'recommendations' => [
                [
                    'priority' => 'medium',
                    'text'     => 'Continue monitoring aggregate usage and emotional trends while the scheduled report is prepared.',
                ],
            ],
            'trends'          => [],
            'wellness_summary' => 'The wellness analytics system is initializing. Insights will improve as more anonymized data is collected and the scheduled daily report runs.',
            'anomalies'       => [],
            'ai_provider'     => 'fallback',
            'generated_at'    => null,   // null = no real report exists yet; frontend shows "Awaiting first generation"
            'is_fallback'     => true,
        ];
    }

    private function latestReport(string $period): ?AiInsightReport
    {
        return AiInsightReport::where('report_type', $period)
            ->where('status', 'completed')
            ->orderByDesc('generated_at')
            ->orderByDesc('created_at')
            ->first();
    }

    private function latestReportByDays(string $period, int $days): ?AiInsightReport
    {
        // Look for a report generated with this specific days window
        return AiInsightReport::where('report_type', $period)
            ->where('status', 'completed')
            ->whereJsonContains('metadata->days_window', $days)
            ->orderByDesc('generated_at')
            ->orderByDesc('created_at')
            ->first();
    }

    private function reportExistsForToday(string $period, int $days = 0): bool
    {
        $query = AiInsightReport::where('report_type', $period)
            ->where('status', 'completed')
            ->whereDate('generated_at', now()->toDateString());

        if ($days > 0) {
            $query->whereJsonContains('metadata->days_window', $days);
        }

        return $query->exists();
    }

    private function storeDurableCache(string $cacheKey, string $summaryType, array $payload): void
    {
        CachedAnalyticsSummary::updateOrCreate(
            ['cache_key' => $cacheKey],
            [
                'summary_type' => $summaryType,
                'payload' => $payload,
                'generated_at' => now(),
                'expires_at' => now()->addSeconds($this->cacheTtl),
            ]
        );
    }

    private function markFallback(array $payload, string $message): array
    {
        $payload['is_stale'] = $payload['is_stale'] ?? true;
        $payload['stale_message'] = $payload['stale_message'] ?? $message;

        return $payload;
    }

    private function isQuotaError(\Exception $e): bool
    {
        $message = strtolower($e->getMessage());

        return str_contains($message, 'quota')
            || str_contains($message, 'rate limit')
            || str_contains($message, 'resource exhausted')
            || str_contains($message, '429');
    }

    private function compactSnapshotForStorage(array $stats): array
    {
        return [
            'period'                      => $stats['period'] ?? null,
            'report_type'                 => $stats['report_type'] ?? null,
            'top_department'              => $stats['top_department'] ?? null,
            'top_gender'                  => $stats['top_gender'] ?? null,
            'department_with_most_alerts' => $stats['department_with_most_alerts'] ?? null,
            'total_flagged_alerts'        => $stats['total_flagged_alerts'] ?? 0,
            'total_classified_alerts'     => $stats['total_classified_alerts'] ?? 0,
            'total_conversations'         => $stats['total_conversations'] ?? 0,
            'total_registered_students'   => $stats['total_registered_students'] ?? 0,
            'top_emotions'                => $stats['top_emotions'] ?? [],
            'sentiment_scores'            => $stats['sentiment_scores'] ?? [],
            'crisis_by_severity'          => $stats['crisis_by_severity'] ?? [],
        ];
    }

    private function cacheKey(string $period, int $days = 0): string
    {
        $suffix = $days > 0 ? ":{$days}d" : '';
        return "ai_insights:latest:{$period}{$suffix}";
    }

    private function resolveProvider(string $name): AIProviderInterface
    {
        return match ($name) {
            'gemini' => new GeminiInsightsProvider(),
            'mock' => new MockInsightsProvider(),
            default => new GeminiInsightsProvider(),
        };
    }
}
