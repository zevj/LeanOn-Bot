<?php

namespace App\Services\AI;

use App\Contracts\AIProviderInterface;

/**
 * Mock Insights Provider
 *
 * Generates realistic, static insights based on the same schema as Gemini.
 * Perfect for local development, demoing, and when API quotas are exceeded.
 */
class MockInsightsProvider implements AIProviderInterface
{
    public function generateInsights(string $prompt): array
    {
        // Simulate minor API latency (500ms)
        usleep(500000);

        // Extract days window from prompt for context-aware mock data
        $is60Days = str_contains($prompt, 'Last 60 Days');
        $windowLabel = $is60Days ? '60-day' : '7-day';

        return [
            'insights' => [
                [
                    'category' => 'usage',
                    'title' => 'Mid-Day Interaction Peak',
                    'text' => "Student interactions show a 24% increase between 12–2 PM in the {$windowLabel} window, suggesting lunch-break usage.",
                    'severity' => 'info',
                ],
                [
                    'category' => 'emotional',
                    'title' => 'Academic Stress Elevation',
                    'text' => "Aggregated sentiment logs show a 12% rise in stress and anxiety levels over the {$windowLabel} period.",
                    'severity' => 'warning',
                ],
                [
                    'category' => 'crisis',
                    'title' => 'Crisis Triggers Stable',
                    'text' => 'Crisis alerts have decreased by 40% this period. No critical wellness triggers require immediate intervention.',
                    'severity' => 'info',
                ],
            ],
            'recommendations' => [
                [
                    'priority' => 'high',
                    'text' => 'Coordinate peer support groups or counseling slots during mid-day breaks to align with peak usage.',
                ],
                [
                    'priority' => 'medium',
                    'text' => 'Deploy targeted stress management resources through the bot for upcoming academic milestones.',
                ],
            ],
            'trends' => [
                [
                    'metric' => 'Daily Active Users',
                    'direction' => 'increasing',
                    'description' => 'Consistent growth in school-wide engagement suggests high trust in the platform.',
                ],
                [
                    'metric' => 'Anxious Expressions',
                    'direction' => 'decreasing',
                    'description' => 'The ratio of anxious expressions has stabilized, indicating positive student coping mechanisms.',
                ],
            ],
            'wellness_summary' => "The student population shows solid coping indicators over the {$windowLabel} window. Mild academic anxiety is present but crisis indicators remain low.",
            'anomalies' => [
                [
                    'type' => 'spike',
                    'description' => 'Unusual conversation duration spike on Tuesday evening.',
                    'severity' => 'info',
                ],
            ],
        ];
    }

    public function getProviderName(): string
    {
        return 'mock';
    }
}
