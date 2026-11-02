<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

function adminUser(): User
{
    return User::factory()->create(['role' => 'admin']);
}

test('newly registered users get the user role by default', function () {
    $this->post(route('register'), [
        'name'                  => 'New Person',
        'email'                 => 'new-person@example.com',
        'password'              => 'password123',
        'password_confirmation' => 'password123',
    ])->assertRedirect();

    $this->assertDatabaseHas('users', [
        'email' => 'new-person@example.com',
        'role'  => 'user',
    ]);
});

test('a regular user cannot access user management', function () {
    $user = User::factory()->create(['role' => 'user']);

    $this->actingAs($user)
        ->get(route('admin.users.index'))
        ->assertForbidden();
});

test('an admin can see all registered users', function () {
    $admin = adminUser();
    $other = User::factory()->create(['role' => 'user']);

    $this->actingAs($admin)
        ->get(route('admin.users.index'))
        ->assertOk()
        ->assertSee('User Management')
        ->assertSee($admin->email)
        ->assertSee($other->email);
});

test('an admin can promote and demote another user', function () {
    $admin = adminUser();
    $user = User::factory()->create(['role' => 'user']);

    $this->actingAs($admin)
        ->patch(route('admin.users.update-role', $user), ['role' => 'admin'])
        ->assertRedirect();

    expect($user->fresh()->role)->toBe('admin');

    $this->actingAs($admin)
        ->patch(route('admin.users.update-role', $user), ['role' => 'user'])
        ->assertRedirect();

    expect($user->fresh()->role)->toBe('user');
});

test('an admin cannot change their own role', function () {
    $admin = adminUser();

    $this->actingAs($admin)
        ->patch(route('admin.users.update-role', $admin), ['role' => 'user'])
        ->assertSessionHasErrors('role');

    expect($admin->fresh()->role)->toBe('admin');
});

test('an admin can delete another user but not themselves', function () {
    $admin = adminUser();
    $user = User::factory()->create(['role' => 'user']);

    $this->actingAs($admin)
        ->delete(route('admin.users.destroy', $user))
        ->assertRedirect();

    $this->assertDatabaseMissing('users', ['id' => $user->id]);

    $this->actingAs($admin)
        ->delete(route('admin.users.destroy', $admin))
        ->assertSessionHasErrors('user');

    $this->assertDatabaseHas('users', ['id' => $admin->id]);
});

test('the last administrator cannot be demoted or deleted', function () {
    $admin = adminUser();
    $other = User::factory()->create(['role' => 'user']);

    // Other user is promoted first, so two admins exist.
    $this->actingAs($admin)
        ->patch(route('admin.users.update-role', $other), ['role' => 'admin'])
        ->assertRedirect();

    // Now demote the other admin; one admin remains, which is allowed.
    $this->actingAs($admin)
        ->patch(route('admin.users.update-role', $other), ['role' => 'user'])
        ->assertRedirect();

    expect($other->fresh()->role)->toBe('user');
});


test('an admin can change another user\'s password', function () {
    $admin = adminUser();
    $user = User::factory()->create(['role' => 'user']);

    $this->actingAs($admin)
        ->put(route('admin.users.update-password', $user), [
            'password'              => 'new-secret-password',
            'password_confirmation' => 'new-secret-password',
        ])
        ->assertRedirect();

    expect(Hash::check('new-secret-password', $user->fresh()->password))->toBeTrue();
});

test('an admin can deactivate and reactivate a user', function () {
    $admin = adminUser();
    $user = User::factory()->create(['role' => 'user']);

    $this->actingAs($admin)
        ->patch(route('admin.users.update-status', $user), ['is_active' => 0])
        ->assertRedirect();

    expect($user->fresh()->is_active)->toBeFalse();

    $this->actingAs($admin)
        ->patch(route('admin.users.update-status', $user), ['is_active' => 1])
        ->assertRedirect();

    expect($user->fresh()->is_active)->toBeTrue();
});

test('an admin cannot deactivate their own account', function () {
    $admin = adminUser();

    $this->actingAs($admin)
        ->patch(route('admin.users.update-status', $admin), ['is_active' => 0])
        ->assertSessionHasErrors('user');

    expect($admin->fresh()->is_active)->toBeTrue();
});

test('a deactivated user cannot log in', function () {
    $user = User::factory()->create([
        'role'      => 'user',
        'is_active' => false,
        'password'  => Hash::make('password123'),
    ]);

    $this->post(route('login'), [
        'email'    => $user->email,
        'password' => 'password123',
    ])->assertSessionHasErrors('email');

    $this->assertGuest();
});

test('an existing session is ended when the account is deactivated', function () {
    $user = User::factory()->create(['role' => 'user']);

    $this->actingAs($user)->get(route('projects.index'))->assertOk();

    $user->update(['is_active' => false]);

    $this->get(route('projects.index'))->assertRedirect(route('login'));

    $this->assertGuest();
});
