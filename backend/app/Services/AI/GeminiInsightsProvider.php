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
    private string $fallbackKey;
    private string $model;

    public function __construct()
    {
        // Key #2: dedicated analytics key — separate quota from chat bot's GEMINI_API_KEY
        $this->apiKey      = (string) (config('services.ai_insights.gemini_key') ?? '');
        // Key #3: optional fallback key — used only when Key #2 hits quota
        $this->fallbackKey = (string) (config('services.ai_insights.gemini_fallback_key') ?? '');
        $this->model       = (string) config('services.ai_insights.gemini_model', 'gemini-2.5-flash');
    }

    public function generateInsights(string $prompt): array
    {
        if (empty($this->apiKey)) {
            throw new \Exception('AI_INSIGHTS_GEMINI_KEY is not configured. Set a dedicated Gemini API key for analytics in your .env file.');
        }

        try {
            return $this->callGemini($this->apiKey, $prompt);
        } catch (\Exception $e) {
            // If quota error and fallback key is configured, try fallback once
            $isQuota = str_contains(strtolower($e->getMessage()), 'quota')
                || str_contains(strtolower($e->getMessage()), 'resource exhausted')
                || str_contains(strtolower($e->getMessage()), '429');

            if ($isQuota && !empty($this->fallbackKey)) {
                Log::info('Gemini primary key quota hit — trying fallback key');
                return $this->callGemini($this->fallbackKey, $prompt);
            }

            throw $e;
        }
    }

    private function callGemini(string $key, string $prompt): array
    {
        $response = Http::withoutVerifying()
            ->connectTimeout(8)
            ->timeout((int) config('services.ai_insights.timeout', 25))
            ->withHeaders(['Content-Type' => 'application/json'])
            ->post("https://generativelanguage.googleapis.com/v1beta/models/{$this->model}:generateContent?key={$key}", [
                'contents' => [
                    [
                        'role' => 'user',
                        'parts' => [['text' => $prompt]],
                    ],
                ],
                'generationConfig' => [
                    'temperature'      => 0.1,
                    'topP'             => 0.8,
                    'maxOutputTokens'  => (int) config('services.ai_insights.max_output_tokens', 1200),
                    'responseMimeType' => 'application/json',
                ],
            ]);

        if (!$response->successful()) {
            $errorMessage = $response->json('error.message') ?? $response->body() ?: (string) $response->status();
            $status = $response->status();

            Log::warning('Gemini Insights API error', [
                'status'  => $status,
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

        // Support both the compact new format and the legacy extended format
        $insights = $this->normalizeList($parsed['insights'] ?? $parsed['key_insights'] ?? []);

        // Accept both "wellness_summary" (correct) and "summary" (Gemini alias)
        $wellnessSummary = '';
        if (is_string($parsed['wellness_summary'] ?? null)) {
            $wellnessSummary = $parsed['wellness_summary'];
        } elseif (is_string($parsed['summary'] ?? null)) {
            $wellnessSummary = $parsed['summary'];
        }

        return [
            'insights'                    => $insights,
            'recommendations'             => $this->normalizeList($parsed['recommendations'] ?? []),
            'trends'                      => $this->normalizeList($parsed['trends'] ?? []),
            'wellness_summary'            => $wellnessSummary,
            'anomalies'                   => $this->normalizeList($parsed['anomalies'] ?? []),
            'top_department'              => $parsed['top_department'] ?? null,
            'top_gender'                  => $parsed['top_gender'] ?? null,
            'department_with_most_alerts' => $parsed['department_with_most_alerts'] ?? null,
        ];
    }

    public function getProviderName(): string
    {
        return 'gemini';
    }

    private function parseStrictJson(string $text): ?array
    {
        // Build a list of candidates to try, from most to least specific
        $candidates = [];

        // 1. Strip markdown code fences
        if (preg_match('/```(?:json)?\s*([\s\S]*?)\s*```/i', $text, $matches)) {
            $candidates[] = trim($matches[1]);
        }

        // 2. Extract the outermost {...} block
        $firstBrace = strpos($text, '{');
        $lastBrace  = strrpos($text, '}');
        if ($firstBrace !== false && $lastBrace !== false && $lastBrace > $firstBrace) {
            $candidates[] = substr($text, $firstBrace, $lastBrace - $firstBrace + 1);
        }

        // 3. Raw trimmed text as last resort
        $candidates[] = trim($text);

        foreach ($candidates as $candidate) {
            // Strip BOM
            $candidate = preg_replace('/^\xEF\xBB\xBF/', '', $candidate);
            // Remove trailing commas before } or ]
            $candidate = preg_replace('/,\s*([}\]])/', '$1', $candidate);
            // Remove control characters that break JSON parsing
            $candidate = preg_replace('/[\x00-\x1F\x7F](?<!["\n\r\t])/', '', $candidate);

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
