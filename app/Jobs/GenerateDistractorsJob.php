<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Translation;
use App\Services\AI\DistractorGeneratorInterface;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class GenerateDistractorsJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public array $backoff = [10, 30, 60];

    /**
     * @param  Collection<int, Translation>  $translations
     */
    public function __construct(public readonly Collection $translations) {}

    public function handle(DistractorGeneratorInterface $generator): void
    {
        if ($this->translations->isEmpty()) {
            return;
        }

        $needed = $this->translations->filter(
            fn (Translation $t) => ! $t->hasDistractors()
        );

        if ($needed->isEmpty()) {
            return;
        }

        $results = $generator->generateBatch($needed, 3);

        foreach ($results as $translationId => $distractors) {
            $translation = $needed->firstWhere('id', $translationId);

            if ($translation && ! empty($distractors)) {
                $translation->replaceDistractors($distractors, 'ai');
            }
        }

        Log::info('Generated distractors for translations', [
            'count'           => count($results),
            'translation_ids' => array_keys($results),
        ]);
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('GenerateDistractorsJob failed', [
            'translation_ids' => $this->translations->pluck('id')->toArray(),
            'error'           => $exception->getMessage(),
        ]);
    }
}
