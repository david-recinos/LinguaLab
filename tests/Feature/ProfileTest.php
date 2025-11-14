<?php

use App\Models\User;

test('profile page is displayed', function () {
    $user = User::factory()->create();

    $this->actingAs($user);
    visit('/profile')
        ->assertSee('Profile Information')
        ->assertSee('Update Password');
});

test('profile information can be updated', function () {
    $user = User::factory()->create(['name' => 'Old Name']);

    $this->actingAs($user);
    visit('/profile')
        ->type('name', 'Test User')
        ->type('email', 'test@example.com')
        ->click('Save')
        ->assertSee('Saved');

    $user->refresh();
    expect($user->name)->toBe('Test User');
    expect($user->email)->toBe('test@example.com');
});
