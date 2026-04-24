<?php

declare(strict_types=1);

namespace App\Services\AI;

use App\Models\AiAuditLog;
use App\Models\Translation;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OpenAiCompatibleGenerator implements DistractorGeneratorInterface
{
    private readonly string $provider;

    private readonly string $providerName;

    private readonly string $baseUrl;

    private readonly ?string $apiKey;

    private readonly ?string $model;

    private readonly int $timeout;

    private readonly int $maxTokens;

    private readonly float $temperature;

    public function __construct()
    {
        $this->provider     = config('ai.default', 'openai_compatible');
        $this->providerName = config("ai.providers.{$this->provider}.name", 'OpenAI Compatible');
        $this->baseUrl      = config("ai.providers.{$this->provider}.base_url", 'http://localhost:1234/v1');
        $this->apiKey       = config("ai.providers.{$this->provider}.api_key");
        $this->model        = config("ai.providers.{$this->provider}.model");
        $this->timeout      = (int) config("ai.providers.{$this->provider}.timeout", 120);
        $this->maxTokens    = (int) config("ai.providers.{$this->provider}.max_tokens", 4096);
        $this->temperature  = (float) config("ai.providers.{$this->provider}.temperature", 0.7);
    }

    public function generate(Translation $translation, int $count = 3): array
    {
        $translation->loadMissing(['sourceLanguage', 'targetLanguage']);

        $prompt    = $this->buildPrompt($translation, $count);
        $startTime = microtime(true);

        try {
            $response = Http::timeout($this->timeout)
                ->withHeaders($this->getHeaders())
                ->post("{$this->baseUrl}/chat/completions", $this->buildRequestBody($prompt));

            $responseTimeMs = $this->elapsedMs($startTime);
            $responseJson   = $response->json();

            if (! $response->successful()) {
                $this->logAudit([
                    'user_id'          => $translation->user_id,
                    'feature'          => 'distractors',
                    'prompt'           => $prompt,
                    'response'         => $response->body(),
                    'success'          => false,
                    'response_time_ms' => $responseTimeMs,
                    'error_message'    => "HTTP {$response->status()}: {$response->body()}",
                    'translation_id'   => (string) $translation->id,
                ]);

                Log::error('AI API error', [
                    'provider' => $this->providerName,
                    'status'   => $response->status(),
                    'body'     => $response->body(),
                ]);

                return [];
            }

            $distractors = $this->parseResponse($responseJson, $count);

            $this->logAudit([
                'user_id'          => $translation->user_id,
                'feature'          => 'distractors',
                'prompt'           => $prompt,
                'response'         => $this->extractResponseContent($responseJson),
                'parsed_result'    => $distractors,
                'success'          => ! empty($distractors),
                'tokens_used'      => $responseJson['usage']['total_tokens'] ?? null,
                'response_time_ms' => $responseTimeMs,
                'translation_id'   => (string) $translation->id,
            ]);

            return $distractors;
        } catch (\Exception $e) {
            $this->logAudit([
                'user_id'          => $translation->user_id,
                'feature'          => 'distractors',
                'prompt'           => $prompt,
                'success'          => false,
                'response_time_ms' => $this->elapsedMs($startTime),
                'error_message'    => $e->getMessage(),
                'translation_id'   => (string) $translation->id,
            ]);

            Log::error('AI API exception', [
                'provider' => $this->providerName,
                'message'  => $e->getMessage(),
            ]);

            return [];
        }
    }

    public function generateBatch(Collection $translations, int $count = 3): array
    {
        if ($translations->isEmpty()) {
            return [];
        }

        $translations->loadMissing(['sourceLanguage', 'targetLanguage']);

        $prompt    = $this->buildBatchPrompt($translations, $count);
        $startTime = microtime(true);

        try {
            $response = Http::timeout($this->timeout)
                ->withHeaders($this->getHeaders())
                ->post("{$this->baseUrl}/chat/completions", $this->buildRequestBody($prompt));

            $responseTimeMs = $this->elapsedMs($startTime);
            $responseJson   = $response->json();

            if (! $response->successful()) {
                $this->logAudit([
                    'user_id'          => $translations->first()?->user_id,
                    'feature'          => 'distractors_batch',
                    'prompt'           => $prompt,
                    'response'         => $response->body(),
                    'success'          => false,
                    'response_time_ms' => $responseTimeMs,
                    'error_message'    => "HTTP {$response->status()}: {$response->body()}",
                    'translation_id'   => $translations->pluck('id')->implode(','),
                ]);

                Log::error('AI API error in batch generation', [
                    'provider' => $this->providerName,
                    'status'   => $response->status(),
                    'body'     => $response->body(),
                ]);

                return [];
            }

            $results = $this->parseBatchResponse($responseJson, $count);

            $this->logAudit([
                'user_id'          => $translations->first()?->user_id,
                'feature'          => 'distractors_batch',
                'prompt'           => $prompt,
                'response'         => $this->extractResponseContent($responseJson),
                'parsed_result'    => $results,
                'success'          => ! empty($results),
                'tokens_used'      => $responseJson['usage']['total_tokens'] ?? null,
                'response_time_ms' => $responseTimeMs,
                'translation_id'   => $translations->pluck('id')->implode(','),
            ]);

            return $results;
        } catch (\Exception $e) {
            $this->logAudit([
                'user_id'          => $translations->first()?->user_id,
                'feature'          => 'distractors_batch',
                'prompt'           => $prompt,
                'success'          => false,
                'response_time_ms' => $this->elapsedMs($startTime),
                'error_message'    => $e->getMessage(),
                'translation_id'   => $translations->pluck('id')->implode(','),
            ]);

            Log::error('AI API exception in batch generation', [
                'provider' => $this->providerName,
                'message'  => $e->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * The generator is available when both a base URL and an API key are configured.
     */
    public function isAvailable(): bool
    {
        return ! empty($this->baseUrl) && ! empty($this->apiKey);
    }

    public function getProviderInfo(): array
    {
        return [
            'provider' => $this->provider,
            'name'     => $this->providerName,
            'base_url' => $this->baseUrl,
            'model'    => $this->model,
        ];
    }

    private function elapsedMs(float $startTime): int
    {
        return (int) round((microtime(true) - $startTime) * 1000);
    }

    private function getHeaders(): array
    {
        $headers = ['Content-Type' => 'application/json'];

        if (! empty($this->apiKey)) {
            $headers['Authorization'] = 'Bearer '.$this->apiKey;
        }

        return $headers;
    }

    private function buildRequestBody(string $prompt): array
    {
        $body = [
            'messages'    => [['role' => 'user', 'content' => $prompt]],
            'temperature' => $this->temperature,
            'max_tokens'  => $this->maxTokens,
            'stream'      => false,
        ];

        if (! empty($this->model)) {
            $body['model'] = $this->model;
        }

        return $body;
    }

    private function buildPrompt(Translation $translation, int $count): string
    {
        $sourceLang = $translation->sourceLanguage->name;
        $targetLang = $translation->targetLanguage->name;
        $sourceText = $translation->source_text;
        $targetText = $translation->target_text;

        return <<<PROMPT
        Task: Create {$count} incorrect {$targetLang} translations for a vocabulary quiz.

        {$sourceLang} word: {$sourceText}
        Correct {$targetLang}: {$targetText}

        Generate {$count} incorrect {$targetLang} words that a student might confuse with the correct answer.
        Output format: JSON array only, no other text.
        PROMPT;
    }

    private function buildBatchPrompt(Collection $translations, int $count): string
    {
        $first      = $translations->first();
        $sourceLang = $first?->sourceLanguage?->name ?? 'source language';
        $targetLang = $first?->targetLanguage?->name ?? 'target language';

        $wordsList = $translations->map(
            fn (Translation $t) => "- {$sourceLang}: \"{$t->source_text}\" → {$targetLang}: \"{$t->target_text}\" (id: {$t->id})"
        )->implode("\n");

        return <<<PROMPT
        Task: Create {$count} incorrect {$targetLang} translations for each {$sourceLang} word in the vocabulary quiz below.

        Words to generate distractors for:
        {$wordsList}

        For each word, generate {$count} incorrect {$targetLang} words that a student might confuse with the correct answer.

        Output format: JSON object with translation IDs as keys and arrays of distractors as values.
        Example: {"1": ["wrong1", "wrong2", "wrong3"], "2": ["wrong1", "wrong2", "wrong3"]}

        Output JSON only, no other text.
        PROMPT;
    }

    private function extractResponseContent(array $responseJson): ?string
    {
        return $responseJson['choices'][0]['message']['content']
            ?? $responseJson['choices'][0]['message']['reasoning_content']
            ?? null;
    }

    private function parseResponse(array $responseJson, int $expectedCount): array
    {
        $content = $this->extractResponseContent($responseJson) ?? '';

        if (empty($content)) {
            return [];
        }

        if (preg_match('/\[[\s\S]*\]/m', $content, $matches)) {
            $decoded = json_decode($matches[0], true);

            if (is_array($decoded) && count($decoded) > 0) {
                return array_slice(
                    array_map('trim', array_filter($decoded, 'is_string')),
                    0,
                    $expectedCount
                );
            }
        }

        $decoded = json_decode($content, true);

        return is_array($decoded)
            ? array_slice(array_map('trim', $decoded), 0, $expectedCount)
            : [];
    }

    private function parseBatchResponse(array $responseJson, int $expectedCount): array
    {
        $content = $this->extractResponseContent($responseJson) ?? '';

        if (empty($content)) {
            return [];
        }

        if (! preg_match('/\{[\s\S]*\}/m', $content, $matches)) {
            return [];
        }

        $decoded = json_decode($matches[0], true);

        if (! is_array($decoded)) {
            return [];
        }

        $results = [];

        foreach ($decoded as $id => $distractors) {
            if (is_numeric($id) && is_array($distractors)) {
                $results[(int) $id] = array_slice(
                    array_map('trim', array_filter($distractors, 'is_string')),
                    0,
                    $expectedCount
                );
            }
        }

        return $results;
    }

    /**
     * Write a single AiAuditLog record for an API call.
     * Shared fields (provider, model) are merged automatically.
     */
    private function logAudit(array $data): void
    {
        AiAuditLog::create(array_merge([
            'provider' => $this->provider,
            'model'    => $this->model,
        ], $data));
    }
}
