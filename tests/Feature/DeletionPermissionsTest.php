<?php

use App\Models\Assignee;
use App\Models\Attachment;
use App\Models\BoardList;
use App\Models\Checklist;
use App\Models\Comments;
use App\Models\Invitation;
use App\Models\Labels;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Models\Workspace;
use App\Services\MembershipService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

function deletionWorkspace(User $owner): Workspace
{
    $workspace = Workspace::create([
        'name'    => 'Owner Workspace',
        'slug'    => 'owner-ws-'.Str::random(6),
        'user_id' => $owner->id,
    ]);

    app(MembershipService::class)->addToWorkspace($workspace, $owner, 'owner');

    return $workspace;
}

function deletionProject(Workspace $workspace, User $owner, ?User $projectOwner = null): Project
{
    $project = Project::create([
        'title'        => 'Disposable Project',
        'user_id'      => ($projectOwner ?? $owner)->id,
        'workspace_id' => $workspace->id,
    ]);

    app(MembershipService::class)->addToProject($project, $projectOwner ?? $owner, 'manager');

    return $project;
}

test('the workspace owner can delete a project and all related data', function () {
    $owner = User::factory()->create(['role' => 'user']);
    $workspace = deletionWorkspace($owner);
    $project = deletionProject($workspace, $owner);

    $list = BoardList::create([
        'title'      => 'To Do',
        'project_id' => $project->id,
        'user_id'    => $owner->id,
        'order'      => 0,
    ]);

    $task = Task::create([
        'title'      => 'Delete me',
        'list_id'    => $list->id,
        'project_id' => $project->id,
        'user_id'    => $owner->id,
        'order'      => 1,
    ]);

    Comments::create(['task_id' => $task->id, 'user_id' => $owner->id, 'details' => 'Hi']);
    Checklist::create(['task_id' => $task->id, 'title' => 'Step 1']);
    Assignee::create(['task_id' => $task->id, 'user_id' => $owner->id]);
    Labels::create(['task_id' => $task->id, 'name' => 'Bug', 'color' => '#ef4444']);
    Attachment::create(['task_id' => $task->id, 'user_id' => $owner->id, 'name' => 'file.txt', 'path' => 'x', 'size' => 10]);

    Invitation::create([
        'workspace_id' => $workspace->id,
        'project_id'   => $project->id,
        'invited_by'   => $owner->id,
        'email'        => 'invitee@example.com',
        'role'         => 'member',
        'token'        => Str::random(48),
        'expires_at'   => now()->addDays(7),
    ]);

    $this->actingAs($owner)
        ->delete(route('projects.destroy', $project))
        ->assertRedirect(route('projects.index'));

    $this->assertDatabaseMissing('projects', ['id' => $project->id]);
    $this->assertDatabaseMissing('board_lists', ['id' => $list->id]);
    $this->assertDatabaseMissing('tasks', ['id' => $task->id]);
    $this->assertDatabaseMissing('comments', ['task_id' => $task->id]);
    $this->assertDatabaseMissing('checklists', ['task_id' => $task->id]);
    $this->assertDatabaseMissing('assignees', ['task_id' => $task->id]);
    $this->assertDatabaseMissing('labels', ['task_id' => $task->id]);
    $this->assertDatabaseMissing('attachments', ['task_id' => $task->id]);
    $this->assertDatabaseMissing('project_members', ['project_id' => $project->id]);
    $this->assertDatabaseMissing('invitations', ['project_id' => $project->id]);
});

test('workspace admins and project managers cannot delete a project', function () {
    $owner = User::factory()->create(['role' => 'user']);
    $admin = User::factory()->create(['role' => 'user']);
    $manager = User::factory()->create(['role' => 'user']);
    $workspace = deletionWorkspace($owner);
    $project = deletionProject($workspace, $owner);

    app(MembershipService::class)->addToWorkspace($workspace, $admin, 'admin');
    app(MembershipService::class)->addToProject($project, $manager, 'manager');

    $this->actingAs($admin)
        ->delete(route('projects.destroy', $project))
        ->assertForbidden();

    $this->actingAs($manager)
        ->delete(route('projects.destroy', $project))
        ->assertForbidden();

    $this->assertDatabaseHas('projects', ['id' => $project->id]);
});

test('a project creator who is not the workspace owner cannot delete the project', function () {
    $owner = User::factory()->create(['role' => 'user']);
    $member = User::factory()->create(['role' => 'user']);
    $workspace = deletionWorkspace($owner);
    app(MembershipService::class)->addToWorkspace($workspace, $member, 'member');

    $project = deletionProject($workspace, $owner, $member);

    $this->actingAs($member)
        ->delete(route('projects.destroy', $project))
        ->assertForbidden();

    $this->assertDatabaseHas('projects', ['id' => $project->id]);
});

test('only the workspace owner can delete the workspace and everything in it', function () {
    $owner = User::factory()->create(['role' => 'user']);
    $admin = User::factory()->create(['role' => 'user']);
    $workspace = deletionWorkspace($owner);
    $project = deletionProject($workspace, $owner);

    $list = BoardList::create([
        'title'      => 'To Do',
        'project_id' => $project->id,
        'user_id'    => $owner->id,
        'order'      => 0,
    ]);

    $task = Task::create([
        'title'      => 'Workspace task',
        'list_id'    => $list->id,
        'project_id' => $project->id,
        'user_id'    => $owner->id,
        'order'      => 1,
    ]);

    app(MembershipService::class)->addToWorkspace($workspace, $admin, 'admin');

    $this->actingAs($admin)
        ->delete(route('workspaces.destroy', $workspace))
        ->assertForbidden();

    $this->assertDatabaseHas('workspaces', ['id' => $workspace->id]);

    $this->actingAs($owner)
        ->delete(route('workspaces.destroy', $workspace))
        ->assertRedirect(route('onboarding'));

    $this->assertDatabaseMissing('workspaces', ['id' => $workspace->id]);
    $this->assertDatabaseMissing('projects', ['id' => $project->id]);
    $this->assertDatabaseMissing('board_lists', ['id' => $list->id]);
    $this->assertDatabaseMissing('tasks', ['id' => $task->id]);
    $this->assertDatabaseMissing('workspace_members', ['workspace_id' => $workspace->id]);
});
