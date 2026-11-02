<?php

use App\Models\BoardList;
use App\Models\Comments;
use App\Models\Project;
use App\Models\Setting;
use App\Models\Task;
use App\Models\User;
use App\Models\Workspace;
use App\Services\AiSettings;
use App\Services\MembershipService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

function aiWorkspace(User $owner): Workspace
{
    $workspace = Workspace::create([
        'name'    => 'AI Workspace',
        'slug'    => 'ai-ws-'.Str::random(6),
        'user_id' => $owner->id,
    ]);

    app(MembershipService::class)->addToWorkspace($workspace, $owner, 'owner');

    return $workspace;
}

function aiProject(Workspace $workspace, User $owner): array
{
    $project = Project::create([
        'title'        => 'AI Project',
        'user_id'      => $owner->id,
        'workspace_id' => $workspace->id,
        'start_date'   => now()->toDateString(),
        'end_date'     => now()->addMonth()->toDateString(),
    ]);

    app(MembershipService::class)->addToProject($project, $owner, 'manager');

    $list = BoardList::create([
        'title'      => 'To Do',
        'project_id' => $project->id,
        'user_id'    => $owner->id,
        'order'      => 0,
    ]);

    return [$project, $list];
}

test('AI settings are encrypted and only admins can manage them', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $user = User::factory()->create(['role' => 'user']);

    $this->actingAs($user)->get(route('admin.ai.index'))->assertForbidden();

    $this->actingAs($admin)->get(route('admin.ai.index'))->assertOk()->assertSee('OpenRouter');

    $this->actingAs($admin)
        ->post(route('admin.ai.update'), [
            'provider' => 'openrouter',
            'model'    => 'google/gemma-4-26b-a4b-it:free',
            'api_key'  => 'sk-test-secret-key',
            'enabled'  => 1,
        ])
        ->assertRedirect();

    expect(AiSettings::apiKey())->toBe('sk-test-secret-key')
        ->and(AiSettings::model())->toBe('google/gemma-4-26b-a4b-it:free')
        ->and(AiSettings::isConfigured())->toBeTrue();

    $rawKey = Setting::where('slug', 'ai_api_key')->value('value');
    expect($rawKey)->not->toBe('sk-test-secret-key');
});

test('AI reports not configured when disabled', function () {
    $user = User::factory()->create(['role' => 'user']);

    AiSettings::save('openrouter', 'google/gemma-4-26b-a4b-it:free', false);

    $this->actingAs($user)
        ->postJson(route('ai.chat'), ['message' => 'Create a workspace called AI'])
        ->assertStatus(503);
});

