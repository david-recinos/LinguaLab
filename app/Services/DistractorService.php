<?php

declare(strict_types=1);

namespace App\Services;

use App\Jobs\GenerateDistractorsJob;
use App\Models\Translation;
use App\Services\AI\DistractorGeneratorInterface;
use App\Services\AI\FallbackDistractorGenerator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class DistractorService
{
    private bool $enabled;

    private bool $fallbackOnFailure;

    public function __construct(
        private readonly DistractorGeneratorInterface $aiGenerator,
        private readonly FallbackDistractorGenerator $fallbackGenerator,
    ) {
        $this->enabled = (bool) config('ai.features.distractors.enabled', true);
        $this->fallbackOnFailure = (bool) config('ai.features.distractors.fallback_on_failure', true);
    }

    /**
     * Get distractors for a translation.
     * Returns stored distractors if available, otherwise generates and stores new ones.
     *
     * @return array{distractors: array<string>, source: string}
     */
    public function getForTranslation(Translation $translation, int $count = 3): array
    {
        if ($translation->hasDistractors()) {
            return [
                'distractors' => $translation->getStoredDistractors($count),
                'source'      => $translation->areDistractorsAiGenerated() ? 'ai' : 'fallback',
            ];
        }

        return $this->generateAndStore($translation, $count);
    }

    /**
     * Dispatch a background job to generate distractors for translations that don't have any yet.
     */
    public function generateBatch(Collection $translations): void
    {
        $needed = $this->filterNeedingDistractors($translations);

        if ($needed->isNotEmpty()) {
            GenerateDistractorsJob::dispatch($needed);
        }
    }

    /**
     * Synchronously generate distractors for a review session.
     * Uses a single batch AI call for all translations missing distractors.
     */
    public function generateBatchForSession(Collection $translations): void
    {
        $needed = $this->filterNeedingDistractors($translations);

        if ($needed->isEmpty()) {
            return;
        }

        if (! $this->enabled || ! $this->aiGenerator->isAvailable()) {
            Log::info('AI distractor generator not available, using fallback for session');
            $this->generateFallbackBatch($needed);

            return;
        }

        try {
            $results = $this->aiGenerator->generateBatch($needed, 3);

            if (! empty($results)) {
                foreach ($results as $translationId => $distractors) {
                    $translation = $needed->firstWhere('id', $translationId);
                    if ($translation && ! empty($distractors)) {
                        $translation->replaceDistractors($distractors, 'ai');
                    }
                }

                $missing = $needed->filter(fn (Translation $t) => ! isset($results[$t->id]) || empty($results[$t->id]));

                if ($missing->isNotEmpty() && $this->fallbackOnFailure) {
                    $this->generateFallbackBatch($missing);
                }

                Log::info('Batch distractor generation completed', [
                    'ai_generated' => count($results),
                    'fallback'     => $missing->count(),
                ]);

                return;
            }

            Log::warning('AI batch distractor generator returned empty result');
        } catch (\Exception $e) {
            Log::error('AI batch distractor generation failed', ['message' => $e->getMessage()]);
        }

        if ($this->fallbackOnFailure) {
            $this->generateFallbackBatch($needed);
        }
    }

    /**
     * Check if a translation has AI-generated distractors.
     */
    public function hasAiDistractors(Translation $translation): bool
    {
        return $translation->areDistractorsAiGenerated();
    }

    public function clearDistractors(Translation $translation): void
    {
        $translation->distractors()->delete();
    }

    /**
     * Regenerate distractors (clears existing ones first).
     *
     * @return array{distractors: array<string>, source: string}
     */
    public function regenerate(Translation $translation, int $count = 3): array
    {
        $this->clearDistractors($translation);

        return $this->generateAndStore($translation, $count);
    }

    /**
     * Generate fallback distractors immediately (no AI call).
     * Used as a safety net when pre-generation was missed.
     *
     * @return array<string>
     */
    public function generateFallbackNow(Translation $translation, int $count = 3): array
    {
        $distractors = $this->fallbackGenerator->generate($translation, $count);
        $translation->replaceDistractors($distractors, 'fallback');

        return $distractors;
    }

    /**
     * @return array{distractors: array<string>, source: string}
     */
    private function generateAndStore(Translation $translation, int $count): array
    {
        if (! $this->enabled || ! $this->aiGenerator->isAvailable()) {
            $distractors = $this->fallbackGenerator->generate($translation, $count);
            $translation->replaceDistractors($distractors, 'fallback');

            return ['distractors' => $distractors, 'source' => 'fallback'];
        }

        try {
            $distractors = $this->aiGenerator->generate($translation, $count);

            if (! empty($distractors)) {
                $translation->replaceDistractors($distractors, 'ai');

                return ['distractors' => $distractors, 'source' => 'ai'];
            }

            Log::warning('AI distractor generator returned empty result');
        } catch (\Exception $e) {
            Log::error('AI distractor generation failed', ['message' => $e->getMessage()]);
        }

        if (! $this->fallbackOnFailure) {
            return ['distractors' => [], 'source' => 'none'];
        }

        $distractors = $this->fallbackGenerator->generate($translation, $count);
        $translation->replaceDistractors($distractors, 'fallback');

        return ['distractors' => $distractors, 'source' => 'fallback'];
    }

    /**
     * Filter to only the translations that have no stored distractors yet.
     */
    private function filterNeedingDistractors(Collection $translations): Collection
    {
        return $translations->filter(fn (Translation $t) => ! $t->hasDistractors());
    }

    private function generateFallbackBatch(Collection $translations): void
    {
        foreach ($translations as $translation) {
            $distractors = $this->fallbackGenerator->generate($translation, 3);
            if (! empty($distractors)) {
                $translation->replaceDistractors($distractors, 'fallback');
            }
        }
    }
}
