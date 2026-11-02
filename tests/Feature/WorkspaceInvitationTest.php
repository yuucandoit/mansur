<?php

use App\Models\Invitation;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceMember;
use App\Services\MembershipService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

function makeWorkspace(User $owner): Workspace
{
    $workspace = Workspace::create([
        'name'    => 'Acme',
        'slug'    => 'acme-'.Str::random(6),
        'user_id' => $owner->id,
    ]);

    app(MembershipService::class)->addToWorkspace($workspace, $owner, 'owner');

    return $workspace;
}

function makeProject(Workspace $workspace, User $owner): Project
{
    $project = Project::create([
        'title'        => 'Website Redesign',
        'user_id'      => $owner->id,
        'workspace_id' => $workspace->id,
    ]);

    app(MembershipService::class)->addToProject($project, $owner, 'manager');

    return $project;
}

test('a user without a workspace sees the onboarding page', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/')
        ->assertOk()
        ->assertSee('Create a workspace')
        ->assertSee('Welcome, '.$user->name);
});

test('creating a workspace makes the creator the owner member', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->postJson('/workspaces', ['name' => 'Marketing', 'description' => 'Team space'])
        ->assertCreated();

    $workspace = Workspace::firstOrFail();

    expect($workspace->user_id)->toBe($user->id)
        ->and(WorkspaceMember::where('workspace_id', $workspace->id)
            ->where('user_id', $user->id)
            ->where('role', 'owner')
            ->exists())->toBeTrue();
});

test('a workspace invitation must be followed by joining a project', function () {
    $owner = User::factory()->create();
    $invitee = User::factory()->create();
    $workspace = makeWorkspace($owner);

    $invitation = Invitation::create([
        'workspace_id' => $workspace->id,
        'invited_by'   => $owner->id,
        'email'        => $invitee->email,
        'role'         => 'member',
        'token'        => Str::random(48),
        'expires_at'   => now()->addDays(7),
    ]);

    $this->actingAs($invitee)
        ->post(route('invitations.accept', $invitation->token))
        ->assertRedirect(route('workspaces.join-project', $workspace));

    expect(WorkspaceMember::where('workspace_id', $workspace->id)
        ->where('user_id', $invitee->id)->exists())->toBeTrue()
        ->and(ProjectMember::where('user_id', $invitee->id)->exists())->toBeFalse()
        ->and($invitation->fresh()->isAccepted())->toBeTrue();
});

test('accepting a project invitation also joins the workspace', function () {
    $owner = User::factory()->create();
    $invitee = User::factory()->create();
    $workspace = makeWorkspace($owner);
    $project = makeProject($workspace, $owner);

    $invitation = Invitation::create([
        'workspace_id' => $workspace->id,
        'project_id'   => $project->id,
        'invited_by'   => $owner->id,
        'email'        => $invitee->email,
        'role'         => 'member',
        'token'        => Str::random(48),
        'expires_at'   => now()->addDays(7),
    ]);

    $this->actingAs($invitee)
        ->post(route('invitations.accept', $invitation->token))
        ->assertRedirect(route('projects.board', $project));

    expect(ProjectMember::where('project_id', $project->id)
        ->where('user_id', $invitee->id)->exists())->toBeTrue()
        ->and(WorkspaceMember::where('workspace_id', $workspace->id)
            ->where('user_id', $invitee->id)->exists())->toBeTrue();
});

test('joining a project through the membership service adds workspace access', function () {
    $owner = User::factory()->create();
    $newcomer = User::factory()->create();
    $workspace = makeWorkspace($owner);
    $project = makeProject($workspace, $owner);

    app(MembershipService::class)->addToProject($project, $newcomer);

    expect(ProjectMember::where('project_id', $project->id)
        ->where('user_id', $newcomer->id)->exists())->toBeTrue()
        ->and(WorkspaceMember::where('workspace_id', $workspace->id)
            ->where('user_id', $newcomer->id)->exists())->toBeTrue()
        ->and($newcomer->fresh()->canAccessWorkspace($workspace))->toBeTrue();
});

test('an invitation cannot be accepted by a different email address', function () {
    $owner = User::factory()->create();
    $invitee = User::factory()->create();
    $wrongUser = User::factory()->create();
    $workspace = makeWorkspace($owner);

    $invitation = Invitation::create([
        'workspace_id' => $workspace->id,
        'invited_by'   => $owner->id,
        'email'        => $invitee->email,
        'role'         => 'member',
        'token'        => Str::random(48),
        'expires_at'   => now()->addDays(7),
    ]);

    $this->actingAs($wrongUser)
        ->post(route('invitations.accept', $invitation->token))
        ->assertForbidden();

    expect(WorkspaceMember::where('workspace_id', $workspace->id)
        ->where('user_id', $wrongUser->id)->exists())->toBeFalse();
});

test('non members cannot open a project board', function () {
    $owner = User::factory()->create();
    $stranger = User::factory()->create();
    $workspace = makeWorkspace($owner);
    $project = makeProject($workspace, $owner);

    $this->actingAs($stranger)
        ->get(route('projects.board', $project))
        ->assertForbidden();
});

test('a workspace admin may invite someone directly to a project', function () {
    $owner = User::factory()->create();
    $admin = User::factory()->create();
    $workspace = makeWorkspace($owner);
    $project = makeProject($workspace, $owner);

    app(MembershipService::class)->addToWorkspace($workspace, $admin, 'admin');

    $this->actingAs($admin)
        ->postJson(route('invitations.store'), [
            'email'     => 'new-person@example.com',
            'project_id' => $project->id,
            'role'      => 'member',
        ])
        ->assertCreated()
        ->assertJsonStructure(['accept_url']);

    expect(Invitation::where('email', 'new-person@example.com')
        ->where('project_id', $project->id)->exists())->toBeTrue();
});

test('workspace and project member pages render for their owner', function () {
    $owner = User::factory()->create();
    $workspace = makeWorkspace($owner);
    $project = makeProject($workspace, $owner);

    $this->actingAs($owner)
        ->get(route('workspaces.members', $workspace))
        ->assertOk()
        ->assertSee('Members')
        ->assertSee($owner->email);

    $this->actingAs($owner)
        ->get(route('projects.members', $project))
        ->assertOk()
        ->assertSee('Project members');
});

test('the project board and join-project pages render', function () {
    $owner = User::factory()->create();
    $workspace = makeWorkspace($owner);
    $project = makeProject($workspace, $owner);

    $this->actingAs($owner)
        ->get(route('projects.board', $project))
        ->assertOk();

    $this->actingAs($owner)
        ->get(route('workspaces.join-project', $workspace))
        ->assertRedirect(route('projects.board', $project));
});

test('the public invitation page renders for a guest', function () {
    $owner = User::factory()->create();
    $workspace = makeWorkspace($owner);
    $project = makeProject($workspace, $owner);

    $invitation = Invitation::create([
        'workspace_id' => $workspace->id,
        'project_id'   => $project->id,
        'invited_by'   => $owner->id,
        'email'        => 'guest@example.com',
        'role'         => 'member',
        'token'        => Str::random(48),
        'expires_at'   => now()->addDays(7),
    ]);

    $this->get(route('invitations.show', $invitation->token))
        ->assertOk()
        ->assertSee('You are invited to a project')
        ->assertSee('Sign in to accept');
});