test('the AI agent executes app actions returned by OpenRouter', function () {
    Http::preventStrayRequests();

    $user = User::factory()->create(['role' => 'user']);
    $workspace = aiWorkspace($user);
    [$project, $list] = aiProject($workspace, $user);

    $existing = Task::create([
        'title'      => 'Existing task',
        'list_id'    => $list->id,
        'project_id' => $project->id,
        'user_id'    => $user->id,
        'order'      => 1,
    ]);

    AiSettings::save('openrouter', 'google/gemma-4-26b-a4b-it:free', true);
    AiSettings::saveApiKey('sk-test-agent-key');

    $actions = [
        [
            'type'       => 'create_task',
            'project_id' => $project->id,
            'list_id'    => $list->id,
            'title'      => 'AI generated task',
            'due_date'   => now()->addDay()->toDateString(),
            'priority'   => 'high',
        ],
        [
            'type'    => 'assign_user',
            'task_id' => $existing->id,
            'user_id' => $user->id,
        ],
        [
            'type'        => 'add_comment',
            'task_id'     => $existing->id,
            'details'     => 'Comment from the AI agent',
        ],
        [
            'type'        => 'update_project_timeline',
            'project_id'  => $project->id,
            'start_date'  => now()->toDateString(),
            'end_date'    => now()->addDays(14)->toDateString(),
        ],
    ];

    Http::fake([
        'openrouter.ai/*' => Http::response([
            'choices' => [
                [
                    'message' => [
                        'content' => json_encode([
                            'reply'   => 'Created the task and updated the project.',
                            'actions' => $actions,
                        ]),
                    ],
                ],
            ],
        ], 200),
    ]);

    $this->actingAs($user)
        ->postJson(route('ai.chat'), ['message' => 'Create a task and assign it to me'])
        ->assertOk()
        ->assertJsonPath('reply', 'Created the task and updated the project.')
        ->assertJsonCount(4, 'results')
        ->assertJsonPath('results.0.status', 'ok')
        ->assertJsonPath('results.1.status', 'ok')
        ->assertJsonPath('results.2.status', 'ok')
        ->assertJsonPath('results.3.status', 'ok');

    $created = Task::where('title', 'AI generated task')->firstOrFail();
    expect($created->priority)->toBe('high')
        ->and($created->due_date?->toDateString())->toBe(now()->addDay()->toDateString());

    $this->assertDatabaseHas('assignees', [
        'task_id'     => $existing->id,
        'user_id'     => $user->id,
        'assigned_by' => $user->id,
    ]);

    $this->assertDatabaseHas('comments', [
        'task_id' => $existing->id,
        'details' => 'Comment from the AI agent',
    ]);

    expect($project->fresh()->end_date?->toDateString())->toBe(now()->addDays(14)->toDateString());

    Http::assertSent(function ($request) {
        return str_contains($request->url(), 'openrouter.ai')
            && data_get($request->data(), 'model') === 'google/gemma-4-26b-a4b-it:free'
            && data_get($request->data(), 'max_tokens') === 3000
            && str_contains((string) data_get($request->data(), 'messages.0.content'), 'You are "Luna"');
    });
});

test('the AI agent retries with the fallback model when the primary is rate-limited', function () {
    Http::preventStrayRequests();
    config(['services.openrouter.fallback_model' => 'fallback/model:free']);

    $user = User::factory()->create(['role' => 'user']);
    $workspace = aiWorkspace($user);
    [$project, $list] = aiProject($workspace, $user);

    AiSettings::save('openrouter', 'primary/model:free', true);
    AiSettings::saveApiKey('sk-test-agent-key');

    Http::fakeSequence('openrouter.ai/*')
        ->push([
            'error' => [
                'message' => 'Provider returned error',
                'code'    => 429,
            ],
        ], 429)
        ->push([
            'choices' => [[
                'message' => [
                    'content' => json_encode([
                        'reply'   => 'Created it with the fallback model.',
                        'actions' => [[
                            'type'       => 'create_task',
                            'project_id' => $project->id,
                            'list_id'    => $list->id,
                            'title'      => 'Fallback task',
                        ]],
                    ]),
                ],
            ]],
        ], 200);

    $this->actingAs($user)
        ->postJson(route('ai.chat'), ['message' => 'Create a fallback task'])
        ->assertOk()
        ->assertJsonPath('results.0.status', 'ok');

    $this->assertDatabaseHas('tasks', ['title' => 'Fallback task']);

    Http::assertSentCount(2);
    Http::assertSent(fn ($request) => data_get($request->data(), 'model') === 'fallback/model:free');
});

test('the AI agent reports rate limiting instead of blaming the API key', function () {
    Http::preventStrayRequests();
    config(['services.openrouter.fallback_model' => null]);

    $user = User::factory()->create(['role' => 'user']);

    AiSettings::save('openrouter', 'google/gemma-4-26b-a4b-it:free', true);
    AiSettings::saveApiKey('sk-test-agent-key');

    Http::fake([
        'openrouter.ai/*' => Http::response([
            'error' => [
                'message' => 'google/gemma-4-26b-a4b-it:free is temporarily rate-limited upstream.',
                'code'    => 429,
            ],
        ], 429),
    ]);

    $this->actingAs($user)
        ->postJson(route('ai.chat'), ['message' => 'Create a task'])
        ->assertOk()
        ->assertJsonPath('results', [],)
        ->assertJsonPath('actions', [])
        ->assertSee('rate-limited');
});

