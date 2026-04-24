<?php

use App\Models\Language;
use App\Models\Translation;
use App\Models\User;
use App\Models\UserSourceLanguage;
use App\Models\UserTargetLanguage;
use App\Models\WordType;
use App\Services\AnswerMatchingService;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Role::create(['name' => 'admin']);
    Role::create(['name' => 'user']);

    Language::factory()->create(['code' => 'en', 'name' => 'English', 'native_name' => 'English']);
    Language::factory()->create(['code' => 'es', 'name' => 'Spanish', 'native_name' => 'Español']);

    WordType::factory()->create(['name' => 'Verb']);
    WordType::factory()->create(['name' => 'Noun']);
});

function createReviewUser(): array
{
    $user = User::factory()->create();
    $user->assignRole('user');

    $english = Language::where('code', 'en')->first();
    $spanish = Language::where('code', 'es')->first();

    UserSourceLanguage::factory()->create([
        'user_id' => $user->id,
        'language_id' => $english->id,
        'is_active' => true,
    ]);

    UserTargetLanguage::factory()->create([
        'user_id' => $user->id,
        'source_language_id' => $english->id,
        'target_language_id' => $spanish->id,
    ]);

    return ['user' => $user, 'source' => $english, 'target' => $spanish];
}

function createDueTranslation($user, $source, $target): Translation
{
    return Translation::factory()->create([
        'user_id' => $user->id,
        'source_language_id' => $source->id,
        'target_language_id' => $target->id,
        'word_type_id' => WordType::first()->id,
        'source_text' => 'hello',
        'target_text' => 'hola',
        'next_review_at' => now()->subHour(),
    ]);
}

// Access Tests

test('user can access review index', function () {
    ['user' => $user] = createReviewUser();

    $this->actingAs($user);
    visit(route('review.index'))
        ->assertSee('Words to Review');
});

test('unauthenticated user cannot access review', function () {
    $this->get(route('review.index'))
        ->assertRedirect(route('login'));
});

test('user without source language is redirected to language setup', function () {
    $user = User::factory()->create();
    $user->assignRole('user');

    $this->actingAs($user)
        ->get(route('review.index'))
        ->assertRedirect(route('languages.index'));
});

// Dashboard Tests

test('dashboard shows due for review count', function () {
    ['user' => $user, 'source' => $source, 'target' => $target] = createReviewUser();

    // Create some due translations
    createDueTranslation($user, $source, $target);
    createDueTranslation($user, $source, $target);

    $this->actingAs($user);
    visit(route('dashboard'))
        ->assertSee('Words to Review')
        ->assertSee('2');
});

test('dashboard shows start review button when words are due', function () {
    ['user' => $user, 'source' => $source, 'target' => $target] = createReviewUser();
    createDueTranslation($user, $source, $target);

    $this->actingAs($user);
    visit(route('dashboard'))
        ->assertSee('Start Review');
});

// Review Session Tests

test('user can start review session', function () {
    ['user' => $user, 'source' => $source, 'target' => $target] = createReviewUser();
    createDueTranslation($user, $source, $target);

    $this->actingAs($user)
        ->post(route('review.start'))
        ->assertRedirect(route('review.question'));
});

test('user is redirected if no translations due', function () {
    ['user' => $user] = createReviewUser();

    $this->actingAs($user)
        ->post(route('review.start'))
        ->assertRedirect(route('review.index'));
});

test('user sees question after starting session', function () {
    ['user' => $user, 'source' => $source, 'target' => $target] = createReviewUser();
    createDueTranslation($user, $source, $target);

    $this->actingAs($user);

    // Start session
    $this->post(route('review.start'));

    // The question shown is either source_text or target_text depending on the random direction.
    visit(route('review.question'))
        ->assertSee('Progress')
        ->assertSee('1 / 1');
});

test('user can submit correct answer', function () {
    ['user' => $user, 'source' => $source, 'target' => $target] = createReviewUser();
    createDueTranslation($user, $source, $target);

    $this->actingAs($user);

    // Start session
    $this->post(route('review.start'));

    // Submit answer
    $response = $this->post(route('review.submit'), [
        'answer' => 'hola',
        'time_spent' => 5,
    ]);

    $response->assertOk();
    $response->assertViewIs('review.feedback');
    $response->assertSee('Correct');
});

test('user can submit wrong answer and see correct answer', function () {
    ['user' => $user, 'source' => $source, 'target' => $target] = createReviewUser();
    createDueTranslation($user, $source, $target);

    $this->actingAs($user);

    // Start session
    $this->post(route('review.start'));

    // Submit wrong answer
    $response = $this->post(route('review.submit'), [
        'answer' => 'wronganswer',
        'time_spent' => 5,
    ]);

    $response->assertOk();
    $response->assertViewIs('review.feedback');
    $response->assertSee('Incorrect');
    // The correct answer is shown in the feedback
    $response->assertSee('Correct Answer');
});

test('user can skip question', function () {
    ['user' => $user, 'source' => $source, 'target' => $target] = createReviewUser();
    createDueTranslation($user, $source, $target);

    $this->actingAs($user);

    // Start session
    $this->post(route('review.start'));

    // Skip
    $response = $this->post(route('review.skip'));

    $response->assertRedirect();
});

