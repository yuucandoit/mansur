<?php

use App\Mail\InvitationMail;
use App\Models\BoardList;
use App\Models\Invitation;
use App\Models\Labels;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Models\Workspace;
use App\Services\MembershipService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

function labelWorkspace(User $owner): Workspace
{
    $workspace = Workspace::create([
        'name'    => 'Label Workspace',
        'slug'    => 'label-ws-'.Str::random(6),
        'user_id' => $owner->id,
    ]);

    app(MembershipService::class)->addToWorkspace($workspace, $owner, 'owner');

    return $workspace;
}

function labelTask(User $owner): array
{
    $workspace = labelWorkspace($owner);

    $project = Project::create([
        'title'        => 'Label Project',
        'user_id'      => $owner->id,
        'workspace_id' => $workspace->id,
    ]);

    app(MembershipService::class)->addToProject($project, $owner, 'manager');

    $list = BoardList::create([
        'title'      => 'To Do',
        'project_id' => $project->id,
        'user_id'    => $owner->id,
        'order'      => 0,
    ]);

    $task = Task::create([
        'title'      => 'Labelled task',
        'list_id'    => $list->id,
        'project_id' => $project->id,
        'user_id'    => $owner->id,
        'order'      => 1,
    ]);

    return [$workspace, $project, $task];
}

test('a project member can create, update, and delete labels', function () {
    $owner = User::factory()->create(['role' => 'user']);
    [, , $task] = labelTask($owner);

    $this->actingAs($owner)
        ->postJson(route('labels.store', $task), [
            'name'  => 'Bug',
            'color' => '#ef4444',
        ])
        ->assertCreated()
        ->assertJsonPath('name', 'Bug');

    $label = Labels::firstOrFail();
    expect($label->task_id)->toBe($task->id)
        ->and($label->color)->toBe('#ef4444');

    $this->actingAs($owner)
        ->putJson(route('labels.update', $label), [
            'name'  => 'Critical Bug',
            'color' => '#dc2626',
        ])
        ->assertOk()
        ->assertJsonPath('name', 'Critical Bug');

    expect($label->fresh()->name)->toBe('Critical Bug')
        ->and($label->fresh()->color)->toBe('#dc2626');

    $this->actingAs($owner)
        ->deleteJson(route('labels.destroy', $label))
        ->assertOk();

    $this->assertDatabaseMissing('labels', ['id' => $label->id]);
});

test('a user outside the project cannot create labels', function () {
    $owner = User::factory()->create(['role' => 'user']);
    $stranger = User::factory()->create(['role' => 'user']);
    [, , $task] = labelTask($owner);

    $this->actingAs($stranger)
        ->postJson(route('labels.store', $task), ['name' => 'Bug'])
        ->assertForbidden();
});

test('creating an invitation sends an email to the invited address', function () {
    Mail::fake();

    $owner = User::factory()->create(['role' => 'user']);
    [$workspace] = labelTask($owner);

    $this->actingAs($owner)
        ->postJson(route('invitations.store'), [
            'email'        => 'invitee@example.com',
            'workspace_id' => $workspace->id,
            'role'         => 'member',
        ])
        ->assertCreated()
        ->assertJsonPath('email_sent', true);

    Mail::assertSent(InvitationMail::class, function (InvitationMail $mail) {
        return $mail->hasTo('invitee@example.com')
            && $mail->invitation->email === 'invitee@example.com';
    });
});

test('the invitation email renders the accept link', function () {
    Mail::fake();

    $owner = User::factory()->create(['role' => 'user']);
    $workspace = labelWorkspace($owner);
    $invitee = User::factory()->create(['role' => 'user']);

    $invitation = Invitation::create([
        'workspace_id' => $workspace->id,
        'invited_by'   => $owner->id,
        'email'        => $invitee->email,
        'role'         => 'member',
        'token'        => Str::random(48),
        'expires_at'   => now()->addDays(7),
    ]);

    $html = (new InvitationMail($invitation->load(['workspace', 'project', 'inviter'])))->render();

    expect($html)->toContain($invitation->accept_url)
        ->and($html)->toContain($workspace->name);
});