test('admins can customize the assistant persona and knowledge notes', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)
        ->post(route('admin.ai.update'), [
            'provider'  => 'openrouter',
            'model'     => 'google/gemma-4-26b-a4b-it:free',
            'api_key'   => 'sk-test-secret-key',
            'enabled'   => 1,
            'name'      => 'Luna',
            'persona'   => 'Warm and concise.',
            'knowledge' => '- New projects start with four lists.\n- Priorities are low, medium, high, critical.',
        ])
        ->assertRedirect();

    expect(AiSettings::name())->toBe('Luna')
        ->and(AiSettings::persona())->toBe('Warm and concise.')
        ->and(AiSettings::knowledge())->toContain('four lists')
        ->and(AiSettings::knowledge())->toContain('critical');
});

test('the system prompt carries identity, admin knowledge, capabilities, and model facts', function () {
    Http::preventStrayRequests();
    config(['services.openrouter.fallback_model' => 'nvidia/nemotron-3-super-120b-a12b:free']);

    $user = User::factory()->create(['role' => 'user']);
    aiWorkspace($user);

    AiSettings::save('openrouter', 'google/gemma-4-26b-a4b-it:free', true);
    AiSettings::saveApiKey('sk-test-agent-key');
    AiSettings::saveAssistant(
        'Luna',
        'Warm and concise.',
        'New projects always start with four board lists.',
    );

    Http::fake([
        'openrouter.ai/*' => Http::response([
            'choices' => [[
                'message' => [
                    'content' => json_encode([
                        'reply'   => "I'm Luna. I can help you create tasks.",
                        'actions' => [],
                    ]),
                ],
            ]],
        ], 200),
    ]);

    $this->actingAs($user)
        ->postJson(route('ai.chat'), ['message' => 'What can you do?'])
        ->assertOk();

    Http::assertSent(function ($request) {
        $system = (string) data_get($request->data(), 'messages.0.content');

        return str_contains($system, 'You are "Luna"')
            && str_contains($system, 'Warm and concise.')
            && str_contains($system, 'New projects always start with four board lists.')
            && str_contains($system, 'Gemma (Google)')
            && str_contains($system, 'Nemotron (NVIDIA)')
            && str_contains($system, 'assign project members to tasks')
            && str_contains($system, 'EXAMPLES');
    });
});

test('the AI agent reports a provider timeout without crashing', function () {
    Http::preventStrayRequests();
    config(['services.openrouter.fallback_model' => null]);

    $user = User::factory()->create(['role' => 'user']);

    AiSettings::save('openrouter', 'google/gemma-4-26b-a4b-it:free', true);
    AiSettings::saveApiKey('sk-test-agent-key');

    Http::fake([
        'openrouter.ai/*' => fn () => throw new \Illuminate\Http\Client\ConnectionException(
            'cURL error 28: Operation timed out after 75001 milliseconds'
        ),
    ]);

    $this->actingAs($user)
        ->postJson(route('ai.chat'), ['message' => 'Create a task'])
        ->assertOk()
        ->assertSee('took too long');
});

test('the AI agent remembers previous turns on the server', function () {
    Http::preventStrayRequests();

    $user = User::factory()->create(['role' => 'user']);
    aiWorkspace($user);

    AiSettings::save('openrouter', 'google/gemma-4-26b-a4b-it:free', true);
    AiSettings::saveApiKey('sk-test-agent-key');

    Http::fakeSequence('openrouter.ai/*')
        ->push([
            'choices' => [[
                'message' => [
                    'content' => json_encode(['reply' => 'First reply', 'actions' => []]),
                ],
            ]],
        ], 200)
        ->push([
            'choices' => [[
                'message' => [
                    'content' => json_encode(['reply' => 'Second reply', 'actions' => []]),
                ],
            ]],
        ], 200);

    $this->actingAs($user)
        ->postJson(route('ai.chat'), ['message' => 'First message'])
        ->assertOk()
        ->assertJsonPath('reply', 'First reply');

    $this->actingAs($user)
        ->postJson(route('ai.chat'), ['message' => 'Second message'])
        ->assertOk()
        ->assertJsonPath('reply', 'Second reply');

    $this->assertDatabaseHas('ai_messages', [
        'user_id' => $user->id,
        'role'    => 'user',
        'content' => 'First message',
    ]);

    $this->assertDatabaseHas('ai_messages', [
        'user_id' => $user->id,
        'role'    => 'assistant',
        'content' => 'First reply',
    ]);

    $recorded = Http::recorded();
    $secondRequest = $recorded[1][0];
    $contents = collect(data_get($secondRequest->data(), 'messages'))->pluck('content');

    expect($contents)->toContain('First message')
        ->and($contents)->toContain('First reply')
        ->and($contents)->toContain('Second message');
});

