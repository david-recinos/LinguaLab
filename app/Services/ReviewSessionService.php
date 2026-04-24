<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\PracticeDirection;
use App\Enums\PracticeInputMethod;
use App\Models\Translation;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Session;

class ReviewSessionService
{
    private const SESSION_KEY = 'review_session';

    public function __construct(private readonly AnswerMatchingService $answerMatcher) {}

    /**
     * Start a new review session with the given translations.
     *
     * @param  Collection<int, Translation>  $translations
     */
    public function startSession(Collection $translations): void
    {
        $queue = $translations->map(fn (Translation $t) => $t->id)->toArray();

        Session::put(self::SESSION_KEY, [
            'queue'             => $queue,
            'total'             => count($queue),
            'current_index'     => 0,
            'current_direction' => null,
            'input_method'      => null,
            'results'           => [],
            'started_at'        => now()->timestamp,
        ]);
    }

    public function hasActiveSession(): bool
    {
        $session = $this->getSession();

        return ! empty($session) && ! empty($session['queue']);
    }

    /**
     * Return a zeroed-out summary structure, used when no session data is available.
     */
    public static function emptySummary(): array
    {
        return [
            'total'              => 0,
            'answered'           => 0,
            'correct'            => 0,
            'wrong'              => 0,
            'skipped'            => 0,
            'accuracy'           => 0.0,
            'total_time_seconds' => 0,
            'started_at'         => null,
            'ended_at'           => null,
        ];
    }

    private function getSession(): array
    {
        return Session::get(self::SESSION_KEY, []);
    }

    /**
     * Get the current question data.
     * Returns null when the session is complete or inactive.
     */
    public function getCurrentQuestion(): ?array
    {
        $session = $this->getSession();

        if (empty($session) || $session['current_index'] >= $session['total']) {
            return null;
        }

        $translationId = $session['queue'][$session['current_index']] ?? null;

        if (! $translationId) {
            return null;
        }

        $translation = Translation::with(['sourceLanguage', 'targetLanguage', 'wordType'])->find($translationId);

        if (! $translation) {
            return null;
        }

        if ($session['current_direction'] === null) {
            $session['current_direction'] = $this->getRandomDirection();
            Session::put(self::SESSION_KEY.'.current_direction', $session['current_direction']);
        }

        if ($session['input_method'] === null) {
            $session['input_method'] = $this->getRandomInputMethod();
            Session::put(self::SESSION_KEY.'.input_method', $session['input_method']);
        }

        $direction = PracticeDirection::from($session['current_direction']);
        $inputMethod = PracticeInputMethod::from($session['input_method']);

        $isSourceToTarget = $direction === PracticeDirection::SOURCE_TO_TARGET;

        return [
            'translation'      => $translation,
            'direction'        => $direction,
            'input_method'     => $inputMethod,
            'question'         => $isSourceToTarget ? $translation->source_text : $translation->target_text,
            'correct_answer'   => $isSourceToTarget ? $translation->target_text : $translation->source_text,
            'question_language' => $isSourceToTarget ? $translation->sourceLanguage->name : $translation->targetLanguage->name,
            'answer_language'  => $isSourceToTarget ? $translation->targetLanguage->name : $translation->sourceLanguage->name,
            'progress'         => [
                'current' => $session['current_index'] + 1,
                'total'   => $session['total'],
            ],
        ];
    }

    /**
     * Submit an answer for the current question.
     *
     * @return array  Result data including correctness and correct answer (or 'error' key on failure)
     */
    public function submitAnswer(string $answer, int $timeSpent = 0): array
    {
        $question = $this->getCurrentQuestion();

        if (! $question) {
            return ['error' => 'No active question'];
        }

        $isCorrect = $this->answerMatcher->matches($answer, $question['correct_answer']);
        $translation = $question['translation'];

        $translation->recordPracticeAttempt(
            correct: $isCorrect,
            direction: $question['direction'],
            inputMethod: $question['input_method'],
            timeSpent: $timeSpent
        );

        Session::push(self::SESSION_KEY.'.results', [
            'translation_id' => $translation->id,
            'direction'      => $question['direction']->value,
            'input_method'   => $question['input_method']->value,
            'user_answer'    => $answer,
            'correct_answer' => $question['correct_answer'],
            'is_correct'     => $isCorrect,
            'time_spent'     => $timeSpent,
            'answered_at'    => now()->timestamp,
        ]);

        return [
            'is_correct'     => $isCorrect,
            'correct_answer' => $question['correct_answer'],
            'user_answer'    => $answer,
            'translation'    => $translation,
            'progress'       => $question['progress'],
        ];
    }

