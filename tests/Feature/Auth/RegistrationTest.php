<?php

test('registration screen can be rendered', function () {
    visit('/register')
        ->assertSee('Register')
        ->assertSee('Name')
        ->assertSee('Email')
        ->assertSee('Password');
});

test('new users can register', function () {
    visit('/register')
        ->type('name', 'Test User')
        ->type('email', 'test@example.com')
        ->type('password', 'password')
        ->type('password_confirmation', 'password')
        ->click('Register')
        ->assertSee('Dashboard');

    expect(\App\Models\User::where('email', 'test@example.com')->exists())->toBeTrue();
});