test('failed AI turns are not persisted as conversation history', function () {
    Http::preventStrayRequests();
    config(['services.openrouter.fallback_model' => null]);

    $user = User::factory()->create(['role' => 'user']);

    AiSettings::save('openrouter', 'google/gemma-4-26b-a4b-it:free', true);
    AiSettings::saveApiKey('sk-test-agent-key');

    Http::fake([
        'openrouter.ai/*' => Http::response(['error' => ['message' => 'Server error']], 500),
    ]);

    $this->actingAs($user)
        ->postJson(route('ai.chat'), ['message' => 'This should not be saved'])
        ->assertOk();

    $this->assertDatabaseCount('ai_messages', 0);
});

test('the AI agent retries when the primary returns empty content', function () {
    Http::preventStrayRequests();
    config(['services.openrouter.fallback_model' => 'fallback/model:free']);

    $user = User::factory()->create(['role' => 'user']);

    AiSettings::save('openrouter', 'google/gemma-4-26b-a4b-it:free', true);
    AiSettings::saveApiKey('sk-test-agent-key');

    Http::fakeSequence('openrouter.ai/*')
        ->push(['choices' => [['message' => ['content' => '']]]], 200)
        ->push([
            'choices' => [[
                'message' => [
                    'content' => json_encode(['reply' => 'Recovered on fallback', 'actions' => []]),
                ],
            ]],
        ], 200);

    $this->actingAs($user)
        ->postJson(route('ai.chat'), ['message' => 'Create a task'])
        ->assertOk()
        ->assertJsonPath('reply', 'Recovered on fallback');

    Http::assertSentCount(2);
    Http::assertSent(fn ($request) => data_get($request->data(), 'model') === 'fallback/model:free');
});

test('the AI agent reports an empty model response', function () {
    Http::preventStrayRequests();
    config(['services.openrouter.fallback_model' => null]);

    $user = User::factory()->create(['role' => 'user']);

    AiSettings::save('openrouter', 'google/gemma-4-26b-a4b-it:free', true);
    AiSettings::saveApiKey('sk-test-agent-key');

    Http::fake([
        'openrouter.ai/*' => Http::response(['choices' => [['message' => ['content' => '']]]], 200),
    ]);

    $this->actingAs($user)
        ->postJson(route('ai.chat'), ['message' => 'Create a task'])
        ->assertOk()
        ->assertSee('empty response');
});

test('AI cannot act on projects the user cannot access', function () {
    Http::preventStrayRequests();

    $user = User::factory()->create(['role' => 'user']);
    $other = User::factory()->create(['role' => 'user']);
    $otherWorkspace = aiWorkspace($other);
    [$otherProject, $otherList] = aiProject($otherWorkspace, $other);

    AiSettings::save('openrouter', 'google/gemma-4-26b-a4b-it:free', true);
    AiSettings::saveApiKey('sk-test-agent-key');

    Http::fake([
        'openrouter.ai/*' => Http::response([
            'choices' => [
                [
                    'message' => [
                        'content' => json_encode([
                            'reply'   => 'Trying.',
                            'actions' => [[
                                'type'       => 'create_task',
                                'project_id' => $otherProject->id,
                                'list_id'    => $otherList->id,
                                'title'      => 'Should not exist',
                            ]],
                        ]),
                    ],
                ],
            ],
        ], 200),
    ]);

    $this->actingAs($user)
        ->postJson(route('ai.chat'), ['message' => 'Create a task in the other project'])
        ->assertOk()
        ->assertJsonPath('results.0.status', 'error');

    $this->assertDatabaseMissing('tasks', ['title' => 'Should not exist']);
});
