<?php

namespace App\Support;

class AiCapabilities
{
    /**
     * Short, user-facing descriptions used in the assistant UI and when the
     * user asks "What can you do?". Keep these aligned with the action schema.
     *
     * @var array<int, string>
     */
    public const SUMMARY = [
        'create workspaces, projects, and board columns',
        'add tasks with due dates, priorities, and subtasks',
        'assign project members to tasks',
        'organize work with comments and labels',
        'update task details and project timelines',
    ];

    /**
     * Action schema injected into the system prompt. This is the single source
     * of truth: the model may only claim capabilities listed here, and
     * AiAgentService::executeAction() implements them.
     */
    public const ACTIONS_PROMPT = <<<'PROMPT'
1. create_workspace
   { "type": "create_workspace", "name": "string", "description": "string|null" }
2. create_project
   { "type": "create_project", "workspace_id": number, "title": "string", "description": "string|null", "start_date": "YYYY-MM-DD|null", "end_date": "YYYY-MM-DD|null" }
3. create_board_list
   { "type": "create_board_list", "project_id": number, "title": "string", "is_done": boolean }
4. create_task
   { "type": "create_task", "project_id": number, "list_id": number|null, "title": "string", "description": "string|null", "due_date": "YYYY-MM-DD|null", "priority": "low|medium|high|critical|null", "parent_id": number|null, "assignee_ids": [number] }
5. update_task
   { "type": "update_task", "task_id": number, "title": "string?", "description": "string|null?", "list_id": number?, "due_date": "YYYY-MM-DD|null?", "priority": "low|medium|high|critical?", "is_done": boolean?, "parent_id": number|null? }
6. assign_user
   { "type": "assign_user", "task_id": number, "user_id": number|string }
7. add_comment
   { "type": "add_comment", "task_id": number, "details": "string" }
8. create_label
   { "type": "create_label", "task_id": number, "name": "string", "color": "#rrggbb" }
9. update_project_timeline
   { "type": "update_project_timeline", "project_id": number, "start_date": "YYYY-MM-DD|null", "end_date": "YYYY-MM-DD|null" }
PROMPT;

    public static function prompt(): string
    {
        return self::ACTIONS_PROMPT;
    }

    /**
     * @return array<int, string>
     */
    public static function summary(): array
    {
        return self::SUMMARY;
    }

    /**
     * Natural-language summary, e.g. "create workspaces..., create tasks...
     * and update task details and project timelines".
     */
    public static function sentence(): string
    {
        $items = array_map(static fn (string $item) => lcfirst($item), self::SUMMARY);
        $last = array_pop($items);

        return implode(', ', $items).' and '.$last;
    }
}
