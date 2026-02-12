<?php

use App\Models\Language;
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
    Language::create(['code' => 'fr', 'name' => 'French', 'native_name' => 'Français']);

    WordType::create(['name' => 'Verb']);
    WordType::create(['name' => 'Noun']);
    WordType::create(['name' => 'Adjective']);
});

function createUserWithLanguages(): array
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

    return ['user' => $user, 'source' => $english, 'target' => $spanish];
}

// Access Tests

test('user without source language is redirected to language setup', function () {
    $user = User::factory()->create();
    $user->assignRole('user');

    $this->actingAs($user)
        ->get(route('translations.index'))
        ->assertRedirect(route('languages.index'));
});

test('unauthenticated user cannot access translations', function () {
    $this->get(route('translations.index'))
        ->assertRedirect(route('login'));
});

test('user can access translations index', function () {
    ['user' => $user] = createUserWithLanguages();

    $this->actingAs($user);
    visit(route('translations.index'))
        ->assertSee('Translations');
});

// Create Translation Tests

test('user can access create translation page', function () {
    ['user' => $user] = createUserWithLanguages();

    $this->actingAs($user);
    visit(route('translations.create'))
        ->assertSee('New Translation');
});

test('user without target languages is redirected to language setup', function () {
    $user = User::factory()->create();
    $user->assignRole('user');
    $english = Language::where('code', 'en')->first();

    UserSourceLanguage::create([
        'user_id' => $user->id,
        'language_id' => $english->id,
        'is_active' => true,
    ]);

    $this->actingAs($user)
        ->get(route('translations.create'))
        ->assertRedirect(route('languages.index'));
});

test('user can create a word translation', function () {
    ['user' => $user, 'target' => $target] = createUserWithLanguages();
    $verb = WordType::where('name', 'Verb')->first();

    $this->actingAs($user);
    visit(route('translations.create'))
        ->select('target_language_id', $target->id)
        ->select('type', 'word')
        ->select('word_type_id', $verb->id)
        ->type('source_text', 'run')
        ->type('target_text', 'correr')
        ->click('Create Translation')
        ->assertSee('Translation created');

    expect(Translation::where('user_id', $user->id)->where('source_text', 'run')->exists())->toBeTrue();
});

test('user can create a text translation', function () {
    ['user' => $user, 'target' => $target] = createUserWithLanguages();

    $this->actingAs($user)
        ->post(route('translations.store'), [
            'target_language_id' => $target->id,
            'type' => 'text',
            'source_text' => 'Hello, how are you?',
            'target_text' => 'Hola, ¿cómo estás?',
        ]);

    $translation = Translation::where('user_id', $user->id)->first();
    expect($translation->type)->toBe('text');
    expect($translation->word_type_id)->toBeNull();
});

test('user can create an expression translation', function () {
    ['user' => $user, 'target' => $target] = createUserWithLanguages();

    $this->actingAs($user)
        ->post(route('translations.store'), [
            'target_language_id' => $target->id,
            'type' => 'expression',
            'source_text' => 'Break a leg',
            'target_text' => 'Mucha mierda',
            'notes' => 'Good luck idiom',
        ]);

    $translation = Translation::where('user_id', $user->id)->first();
    expect($translation->type)->toBe('expression');
    expect($translation->notes)->toBe('Good luck idiom');
});

test('user cannot store translation with unconfigured target language', function () {
    ['user' => $user, 'target' => $target] = createUserWithLanguages();
    $french = Language::where('code', 'fr')->first();

    $response = $this->actingAs($user)
        ->post(route('translations.store'), [
            'target_language_id' => $french->id,
            'type' => 'text',
            'source_text' => 'hello',
            'target_text' => 'bonjour',
        ]);

    $response->assertRedirect(route('translations.create'));
    expect(Translation::where('user_id', $user->id)->count())->toBe(0);
});

test('user cannot update translation with unconfigured target language', function () {
    ['user' => $user, 'source' => $source, 'target' => $target] = createUserWithLanguages();
    $french = Language::where('code', 'fr')->first();

    $translation = Translation::create([
        'user_id' => $user->id,
        'source_language_id' => $source->id,
        'target_language_id' => $target->id,
        'type' => 'text',
        'source_text' => 'hello',
        'target_text' => 'hola',
    ]);

    $response = $this->actingAs($user)
        ->put(route('translations.update', $translation), [
            'target_language_id' => $french->id,
            'type' => 'text',
            'source_text' => 'hello',
            'target_text' => 'bonjour',
        ]);

    $response->assertRedirect(route('translations.edit', $translation));
    expect($translation->fresh()->target_language_id)->toBe($target->id);
});

test('word translation requires word_type_id', function () {
    ['user' => $user, 'target' => $target] = createUserWithLanguages();

    $response = $this->actingAs($user)
        ->post(route('translations.store'), [
            'target_language_id' => $target->id,
            'type' => 'word',
            'source_text' => 'house',
            'target_text' => 'casa',
        ]);

    $response->assertSessionHasErrors('word_type_id');
});

