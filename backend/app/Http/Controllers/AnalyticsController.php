<?php

namespace App\Http\Controllers;

use App\Services\AnalyticsService;
use App\Services\AIInsightsService;
use App\Models\AiInsightReport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Analytics Controller
 *
 * All endpoints are protected by auth:sanctum + role:guidance middleware.
 * Admin UI can access student identity for searching/picker purposes.
 */
class AnalyticsController extends Controller
{
    private AnalyticsService $analytics;
    private AIInsightsService $aiInsights;

    public function __construct(AnalyticsService $analytics, AIInsightsService $aiInsights)
    {
        $this->analytics = $analytics;
        $this->aiInsights = $aiInsights;
    }

    /**
     * GET /api/admin/analytics/dashboard
     *
     * Extended dashboard statistics with growth indicators.
     */
    public function dashboard(Request $request)
    {
        $period = $request->query('period', '7d');
        $allowed = ['1d', '7d', '14d', '30d', '90d'];

        if (!in_array($period, $allowed)) {
            $period = '7d';
        }

        try {
            $stats = $this->analytics->getDashboardStats($period);
            return response()->json($stats);
        } catch (\Exception $e) {
            Log::error('Analytics dashboard error: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to load analytics.'], 500);
        }
    }

    /**
     * GET /api/admin/analytics/students?q=
     *
     * Anonymized student search for the Student Insights page.
     */
    public function students(Request $request)
    {
        $q = (string) $request->query('q', '');

        try {
            $students = $this->analytics->searchStudents($q);
            return response()->json(['students' => $students]);
        } catch (\Exception $e) {
            Log::error('Analytics student search error: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to search students.'], 500);
        }
    }

    /**
     * GET /api/admin/analytics/student/dashboard?user_id=&period=
     *
     * Per-student wellness dashboard (anonymized subject + live metrics).
     */
    public function studentDashboard(Request $request)
    {
        $userId = (int) $request->query('user_id', 0);
        if ($userId < 1) {
            return response()->json(['error' => 'user_id is required.'], 422);
        }

        $period = $request->query('period', '7d');
        $allowed = ['1d', '7d', '14d', '30d', '90d'];
        if (!in_array($period, $allowed)) {
            $period = '7d';
        }

        try {
            $stats = $this->analytics->getStudentDashboardStats($userId, $period);
            if (!$stats) {
                return response()->json(['error' => 'Student not found.'], 404);
            }
            return response()->json($stats);
        } catch (\Exception $e) {
            Log::error('Student analytics dashboard error: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to load student analytics.'], 500);
        }
    }

    /**
     * GET /api/admin/analytics/student/trends?user_id=&period=
     *
     * Per-student emotion / usage trends.
     */
    public function studentTrends(Request $request)
    {
        $userId = (int) $request->query('user_id', 0);
        if ($userId < 1) {
            return response()->json(['error' => 'user_id is required.'], 422);
        }

        $period = $request->query('period', '30d');
        $allowed = ['7d', '14d', '30d', '90d'];
        if (!in_array($period, $allowed)) {
            $period = '30d';
        }

        try {
            $trends = $this->analytics->getStudentTrends($userId, $period);
            if (!$trends) {
                return response()->json(['error' => 'Student not found.'], 404);
            }
            return response()->json($trends);
        } catch (\Exception $e) {
            Log::error('Student analytics trends error: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to load student trends.'], 500);
        }
    }

    /**
     * GET /api/admin/analytics/trends
     *
     * Emotion/sentiment trends and weekly comparisons.
     */
    public function trends(Request $request)
    {
        $period = $request->query('period', '30d');
        $allowed = ['7d', '14d', '30d', '90d'];

        if (!in_array($period, $allowed)) {
            $period = '30d';
        }

        try {
            $trends = $this->analytics->getTrends($period);
            return response()->json($trends);
        } catch (\Exception $e) {
            Log::error('Analytics trends error: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to load trends.'], 500);
        }
    }

    /**
     * GET /api/admin/analytics/insights
     *
     * Latest AI-generated insights (from cache/DB, no real-time AI call).
     *
     * Query params:
     *   period = daily|weekly|monthly  (default: weekly)
     *   days   = 7|60                  (optional explicit window; overrides period for data range)
     */
    public function insights(Request $request)
    {
        $period = $request->query('period', 'weekly');
        $allowed = ['daily', 'weekly', 'monthly'];

        if (!in_array($period, $allowed)) {
            $period = 'weekly';
        }

        $days = (int) $request->query('days', 0);
        if (!in_array($days, [0, 7, 60])) {
            $days = 0;
        }

        try {
            $insights = $this->aiInsights->getLatestInsights($period, $days);
            return response()->json($insights);
        } catch (\Exception $e) {
            Log::error('AI Insights retrieval error: ' . $e->getMessage());
            return response()->json([
                'error' => 'Failed to load insights.',
                'is_fallback' => true,
            ], 500);
        }
    }

    /**
     * POST /api/admin/analytics/insights/generate
     *
     * Manually trigger AI insight generation without shell access.
     * Useful for Render free-tier deployments where cron/shell is unavailable.
     *
     * Query params:
     *   period = daily|weekly|monthly  (default: weekly)
     *   force  = 1                     (bypass once-per-day guard)
     *   days   = 7|60                  (optional explicit window; 7 = Last 7 Days, 60 = Last 60 Days)
     */
    public function generateInsights(Request $request)
    {
        $period = $request->query('period', 'weekly');
        $allowed = ['daily', 'weekly', 'monthly'];

        if (!in_array($period, $allowed)) {
            $period = 'weekly';
        }

        $force = filter_var($request->query('force', false), FILTER_VALIDATE_BOOLEAN);

        $days = (int) $request->query('days', 0);
        if (!in_array($days, [0, 7, 60])) {
            $days = 0;
        }

        try {
            Log::info('Manual AI insights generation triggered via API', [
                'period'       => $period,
                'days'         => $days,
                'force'        => $force,
                'triggered_by' => $request->user()?->id,
            ]);

            // Snapshot computation is best-effort — don't let it block generation
            try {
                $this->analytics->computeDailySnapshot(now()->subDay());
            } catch (\Throwable $e) {
                Log::warning('Snapshot computation failed (non-fatal): ' . $e->getMessage());
            }

            // Always force=true from the UI so cache guards never block it
            $insights = $this->aiInsights->generateInsights($period, true, $days);

            return response()->json($insights);

        } catch (\Exception $e) {
            Log::error('Manual AI insights generation failed: ' . $e->getMessage());
            return response()->json([
                'error'  => 'Insight generation failed: ' . $e->getMessage(),
                'period' => $period,
            ], 500);
        }
    }

    /**
     * GET /api/admin/analytics/wellness-report
     *
     * Wellness summary report for a given period.
     */
    public function wellnessReport(Request $request)
    {
        $period = $request->query('period', 'weekly');

        try {
            $insights = $this->aiInsights->getLatestInsights($period);

            return response()->json([
                'wellness_summary' => $insights['wellness_summary'] ?? '',
                'recommendations'  => $insights['recommendations'] ?? [],
                'trends'           => $insights['trends'] ?? [],
                'period'           => $period,
                'generated_at'     => $insights['generated_at'] ?? now()->toIso8601String(),
            ]);
        } catch (\Exception $e) {
            Log::error('Wellness report error: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to load wellness report.'], 500);
        }
    }

    /**
     * GET /api/admin/analytics/snapshots
     *
     * Historical snapshot data for time-series charts.
     */
    public function snapshots(Request $request)
    {
        $days = (int) $request->query('days', 30);
        $days = min(max($days, 7), 90); // Clamp between 7–90

        try {
            $snapshots = $this->analytics->getSnapshots($days);
            return response()->json(['snapshots' => $snapshots]);
        } catch (\Exception $e) {
            Log::error('Analytics snapshots error: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to load snapshots.'], 500);
        }
    }

    /**
     * GET /api/admin/analytics/export
     *
     * Returns a structured JSON payload for PDF/CSV report generation on the frontend.
     * Supports filtering by sections, preset period, or a custom date range.
     *
     * Query params:
     *   period      = 1d|7d|14d|30d|90d  (default: 7d) — ignored when start_date/end_date are set
     *   start_date  = YYYY-MM-DD          (optional, custom range start)
     *   end_date    = YYYY-MM-DD          (optional, custom range end)
     *   sections[]  = dashboard|trends|insights|snapshots  (default: all)
     */
    public function export(Request $request)
    {
        // ── Date range resolution ──────────────────────────────────────────
        $startDate = $request->query('start_date');
        $endDate   = $request->query('end_date');
        $isCustomRange = $startDate && $endDate;
        $format    = $request->query('format', 'pdf'); // 'pdf' or 'csv'

        if ($isCustomRange) {
            // Validate date format
            try {
                $start = \Carbon\Carbon::createFromFormat('Y-m-d', $startDate)->startOfDay();
                $end   = \Carbon\Carbon::createFromFormat('Y-m-d', $endDate)->endOfDay();
                if ($start->gt($end)) {
                    return response()->json(['error' => 'start_date must be before end_date.'], 422);
                }
            } catch (\Exception $e) {
                return response()->json(['error' => 'Invalid date format. Use YYYY-MM-DD.'], 422);
            }
            $period = null; // not used in custom mode
            $periodLabel = $startDate . ' to ' . $endDate;
        } else {
            $period = $request->query('period', '7d');
            $allowedPeriods = ['1d', '7d', '14d', '30d', '90d'];
            if (!in_array($period, $allowedPeriods)) {
                $period = '7d';
            }
            $periodLabel = $period;
            $start = null;
            $end   = null;
        }

        // ── Sections ──────────────────────────────────────────────────────
        $requestedSections = $request->query('sections', ['dashboard', 'trends', 'insights']);
        if (is_string($requestedSections)) {
            $requestedSections = explode(',', $requestedSections);
        }
        $allowedSections = ['dashboard', 'trends', 'insights', 'snapshots'];
        $sections = array_intersect($requestedSections, $allowedSections);
        if (empty($sections)) {
            $sections = ['dashboard', 'trends', 'insights'];
        }

        $payload = [
            'generated_at' => now()->toIso8601String(),
            'period'       => $periodLabel,
            'sections'     => array_values($sections),
        ];

        try {
            if (in_array('dashboard', $sections)) {
                if ($isCustomRange) {
                    $payload['dashboard'] = $this->analytics->getDashboardStatsByRange($start, $end);
                } else {
                    $payload['dashboard'] = $this->analytics->getDashboardStats($period);
                }
            }

            if (in_array('trends', $sections)) {
                if ($isCustomRange) {
                    $payload['trends'] = $this->analytics->getTrendsByRange($start, $end);
                } else {
                    $trendPeriod = $period === '1d' ? '7d' : $period;
                    $payload['trends'] = $this->analytics->getTrends($trendPeriod);
                }
            }

            if (in_array('insights', $sections)) {
                // Insights are AI-generated summaries — always use latest cached version
                $insightPeriod = 'weekly';
                if (!$isCustomRange) {
                    $insightPeriod = match (true) {
                        in_array($period, ['1d', '7d', '14d']) => 'weekly',
                        $period === '30d'                      => 'monthly',
                        $period === '90d'                      => 'monthly',
                        default                                => 'weekly',
                    };
                }
                $payload['insights'] = $this->aiInsights->getLatestInsights($insightPeriod);
            }

            if (in_array('snapshots', $sections)) {
                if ($isCustomRange) {
                    $payload['snapshots'] = $this->analytics->getSnapshotsByRange($start, $end);
                } else {
                    $days = match ($period) {
                        '1d'  => 7,
                        '7d'  => 7,
                        '14d' => 14,
                        '30d' => 30,
                        '90d' => 90,
                        default => 30,
                    };
                    $payload['snapshots'] = $this->analytics->getSnapshots($days);
                }
            }

            // Record export notification for admin panel
            try {
                if ($format === 'csv') {
                    \App\Models\AdminNotification::csvExported($periodLabel);
                } else {
                    \App\Models\AdminNotification::reportExported($periodLabel, array_values($sections));
                }
            } catch (\Exception $e) {
                Log::warning('Failed to create export notification: ' . $e->getMessage());
            }

            return response()->json($payload);
        } catch (\Exception $e) {
            Log::error('Analytics export error: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to generate export data.'], 500);
        }
    }
}