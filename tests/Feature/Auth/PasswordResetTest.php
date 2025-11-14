<?php

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Notification;

test('reset password link screen can be rendered', function () {
    visit('/forgot-password')
        ->assertSee('Forgot your password')
        ->assertSee('Email');
});

test('reset password link can be requested', function () {
    Notification::fake();

    $user = User::factory()->create();

    visit('/forgot-password')
        ->type('email', $user->email)
        ->click('Email Password Reset Link');

    Notification::assertSentTo($user, ResetPassword::class);
});
