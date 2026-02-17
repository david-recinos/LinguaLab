<?php

use App\Models\Language;
use App\Models\User;
use App\Models\UserSourceLanguage;
use App\Models\UserTargetLanguage;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Role::create(['name' => 'admin']);
    Role::create(['name' => 'user']);

    // Create test languages
    Language::create(['code' => 'en', 'name' => 'English', 'native_name' => 'English']);
    Language::create(['code' => 'es', 'name' => 'Spanish', 'native_name' => 'Español']);
    Language::create(['code' => 'fr', 'name' => 'French', 'native_name' => 'Français']);
    Language::create(['code' => 'de', 'name' => 'German', 'native_name' => 'Deutsch']);
});

// Access Tests

test('authenticated user can access languages page', function () {
    $user = User::factory()->create();
    $user->assignRole('user');

    $this->actingAs($user);
    visit(route('languages.index'))
        ->assertSee('My Languages');
});

test('unauthenticated user cannot access languages page', function () {
    $this->get(route('languages.index'))
        ->assertRedirect(route('login'));
});

// Source Language Tests

test('user can add a source language', function () {
    $user = User::factory()->create();
    $user->assignRole('user');
    $english = Language::where('code', 'en')->first();

    $this->actingAs($user);
    visit(route('languages.index'))
        ->select('language_id', $english->id)
        ->click('Add')
        ->assertSee('Source language added');

    expect(UserSourceLanguage::where('user_id', $user->id)->where('language_id', $english->id)->exists())->toBeTrue();
});

test('first source language is automatically set as active', function () {
    $user = User::factory()->create();
    $user->assignRole('user');
    $english = Language::where('code', 'en')->first();

    $this->actingAs($user)
        ->post(route('languages.source.store'), ['language_id' => $english->id]);

    $sourceLanguage = UserSourceLanguage::where('user_id', $user->id)->first();
    expect($sourceLanguage->is_active)->toBeTrue();
});

test('second source language is not automatically active', function () {
    $user = User::factory()->create();
    $user->assignRole('user');
    $english = Language::where('code', 'en')->first();
    $spanish = Language::where('code', 'es')->first();

    $this->actingAs($user);
    $this->post(route('languages.source.store'), ['language_id' => $english->id]);
    $this->post(route('languages.source.store'), ['language_id' => $spanish->id]);

    $second = UserSourceLanguage::where('user_id', $user->id)->where('language_id', $spanish->id)->first();
    expect($second->is_active)->toBeFalse();
});

test('user cannot add duplicate source language', function () {
    $user = User::factory()->create();
    $user->assignRole('user');
    $english = Language::where('code', 'en')->first();

    $this->actingAs($user);
    $this->post(route('languages.source.store'), ['language_id' => $english->id]);
    $this->post(route('languages.source.store'), ['language_id' => $english->id]);

    expect(UserSourceLanguage::where('user_id', $user->id)->count())->toBe(1);
});

test('user can switch active source language', function () {
    $user = User::factory()->create();
    $user->assignRole('user');
    $english = Language::where('code', 'en')->first();
    $spanish = Language::where('code', 'es')->first();

    $this->actingAs($user);
    $this->post(route('languages.source.store'), ['language_id' => $english->id]);
    $this->post(route('languages.source.store'), ['language_id' => $spanish->id]);

    $spanishSource = UserSourceLanguage::where('user_id', $user->id)->where('language_id', $spanish->id)->first();
    $this->patch(route('languages.source.switch', $spanishSource->id));

    expect($spanishSource->fresh()->is_active)->toBeTrue();
    $englishSource = UserSourceLanguage::where('user_id', $user->id)->where('language_id', $english->id)->first();
    expect($englishSource->is_active)->toBeFalse();
});

test('user can delete a source language', function () {
    $user = User::factory()->create();
    $user->assignRole('user');
    $english = Language::where('code', 'en')->first();

    $this->actingAs($user);
    $this->post(route('languages.source.store'), ['language_id' => $english->id]);

    $source = UserSourceLanguage::where('user_id', $user->id)->first();
    $this->delete(route('languages.source.destroy', $source->id));

    expect(UserSourceLanguage::where('user_id', $user->id)->count())->toBe(0);
});

test('deleting active source language activates another', function () {
    $user = User::factory()->create();
    $user->assignRole('user');
    $english = Language::where('code', 'en')->first();
    $spanish = Language::where('code', 'es')->first();

    $this->actingAs($user);
    $this->post(route('languages.source.store'), ['language_id' => $english->id]);
    $this->post(route('languages.source.store'), ['language_id' => $spanish->id]);

    $englishSource = UserSourceLanguage::where('user_id', $user->id)->where('language_id', $english->id)->first();
    $this->delete(route('languages.source.destroy', $englishSource->id));

    $remaining = UserSourceLanguage::where('user_id', $user->id)->first();
    expect($remaining->is_active)->toBeTrue();
});

// Target Language Tests