    /**
     * Skip the current question without recording a practice attempt.
     *
     * @return array  Result data (or 'error' key on failure)
     */
    public function skipCurrent(): array
    {
        $question = $this->getCurrentQuestion();

        if (! $question) {
            return ['error' => 'No active question'];
        }

        Session::push(self::SESSION_KEY.'.results', [
            'translation_id' => $question['translation']->id,
            'direction'      => $question['direction']->value,
            'input_method'   => $question['input_method']->value,
            'user_answer'    => null,
            'correct_answer' => $question['correct_answer'],
            'is_correct'     => null,
            'time_spent'     => 0,
            'skipped'        => true,
            'answered_at'    => now()->timestamp,
        ]);

        $this->advance();

        return [
            'skipped'        => true,
            'correct_answer' => $question['correct_answer'],
        ];
    }

    /**
     * Advance to the next question.
     * Returns true if more questions remain, false if the session is complete.
     */
    public function advance(): bool
    {
        $session = $this->getSession();

        if (empty($session)) {
            return false;
        }

        $nextIndex = $session['current_index'] + 1;

        Session::put(self::SESSION_KEY.'.current_index', $nextIndex);
        Session::put(self::SESSION_KEY.'.current_direction', null);
        Session::put(self::SESSION_KEY.'.input_method', null);

        return $nextIndex < $session['total'];
    }

    public function getProgress(): array
    {
        $session = $this->getSession();

        if (empty($session)) {
            return ['current' => 0, 'total' => 0, 'results' => [], 'started_at' => null];
        }

        return [
            'current'    => $session['current_index'] + 1,
            'total'      => $session['total'],
            'results'    => $session['results'],
            'started_at' => $session['started_at'] ?? null,
        ];
    }

    /**
     * End the session and return the summary statistics.
     */
    public function endSession(): array
    {
        $session = $this->getSession();

        if (empty($session)) {
            return self::emptySummary();
        }

        $results = $session['results'] ?? [];
        $correct  = count(array_filter($results, fn (array $r) => ($r['is_correct'] ?? null) === true));
        $wrong    = count(array_filter($results, fn (array $r) => ($r['is_correct'] ?? null) === false));
        $skipped  = count(array_filter($results, fn (array $r) => ($r['skipped'] ?? false) === true));

        $total    = $session['total'] ?? 0;
        $answered = $correct + $wrong;
        $accuracy = $answered > 0 ? round(($correct / $answered) * 100, 1) : 0;

        Session::forget(self::SESSION_KEY);

        return [
            'total'              => $total,
            'answered'           => $answered,
            'correct'            => $correct,
            'wrong'              => $wrong,
            'skipped'            => $skipped,
            'accuracy'           => $accuracy,
            'total_time_seconds' => (int) array_sum(array_column($results, 'time_spent')),
            'started_at'         => $session['started_at'] ?? null,
            'ended_at'           => now()->timestamp,
        ];
    }

    private function getRandomDirection(): string
    {
        $directions = [PracticeDirection::SOURCE_TO_TARGET, PracticeDirection::TARGET_TO_SOURCE];

        return $directions[array_rand($directions)]->value;
    }

    private function getRandomInputMethod(): string
    {
        $methods = [PracticeInputMethod::TYPING, PracticeInputMethod::MULTIPLE_CHOICE];

        return $methods[array_rand($methods)]->value;
    }
}
