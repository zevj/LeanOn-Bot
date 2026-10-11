<?php

namespace App\Services;

use App\Models\CrisisAlert;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * CrisisHistoryService
 *
 * Loads a student's all-time crisis alert history from `crisis_alerts`
 * and computes a risk tier used to inject context into the AI prompt.
 *
 * Results are cached per-user for 5 minutes so repeated messages within
 * the same conversation incur only one DB round-trip.
 *
 * Risk tier thresholds (conservative, counselor-informed):
 *   none      - 0 alerts ever
 *   low       - 1-2 alerts, all low/moderate severity
 *   moderate  - 3-5 alerts, OR any severe severity
 *   elevated  - 6-9 alerts, OR any high severity in last 30 days
 *   high      - 10+ alerts, OR any high severity in the last 7 days
 */
class CrisisHistoryService
{
    /** Cache TTL in seconds (5 minutes) */
    private const CACHE_TTL = 300;

    /**
     * Return crisis history summary for a given user.
     */
    public function getForUser(int $userId): array
    {
        $cacheKey = "crisis_history_{$userId}";
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($userId) {
            return $this->computeForUser($userId);
        });
    }

    /**
     * Flush cached history for a user (call after a new alert is created).
     */
    public function flushCacheForUser(int $userId): void
    {
        Cache::forget("crisis_history_{$userId}");
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    private function computeForUser(int $userId): array
    {
        try {
            $alerts = CrisisAlert::where('user_id', $userId)
                ->select(['severity', 'created_at'])
                ->orderBy('created_at', 'desc')
                ->get();

            if ($alerts->isEmpty()) {
                return $this->emptyResult();
            }

            $totalCount  = $alerts->count();
            $recentCount = $alerts->where('created_at', '>=', now()->subDays(30))->count();
            $lastAlertAt = $alerts->first()->created_at?->toDateTimeString();

            $severityRank = ['high' => 4, 'severe' => 3, 'moderate' => 2, 'low' => 1];
            $maxSeverity  = $alerts
                ->whereIn('severity', array_keys($severityRank))
                ->sortByDesc(fn($a) => $severityRank[$a->severity] ?? 0)
                ->first()
                ?->severity;

            $hasHighIn7d  = $alerts
                ->where('severity', 'high')
                ->where('created_at', '>=', now()->subDays(7))
                ->isNotEmpty();

            $hasHighIn30d = $alerts
                ->where('severity', 'high')
                ->where('created_at', '>=', now()->subDays(30))
                ->isNotEmpty();

            $riskTier = $this->computeRiskTier($totalCount, $maxSeverity, $hasHighIn7d, $hasHighIn30d);

            return [
                'total_count'          => $totalCount,
                'recent_count'         => $recentCount,
                'max_severity'         => $maxSeverity,
                'last_alert_at'        => $lastAlertAt,
                'has_high_in_last_7d'  => $hasHighIn7d,
                'has_high_in_last_30d' => $hasHighIn30d,
                'risk_tier'            => $riskTier,
            ];
        } catch (\Exception $e) {
            Log::warning('[CrisisHistoryService] Failed to compute crisis history: ' . $e->getMessage(), [
                'user_id' => $userId,
            ]);
            return $this->emptyResult();
        }
    }

    private function computeRiskTier(
        int    $totalCount,
        ?string $maxSeverity,
        bool   $hasHighIn7d,
        bool   $hasHighIn30d
    ): string {
        if ($totalCount >= 10 || $hasHighIn7d) {
            return 'high';
        }
        if ($totalCount >= 6 || $hasHighIn30d) {
            return 'elevated';
        }
        if ($totalCount >= 3 || $maxSeverity === 'severe') {
            return 'moderate';
        }
        if ($totalCount >= 1) {
            return 'low';
        }
        return 'none';
    }

    private function emptyResult(): array
    {
        return [
            'total_count'          => 0,
            'recent_count'         => 0,
            'max_severity'         => null,
            'last_alert_at'        => null,
            'has_high_in_last_7d'  => false,
            'has_high_in_last_30d' => false,
            'risk_tier'            => 'none',
        ];
    }
}