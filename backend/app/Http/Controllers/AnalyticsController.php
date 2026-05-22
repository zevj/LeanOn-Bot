<?php

namespace App\Http\Controllers;

use App\Services\AnalyticsService;
use App\Services\AIInsightsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Analytics Controller
 *
 * All endpoints are protected by auth:sanctum + role:guidance middleware.
 * All data returned is anonymized — no student PII is ever exposed.
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
     */
    public function insights(Request $request)
    {
        $period = $request->query('period', 'weekly');
        $allowed = ['daily', 'weekly', 'monthly'];

        if (!in_array($period, $allowed)) {
            $period = 'weekly';
        }

        try {
            $insights = $this->aiInsights->getLatestInsights($period);
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
}
