<?php

namespace App\Contracts;

/**
 * AI Provider Interface
 *
 * Contract for AI providers used by the insights system.
 * Implement this to add support for OpenAI, Ollama, DeepSeek, Mistral, etc.
 */
interface AIProviderInterface
{
    /**
     * Generate insights from anonymized analytics data.
     *
     * @param  string $prompt  The constructed prompt with anonymized stats
     * @return array           Parsed response with insights, recommendations, trends, etc.
     * @throws \Exception      If the AI provider fails
     */
    public function generateInsights(string $prompt): array;

    /**
     * Get the provider name identifier.
     *
     * @return string  e.g. 'gemini', 'openai', 'ollama'
     */
    public function getProviderName(): string;
}
