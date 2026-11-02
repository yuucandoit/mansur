<?php

use App\Models\Assignee;
use App\Models\Attachment;
use App\Models\BoardList;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Models\Workspace;
use App\Services\AttachmentCompressor;
use App\Services\MembershipService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

function aaWorkspace(User $owner): Workspace
{
    $workspace = Workspace::create([
        'name'    => 'Assignee Workspace',
        'slug'    => 'aa-ws-'.Str::random(6),
        'user_id' => $owner->id,
    ]);

    app(MembershipService::class)->addToWorkspace($workspace, $owner, 'owner');

    return $workspace;
}

function aaTask(User $owner, array $memberIds = []): array
{
    $workspace = aaWorkspace($owner);

    $project = Project::create([
        'title'        => 'Assignee Project',
        'user_id'      => $owner->id,
        'workspace_id' => $workspace->id,
    ]);

    app(MembershipService::class)->addToProject($project, $owner, 'manager');

    foreach ($memberIds as $memberId) {
        app(MembershipService::class)->addToProject($project, User::findOrFail($memberId));
    }

    $list = BoardList::create([
        'title'      => 'To Do',
        'project_id' => $project->id,
        'user_id'    => $owner->id,
        'order'      => 0,
    ]);

    $task = Task::create([
        'title'      => 'Assignable task',
        'list_id'    => $list->id,
        'project_id' => $project->id,
        'user_id'    => $owner->id,
        'order'      => 1,
    ]);

    return [$workspace, $project, $task];
}

test('assigning a project member records who assigned them', function () {
    $owner = User::factory()->create(['role' => 'user']);
    $member = User::factory()->create(['role' => 'user']);
    [, $project, $task] = aaTask($owner, [$member->id]);

    $this->actingAs($owner)
        ->postJson(route('assignees.store', $task), ['user_id' => $member->id])
        ->assertCreated()
        ->assertJsonPath('user.id', $member->id)
        ->assertJsonPath('assigner.id', $owner->id);

    $this->assertDatabaseHas('assignees', [
        'task_id'     => $task->id,
        'user_id'     => $member->id,
        'assigned_by' => $owner->id,
    ]);
});

test('a user outside the project cannot be assigned', function () {
    $owner = User::factory()->create(['role' => 'user']);
    $stranger = User::factory()->create(['role' => 'user']);
    [, , $task] = aaTask($owner);

    $this->actingAs($owner)
        ->postJson(route('assignees.store', $task), ['user_id' => $stranger->id])
        ->assertUnprocessable();
});

test('an assignee can be removed by the person who assigned them', function () {
    $owner = User::factory()->create(['role' => 'user']);
    $member = User::factory()->create(['role' => 'user']);
    [, , $task] = aaTask($owner, [$member->id]);

    $assignee = Assignee::create([
        'task_id'     => $task->id,
        'user_id'     => $member->id,
        'assigned_by' => $owner->id,
    ]);

    $this->actingAs($owner)
        ->deleteJson(route('assignees.destroy', $assignee))
        ->assertOk();

    $this->assertDatabaseMissing('assignees', ['id' => $assignee->id]);
});

test('a project member can upload and delete an attachment', function () {
    Storage::fake('public');

    $owner = User::factory()->create(['role' => 'user']);
    [, , $task] = aaTask($owner);

    $file = UploadedFile::fake()->createWithContent('notes.txt', 'hello attachment');

    $response = $this->actingAs($owner)
        ->post(route('attachments.store', $task), ['file' => $file])
        ->assertCreated();

    $attachment = Attachment::firstOrFail();

    $this->assertSame('notes.txt', $attachment->name);
    $this->assertSame($owner->id, $attachment->user_id);
    Storage::disk('public')->assertExists($attachment->path);

    $this->actingAs($owner)
        ->deleteJson(route('attachments.destroy', $attachment))
        ->assertOk();

    $this->assertDatabaseMissing('attachments', ['id' => $attachment->id]);
    Storage::disk('public')->assertMissing($attachment->path);

    $response->assertJsonPath('compressed', false);
});

test('files larger than 20 MB are compressed before storage', function () {
    $size = AttachmentCompressor::THRESHOLD_BYTES + 1024;

    $file = UploadedFile::fake()->createWithContent('large.txt', str_repeat('A', $size));

    $result = app(AttachmentCompressor::class)->compressIfNeeded($file);

    expect($result['needs_compression'])->toBeTrue()
        ->and($result['name'])->toEndWith('.zip')
        ->and($result['size'])->toBeLessThan(AttachmentCompressor::THRESHOLD_BYTES)
        ->and($result['temp_path'])->not->toBeNull();

    @unlink($result['temp_path']);
});

test('task details expose assigners, assignable users, and attachment urls', function () {
    Storage::fake('public');

    $owner = User::factory()->create(['role' => 'user']);
    $member = User::factory()->create(['role' => 'user']);
    [, , $task] = aaTask($owner, [$member->id]);

    Assignee::create([
        'task_id'     => $task->id,
        'user_id'     => $member->id,
        'assigned_by' => $owner->id,
    ]);

    $this->actingAs($owner)->post(route('attachments.store', $task), [
        'file' => UploadedFile::fake()->createWithContent('readme.txt', 'hello'),
    ])->assertCreated();

    $this->actingAs($owner)
        ->getJson(route('tasks.show', $task))
        ->assertOk()
        ->assertJsonPath('assignees.0.assigned_by', $owner->id)
        ->assertJsonPath('assignees.0.assigner.id', $owner->id)
        ->assertJsonPath('can_manage', true)
        ->assertJsonCount(2, 'assignable_users')
        ->assertJsonStructure([
            'attachments' => [
                ['id', 'name', 'path', 'url', 'size'],
            ],
        ]);
});
