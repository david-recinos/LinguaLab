<?php

declare(strict_types=1);

namespace App\Services\AI;

use App\Models\Translation;
use Illuminate\Support\Collection;

class FallbackDistractorGenerator implements DistractorGeneratorInterface
{
    public function generate(Translation $translation, int $count = 3): array
    {
        $user             = $translation->user;
        $targetLanguageId = $translation->target_language_id;
        $correctAnswer    = $translation->target_text;

        $potentialDistractors = Translation::where('user_id', $user->id)
            ->where('target_language_id', $targetLanguageId)
            ->where('id', '!=', $translation->id)
            ->pluck('target_text')
            ->unique()
            ->filter(fn (string $text) => mb_strtolower(trim($text)) !== mb_strtolower(trim($correctAnswer)))
            ->values();

        if ($potentialDistractors->isEmpty()) {
            return $this->generateGenericDistractors($correctAnswer, $count);
        }

        return $potentialDistractors->shuffle()->take($count)->values()->toArray();
    }

    public function generateBatch(Collection $translations, int $count = 3): array
    {
        $results = [];

        foreach ($translations as $translation) {
            $results[$translation->id] = $this->generate($translation, $count);
        }

        return $results;
    }

    public function isAvailable(): bool
    {
        return true;
    }

    /**
     * Generate generic distractors when the user has no other translations to pull from.
     * All string operations use mb_* functions to support multibyte characters (é, ñ, ü, etc.).
     */
    private function generateGenericDistractors(string $correctAnswer, int $count): array
    {
        $distractors = [];
        $chars       = mb_str_split($correctAnswer);
        $length      = count($chars);

        if ($length > 1) {
            $prefixes      = ['un', 're', 'pre', 'dis', 'mis'];
            $distractors[] = $prefixes[array_rand($prefixes)].$correctAnswer;
        }

        if ($length > 1 && count($distractors) < $count) {
            $suffixes      = ['ing', 'ed', 'ly', 'ness', 'tion'];
            $distractors[] = $correctAnswer.$suffixes[array_rand($suffixes)];
        }

        if ($length > 2 && count($distractors) < $count) {
            $alphabet      = array_merge(range('a', 'z'), ['á', 'é', 'í', 'ó', 'ú', 'ñ', 'ü']);
            $pos           = random_int(1, $length - 1);
            $mutated       = $chars;
            $mutated[$pos] = $alphabet[array_rand($alphabet)];
            $distractors[] = implode('', $mutated);
        }

        if ($length > 3 && count($distractors) < $count) {
            $reversed = implode('', array_reverse($chars));
            if ($reversed !== $correctAnswer) {
                $distractors[] = $reversed;
            }
        }

        if ($length > 1 && count($distractors) < $count) {
            $pos           = random_int(0, $length - 1);
            $duplicated    = $chars;
            array_splice($duplicated, $pos, 0, [$chars[$pos]]);
            $distractors[] = implode('', $duplicated);
        }

        $n = 1;
        while (count($distractors) < $count) {
            $distractors[] = "(other) {$n}";
            $n++;
        }

        return array_slice($distractors, 0, $count);
    }
}
