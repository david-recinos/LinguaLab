<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class UserLanguageService
{
    /**
     * Count the target languages for the user's active source language.
     */
    public function getTargetLanguageCountForActiveSource(User $user): int
    {
        $activeSource = $user->activeSourceLanguage();

        if (! $activeSource) {
            return 0;
        }

        return $user->targetLanguages()
            ->where('source_language_id', $activeSource->language_id)
            ->count();
    }

    /**
     * Get the target languages (with their language model) for the user's active source language.
     */
    public function getTargetLanguagesForActiveSource(User $user): Collection
    {
        $activeSource = $user->activeSourceLanguage();

        if (! $activeSource) {
            return new Collection;
        }

        return $user->targetLanguages()
            ->where('source_language_id', $activeSource->language_id)
            ->with('targetLanguage')
            ->get();
    }

    /**
     * Get the target languages for a specific source language ID.
     */
    public function getTargetLanguagesForSource(User $user, int $sourceLanguageId): Collection
    {
        return $user->targetLanguages()
            ->where('source_language_id', $sourceLanguageId)
            ->with('targetLanguage')
            ->get();
    }

    /**
     * Check whether the user owns the given target language under the given source language.
     */
    public function ownsTargetLanguage(User $user, int $sourceLanguageId, int $targetLanguageId): bool
    {
        return $user->targetLanguages()
            ->where('source_language_id', $sourceLanguageId)
            ->where('target_language_id', $targetLanguageId)
            ->exists();
    }

    /**
     * Check whether the user owns the given target language under their active source language.
     */
    public function ownsTargetLanguageForActiveSource(User $user, int $targetLanguageId): bool
    {
        $activeSource = $user->activeSourceLanguage();

        if (! $activeSource) {
            return false;
        }

        return $this->ownsTargetLanguage($user, $activeSource->language_id, $targetLanguageId);
    }
}
