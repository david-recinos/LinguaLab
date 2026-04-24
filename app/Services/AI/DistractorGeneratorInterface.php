<?php

declare(strict_types=1);

namespace App\Services\AI;

use App\Models\Translation;
use Illuminate\Support\Collection;

interface DistractorGeneratorInterface
{
    /**
     * Generate distractor options for a single multiple-choice question.
     *
     * @param  Translation  $translation  The translation to generate distractors for
     * @param  int  $count  Number of distractors to generate
     * @return array<string>
     */
    public function generate(Translation $translation, int $count = 3): array;

    /**
     * Generate distractors for multiple translations in a single call.
     * Implementations that do not support batch calls may loop internally.
     *
     * @param  Collection<int, Translation>  $translations
     * @return array<int, array<string>>  Map of translation ID → distractor strings
     */
    public function generateBatch(Collection $translations, int $count = 3): array;

    /**
     * Check if the generator is available and properly configured.
     */
    public function isAvailable(): bool;
}