test('word_type_id is cleared for non-word types', function () {
    ['user' => $user, 'target' => $target] = createUserWithLanguages();
    $verb = WordType::where('name', 'Verb')->first();

    $this->actingAs($user)
        ->post(route('translations.store'), [
            'target_language_id' => $target->id,
            'type' => 'text',
            'word_type_id' => $verb->id,
            'source_text' => 'Hello',
            'target_text' => 'Hola',
        ]);

    $translation = Translation::where('user_id', $user->id)->first();
    expect($translation->word_type_id)->toBeNull();
});

// Show Translation Tests

test('user can view own translation', function () {
    ['user' => $user, 'source' => $source, 'target' => $target] = createUserWithLanguages();

    $translation = Translation::create([
        'user_id' => $user->id,
        'source_language_id' => $source->id,
        'target_language_id' => $target->id,
        'type' => 'word',
        'word_type_id' => WordType::first()->id,
        'source_text' => 'hello',
        'target_text' => 'hola',
        'pronunciation' => 'oh-la',
    ]);

    $this->actingAs($user);
    visit(route('translations.show', $translation))
        ->assertSee('hello')
        ->assertSee('hola')
        ->assertSee('oh-la');
});

// Edit Translation Tests

test('user can edit own translation', function () {
    ['user' => $user, 'source' => $source, 'target' => $target] = createUserWithLanguages();

    $translation = Translation::create([
        'user_id' => $user->id,
        'source_language_id' => $source->id,
        'target_language_id' => $target->id,
        'type' => 'word',
        'word_type_id' => WordType::first()->id,
        'source_text' => 'hello',
        'target_text' => 'hola',
    ]);

    $this->actingAs($user);
    visit(route('translations.edit', $translation))
        ->assertSee('Edit Translation');
});

test('user can update own translation', function () {
    ['user' => $user, 'source' => $source, 'target' => $target] = createUserWithLanguages();

    $translation = Translation::create([
        'user_id' => $user->id,
        'source_language_id' => $source->id,
        'target_language_id' => $target->id,
        'type' => 'word',
        'word_type_id' => WordType::first()->id,
        'source_text' => 'hello',
        'target_text' => 'hola',
    ]);

    $this->actingAs($user)
        ->put(route('translations.update', $translation), [
            'target_language_id' => $target->id,
            'type' => 'word',
            'word_type_id' => WordType::first()->id,
            'source_text' => 'goodbye',
            'target_text' => 'adiós',
        ]);

    expect($translation->fresh()->source_text)->toBe('goodbye');
    expect($translation->fresh()->target_text)->toBe('adiós');
});

// Delete Translation Tests

test('user can delete own translation', function () {
    ['user' => $user, 'source' => $source, 'target' => $target] = createUserWithLanguages();

    $translation = Translation::create([
        'user_id' => $user->id,
        'source_language_id' => $source->id,
        'target_language_id' => $target->id,
        'type' => 'text',
        'source_text' => 'hello',
        'target_text' => 'hola',
    ]);

    $translationId = $translation->id;

    $this->actingAs($user)
        ->delete(route('translations.destroy', $translation));

    expect(Translation::find($translationId))->toBeNull();
});

// Cross-User Authorization Tests

test('user cannot view another users translation', function () {
    ['user' => $user1, 'source' => $source, 'target' => $target] = createUserWithLanguages();

    $user2 = User::factory()->create();
    $user2->assignRole('user');

    $translation = Translation::create([
        'user_id' => $user1->id,
        'source_language_id' => $source->id,
        'target_language_id' => $target->id,
        'type' => 'text',
        'source_text' => 'hello',
        'target_text' => 'hola',
    ]);

    $this->actingAs($user2)
        ->get(route('translations.show', $translation))
        ->assertForbidden();
});

test('user cannot edit another users translation', function () {
    ['user' => $user1, 'source' => $source, 'target' => $target] = createUserWithLanguages();

    $user2 = User::factory()->create();
    $user2->assignRole('user');

    $translation = Translation::create([
        'user_id' => $user1->id,
        'source_language_id' => $source->id,
        'target_language_id' => $target->id,
        'type' => 'text',
        'source_text' => 'hello',
        'target_text' => 'hola',
    ]);

    $this->actingAs($user2)
        ->get(route('translations.edit', $translation))
        ->assertForbidden();
});

test('user cannot update another users translation', function () {
    ['user' => $user1, 'source' => $source, 'target' => $target] = createUserWithLanguages();

    $user2 = User::factory()->create();
    $user2->assignRole('user');

    $translation = Translation::create([
        'user_id' => $user1->id,
        'source_language_id' => $source->id,
        'target_language_id' => $target->id,
        'type' => 'text',
        'source_text' => 'hello',
        'target_text' => 'hola',
    ]);

    $this->actingAs($user2)
        ->put(route('translations.update', $translation), [
            'target_language_id' => $target->id,
            'type' => 'text',
            'source_text' => 'hacked',
            'target_text' => 'hackeado',
        ])
        ->assertForbidden();

    expect($translation->fresh()->source_text)->toBe('hello');
});

