<?php

use App\Models\User;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    // Create roles
    Role::create(['name' => 'admin']);
    Role::create(['name' => 'user']);
});

// Admin User Tests
test('admin user can see users menu link', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $this->actingAs($admin);
    visit('/dashboard')
        ->assertSee('Users');
});

test('admin user can access users list', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $this->actingAs($admin);
    visit(route('users.index'))
        ->assertSee('Users Management');
});

test('admin user can see all users in the list', function () {
    $admin = User::factory()->create(['name' => 'Admin User']);
    $admin->assignRole('admin');

    $regularUser = User::factory()->create(['name' => 'Regular User']);
    $regularUser->assignRole('user');

    $this->actingAs($admin);
    visit(route('users.index'))
        ->assertSee('Admin User')
        ->assertSee('Regular User');
});

test('admin user can access create user page', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $this->actingAs($admin);
    visit(route('users.create'))
        ->assertSee('Create User');
});

test('admin user can create a new user', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $this->actingAs($admin);
    visit(route('users.create'))
        ->type('name', 'New User')
        ->type('email', 'newuser@example.com')
        ->type('password', 'password123')
        ->type('password_confirmation', 'password123')
        ->select('role', 'user')
        ->click('Create User')
        ->assertUrlIs(route('users.index'))
        ->assertSee('User created successfully');

    expect(User::where('email', 'newuser@example.com')->exists())->toBeTrue();
});

test('admin user can access edit user page', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $userToEdit = User::factory()->create(['name' => 'Edit Me']);
    $userToEdit->assignRole('user');

    $this->actingAs($admin);
    visit(route('users.edit', $userToEdit))
        ->assertSee('Edit User');
});

test('admin user can update a user', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $userToEdit = User::factory()->create(['name' => 'Old Name']);
    $userToEdit->assignRole('user');

    $this->actingAs($admin);
    visit(route('users.edit', $userToEdit))
        ->type('name', 'Updated Name')
        ->click('Update User')
        ->assertSee('User updated successfully');

    expect($userToEdit->fresh()->name)->toBe('Updated Name');
});

// TODO: To fix this test and to update the functionality to use modal confirmation instead of the popup window
//test('admin user can delete a user', function () {
//    $admin = User::factory()->create();
//    $admin->assignRole('admin');
//
//    $userToDelete = User::factory()->create(['name' => 'Delete Me']);
//    $userToDelete->assignRole('user');
//    $userId = $userToDelete->id;
//
//    $this->actingAs($admin);
//
//    visit(route('users.index'))
//        ->click('[data-test="delete-user-' . $userId . '"]')
//        ->assertSee('User deleted successfully');
//
//    expect(User::find($userId))->toBeNull();
//});

// Regular User Tests
test('regular user cannot see users menu link', function () {
    $user = User::factory()->create();
    $user->assignRole('user');

    $this->actingAs($user);
    visit('/dashboard')
        ->assertDontSee('Users');
});

test('regular user cannot access users list', function () {
    $user = User::factory()->create();
    $user->assignRole('user');

    $this->actingAs($user);
    visit(route('users.index'))
        ->assertSee('403');
});

test('regular user cannot access create user page', function () {
    $user = User::factory()->create();
    $user->assignRole('user');

    $this->actingAs($user);
    visit(route('users.create'))
        ->assertSee('403');
});

test('regular user cannot access edit user page', function () {
    $user = User::factory()->create();
    $user->assignRole('user');

    $otherUser = User::factory()->create();
    $otherUser->assignRole('user');

    $this->actingAs($user);
    visit(route('users.edit', $otherUser))
        ->assertSee('403');
});
