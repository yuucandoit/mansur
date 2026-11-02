<?php

use App\Models\Backup;
use App\Models\Email_Template;
use App\Models\Language;
use App\Models\Role;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

function systemAdmin(): User
{
    return User::factory()->create(['role' => 'admin']);
}

test('a regular user cannot access admin system pages', function () {
    $user = User::factory()->create(['role' => 'user']);

    $routes = [
        'admin.settings.index',
        'admin.email-templates.index',
        'admin.languages.index',
        'admin.backups.index',
        'admin.roles.index',
    ];

    foreach ($routes as $route) {
        $this->actingAs($user)->get(route($route))->assertForbidden();
    }
});

test('an admin can render every admin system page', function () {
    $admin = systemAdmin();

    $routes = [
        'admin.settings.index',
        'admin.email-templates.index',
        'admin.email-templates.create',
        'admin.languages.index',
        'admin.backups.index',
        'admin.roles.index',
    ];

    foreach ($routes as $route) {
        $this->actingAs($admin)->get(route($route))->assertOk();
    }
});

test('user preferences are persisted per user', function () {
    $user = User::factory()->create([
        'role' => 'user',
        'task_notifications' => true,
        'compact_cards' => false,
    ]);
    $other = User::factory()->create(['role' => 'user', 'compact_cards' => false]);

    $this->actingAs($user)
        ->patchJson(route('preferences.update'), ['key' => 'compact_cards', 'value' => 1])
        ->assertOk();

    $this->actingAs($user)
        ->patchJson(route('preferences.update'), ['key' => 'task_notifications', 'value' => 0])
        ->assertOk();

    expect($user->fresh()->compact_cards)->toBeTrue()
        ->and($user->fresh()->task_notifications)->toBeFalse()
        ->and($other->fresh()->compact_cards)->toBeFalse();
});

test('invalid preference keys are rejected', function () {
    $user = User::factory()->create(['role' => 'user']);

    $this->actingAs($user)
        ->patchJson(route('preferences.update'), ['key' => 'is_admin', 'value' => 1])
        ->assertUnprocessable();
});

test('admin can manage global settings', function () {
    $admin = systemAdmin();

    $this->actingAs($admin)
        ->post(route('admin.settings.store'), [
            'name' => 'Site Name',
            'slug' => 'site_name',
            'type' => 'string',
            'value' => 'ProTask',
        ])
        ->assertRedirect();

    $setting = Setting::firstOrFail();
    expect($setting->value)->toBe('ProTask');

    $this->actingAs($admin)
        ->put(route('admin.settings.update', $setting), [
            'name' => 'Site Name',
            'slug' => 'site_name',
            'type' => 'string',
            'value' => 'ProTask Cloud',
        ])
        ->assertRedirect();

    expect($setting->fresh()->value)->toBe('ProTask Cloud');

    $this->actingAs($admin)
        ->delete(route('admin.settings.destroy', $setting))
        ->assertRedirect();

    $this->assertDatabaseMissing('settings', ['id' => $setting->id]);
});

test('admin can manage email templates', function () {
    $admin = systemAdmin();

    $this->actingAs($admin)
        ->post(route('admin.email-templates.store'), [
            'name' => 'Task Assigned',
            'details' => 'Sent when a task is assigned',
            'slug' => 'task-assigned',
            'language' => 'en',
            'html' => '<h1>Hello</h1>',
        ])
        ->assertRedirect(route('admin.email-templates.index'));

    $template = Email_Template::firstOrFail();
    expect($template->name)->toBe('Task Assigned');

    $this->actingAs($admin)
        ->get(route('admin.email-templates.edit', $template))
        ->assertOk();

    $this->actingAs($admin)
        ->put(route('admin.email-templates.update', $template), [
            'name' => 'Task Assigned v2',
            'details' => 'Updated',
            'slug' => 'task-assigned',
            'language' => 'en',
            'html' => '<h1>Updated</h1>',
        ])
        ->assertRedirect(route('admin.email-templates.index'));

    expect($template->fresh()->name)->toBe('Task Assigned v2');

    $this->actingAs($admin)
        ->delete(route('admin.email-templates.destroy', $template))
        ->assertRedirect();

    $this->assertDatabaseMissing('email_templates', ['id' => $template->id]);
});

test('admin can manage languages', function () {
    $admin = systemAdmin();

    $this->actingAs($admin)
        ->post(route('admin.languages.store'), ['code' => 'id', 'name' => 'Indonesian'])
        ->assertRedirect();

    $language = Language::firstOrFail();

    $this->actingAs($admin)
        ->put(route('admin.languages.update', $language), ['code' => 'id', 'name' => 'Bahasa Indonesia'])
        ->assertRedirect();

    expect($language->fresh()->name)->toBe('Bahasa Indonesia');

    $this->actingAs($admin)
        ->delete(route('admin.languages.destroy', $language))
        ->assertRedirect();

    $this->assertDatabaseMissing('languages', ['id' => $language->id]);
});

test('admin can create, download, and delete backups', function () {
    Storage::fake('local');

    $admin = systemAdmin();

    $this->actingAs($admin)
        ->post(route('admin.backups.store'), ['name' => 'test-backup'])
        ->assertRedirect();

    $backup = Backup::firstOrFail();
    $path = $backup->details['path'];

    Storage::disk('local')->assertExists($path);

    $this->actingAs($admin)
        ->get(route('admin.backups.download', $backup))
        ->assertOk();

    $this->actingAs($admin)
        ->delete(route('admin.backups.destroy', $backup))
        ->assertRedirect();

    Storage::disk('local')->assertMissing($path);
    $this->assertDatabaseMissing('backups', ['id' => $backup->id]);
});

test('admin can manage legacy roles', function () {
    $admin = systemAdmin();

    $this->actingAs($admin)
        ->post(route('admin.roles.store'), [
            'name' => 'Manager',
            'slug' => 'manager',
            'create_workspace' => 1,
            'create_project' => 1,
            'access' => '{"tasks":["create","update"]}',
        ])
        ->assertRedirect();

    $role = Role::firstOrFail();
    expect($role->access)->toBe(['tasks' => ['create', 'update']]);

    $this->actingAs($admin)
        ->put(route('admin.roles.update', $role), [
            'name' => 'Senior Manager',
            'slug' => 'senior-manager',
            'create_project' => 1,
        ])
        ->assertRedirect();

    expect($role->fresh()->name)->toBe('Senior Manager')
        ->and($role->fresh()->access)->toBeNull();

    $this->actingAs($admin)
        ->delete(route('admin.roles.destroy', $role))
        ->assertRedirect();

    $this->assertDatabaseMissing('roles', ['id' => $role->id]);
});
