<?php

use App\Enums\PracticeDirection;
use App\Enums\PracticeInputMethod;
use App\Models\Language;
use App\Models\PracticeAttempt;
use App\Models\Translation;
use App\Models\User;
use App\Models\UserSourceLanguage;
use App\Models\UserTargetLanguage;
use App\Models\WordType;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Role::create(['name' => 'admin']);
    Role::create(['name' => 'user']);

    Language::create(['code' => 'en', 'name' => 'English', 'native_name' => 'English']);
    Language::create(['code' => 'es', 'name' => 'Spanish', 'native_name' => 'Español']);

    WordType::create(['name' => 'Verb']);
    WordType::create(['name' => 'Noun']);
});

/**
 * @return array{user: User, translation: Translation, source: Language, target: Language}
 */
function createTranslationWithUser(): array
{
    $user = User::factory()->create();
    $user->assignRole('user');

    $english = Language::where('code', 'en')->first();
    $spanish = Language::where('code', 'es')->first();

    UserSourceLanguage::create([
        'user_id' => $user->id,
        'language_id' => $english->id,
        'is_active' => true,
    ]);

    UserTargetLanguage::create([
        'user_id' => $user->id,
        'source_language_id' => $english->id,
        'target_language_id' => $spanish->id,
    ]);

    $translation = Translation::create([
        'user_id' => $user->id,
        'source_language_id' => $english->id,
        'target_language_id' => $spanish->id,
        'type' => 'word',
        'word_type_id' => WordType::first()->id,
        'source_text' => 'hello',
        'target_text' => 'hola',
        'next_review_at' => now(),
    ]);

    // Refresh to get database defaults for SRS fields
    $translation->refresh();

    return ['user' => $user, 'translation' => $translation, 'source' => $english, 'target' => $spanish];
}

// PracticeAttempt Model Tests

test('practice attempt can be created', function () {
    ['user' => $user, 'translation' => $translation] = createTranslationWithUser();

    $attempt = PracticeAttempt::create([
        'user_id' => $user->id,
        'translation_id' => $translation->id,
        'direction' => PracticeDirection::SOURCE_TO_TARGET,
        'input_method' => PracticeInputMethod::TYPING,
        'is_correct' => true,
        'time_spent_seconds' => 5,
    ]);

    expect($attempt->user_id)->toBe($user->id);
    expect($attempt->translation_id)->toBe($translation->id);
    expect($attempt->direction)->toBe(PracticeDirection::SOURCE_TO_TARGET);
    expect($attempt->input_method)->toBe(PracticeInputMethod::TYPING);
    expect($attempt->is_correct)->toBeTrue();
    expect($attempt->time_spent_seconds)->toBe(5);
});

test('practice attempt belongs to user', function () {
    ['user' => $user, 'translation' => $translation] = createTranslationWithUser();

    $attempt = PracticeAttempt::create([
        'user_id' => $user->id,
        'translation_id' => $translation->id,
        'direction' => PracticeDirection::SOURCE_TO_TARGET,
        'input_method' => PracticeInputMethod::TYPING,
        'is_correct' => true,
    ]);

    expect($attempt->user->id)->toBe($user->id);
});

test('practice attempt belongs to translation', function () {
    ['user' => $user, 'translation' => $translation] = createTranslationWithUser();

    $attempt = PracticeAttempt::create([
        'user_id' => $user->id,
        'translation_id' => $translation->id,
        'direction' => PracticeDirection::SOURCE_TO_TARGET,
        'input_method' => PracticeInputMethod::TYPING,
        'is_correct' => true,
    ]);

    expect($attempt->translation->id)->toBe($translation->id);
});

// Translation SRS Fields Tests

test('translation has default srs values', function () {
    ['translation' => $translation] = createTranslationWithUser();

    expect((float) $translation->ease_factor)->toBe(2.50);
    expect($translation->interval_days)->toBe(1);
    expect($translation->total_reviews)->toBe(0);
    expect($translation->successful_reviews)->toBe(0);
});

test('translation has many practice attempts', function () {
    ['user' => $user, 'translation' => $translation] = createTranslationWithUser();

    PracticeAttempt::create([
        'user_id' => $user->id,
        'translation_id' => $translation->id,
        'direction' => PracticeDirection::SOURCE_TO_TARGET,
        'input_method' => PracticeInputMethod::TYPING,
        'is_correct' => true,
    ]);

    PracticeAttempt::create([
        'user_id' => $user->id,
        'translation_id' => $translation->id,
        'direction' => PracticeDirection::TARGET_TO_SOURCE,
        'input_method' => PracticeInputMethod::MULTIPLE_CHOICE,
        'is_correct' => false,
    ]);

    expect($translation->practiceAttempts)->toHaveCount(2);
});

// isDueForReview Tests

