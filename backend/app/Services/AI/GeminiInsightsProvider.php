<?php

namespace App\Services\AI;

use App\Contracts\AIProviderInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Gemini Insights Provider
 *
 * Uses the existing Gemini API (same key as ChatController) to generate
 * natural-language insights from anonymized statistics.
 *
 * Privacy: This provider ONLY receives aggregated stats — never PII or raw messages.
 */
class GeminiInsightsProvider implements AIProviderInterface
{
    private string $apiKey;
    private string $model;

    public function __construct()
    {
        $this->apiKey = (string) (config('services.ai_insights.gemini_key') ?? env('GEMINI_API_KEY', ''));
        $this->model = (string) config('services.ai_insights.gemini_model', 'gemini-2.5-flash');
    }

    public function generateInsights(string $prompt): array
    {
        if (empty($this->apiKey)) {
            throw new \Exception('Gemini API key is not configured for insights generation.');
        }

        $response = Http::withoutVerifying()
            ->connectTimeout(8)
            ->timeout((int) config('services.ai_insights.timeout', 25))
            ->withHeaders(['Content-Type' => 'application/json'])
            ->post("https://generativelanguage.googleapis.com/v1beta/models/{$this->model}:generateContent?key=" . $this->apiKey, [
                'contents' => [
                    [
                        'role' => 'user',
                        'parts' => [['text' => $prompt]],
                    ],
                ],
                'generationConfig' => [
                    'temperature' => 0.2,
                    'topP' => 0.8,
                    'maxOutputTokens' => (int) config('services.ai_insights.max_output_tokens', 1200),
                    'responseMimeType' => 'application/json',
                ],
            ]);

        if (!$response->successful()) {
            $errorMessage = $response->json('error.message') ?? $response->body() ?: (string) $response->status();
            $status = $response->status();

            Log::warning('Gemini Insights API error', [
                'status' => $status,
                'message' => $errorMessage,
            ]);

            throw new \Exception("Gemini Insights API Error ({$status}): {$errorMessage}");
        }

        $text = $response->json('candidates.0.content.parts.0.text');

        if (empty($text)) {
            throw new \Exception('Gemini returned an empty response for insights.');
        }

        $parsed = $this->parseStrictJson($text);

        if (!$parsed) {
            Log::warning('Gemini insights response was not valid JSON after cleanup', [
                'sample' => substr($text, 0, 500),
            ]);

            throw new \Exception('Gemini returned malformed JSON for insights.');
        }

        return [
            'insights' => $this->normalizeList($parsed['insights'] ?? $parsed['key_insights'] ?? []),
            'recommendations' => $this->normalizeList($parsed['recommendations'] ?? []),
            'trends' => $this->normalizeList($parsed['trends'] ?? []),
            'wellness_summary' => is_string($parsed['wellness_summary'] ?? null) ? $parsed['wellness_summary'] : '',
            'anomalies' => $this->normalizeList($parsed['anomalies'] ?? []),
        ];
    }

    public function getProviderName(): string
    {
        return 'gemini';
    }

    private function parseStrictJson(string $text): ?array
    {
        $candidates = [trim($text)];

        if (preg_match('/```(?:json)?\s*([\s\S]*?)\s*```/', $text, $matches)) {
            $candidates[] = trim($matches[1]);
        }

        $firstBrace = strpos($text, '{');
        $lastBrace = strrpos($text, '}');
        if ($firstBrace !== false && $lastBrace !== false && $lastBrace > $firstBrace) {
            $candidates[] = substr($text, $firstBrace, $lastBrace - $firstBrace + 1);
        }

        foreach ($candidates as $candidate) {
            $candidate = preg_replace('/^\xEF\xBB\xBF/', '', $candidate);
            $candidate = preg_replace('/,\s*([}\]])/', '$1', $candidate);
            $decoded = json_decode($candidate, true);

            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return $decoded;
            }
        }

        return null;
    }

    private function normalizeList(mixed $value): array
    {
        return is_array($value) ? array_values($value) : [];
    }
}
