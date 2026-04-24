<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PracticeDirection;
use App\Enums\PracticeInputMethod;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

/**
 * @mixin IdeHelperTranslation
 */
class Translation extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'source_language_id',
        'target_language_id',
        'type',
        'word_type_id',
        'source_text',
        'target_text',
        'example_sentence',
        'notes',
        'pronunciation',
        'ease_factor',
        'interval_days',
        'next_review_at',
        'last_reviewed_at',
        'total_reviews',
        'successful_reviews',
    ];

    protected function casts(): array
    {
        return [
            'next_review_at'     => 'datetime',
            'last_reviewed_at'   => 'datetime',
            'ease_factor'        => 'decimal:2',
            'interval_days'      => 'integer',
            'total_reviews'      => 'integer',
            'successful_reviews' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function sourceLanguage(): BelongsTo
    {
        return $this->belongsTo(Language::class, 'source_language_id');
    }

    public function targetLanguage(): BelongsTo
    {
        return $this->belongsTo(Language::class, 'target_language_id');
    }

    public function wordType(): BelongsTo
    {
        return $this->belongsTo(WordType::class);
    }

    public function practiceAttempts(): HasMany
    {
        return $this->hasMany(PracticeAttempt::class);
    }

    public function distractors(): HasMany
    {
        return $this->hasMany(Distractor::class);
    }

    /**
     * Scope: translations that are overdue or have never been reviewed.
     */
    public function scopeDueForReview(Builder $query): Builder
    {
        return $query->where(function (Builder $q) {
            $q->whereNull('next_review_at')
                ->orWhere('next_review_at', '<=', now());
        });
    }

    /**
     * Check if this translation is due for review.
     */
    public function isDueForReview(): bool
    {
        return $this->next_review_at === null || $this->next_review_at->isPast();
    }

    /**
     * Record a practice attempt and update SRS fields.
     * Uses a transaction to ensure data integrity and atomic increments.
     *
     * @param  int  $quality  Quality rating 0-5 (0=complete failure, 5=perfect).
     */
    public function recordPracticeAttempt(
        bool $correct,
        PracticeDirection $direction,
        PracticeInputMethod $inputMethod,
        ?int $timeSpent = null,
        ?int $quality = null
    ): PracticeAttempt {
        $quality = $quality ?? ($correct ? 4 : 1);

        return DB::transaction(function () use ($correct, $direction, $inputMethod, $timeSpent, $quality) {
            $attempt = PracticeAttempt::create([
                'user_id'           => $this->user_id,
                'translation_id'    => $this->id,
                'direction'         => $direction,
                'input_method'      => $inputMethod,
                'is_correct'        => $correct,
                'time_spent_seconds' => $timeSpent,
            ]);

            $this->increment('total_reviews');

            if ($correct) {
                $this->increment('successful_reviews');
            }

            $this->refresh();
            $this->calculateNextReview($quality);
            $this->last_reviewed_at = now();
            $this->save();

            return $attempt;
        });
    }

    /**
     * Calculate the next review date using the SM-2 algorithm.
     *
     * @param  int  $quality  Quality rating 0-5 (0=complete failure, 5=perfect)
     */
    public function calculateNextReview(int $quality): void
    {
        if ($quality >= 3) {
            $oldEaseFactor = $this->ease_factor;

            // I(n) = I(n-1) × EF_old  (SM-2 spec uses the OLD ease factor)
            $this->interval_days = (int) ceil($this->interval_days * $oldEaseFactor);

            // EF' = EF + (0.1 - (5-q) × (0.08 + (5-q) × 0.02))
            $newEaseFactor = $this->ease_factor + (0.1 - (5 - $quality) * (0.08 + (5 - $quality) * 0.02));

            $this->ease_factor = min(9.99, max(1.3, $newEaseFactor));
        } else {
            $this->interval_days = 1;
            $this->ease_factor = max(1.3, $this->ease_factor - 0.2);
        }

        $this->next_review_at = now()->addDays($this->interval_days);
    }

    /**
     * Get the mastery level based on SRS data.
     * Levels: new, learning, recognized, known, mastered.
     */
    public function getMasteryLevel(): string
    {
        if ($this->total_reviews === 0) {
            return 'new';
        }

        if ($this->total_reviews < 3) {
            return 'learning';
        }

        if ($this->successful_reviews < 5 || $this->ease_factor < 2.0) {
            return 'recognized';
        }

        if ($this->interval_days < 21) {
            return 'known';
        }

        return 'mastered';
    }

    /**
     * Check if this translation has stored distractors.
     */
    public function hasDistractors(): bool
    {
        return $this->distractors()->exists();
    }

    /**
     * Check if distractors are AI-generated.
     */
    public function areDistractorsAiGenerated(): bool
    {
        return $this->distractors()->ai()->exists();
    }

    /**
     * Get stored distractors as an array of strings.
     */
    public function getStoredDistractors(int $count = 3): array
    {
        return $this->distractors()
            ->limit($count)
            ->pluck('distractor_text')
            ->toArray();
    }

    /**
     * Replace all stored distractors for this translation.
     * Deletes existing ones and bulk-inserts the new set.
     *
     * @param  array<string>  $distractors
     */
    public function replaceDistractors(array $distractors, string $source): void
    {
        $this->distractors()->delete();

        $this->distractors()->createMany(
            array_map(
                fn (string $text) => ['distractor_text' => $text, 'source' => $source],
                $distractors
            )
        );
    }
}