test('translation with null next_review_at is due', function () {
    ['translation' => $translation] = createTranslationWithUser();
    $translation->update(['next_review_at' => null]);

    expect($translation->isDueForReview())->toBeTrue();
});

test('translation with past next_review_at is due', function () {
    ['translation' => $translation] = createTranslationWithUser();
    $translation->update(['next_review_at' => now()->subDay()]);

    expect($translation->isDueForReview())->toBeTrue();
});

test('translation with future next_review_at is not due', function () {
    ['translation' => $translation] = createTranslationWithUser();
    $translation->update(['next_review_at' => now()->addDay()]);

    expect($translation->isDueForReview())->toBeFalse();
});

// recordPracticeAttempt Tests

test('record practice attempt creates practice attempt', function () {
    ['user' => $user, 'translation' => $translation] = createTranslationWithUser();

    $attempt = $translation->recordPracticeAttempt(
        correct: true,
        direction: PracticeDirection::SOURCE_TO_TARGET,
        inputMethod: PracticeInputMethod::TYPING,
        timeSpent: 10
    );

    expect($attempt->user_id)->toBe($user->id);
    expect($attempt->translation_id)->toBe($translation->id);
    expect($attempt->is_correct)->toBeTrue();
    expect($attempt->direction)->toBe(PracticeDirection::SOURCE_TO_TARGET);
    expect($attempt->input_method)->toBe(PracticeInputMethod::TYPING);
    expect($attempt->time_spent_seconds)->toBe(10);
});

test('record practice attempt increments total reviews', function () {
    ['translation' => $translation] = createTranslationWithUser();

    expect($translation->total_reviews)->toBe(0);

    $translation->recordPracticeAttempt(true, PracticeDirection::SOURCE_TO_TARGET, PracticeInputMethod::TYPING);

    expect($translation->fresh()->total_reviews)->toBe(1);

    $translation->recordPracticeAttempt(false, PracticeDirection::SOURCE_TO_TARGET, PracticeInputMethod::TYPING);

    expect($translation->fresh()->total_reviews)->toBe(2);
});

test('record practice attempt increments successful reviews only on correct', function () {
    ['translation' => $translation] = createTranslationWithUser();

    expect($translation->successful_reviews)->toBe(0);

    $translation->recordPracticeAttempt(true, PracticeDirection::SOURCE_TO_TARGET, PracticeInputMethod::TYPING);
    expect($translation->fresh()->successful_reviews)->toBe(1);

    $translation->recordPracticeAttempt(false, PracticeDirection::SOURCE_TO_TARGET, PracticeInputMethod::TYPING);
    expect($translation->fresh()->successful_reviews)->toBe(1);

    $translation->recordPracticeAttempt(true, PracticeDirection::SOURCE_TO_TARGET, PracticeInputMethod::TYPING);
    expect($translation->fresh()->successful_reviews)->toBe(2);
});

test('record practice attempt updates last reviewed at', function () {
    ['translation' => $translation] = createTranslationWithUser();

    expect($translation->last_reviewed_at)->toBeNull();

    $translation->recordPracticeAttempt(true, PracticeDirection::SOURCE_TO_TARGET, PracticeInputMethod::TYPING);

    expect($translation->fresh()->last_reviewed_at)->not->toBeNull();
});

// SM-2 Algorithm Tests

test('correct answer increases interval based on ease factor', function () {
    ['translation' => $translation] = createTranslationWithUser();

    $initialEaseFactor = (float) $translation->ease_factor; // 2.50
    $initialInterval = $translation->interval_days; // 1

    $translation->recordPracticeAttempt(true, PracticeDirection::SOURCE_TO_TARGET, PracticeInputMethod::TYPING);
    $translation->refresh();

    // New interval should be ceil(1 * 2.50) = 3
    expect($translation->interval_days)->toBe(3);
    expect($translation->next_review_at->isToday() || $translation->next_review_at->isFuture())->toBeTrue();
});

test('wrong answer resets interval to one day', function () {
    ['translation' => $translation] = createTranslationWithUser();

    // First, get some progress
    $translation->update(['interval_days' => 10]);

    $translation->recordPracticeAttempt(false, PracticeDirection::SOURCE_TO_TARGET, PracticeInputMethod::TYPING);
    $translation->refresh();

    expect($translation->interval_days)->toBe(1);
});

test('wrong answer decreases ease factor', function () {
    ['translation' => $translation] = createTranslationWithUser();

    $initialEaseFactor = (float) $translation->ease_factor; // 2.50

    $translation->recordPracticeAttempt(false, PracticeDirection::SOURCE_TO_TARGET, PracticeInputMethod::TYPING);
    $translation->refresh();

    // Ease factor should decrease but not below 1.3
    expect((float) $translation->ease_factor)->toBeLessThan($initialEaseFactor);
    expect((float) $translation->ease_factor)->toBeGreaterThanOrEqual(1.3);
});