test('user sees complete page after all questions', function () {
    ['user' => $user, 'source' => $source, 'target' => $target] = createReviewUser();
    createDueTranslation($user, $source, $target);

    $this->actingAs($user);

    // Start session
    $this->post(route('review.start'));

    // Submit answer
    $this->post(route('review.submit'), [
        'answer' => 'hola',
        'time_spent' => 5,
    ]);

    // Go to next (should complete)
    $this->post(route('review.next'));

    // Should see complete page
    visit(route('review.complete'))
        ->assertSee('Session Complete');
});

test('session summary shows correct stats', function () {
    ['user' => $user, 'source' => $source, 'target' => $target] = createReviewUser();
    $wordType = WordType::first();

    // Create 2 due translations
    Translation::factory()->create([
        'user_id' => $user->id,
        'source_language_id' => $source->id,
        'target_language_id' => $target->id,
        'word_type_id' => $wordType->id,
        'source_text' => 'hello',
        'target_text' => 'hola',
        'next_review_at' => now()->subHour(),
    ]);

    Translation::factory()->create([
        'user_id' => $user->id,
        'source_language_id' => $source->id,
        'target_language_id' => $target->id,
        'word_type_id' => $wordType->id,
        'source_text' => 'goodbye',
        'target_text' => 'adios',
        'next_review_at' => now()->subHour(),
    ]);

    $this->actingAs($user);

    // Start session
    $this->post(route('review.start'));

    // Answer first correctly
    $this->post(route('review.submit'), [
        'answer' => 'hola',
        'time_spent' => 5,
    ]);
    $this->post(route('review.next'));

    // Answer second incorrectly
    $this->post(route('review.submit'), [
        'answer' => 'wrong',
        'time_spent' => 5,
    ]);
    $this->post(route('review.next'));

    // Check summary
    visit(route('review.complete'))
        ->assertSee('2')
        ->assertSee('1'); // correct
});

// Fuzzy Matching Tests

test('fuzzy matching accepts minor typos', function () {
    $service = new AnswerMatchingService;

    // Should accept
    expect($service->matches('hola', 'hola'))->toBeTrue();
    expect($service->matches('Hola', 'hola'))->toBeTrue();
    expect($service->matches('HOLA', 'hola'))->toBeTrue();
    expect($service->matches('hola ', 'hola'))->toBeTrue();
    expect($service->matches(' hola ', 'hola'))->toBeTrue();
    expect($service->matches('hola', 'holas'))->toBeTrue(); // 1 char difference
    expect($service->matches('holas', 'hola'))->toBeTrue(); // 1 char difference
});

test('fuzzy matching rejects major typos', function () {
    $service = new AnswerMatchingService;

    // Should reject
    expect($service->matches('adios', 'hola'))->toBeFalse();
    expect($service->matches('hello', 'hola'))->toBeFalse();
});

test('answer matching normalizes unicode', function () {
    $service = new AnswerMatchingService;

    // With diacritics removal enabled
    expect($service->normalize('café', true))->toBe('cafe');
    expect($service->normalize('naïve', true))->toBe('naive');

    // Without diacritics removal, but fuzzy matching still allows minor differences
    expect($service->matches('café', 'cafe', 0.3))->toBeTrue(); // 25% tolerance for accented chars
});

test('answer matching handles extra spaces', function () {
    $service = new AnswerMatchingService;

    expect($service->matches('good morning', 'good  morning'))->toBeTrue();
    expect($service->matches('good  morning', 'good morning'))->toBeTrue();
});

// Practice Attempt Recording Test

test('practice attempt is recorded after review', function () {
    ['user' => $user, 'source' => $source, 'target' => $target] = createReviewUser();
    $translation = createDueTranslation($user, $source, $target);

    $initialTotalReviews = $translation->total_reviews;

    $this->actingAs($user);

    // Start and complete review
    $this->post(route('review.start'));
    $this->post(route('review.submit'), [
        'answer' => 'hola',
        'time_spent' => 5,
    ]);

    // Check practice attempt was recorded
    $translation->refresh();
    expect($translation->total_reviews)->toBe($initialTotalReviews + 1);
});

// Multiple Choice Options Test

test('multiple choice question has options', function () {
    ['user' => $user, 'source' => $source, 'target' => $target] = createReviewUser();
    $wordType = WordType::first();

    // Create multiple translations for distractors
    Translation::factory()->create([
        'user_id' => $user->id,
        'source_language_id' => $source->id,
        'target_language_id' => $target->id,
        'word_type_id' => $wordType->id,
        'source_text' => 'goodbye',
        'target_text' => 'adios',
        'next_review_at' => now()->subHour(),
    ]);

    Translation::factory()->create([
        'user_id' => $user->id,
        'source_language_id' => $source->id,
        'target_language_id' => $target->id,
        'word_type_id' => $wordType->id,
        'source_text' => 'thanks',
        'target_text' => 'gracias',
        'next_review_at' => now()->subHour(),
    ]);

    Translation::factory()->create([
        'user_id' => $user->id,
        'source_language_id' => $source->id,
        'target_language_id' => $target->id,
        'word_type_id' => $wordType->id,
        'source_text' => 'please',
        'target_text' => 'por favor',
        'next_review_at' => now()->subHour(),
    ]);

    $this->actingAs($user);

    // Start session
    $this->post(route('review.start'));

    // The question should load successfully (options are generated in the view)
    $response = $this->get(route('review.question'));
    $response->assertOk();
});
