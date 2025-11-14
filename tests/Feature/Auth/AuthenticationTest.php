<?php

use App\Models\User;

test('login screen can be rendered', function () {
    visit('/login')
        ->assertSee('Log in')
        ->assertSee('Email')
        ->assertSee('Password');
});

test('users can authenticate using the login screen', function () {
    $user = User::factory()->create();

    visit('/login')
        ->type('email', $user->email)
        ->type('password', 'password')
        ->click('Log in')
        ->assertSee('Dashboard');
});

test('users can not authenticate with invalid password', function () {
    $user = User::factory()->create();

    visit('/login')
        ->type('email', $user->email)
        ->type('password', 'wrong-password')
        ->click('Log in')
        ->assertSee('These credentials do not match our records');
});

test('users can logout', function () {
    $user = User::factory()->create([
        'name' => 'Admin',
    ]);

    $this->actingAs($user);

    visit('/dashboard')
        ->click('button:has-text("Admin")')
        ->click('(//a[normalize-space()="Log Out"])[1]') // there are 2 buttons
        ->assertSee('Log in');
});