test('ease factor has minimum of 1.3', function () {
    ['translation' => $translation] = createTranslationWithUser();

    // Simulate many wrong answers
    for ($i = 0; $i < 10; $i++) {
        $translation->recordPracticeAttempt(false, PracticeDirection::SOURCE_TO_TARGET, PracticeInputMethod::TYPING);
        $translation->refresh();
    }

    expect($translation->ease_factor)->toBeGreaterThanOrEqual(1.3);
});

// Mastery Level Tests

test('translation with no reviews is new', function () {
    ['translation' => $translation] = createTranslationWithUser();

    expect($translation->getMasteryLevel())->toBe('new');
});

test('translation with 1-2 reviews is learning', function () {
    ['translation' => $translation] = createTranslationWithUser();

    $translation->update(['total_reviews' => 1]);
    expect($translation->getMasteryLevel())->toBe('learning');

    $translation->update(['total_reviews' => 2]);
    expect($translation->getMasteryLevel())->toBe('learning');
});

test('translation with few successful reviews or low ease factor is recognized', function () {
    ['translation' => $translation] = createTranslationWithUser();

    $translation->update([
        'total_reviews' => 5,
        'successful_reviews' => 3, // Less than 5
        'ease_factor' => 2.5,
    ]);
    expect($translation->getMasteryLevel())->toBe('recognized');

    $translation->update([
        'total_reviews' => 5,
        'successful_reviews' => 5,
        'ease_factor' => 1.8, // Below 2.0
    ]);
    expect($translation->getMasteryLevel())->toBe('recognized');
});

test('translation with interval less than 21 days is known', function () {
    ['translation' => $translation] = createTranslationWithUser();

    $translation->update([
        'total_reviews' => 10,
        'successful_reviews' => 8,
        'ease_factor' => 2.5,
        'interval_days' => 14,
    ]);

    expect($translation->getMasteryLevel())->toBe('known');
});

test('translation with interval 21 or more days is mastered', function () {
    ['translation' => $translation] = createTranslationWithUser();

    $translation->update([
        'total_reviews' => 15,
        'successful_reviews' => 14,
        'ease_factor' => 2.5,
        'interval_days' => 21,
    ]);

    expect($translation->getMasteryLevel())->toBe('mastered');
});

// User translationsDueForReview Tests

test('user can get translations due for review', function () {
    ['user' => $user, 'translation' => $translation] = createTranslationWithUser();

    // Create another translation not due
    $translation2 = Translation::create([
        'user_id' => $user->id,
        'source_language_id' => $translation->source_language_id,
        'target_language_id' => $translation->target_language_id,
        'type' => 'word',
        'word_type_id' => WordType::first()->id,
        'source_text' => 'goodbye',
        'target_text' => 'adiós',
        'next_review_at' => now()->addDays(10),
    ]);

    $dueTranslations = $user->translationsDueForReview()->get();

    expect($dueTranslations)->toHaveCount(1);
    expect($dueTranslations->first()->id)->toBe($translation->id);
});

test('user can get translations with null next_review_at', function () {
    ['user' => $user, 'translation' => $translation] = createTranslationWithUser();
    $translation->update(['next_review_at' => null]);

    $dueTranslations = $user->translationsDueForReview()->get();

    expect($dueTranslations)->toHaveCount(1);
});

test('user has practice attempts relationship', function () {
    ['user' => $user, 'translation' => $translation] = createTranslationWithUser();

    PracticeAttempt::create([
        'user_id' => $user->id,
        'translation_id' => $translation->id,
        'direction' => PracticeDirection::SOURCE_TO_TARGET,
        'input_method' => PracticeInputMethod::TYPING,
        'is_correct' => true,
    ]);

    expect($user->practiceAttempts)->toHaveCount(1);
});

// Practice attempts are deleted when translation is deleted

test('practice attempts are deleted when translation is deleted', function () {
    ['user' => $user, 'translation' => $translation] = createTranslationWithUser();

    $attempt = PracticeAttempt::create([
        'user_id' => $user->id,
        'translation_id' => $translation->id,
        'direction' => PracticeDirection::SOURCE_TO_TARGET,
        'input_method' => PracticeInputMethod::TYPING,
        'is_correct' => true,
    ]);

    $translation->delete();

    expect(PracticeAttempt::find($attempt->id))->toBeNull();
});

// Practice attempts are deleted when user is deleted

test('practice attempts are deleted when user is deleted', function () {
    ['user' => $user, 'translation' => $translation] = createTranslationWithUser();

    $attempt = PracticeAttempt::create([
        'user_id' => $user->id,
        'translation_id' => $translation->id,
        'direction' => PracticeDirection::SOURCE_TO_TARGET,
        'input_method' => PracticeInputMethod::TYPING,
        'is_correct' => true,
    ]);

    $user->delete();

    expect(PracticeAttempt::find($attempt->id))->toBeNull();
});