test('user can add a target language', function () {
    $user = User::factory()->create();
    $user->assignRole('user');
    $english = Language::where('code', 'en')->first();
    $spanish = Language::where('code', 'es')->first();

    $this->actingAs($user);
    $this->post(route('languages.source.store'), ['language_id' => $english->id]);

    $this->post(route('languages.target.store'), [
        'source_language_id' => $english->id,
        'target_language_id' => $spanish->id,
    ]);

    expect(UserTargetLanguage::where('user_id', $user->id)->count())->toBe(1);
});

test('user cannot add same target language twice for same source', function () {
    $user = User::factory()->create();
    $user->assignRole('user');
    $english = Language::where('code', 'en')->first();
    $spanish = Language::where('code', 'es')->first();

    $this->actingAs($user);
    $this->post(route('languages.source.store'), ['language_id' => $english->id]);

    $this->post(route('languages.target.store'), [
        'source_language_id' => $english->id,
        'target_language_id' => $spanish->id,
    ]);
    $this->post(route('languages.target.store'), [
        'source_language_id' => $english->id,
        'target_language_id' => $spanish->id,
    ]);

    expect(UserTargetLanguage::where('user_id', $user->id)->count())->toBe(1);
});

test('user cannot add target language matching source language', function () {
    $user = User::factory()->create();
    $user->assignRole('user');
    $english = Language::where('code', 'en')->first();

    $this->actingAs($user);
    $this->post(route('languages.source.store'), ['language_id' => $english->id]);

    $response = $this->post(route('languages.target.store'), [
        'source_language_id' => $english->id,
        'target_language_id' => $english->id,
    ]);

    $response->assertSessionHasErrors('target_language_id');
    expect(UserTargetLanguage::where('user_id', $user->id)->count())->toBe(0);
});

test('user can delete a target language', function () {
    $user = User::factory()->create();
    $user->assignRole('user');
    $english = Language::where('code', 'en')->first();
    $spanish = Language::where('code', 'es')->first();

    $this->actingAs($user);
    $this->post(route('languages.source.store'), ['language_id' => $english->id]);
    $this->post(route('languages.target.store'), [
        'source_language_id' => $english->id,
        'target_language_id' => $spanish->id,
    ]);

    $target = UserTargetLanguage::where('user_id', $user->id)->first();
    $this->delete(route('languages.target.destroy', $target->id));

    expect(UserTargetLanguage::where('user_id', $user->id)->count())->toBe(0);
});

// Cross-User Authorization Tests

test('user cannot switch another users source language', function () {
    $user1 = User::factory()->create();
    $user1->assignRole('user');
    $user2 = User::factory()->create();
    $user2->assignRole('user');
    $english = Language::where('code', 'en')->first();

    $this->actingAs($user1);
    $this->post(route('languages.source.store'), ['language_id' => $english->id]);
    $source = UserSourceLanguage::where('user_id', $user1->id)->first();

    $this->actingAs($user2);
    $response = $this->patch(route('languages.source.switch', $source->id));
    $response->assertForbidden();
});

test('user cannot delete another users source language', function () {
    $user1 = User::factory()->create();
    $user1->assignRole('user');
    $user2 = User::factory()->create();
    $user2->assignRole('user');
    $english = Language::where('code', 'en')->first();

    $this->actingAs($user1);
    $this->post(route('languages.source.store'), ['language_id' => $english->id]);
    $source = UserSourceLanguage::where('user_id', $user1->id)->first();

    $this->actingAs($user2);
    $response = $this->delete(route('languages.source.destroy', $source->id));
    $response->assertForbidden();
});

test('user cannot delete another users target language', function () {
    $user1 = User::factory()->create();
    $user1->assignRole('user');
    $user2 = User::factory()->create();
    $user2->assignRole('user');
    $english = Language::where('code', 'en')->first();
    $spanish = Language::where('code', 'es')->first();

    $this->actingAs($user1);
    $this->post(route('languages.source.store'), ['language_id' => $english->id]);
    $this->post(route('languages.target.store'), [
        'source_language_id' => $english->id,
        'target_language_id' => $spanish->id,
    ]);
    $target = UserTargetLanguage::where('user_id', $user1->id)->first();

    $this->actingAs($user2);
    $response = $this->delete(route('languages.target.destroy', $target->id));
    $response->assertForbidden();
});

// Target Languages Display Tests

test('target languages section shows when active source exists', function () {
    $user = User::factory()->create();
    $user->assignRole('user');
    $english = Language::where('code', 'en')->first();

    $this->actingAs($user);
    $this->post(route('languages.source.store'), ['language_id' => $english->id]);

    visit(route('languages.index'))
        ->assertSee('Target Languages for');
});

test('target languages section hidden when no source languages', function () {
    $user = User::factory()->create();
    $user->assignRole('user');

    $this->actingAs($user);
    visit(route('languages.index'))
        ->assertDontSee('Target Languages for');
});
