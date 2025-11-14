<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;

test('password can be updated', function () {
    $user = User::factory()->create();

    $this->actingAs($user);
    visit('/profile')
        ->type('#update_password_current_password', 'password')
        ->type('#update_password_password', 'new-password')
        ->type('#update_password_password_confirmation', 'new-password')
        ->click('(//button[normalize-space()="Save"])[2]') // There are 2 buttons with the same text
        ->assertSee('Saved');

    expect(Hash::check('new-password', $user->refresh()->password))->toBeTrue();
});

test('correct password must be provided to update password', function () {
    $user = User::factory()->create();

    $this->actingAs($user);
    visit('/profile')
        ->type('#update_password_current_password', 'wrong-password')
        ->type('#update_password_password', 'new-password')
        ->type('#update_password_password_confirmation', 'new-password')
        ->click('(//button[normalize-space()="Save"])[2]') // There are 2 buttons with the same text
        ->assertSee('The password is incorrect');
});
