<?php

namespace App\Console\Commands;

use App\Services\AIInsightsService;
use App\Services\AnalyticsService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class GenerateDailyAiAnalytics extends Command
{
    protected $signature = 'analytics:generate-daily-ai
        {--period=weekly : daily, weekly, or monthly}
        {--force : Ignore once-per-day guard for manual maintenance}';

    protected $description = 'Generate cached anonymized AI analytics once per day for the admin dashboard.';

    public function handle(AnalyticsService $analytics, AIInsightsService $insights): int
    {
        $period = (string) $this->option('period');
        $force = (bool) $this->option('force');

        if (!in_array($period, ['daily', 'weekly', 'monthly'], true)) {
            $this->error('Invalid period. Use daily, weekly, or monthly.');
            return self::INVALID;
        }

        $runKey = "analytics:scheduled-run:{$period}:" . now()->toDateString();

        if (!$force && Cache::has($runKey)) {
            $this->info("AI analytics already generated today for {$period}.");
            return self::SUCCESS;
        }

        try {
            $snapshot = $analytics->computeDailySnapshot(now()->subDay());
            $report = $insights->generateInsights($period, $force);
            Cache::put($runKey, true, 86400);

            Log::info('Scheduled AI analytics generation completed', [
                'period' => $period,
                'snapshot_id' => $snapshot->id,
                'report_id' => $report['id'] ?? null,
                'generated_at' => $report['generated_at'] ?? now()->toIso8601String(),
            ]);

            $this->info("AI analytics generated for {$period}.");
            return self::SUCCESS;
        } catch (\Throwable $e) {
            Log::warning('Scheduled AI analytics generation failed; cache fallback remains available', [
                'period' => $period,
                'message' => $e->getMessage(),
            ]);

            $insights->warmInsightCache($period);
            $this->warn('Generation failed; warmed cache with latest available fallback.');
            return self::SUCCESS;
        }
    }
}
