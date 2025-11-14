<?php

use App\Models\User;

test('confirm password screen can be rendered', function () {
    $user = User::factory()->create();

    $this->actingAs($user);
    visit('/confirm-password')
        ->assertSee('Please confirm your password before continuing.')
        ->assertSee('This is a secure area');
});

test('password can be confirmed', function () {
    $user = User::factory()->create();

    $this->actingAs($user);
    visit('/confirm-password')
        ->type('password', 'password')
        ->click('Confirm');
});

test('password is not confirmed with invalid password', function () {
    $user = User::factory()->create();

    $this->actingAs($user);
    visit('/confirm-password')
        ->type('password', 'wrong-password')
        ->click('Confirm')
        ->assertSee('The provided password is incorrect');
});
