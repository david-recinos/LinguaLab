<?php

use App\Models\Language;
use App\Models\Translation;
use App\Models\User;
use App\Models\UserSourceLanguage;
use App\Models\UserTargetLanguage;
use App\Models\WordType;
use App\Services\AI\FallbackDistractorGenerator;
use App\Services\AnswerMatchingService;
use App\Services\DistractorService;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Role::create(['name' => 'admin']);
    Role::create(['name' => 'user']);

    Language::factory()->create(['code' => 'en', 'name' => 'English', 'native_name' => 'English']);
    Language::factory()->create(['code' => 'es', 'name' => 'Spanish', 'native_name' => 'Español']);

    WordType::factory()->create(['name' => 'Verb']);
});

function createDistractorUser(): array
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

// AnswerMatchingService Tests

test('exact match returns true', function () {
    $service = new AnswerMatchingService;

    expect($service->matches('hola', 'hola'))->toBeTrue();
});

test('case insensitive match returns true', function () {
    $service = new AnswerMatchingService;

    expect($service->matches('HOLA', 'hola'))->toBeTrue();
    expect($service->matches('Hola', 'HOLA'))->toBeTrue();
});

test('match with extra spaces returns true', function () {
    $service = new AnswerMatchingService;

    expect($service->matches('  hola  ', 'hola'))->toBeTrue();
    expect($service->matches('hola', '  hola  '))->toBeTrue();
});

test('match with one character difference returns true', function () {
    $service = new AnswerMatchingService;

    // Default tolerance is 10%, so for 4-char word, 1 error is allowed
    expect($service->matches('holas', 'hola'))->toBeTrue();
    expect($service->matches('hola', 'holas'))->toBeTrue();
});

test('match with too many differences returns false', function () {
    $service = new AnswerMatchingService;

    expect($service->matches('adios', 'hola'))->toBeFalse();
    expect($service->matches('completely different', 'hola'))->toBeFalse();
});

test('normalize function works correctly', function () {
    $service = new AnswerMatchingService;

    expect($service->normalize('  HOLA  '))->toBe('hola');
    expect($service->normalize('Hello   World'))->toBe('hello world');
});

test('get similarity score returns correct value', function () {
    $service = new AnswerMatchingService;

    expect($service->getSimilarityScore('hola', 'hola'))->toBe(1.0);
    expect($service->getSimilarityScore('hola', 'adios'))->toBeLessThan(0.5);
});

// FallbackDistractorGenerator Tests

test('fallback generator returns distractors from user translations', function () {
    ['user' => $user, 'source' => $source, 'target' => $target] = createDistractorUser();
    $wordType = WordType::first();

    $translation = Translation::factory()->create([
        'user_id' => $user->id,
        'source_language_id' => $source->id,
        'target_language_id' => $target->id,
        'word_type_id' => $wordType->id,
        'source_text' => 'hello',
        'target_text' => 'hola',
    ]);

    Translation::factory()->create([
        'user_id' => $user->id,
        'source_language_id' => $source->id,
        'target_language_id' => $target->id,
        'word_type_id' => $wordType->id,
        'source_text' => 'goodbye',
        'target_text' => 'adios',
    ]);

    Translation::factory()->create([
        'user_id' => $user->id,
        'source_language_id' => $source->id,
        'target_language_id' => $target->id,
        'word_type_id' => $wordType->id,
        'source_text' => 'thanks',
        'target_text' => 'gracias',
    ]);

    Translation::factory()->create([
        'user_id' => $user->id,
        'source_language_id' => $source->id,
        'target_language_id' => $target->id,
        'word_type_id' => $wordType->id,
        'source_text' => 'please',
        'target_text' => 'por favor',
    ]);

    $generator = new FallbackDistractorGenerator;
    $distractors = $generator->generate($translation, 3);

    expect($distractors)->toHaveCount(3);
    expect($distractors)->toContain('adios');
    expect($distractors)->toContain('gracias');
    expect($distractors)->toContain('por favor');
    expect($distractors)->not->toContain('hola'); // Should not include correct answer
});

test('fallback generator handles no other translations', function () {
    ['user' => $user, 'source' => $source, 'target' => $target] = createDistractorUser();

    $translation = Translation::factory()->create([
        'user_id' => $user->id,
        'source_language_id' => $source->id,
        'target_language_id' => $target->id,
        'word_type_id' => WordType::first()->id,
        'source_text' => 'hello',
        'target_text' => 'hola',
    ]);

    $generator = new FallbackDistractorGenerator;
    $distractors = $generator->generate($translation, 3);

    // Should still return 3 distractors (generated ones)
    expect($distractors)->toHaveCount(3);
});

test('fallback generator is always available', function () {
    $generator = new FallbackDistractorGenerator;

    expect($generator->isAvailable())->toBeTrue();
});

// DistractorService Tests (with fallback)

test('distractor service uses fallback when ai is disabled', function () {
    ['user' => $user, 'source' => $source, 'target' => $target] = createDistractorUser();
    $wordType = WordType::first();

    $translation = Translation::factory()->create([
        'user_id' => $user->id,
        'source_language_id' => $source->id,
        'target_language_id' => $target->id,
        'word_type_id' => $wordType->id,
        'source_text' => 'hello',
        'target_text' => 'hola',
    ]);

    Translation::factory()->create([
        'user_id' => $user->id,
        'source_language_id' => $source->id,
        'target_language_id' => $target->id,
        'word_type_id' => $wordType->id,
        'source_text' => 'goodbye',
        'target_text' => 'adios',
    ]);

    Translation::factory()->create([
        'user_id' => $user->id,
        'source_language_id' => $source->id,
        'target_language_id' => $target->id,
        'word_type_id' => $wordType->id,
        'source_text' => 'thanks',
        'target_text' => 'gracias',
    ]);

    Translation::factory()->create([
        'user_id' => $user->id,
        'source_language_id' => $source->id,
        'target_language_id' => $target->id,
        'word_type_id' => $wordType->id,
        'source_text' => 'please',
        'target_text' => 'por favor',
    ]);

    // Set AI disabled
    config(['ai.features.distractors.enabled' => false]);

    $service = app(DistractorService::class);
    $result = $service->getForTranslation($translation, 3);

    expect($result['distractors'])->toHaveCount(3);
    expect($result['distractors'])->not->toContain('hola');
    expect($result['source'])->toBe('fallback');
});