test('user cannot delete another users translation', function () {
    ['user' => $user1, 'source' => $source, 'target' => $target] = createUserWithLanguages();

    $user2 = User::factory()->create();
    $user2->assignRole('user');

    $translation = Translation::create([
        'user_id' => $user1->id,
        'source_language_id' => $source->id,
        'target_language_id' => $target->id,
        'type' => 'text',
        'source_text' => 'hello',
        'target_text' => 'hola',
    ]);

    $this->actingAs($user2)
        ->delete(route('translations.destroy', $translation))
        ->assertForbidden();

    expect(Translation::find($translation->id))->not->toBeNull();
});

// Filter Tests

test('user can filter translations by target language', function () {
    ['user' => $user, 'source' => $source, 'target' => $spanish] = createUserWithLanguages();
    $french = Language::where('code', 'fr')->first();

    UserTargetLanguage::create([
        'user_id' => $user->id,
        'source_language_id' => $source->id,
        'target_language_id' => $french->id,
    ]);

    Translation::create([
        'user_id' => $user->id,
        'source_language_id' => $source->id,
        'target_language_id' => $spanish->id,
        'type' => 'word',
        'word_type_id' => WordType::first()->id,
        'source_text' => 'house',
        'target_text' => 'casa',
    ]);

    Translation::create([
        'user_id' => $user->id,
        'source_language_id' => $source->id,
        'target_language_id' => $french->id,
        'type' => 'word',
        'word_type_id' => WordType::first()->id,
        'source_text' => 'house',
        'target_text' => 'maison',
    ]);

    $this->actingAs($user);
    $response = $this->get(route('translations.index', ['target_language_id' => $spanish->id]));
    $response->assertSee('casa');
    $response->assertDontSee('maison');
});

test('user can filter translations by type', function () {
    ['user' => $user, 'source' => $source, 'target' => $target] = createUserWithLanguages();

    Translation::create([
        'user_id' => $user->id,
        'source_language_id' => $source->id,
        'target_language_id' => $target->id,
        'type' => 'word',
        'word_type_id' => WordType::first()->id,
        'source_text' => 'cat',
        'target_text' => 'gato',
    ]);

    Translation::create([
        'user_id' => $user->id,
        'source_language_id' => $source->id,
        'target_language_id' => $target->id,
        'type' => 'expression',
        'source_text' => 'break a leg',
        'target_text' => 'mucha mierda',
    ]);

    $this->actingAs($user);
    $response = $this->get(route('translations.index', ['type' => 'word']));
    $response->assertSee('cat');
    $response->assertDontSee('break a leg');
});

test('user can search translations', function () {
    ['user' => $user, 'source' => $source, 'target' => $target] = createUserWithLanguages();

    Translation::create([
        'user_id' => $user->id,
        'source_language_id' => $source->id,
        'target_language_id' => $target->id,
        'type' => 'word',
        'word_type_id' => WordType::first()->id,
        'source_text' => 'apple',
        'target_text' => 'manzana',
    ]);

    Translation::create([
        'user_id' => $user->id,
        'source_language_id' => $source->id,
        'target_language_id' => $target->id,
        'type' => 'word',
        'word_type_id' => WordType::first()->id,
        'source_text' => 'banana',
        'target_text' => 'plátano',
    ]);

    $this->actingAs($user);
    $response = $this->get(route('translations.index', ['search' => 'apple']));
    $response->assertSee('apple');
    $response->assertDontSee('banana');
});

// Scoping Tests

test('translations are scoped to active source language', function () {
    $user = User::factory()->create();
    $user->assignRole('user');

    $english = Language::where('code', 'en')->first();
    $spanish = Language::where('code', 'es')->first();
    $french = Language::where('code', 'fr')->first();

    UserSourceLanguage::create(['user_id' => $user->id, 'language_id' => $english->id, 'is_active' => true]);
    UserSourceLanguage::create(['user_id' => $user->id, 'language_id' => $spanish->id, 'is_active' => false]);

    UserTargetLanguage::create(['user_id' => $user->id, 'source_language_id' => $english->id, 'target_language_id' => $spanish->id]);
    UserTargetLanguage::create(['user_id' => $user->id, 'source_language_id' => $spanish->id, 'target_language_id' => $french->id]);

    Translation::create([
        'user_id' => $user->id,
        'source_language_id' => $english->id,
        'target_language_id' => $spanish->id,
        'type' => 'word',
        'word_type_id' => WordType::first()->id,
        'source_text' => 'dog',
        'target_text' => 'perro',
    ]);

    Translation::create([
        'user_id' => $user->id,
        'source_language_id' => $spanish->id,
        'target_language_id' => $french->id,
        'type' => 'word',
        'word_type_id' => WordType::first()->id,
        'source_text' => 'perro',
        'target_text' => 'chien',
    ]);

    $this->actingAs($user);
    $response = $this->get(route('translations.index'));
    $response->assertSee('dog');
    $response->assertDontSee('chien');
});
