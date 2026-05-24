<?php

namespace App\Console\Commands;

use App\Models\AiInsightReport;
use App\Models\CachedAnalyticsSummary;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class ResetAiInsights extends Command
{
    protected $signature = 'insights:reset
        {--period= : Only reset a specific period (daily, weekly, monthly). Omit to reset all.}';

    protected $description = 'Delete stored AI insight reports and clear all related caches. Use before demo re-generation.';

    public function handle(): int
    {
        $period = $this->option('period');

        if ($period && !in_array($period, ['daily', 'weekly', 'monthly'], true)) {
            $this->error('Invalid period. Use daily, weekly, or monthly (or omit to reset all).');
            return self::INVALID;
        }

        $scope = $period ? "period: {$period}" : 'ALL periods';

        if (!$this->confirm("This will delete all AI insight reports and caches for {$scope}. Continue?")) {
            $this->info('Aborted.');
            return self::SUCCESS;
        }

        // 1. Delete rows from ai_insight_reports
        $reportQuery = AiInsightReport::query();
        if ($period) {
            $reportQuery->where('report_type', $period);
        }
        $reportCount = $reportQuery->count();
        $reportQuery->delete();
        $this->line("  Deleted {$reportCount} row(s) from <info>ai_insight_reports</info>");

        // 2. Delete rows from cached_analytics_summaries
        $cacheQuery = CachedAnalyticsSummary::where('summary_type', 'ai_insights');
        if ($period) {
            // Cache keys follow the pattern: ai_insights:latest:{period}[:{days}d]
            $cacheQuery->where('cache_key', 'like', "ai_insights:latest:{$period}%");
        }
        $cacheCount = $cacheQuery->count();
        $cacheQuery->delete();
        $this->line("  Deleted {$cacheCount} row(s) from <info>cached_analytics_summaries</info>");

        // 3. Flush Laravel cache keys
        $periods = $period ? [$period] : ['daily', 'weekly', 'monthly'];
        $dayWindows = [0, 7, 60];
        $flushed = 0;

        foreach ($periods as $p) {
            foreach ($dayWindows as $d) {
                $suffix = $d > 0 ? ":{$d}d" : '';
                $keys = [
                    "ai_insights:latest:{$p}{$suffix}",
                    "ai_insights:generated:{$p}" . ($d > 0 ? "_{$d}d" : '') . ':' . now()->toDateString(),
                    "analytics:scheduled-run:{$p}" . ($d > 0 ? "_{$d}d" : '') . ':' . now()->toDateString(),
                ];
                foreach ($keys as $key) {
                    if (Cache::forget($key)) {
                        $flushed++;
                    }
                }
            }
            // Also clear the cooldown so generation isn't blocked
            Cache::forget('ai_insights:cooldown');
        }

        $this->line("  Cleared <info>{$flushed}</info> Laravel cache key(s)");
        $this->newLine();
        $this->info("Reset complete. You can now run:");
        $this->line("  php artisan analytics:generate-daily-ai --period=" . ($period ?? 'weekly') . " --force");

        return self::SUCCESS;
    }
}
