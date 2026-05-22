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
     */
    public function getLatestInsights(string $period = 'weekly'): array
    {
        $cacheKey = $this->cacheKey($period);

        return Cache::remember($cacheKey, $this->cacheTtl, function () use ($period, $cacheKey) {
            $durable = CachedAnalyticsSummary::where('cache_key', $cacheKey)
                ->where(function ($query) {
                    $query->whereNull('expires_at')->orWhere('expires_at', '>', now());
                })
                ->first();

            if ($durable) {
                Log::info('AI insights served from durable cache', [
                    'period' => $period,
                    'cache_key' => $cacheKey,
                ]);

                return $durable->payload;
            }

            $report = $this->latestReport($period);

            if ($report) {
                $payload = $this->formatReport($report);
                $this->storeDurableCache($cacheKey, 'ai_insights', $payload);

                Log::info('AI insights served from database report', [
                    'period' => $period,
                    'report_id' => $report->id,
                ]);

                return $payload;
            }

            Log::info('AI insights served from static fallback; no generated report exists', [
                'period' => $period,
            ]);

            return $this->getFallbackInsights($period);
        });
    }

    /**
     * Generate fresh AI insights. Intended for scheduler/Artisan only.
     */
    public function generateInsights(string $period = 'weekly', bool $force = false): array
    {
        $today = now()->toDateString();
        $dailyKey = "ai_insights:generated:{$period}:{$today}";
        $lockKey = "ai_insights:lock:{$period}:{$today}";
        $cooldownKey = 'ai_insights:cooldown';

        if (!$force && Cache::has($dailyKey)) {
            Log::info('AI insights generation skipped; daily generation already completed', [
                'period' => $period,
                'date' => $today,
            ]);

            return $this->getLatestInsights($period);
        }

        if (!$force && Cache::has($cooldownKey)) {
            Log::warning('AI insights generation skipped; Gemini cooldown is active', [
                'period' => $period,
                'cooldown_until' => Cache::get($cooldownKey),
            ]);

            return $this->markFallback($this->getLatestInsights($period), 'AI quota cooldown is active. Showing the latest stored report.');
        }

        $lock = Cache::lock($lockKey, 300);

        if (!$lock->get()) {
            Log::info('AI insights generation skipped; another generation is already running', [
                'period' => $period,
                'date' => $today,
            ]);

            return $this->getLatestInsights($period);
        }

        try {
            if (!$force && $this->reportExistsForToday($period)) {
                Cache::put($dailyKey, true, $this->cacheTtl);
                return $this->getLatestInsights($period);
            }

            return $this->performGeneration($period, $dailyKey);
        } finally {
            optional($lock)->release();
        }
    }

    public function warmInsightCache(string $period = 'weekly'): array
    {
        $report = $this->latestReport($period);
        $payload = $report ? $this->formatReport($report) : $this->getFallbackInsights($period);
        $cacheKey = $this->cacheKey($period);

        Cache::put($cacheKey, $payload, $this->cacheTtl);
        $this->storeDurableCache($cacheKey, 'ai_insights', $payload);

        return $payload;
    }

    private function performGeneration(string $period, string $dailyKey): array
    {
        $stats = $this->analytics->buildAnonymizedStatsForAI($period);
        $prompt = $this->buildInsightsPrompt($stats);
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
        $snapshot = $this->analytics->computeDailySnapshot(now()->subDay());
        $cacheExpiresAt = now()->addSeconds($this->cacheTtl);

        $report = AiInsightReport::updateOrCreate(
            [
                'report_type' => $period,
                'period_end' => $end->toDateString(),
            ],
            [
                'analytics_snapshot_id' => $snapshot->id,
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
                ],
            ]
        );

        $payload = $this->formatReport($report);
        $cacheKey = $this->cacheKey($period);
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

    private function buildInsightsPrompt(array $stats): string
    {
        $statsJson = json_encode($stats, JSON_PRETTY_PRINT);

        return <<<PROMPT
You are a mental health analytics advisor for a university student wellness chatbot called LeanOn Bot.
Analyze the following ANONYMIZED aggregate statistics and generate school-wide wellness insights.

PRIVACY AND SAFETY RULES:
- Never reference or attempt to identify individual students.
- Never infer personal identities.
- Use only the aggregate statistics provided below.
- Do not ask for raw conversations, names, emails, IDs, or personal details.
- Focus on wellness summaries, engagement observations, usage trends, recommendations, anomaly detection, and emotional trend summaries.

ANONYMIZED STATISTICS:
{$statsJson}

Return STRICT VALID JSON ONLY.
Do not include markdown, code fences, prose, comments, or text outside JSON.
Use this exact JSON shape:
{
  "insights": [
    {
      "category": "usage|emotional|crisis|engagement|academic",
      "title": "Brief insight title",
      "text": "Detailed observation with specific numbers",
      "severity": "info|warning|critical"
    }
  ],
  "recommendations": [
    {
      "priority": "high|medium|low",
      "text": "Actionable recommendation for counselors or administrators"
    }
  ],
  "trends": [
    {
      "metric": "Name of the metric",
      "direction": "increasing|decreasing|stable",
      "description": "What this trend means in context"
    }
  ],
  "wellness_summary": "A 2-3 sentence overall wellness assessment of the student population based on the data.",
  "anomalies": [
    {
      "type": "spike|drop|unusual_pattern",
      "description": "Description of the anomaly",
      "severity": "info|warning|critical"
    }
  ]
}

Generate 3-5 insights, 2-4 recommendations, and 2-3 trends. Include anomalies only when the data shows unusual patterns.
PROMPT;
    }

    private function formatReport(AiInsightReport $report): array
    {
        return [
            'id' => $report->id,
            'report_type' => $report->report_type,
            'period_start' => $report->period_start?->toDateString(),
            'period_end' => $report->period_end?->toDateString(),
            'insights' => $report->insights ?? [],
            'recommendations' => $report->recommendations ?? [],
            'trends' => $report->trends ?? [],
            'wellness_summary' => $report->wellness_summary ?? '',
            'anomalies' => $report->anomalies ?? [],
            'ai_provider' => $report->ai_provider,
            'generated_at' => ($report->generated_at ?? $report->created_at)->toIso8601String(),
            'cache_expires_at' => $report->cache_expires_at?->toIso8601String(),
            'metadata' => $report->metadata ?? [],
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
            'report_type' => $period,
            'insights' => [
                [
                    'category' => 'general',
                    'title' => 'Analytics Ready',
                    'text' => 'The analytics system is active and collecting anonymized data. Scheduled AI insights will appear after the next daily generation run.',
                    'severity' => 'info',
                ],
            ],
            'recommendations' => [
                [
                    'priority' => 'medium',
                    'text' => 'Continue monitoring aggregate usage and emotional trends while the scheduled report is prepared.',
                ],
            ],
            'trends' => [],
            'wellness_summary' => 'The wellness analytics system is initializing. Insights will improve as more anonymized data is collected and the scheduled daily report runs.',
            'anomalies' => [],
            'ai_provider' => 'fallback',
            'generated_at' => now()->toIso8601String(),
            'is_fallback' => true,
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

    private function reportExistsForToday(string $period): bool
    {
        return AiInsightReport::where('report_type', $period)
            ->where('status', 'completed')
            ->whereDate('generated_at', now()->toDateString())
            ->exists();
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
            'period' => $stats['period'] ?? null,
            'report_type' => $stats['report_type'] ?? null,
            'daily_active_users_avg' => $stats['daily_active_users_avg'] ?? 0,
            'total_conversations' => $stats['total_conversations'] ?? 0,
            'total_messages' => $stats['total_messages'] ?? 0,
            'avg_session_minutes' => $stats['avg_session_minutes'] ?? 0,
            'emotion_distribution' => $stats['emotion_distribution'] ?? [],
            'peak_usage_hours' => $stats['peak_usage_hours'] ?? [],
            'crisis_alerts_total' => $stats['crisis_alerts_total'] ?? 0,
            'sentiment_scores' => $stats['sentiment_scores'] ?? [],
            'trend_metrics' => $stats['trend_metrics'] ?? [],
        ];
    }

    private function cacheKey(string $period): string
    {
        return "ai_insights:latest:{$period}";
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
