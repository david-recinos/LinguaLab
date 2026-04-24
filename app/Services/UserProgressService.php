<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class UserProgressService
{
    /**
     * Get all translations due for review, ordered by urgency, with relationships eager-loaded.
     */
    public function getTranslationsDueForReview(User $user): Collection
    {
        return $user->translations()
            ->dueForReview()
            ->with(['sourceLanguage', 'targetLanguage', 'wordType'])
            ->orderByRaw('next_review_at IS NULL, next_review_at ASC')
            ->get();
    }

    /**
     * Get the count of translations due for review.
     */
    public function getDueForReviewCount(User $user): int
    {
        return $user->translations()->dueForReview()->count();
    }

    /**
     * Get the total number of translations for the user's active source language.
     */
    public function getTotalTranslationsForActiveSource(User $user): int
    {
        $activeSource = $user->activeSourceLanguage();

        if (! $activeSource) {
            return 0;
        }

        return $user->translations()
            ->where('source_language_id', $activeSource->language_id)
            ->count();
    }

    /**
     * Get translations prioritised for practice (worst mastery first), with relationships eager-loaded.
     */
    public function getTranslationsForPractice(User $user, int $limit = 10): Collection
    {
        $activeSource = $user->activeSourceLanguage();

        if (! $activeSource) {
            return new Collection;
        }

        return $user->translations()
            ->where('source_language_id', $activeSource->language_id)
            ->with(['sourceLanguage', 'targetLanguage', 'wordType'])
            ->orderByRaw('
                CASE
                    WHEN total_reviews = 0 THEN 1
                    WHEN total_reviews < 3 THEN 2
                    WHEN successful_reviews < 5 OR ease_factor < 2.0 THEN 3
                    WHEN interval_days < 21 THEN 4
                    ELSE 5
                END ASC,
                ease_factor ASC,
                successful_reviews ASC
            ')
            ->limit($limit)
            ->get()
            ->shuffle();
    }
}
