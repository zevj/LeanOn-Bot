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
        {--days=0 : Explicit day window for data range: 7 (Last 7 Days) or 60 (Last 60 Days). 0 = use period default.}
        {--force : Ignore once-per-day guard for manual maintenance}';

    protected $description = 'Generate cached anonymized AI analytics once per day for the admin dashboard.';

    public function handle(AnalyticsService $analytics, AIInsightsService $insights): int
    {
        $period = (string) $this->option('period');
        $force  = (bool)   $this->option('force');
        $days   = (int)    $this->option('days');

        if (!in_array($period, ['daily', 'weekly', 'monthly'], true)) {
            $this->error('Invalid period. Use daily, weekly, or monthly.');
            return self::INVALID;
        }

        if ($days > 0 && !in_array($days, [7, 60], true)) {
            $this->error('Invalid days value. Use 7 or 60 (or 0 to use the period default).');
            return self::INVALID;
        }

        $windowTag = $days > 0 ? "_{$days}d" : '';
        $runKey = "analytics:scheduled-run:{$period}{$windowTag}:" . \now()->toDateString();

        if (!$force && Cache::has($runKey)) {
            $this->info("AI analytics already generated today for {$period}" . ($days > 0 ? " ({$days}-day window)" : '') . '.');
            return self::SUCCESS;
        }

        try {
            $snapshot = $analytics->computeDailySnapshot(\now()->subDay());
            $report   = $insights->generateInsights($period, $force, $days);
            Cache::put($runKey, true, 86400);

            Log::info('Scheduled AI analytics generation completed', [
                'period'       => $period,
                'days'         => $days,
                'snapshot_id'  => $snapshot->getKey(),
                'report_id'    => $report['id'] ?? null,
                'generated_at' => $report['generated_at'] ?? \now()->toIso8601String(),
            ]);

            $this->info("AI analytics generated for {$period}" . ($days > 0 ? " ({$days}-day window)" : '') . '.');
            return self::SUCCESS;
        } catch (\Throwable $e) {
            Log::warning('Scheduled AI analytics generation failed; cache fallback remains available', [
                'period'  => $period,
                'days'    => $days,
                'message' => $e->getMessage(),
            ]);

            $insights->warmInsightCache($period, $days);
            $this->warn('Generation failed; warmed cache with latest available fallback.');
            return self::SUCCESS;
        }
    }
}
