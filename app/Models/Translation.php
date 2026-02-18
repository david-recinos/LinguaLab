<?php

namespace App\Models;

use App\Enums\PracticeDirection;
use App\Enums\PracticeInputMethod;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class Translation extends Model
{
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

    protected $casts = [
        'next_review_at' => 'datetime',
        'last_reviewed_at' => 'datetime',
        'ease_factor' => 'decimal:2',
        'interval_days' => 'integer',
        'total_reviews' => 'integer',
        'successful_reviews' => 'integer',
    ];

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

    /**
     * Check if this translation is due for review.
     */
    public function isDueForReview(): bool
    {
        if ($this->next_review_at === null) {
            return true;
        }

        return $this->next_review_at->isPast();
    }

    /**
     * Record a practice attempt and update SRS fields.
     * Uses transaction to ensure data integrity and atomic increments for race condition safety.
     *
     * @param  int  $quality  Quality rating 0-5 (0=complete failure, 5=perfect). Defaults to 4 for correct, 1 for incorrect.
     */
    public function recordPracticeAttempt(
        bool $correct,
        PracticeDirection $direction,
        PracticeInputMethod $inputMethod,
        ?int $timeSpent = null,
        ?int $quality = null
    ): PracticeAttempt {
        // Default quality based on correctness if not provided
        $quality = $quality ?? ($correct ? 4 : 1);

        return DB::transaction(function () use ($correct, $direction, $inputMethod, $timeSpent, $quality) {
            $attempt = PracticeAttempt::create([
                'user_id' => $this->user_id,
                'translation_id' => $this->id,
                'direction' => $direction,
                'input_method' => $inputMethod,
                'is_correct' => $correct,
                'time_spent_seconds' => $timeSpent,
            ]);

            // Use atomic increments to prevent race conditions
            $this->increment('total_reviews');
            if ($correct) {
                $this->increment('successful_reviews');
            }

            // Refresh model to get updated values
            $this->refresh();

            $this->calculateNextReview($quality);
            $this->last_reviewed_at = now();
            $this->save();

            return $attempt;
        });
    }

    /**
     * Calculate the next review date using SM-2 algorithm.
     *
     * @param  int  $quality  Quality rating 0-5 (0=complete failure, 5=perfect)
     */
    public function calculateNextReview(int $quality): void
    {
        if ($quality >= 3) {
            // Store the old ease factor before updating
            $oldEaseFactor = $this->ease_factor;

            // Calculate new interval using OLD ease factor (SM-2 spec: I(n) = I(n-1) × EF_old)
            $this->interval_days = (int) ceil($this->interval_days * $oldEaseFactor);

            // Calculate new ease factor
            // EF' = EF + (0.1 - (5-q) * (0.08 + (5-q) * 0.02))
            $newEaseFactor = $this->ease_factor + (0.1 - (5 - $quality) * (0.08 + (5 - $quality) * 0.02));

            // Ease factor must be between 1.3 and 9.99 (column decimal(3,2) limit)
            $this->ease_factor = min(9.99, max(1.3, $newEaseFactor));
        } else {
            // Reset on incorrect answer (quality < 3)
            $this->interval_days = 1;
            $this->ease_factor = max(1.3, $this->ease_factor - 0.2);
        }

        // Set next review date
        $this->next_review_at = now()->addDays($this->interval_days);
    }

    /**
     * Get the mastery level based on SRS data.
     * Levels: new, learning, recognized, known, mastered
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
}
