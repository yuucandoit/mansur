<?php

namespace App\Services;

use App\Models\AiMessage;
use App\Models\Assignee;
use App\Models\BoardList;
use App\Models\Comments;
use App\Models\Labels;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Models\Workspace;
use App\Support\AiCapabilities;
use App\Support\ModelLabel;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AiAgentService
{
    public function __construct(
        private OpenRouterService $client,
        private MembershipService $membership,
    ) {
    }

    /**
     * Run one AI conversation turn and execute any requested actions.
     */
    public function chat(User $user, string $message): array
    {
        // Free OpenRouter models can take longer than PHP's default 30s web
        // execution limit, especially when a request falls back to a slower
        // provider. Allow enough headroom for the HTTP timeout to be handled
        // gracefully instead of aborting with a fatal error.
        @set_time_limit(90);

        if (! AiSettings::isConfigured()) {
            return [
                'reply'   => 'AI is not configured yet. Ask an administrator to add an OpenRouter API key in Admin → AI Agent.',
                'actions' => [],
                'results' => [],
            ];
        }

        $context = $this->context($user);

        $messages = [
            ['role' => 'system', 'content' => $this->systemPrompt($context)],
        ];

        foreach ($this->historyFor($user) as $item) {
            $messages[] = $item;
        }

        $messages[] = ['role' => 'user', 'content' => $message];

        $raw = $this->client->chat($messages, null, 0.2);

        if (! $raw) {
            // Do not persist failed turns: they would pollute the next prompt.
            return [
                'reply'   => $this->failureMessage(),
                'actions' => [],
                'results' => [],
            ];
        }

        $parsed = $this->parseResponse($raw);

        if ($parsed === null) {
            $reply = trim($raw);
            $this->remember($user, $message, $reply);

            return [
                'reply'   => $reply,
                'actions' => [],
                'results' => [],
            ];
        }

        $actions = is_array($parsed['actions'] ?? null) ? $parsed['actions'] : [];
        $results = [];

        foreach ($actions as $action) {
            if (! is_array($action)) {
                continue;
            }

            $results[] = $this->executeAction($user, $action);
        }

        $reply = (string) ($parsed['reply'] ?? ($results ? 'Done.' : 'I need a bit more detail to continue.'));

        $this->remember($user, $message, $reply, $actions);

        return [
            'reply'   => $reply,
            'actions' => $actions,
            'results' => $results,
        ];
    }

    /**
     * Last few turns for this user, oldest first. This replaces the old
     * browser-supplied history, so a refresh no longer erases context.
     *
     * @return array<int, array{role: string, content: string}>
     */
    private function historyFor(User $user): array
    {
        return AiMessage::query()
            ->where('user_id', $user->id)
            ->orderByDesc('id')
            ->limit(20)
            ->get(['role', 'content'])
            ->reverse()
            ->map(fn (AiMessage $message) => [
                'role'    => $message->role,
                'content' => mb_substr((string) $message->content, 0, 4000),
            ])
            ->values()
            ->all();
    }

    /**
     * Persist one successful turn and keep only the most recent messages.
     *
     * @param  array<int, mixed>  $actions
     */
    private function remember(User $user, string $message, string $reply, array $actions = []): void
    {
        try {
            AiMessage::create([
                'user_id' => $user->id,
                'role'    => 'user',
                'content' => $message,
            ]);

            AiMessage::create([
                'user_id' => $user->id,
                'role'    => 'assistant',
                'content' => $reply,
                'actions' => $actions ?: null,
            ]);

            $keep = AiMessage::query()
                ->where('user_id', $user->id)
                ->orderByDesc('id')
                ->limit(50)
                ->pluck('id')
                ->all();

            AiMessage::query()
                ->where('user_id', $user->id)
                ->whereNotIn('id', $keep)
                ->delete();
        } catch (\Throwable $e) {
            Log::warning('Could not persist AI conversation: '.$e->getMessage());
        }
    }

    /**
     * Compact context the model is allowed to reason about.
     */
    private function context(User $user): array
    {
        $workspaces = Workspace::forUser($user)
            ->with(['projects' => fn ($q) => $q->visibleTo($user)])
            ->orderBy('name')
            ->get();

        return [
            'today' => now()->toDateString(),
            'user'  => [
                'id'    => $user->id,
                'name'  => $user->name,
                'email' => $user->email,
            ],
            'workspaces' => $workspaces->map(function (Workspace $workspace) use ($user) {
                return [
                    'id'   => $workspace->id,
                    'name' => $workspace->name,
                    'role' => $user->workspaceRole($workspace),
                    'projects' => $workspace->projects->map(function (Project $project) {
                        return [
                            'id'         => $project->id,
                            'title'      => $project->title,
                            'description'=> Str::limit((string) $project->description, 300),
                            'start_date' => $project->start_date?->toDateString(),
                            'end_date'   => $project->end_date?->toDateString(),
                            'lists'      => $project->activeLists()->get(['id', 'title', 'is_done'])->toArray(),
                            'members'    => $project->assignableUsers()
                                ->map(fn (User $u) => ['id' => $u->id, 'name' => $u->name, 'email' => $u->email])
                                ->values()
                                ->all(),
                            'tasks' => $project->tasks()
                                ->where('is_archive', 0)
                                ->orderByDesc('updated_at')
                                ->limit(25)
                                ->get(['id', 'title', 'list_id', 'due_date', 'priority', 'is_done', 'parent_id'])
                                ->map(fn (Task $t) => [
                                    'id'       => $t->id,
                                    'title'    => $t->title,
                                    'list_id'  => $t->list_id,
                                    'due_date' => $t->due_date?->toDateString(),
                                    'priority' => $t->priority,
                                    'is_done'  => (bool) $t->is_done,
                                    'parent_id'=> $t->parent_id,
                                ])
                                ->values()
                                ->all(),
                        ];
                    })->values()->all(),
                ];
            })->values()->all(),
        ];
    }

    private function systemPrompt(array $context): string
    {
        $contextJson = json_encode($context, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $today = now()->toDateString();

        $assistant = AiSettings::name();
        $persona = trim(AiSettings::persona());
        $knowledge = trim(AiSettings::knowledge());
        $capabilities = AiCapabilities::sentence();
        $actions = AiCapabilities::prompt();

        $primaryModel = AiSettings::model();
        $primaryLabel = ModelLabel::for($primaryModel);
        $fallbackModel = (string) config('services.openrouter.fallback_model');
        $fallbackLabel = $fallbackModel !== '' ? ModelLabel::for($fallbackModel) : null;

        $personaBlock = $persona !== '' ? $persona : 'Warm, concise, and practical.';

        $deployment = "- This deployment primarily runs on {$primaryLabel} (`{$primaryModel}`).";

        if ($fallbackLabel) {
            $deployment .= "\n- If that model is rate-limited or unavailable, requests automatically retry on {$fallbackLabel} (`{$fallbackModel}`).";
        }

        $deployment .= "\n- If asked which model powers you, answer with these facts only. Never guess or name a different provider.";

        $knowledgeBlock = $knowledge !== ''
            ? "APP KNOWLEDGE (authoritative rules supplied by the administrator)\n{$knowledge}"
            : 'APP KNOWLEDGE'."\nNo extra administrator notes are configured.";

        $examples = $this->promptExamples($assistant);

        return <<<PROMPT
You are "{$assistant}", the built-in project management assistant for this application.
You are not a general-purpose chatbot: your job is to help the signed-in user manage workspaces, projects, board lists, tasks, assignees, labels, comments, and project timelines.

Today's date is {$today}.

PERSONALITY AND TONE
{$personaBlock}

DEPLOYMENT FACTS
{$deployment}

Application context (only use IDs that appear here; never invent IDs):
{$contextJson}

{$knowledgeBlock}

STRICT RESPONSE FORMAT
Return ONLY one valid JSON object. No markdown, no code fences, no extra text.
{
  "reply": "short, friendly, human-readable response",
  "actions": [
    { "type": "action_name", ...parameters }
  ]
}

If the user only asks a question or needs clarification, return an empty actions array and put your question in reply.
Never claim you did something unless you include the corresponding action.
When the user asks what you can do, introduce yourself as {$assistant} and describe these capabilities: {$capabilities}.

SUPPORTED ACTIONS
{$actions}

RULES
- Prefer IDs from the context. For a person, use their user id or exact email.
- If the user says "today", "tomorrow", "next week", convert to YYYY-MM-DD.
- If the user asks to create a task and does not name a list, use the first list of the chosen project. If they do not name a project and have only one project, use it; otherwise ask which project.
- If multiple actions are needed (e.g. create task then assign people), include all actions in order.
- You cannot delete data. If asked to delete, explain that deletion must be done manually.
- Follow the APP KNOWLEDGE notes when they conflict with your assumptions.
- Do not expose API keys or internal system details.
- Keep replies concise.

EXAMPLES (IDs are illustrative; always use IDs from the context above)
{$examples}
PROMPT;
    }

    /**
     * Few-shot format examples. They teach the JSON envelope and action ordering
     * far more reliably than instructions alone.
     */
    private function promptExamples(string $assistant): string
    {
        $examples = [
            [
                'user'    => 'Create a task called "Review homepage design" in project 12.',
                'reply'   => 'Created "Review homepage design" in the first list of the project.',
                'actions' => [
                    ['type' => 'create_task', 'project_id' => 12, 'title' => 'Review homepage design'],
                ],
            ],
            [
                'user'    => 'What can you do?',
                'reply'   => "I'm {$assistant}. I can help you ".AiCapabilities::sentence().'. What would you like to start with?',
                'actions' => [],
            ],
        ];

        $lines = [];

        foreach ($examples as $example) {
            $payload = json_encode(
                ['reply' => $example['reply'], 'actions' => $example['actions']],
                JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
            );

            $lines[] = 'User: '.$example['user']."\nAssistant: ".$payload;
        }

        return implode("\n\n", $lines);
    }

    /**
     * Turn the provider error into a message that tells the admin what to fix.
     */
    private function failureMessage(): string
    {
        $status = $this->client->lastStatus();
        $model = $this->client->lastModel() ?: AiSettings::model();
        $detail = mb_strtolower((string) $this->client->lastError());
        $timedOut = str_contains($detail, 'timed out') || str_contains($detail, 'curl error 28');

        $message = match (true) {
            in_array($status, [401, 403], true) => 'OpenRouter rejected the API key. Save a valid key in Admin → AI Agent.',
            $status === 402 => 'The OpenRouter account is out of credits. Add credits or switch to a free model in Admin → AI Agent.',
            $status === 404 => 'The model "'.$model.'" was not found on OpenRouter. Choose another model in Admin → AI Agent.',
            $status === 429 => 'The AI model "'.$model.'" is rate-limited right now. Retry in a minute, or choose a different model in Admin → AI Agent.',
            $status !== null && $status >= 500 => 'OpenRouter is temporarily unavailable. Please try again shortly.',
            $status === 200 => 'The AI model returned an empty response. Please retry, or choose a different model in Admin → AI Agent.',
            $timedOut => 'The AI provider took too long to respond. Please retry, or choose a faster model in Admin → AI Agent.',
            default => 'I could not reach the AI provider. Check the API key and model in Admin → AI Agent.',
        };

        if ($status !== null && $status >= 500 && filled($this->client->lastError())) {
            $message .= ' ('.$status.')';
        }

        return $message;
    }

    private function executeAction(User $user, array $action): array
    {
        $type = (string) ($action['type'] ?? 'unknown');

        try {
            return match ($type) {
                'create_workspace'       => $this->createWorkspace($user, $action),
                'create_project'         => $this->createProject($user, $action),
                'create_board_list'      => $this->createBoardList($user, $action),
                'create_task'            => $this->createTask($user, $action),
                'update_task'            => $this->updateTask($user, $action),
                'assign_user'            => $this->assignUser($user, $action),
                'add_comment'            => $this->addComment($user, $action),
                'create_label'           => $this->createLabel($user, $action),
                'update_project_timeline'=> $this->updateProjectTimeline($user, $action),
                default                  => [
                    'type'    => $type,
                    'status'  => 'error',
                    'message' => 'Unknown action.',
                ],
            };
        } catch (\Throwable $e) {
            return [
                'type'    => $type,
                'status'  => 'error',
                'message' => $e->getMessage(),
            ];
        }
    }

    private function createWorkspace(User $user, array $action): array
    {
        $name = trim((string) ($action['name'] ?? ''));

        if ($name === '') {
            throw new \InvalidArgumentException('Workspace name is required.');
        }

        $workspace = Workspace::create([
            'name'        => $name,
            'slug'        => Str::slug($name).'-'.time(),
            'description' => $action['description'] ?? null,
            'user_id'     => $user->id,
        ]);

        $this->membership->addToWorkspace($workspace, $user, 'owner');

        return [
            'type'    => 'create_workspace',
            'status'  => 'ok',
            'message' => 'Workspace "'.$workspace->name.'" created.',
            'data'    => ['id' => $workspace->id, 'name' => $workspace->name],
        ];
    }

    private function createProject(User $user, array $action): array
    {
        $workspace = $this->workspaceFor($user, $action['workspace_id'] ?? null);
        $title = trim((string) ($action['title'] ?? ''));

        if ($title === '') {
            throw new \InvalidArgumentException('Project title is required.');
        }

        $project = Project::create([
            'title'        => $title,
            'slug'         => Str::slug($title).'-'.time(),
            'description'  => $action['description'] ?? null,
            'workspace_id' => $workspace->id,
            'user_id'      => $user->id,
            'start_date'   => $this->dateOrNull($action['start_date'] ?? null),
            'end_date'     => $this->dateOrNull($action['end_date'] ?? null),
        ]);

        $this->membership->addToProject($project, $user, 'manager');

        foreach (['To Do', 'On Progress', 'Revision', 'Done'] as $index => $listTitle) {
            BoardList::create([
                'title'      => $listTitle,
                'project_id' => $project->id,
                'user_id'    => $user->id,
                'order'      => $index,
                'is_done'    => $listTitle === 'Done' ? 1 : 0,
            ]);
        }

        return [
            'type'    => 'create_project',
            'status'  => 'ok',
            'message' => 'Project "'.$project->title.'" created in '.$workspace->name.'.',
            'data'    => ['id' => $project->id, 'title' => $project->title, 'workspace_id' => $workspace->id],
        ];
    }

    private function createBoardList(User $user, array $action): array
    {
        $project = $this->projectFor($user, $action['project_id'] ?? null);
        $title = trim((string) ($action['title'] ?? ''));

        if ($title === '') {
            throw new \InvalidArgumentException('List title is required.');
        }

        $list = BoardList::create([
            'title'      => $title,
            'project_id' => $project->id,
            'user_id'    => $user->id,
            'order'      => BoardList::where('project_id', $project->id)->count(),
            'is_done'    => (bool) ($action['is_done'] ?? false),
        ]);

        return [
            'type'    => 'create_board_list',
            'status'  => 'ok',
            'message' => 'Column "'.$list->title.'" created.',
            'data'    => ['id' => $list->id, 'title' => $list->title],
        ];
    }

    private function createTask(User $user, array $action): array
    {
        $project = $this->projectFor($user, $action['project_id'] ?? null);
        $title = trim((string) ($action['title'] ?? ''));

        if ($title === '') {
            throw new \InvalidArgumentException('Task title is required.');
        }

        $list = null;

        if (! empty($action['list_id'])) {
            $list = BoardList::where('project_id', $project->id)->find((int) $action['list_id']);

            if (! $list) {
                throw new \InvalidArgumentException('That board list does not belong to the project.');
            }
        } else {
            $list = $project->activeLists()->first();

            if (! $list) {
                $list = BoardList::create([
                    'title'      => 'To Do',
                    'project_id' => $project->id,
                    'user_id'    => $user->id,
                    'order'      => 0,
                ]);
            }
        }

        $parentId = null;

        if (! empty($action['parent_id'])) {
            $parent = Task::where('project_id', $project->id)->find((int) $action['parent_id']);
            $parentId = $parent?->id;
        }

        $task = Task::create([
            'title'       => $title,
            'slug'        => Str::slug($title).'-'.time(),
            'description' => $action['description'] ?? null,
            'list_id'     => $list->id,
            'project_id'  => $project->id,
            'user_id'     => $user->id,
            'order'       => Task::where('list_id', $list->id)->count() + 1,
            'due_date'    => $this->dateOrNull($action['due_date'] ?? null),
            'priority'    => $this->priority($action['priority'] ?? null),
            'parent_id'   => $parentId,
        ]);

        $assigned = [];

        foreach ((array) ($action['assignee_ids'] ?? []) as $assigneeValue) {
            try {
                $assigneeId = $this->resolveAssigneeId($assigneeValue, $project);
                Assignee::firstOrCreate(
                    ['task_id' => $task->id, 'user_id' => $assigneeId],
                    ['assigned_by' => $user->id],
                );
                $assigned[] = $assigneeId;
            } catch (\Throwable) {
                // Ignore invalid assignees; the task itself still gets created.
            }
        }

        return [
            'type'    => 'create_task',
            'status'  => 'ok',
            'message' => 'Task "'.$task->title.'" created'.($assigned ? ' and assigned to '.count($assigned).' member(s)' : '').'.',
            'data'    => [
                'id'         => $task->id,
                'title'      => $task->title,
                'project_id' => $project->id,
                'list_id'    => $list->id,
                'assignees'  => $assigned,
            ],
        ];
    }

    private function updateTask(User $user, array $action): array
    {
        $task = $this->taskFor($user, $action['task_id'] ?? null);
        $updates = [];

        if (isset($action['title'])) {
            $title = trim((string) $action['title']);
            if ($title !== '') {
                $updates['title'] = $title;
            }
        }

        if (array_key_exists('description', $action)) {
            $updates['description'] = $action['description'];
        }

        if (! empty($action['list_id'])) {
            $list = BoardList::where('project_id', $task->project_id)->find((int) $action['list_id']);

            if (! $list) {
                throw new \InvalidArgumentException('That board list does not belong to the task project.');
            }

            $updates['list_id'] = $list->id;
        }

        if (array_key_exists('due_date', $action)) {
            $updates['due_date'] = $this->dateOrNull($action['due_date']);
        }

        if (! empty($action['priority'])) {
            $updates['priority'] = $this->priority($action['priority']);
        }

        if (array_key_exists('is_done', $action)) {
            $updates['is_done'] = (bool) $action['is_done'];
        }

        if (array_key_exists('parent_id', $action)) {
            if (empty($action['parent_id'])) {
                $updates['parent_id'] = null;
            } else {
                $parent = Task::where('project_id', $task->project_id)->find((int) $action['parent_id']);
                $updates['parent_id'] = $parent?->id;
            }
        }

        if ($updates) {
            $task->update($updates);
        }

        return [
            'type'    => 'update_task',
            'status'  => 'ok',
            'message' => 'Task "'.$task->title.'" updated.',
            'data'    => ['id' => $task->id, 'updated' => array_keys($updates)],
        ];
    }

    private function assignUser(User $user, array $action): array
    {
        $task = $this->taskFor($user, $action['task_id'] ?? null);
        $assigneeId = $this->resolveAssigneeId($action['user_id'] ?? null, $task->project);
        $assignee = User::findOrFail($assigneeId);

        Assignee::firstOrCreate(
            ['task_id' => $task->id, 'user_id' => $assignee->id],
            ['assigned_by' => $user->id],
        );

        return [
            'type'    => 'assign_user',
            'status'  => 'ok',
            'message' => $assignee->name.' assigned to "'.$task->title.'".',
            'data'    => ['task_id' => $task->id, 'user_id' => $assignee->id],
        ];
    }

    private function addComment(User $user, array $action): array
    {
        $task = $this->taskFor($user, $action['task_id'] ?? null);
        $details = trim((string) ($action['details'] ?? ''));

        if ($details === '') {
            throw new \InvalidArgumentException('Comment text is required.');
        }

        Comments::create([
            'task_id' => $task->id,
            'user_id' => $user->id,
            'details' => $details,
        ]);

        return [
            'type'    => 'add_comment',
            'status'  => 'ok',
            'message' => 'Comment added to "'.$task->title.'".',
            'data'    => ['task_id' => $task->id],
        ];
    }

    private function createLabel(User $user, array $action): array
    {
        $task = $this->taskFor($user, $action['task_id'] ?? null);
        $name = trim((string) ($action['name'] ?? ''));

        if ($name === '') {
            throw new \InvalidArgumentException('Label name is required.');
        }

        $label = Labels::create([
            'task_id' => $task->id,
            'name'    => $name,
            'color'   => $action['color'] ?? '#6366f1',
        ]);

        return [
            'type'    => 'create_label',
            'status'  => 'ok',
            'message' => 'Label "'.$label->name.'" added to "'.$task->title.'".',
            'data'    => ['id' => $label->id, 'task_id' => $task->id],
        ];
    }

    private function updateProjectTimeline(User $user, array $action): array
    {
        $project = $this->projectFor($user, $action['project_id'] ?? null);

        if (! $user->canManageProject($project)) {
            throw new \InvalidArgumentException('You do not have permission to change this project timeline.');
        }

        $project->update([
            'start_date' => $this->dateOrNull($action['start_date'] ?? null),
            'end_date'   => $this->dateOrNull($action['end_date'] ?? null),
        ]);

        return [
            'type'    => 'update_project_timeline',
            'status'  => 'ok',
            'message' => 'Timeline updated for "'.$project->title.'".',
            'data'    => [
                'project_id' => $project->id,
                'start_date' => $project->start_date?->toDateString(),
                'end_date'   => $project->end_date?->toDateString(),
            ],
        ];
    }

    private function workspaceFor(User $user, mixed $id): Workspace
    {
        $workspace = Workspace::forUser($user)->find((int) $id);

        if (! $workspace) {
            throw new \InvalidArgumentException('Workspace not found or not accessible.');
        }

        return $workspace;
    }

    private function projectFor(User $user, mixed $id): Project
    {
        $project = Project::visibleTo($user)->find((int) $id);

        if (! $project) {
            throw new \InvalidArgumentException('Project not found or not accessible.');
        }

        return $project;
    }

    private function taskFor(User $user, mixed $id): Task
    {
        $task = Task::with('project')->find((int) $id);

        if (! $task || ! $task->project || ! $user->canAccessProject($task->project)) {
            throw new \InvalidArgumentException('Task not found or not accessible.');
        }

        return $task;
    }

    private function resolveAssigneeId(mixed $value, Project $project): int
    {
        $users = $project->assignableUsers();

        if (is_numeric($value)) {
            $user = $users->firstWhere('id', (int) $value);
        } else {
            $email = mb_strtolower(trim((string) $value));
            $user = $users->first(fn (User $u) => mb_strtolower($u->email) === $email);
        }

        if (! $user) {
            throw new \InvalidArgumentException('That user cannot be assigned to this project.');
        }

        return $user->id;
    }

    private function dateOrNull(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            return Carbon::parse((string) $value)->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }

    private function priority(mixed $value): string
    {
        $priority = mb_strtolower((string) $value);

        return in_array($priority, ['low', 'medium', 'high', 'critical'], true)
            ? $priority
            : 'medium';
    }

    private function parseResponse(string $raw): ?array
    {
        $text = trim($raw);
        $text = preg_replace('/^```(?:json)?\s*|\s*```$/i', '', $text);

        $start = strpos($text, '{');
        $end = strrpos($text, '}');

        if ($start === false || $end === false || $end < $start) {
            return null;
        }

        $decoded = json_decode(substr($text, $start, $end - $start + 1), true);

        return is_array($decoded) ? $decoded : null;
    }
}
