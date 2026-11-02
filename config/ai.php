<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Assistant Identity
    |--------------------------------------------------------------------------
    |
    | These defaults are used until an administrator overrides them in
    | Admin → AI Agent. The name is shown in the chat UI and is injected into
    | the system prompt so the assistant introduces itself consistently.
    |
    */

    'name' => env('AI_ASSISTANT_NAME', 'Luna'),

    'persona' => env(
        'AI_ASSISTANT_PERSONA',
        'Warm, concise, and practical. Be direct, confirm what was done, and ask a short follow-up question only when a detail is genuinely missing.',
    ),

    /*
    |--------------------------------------------------------------------------
    | Default App Knowledge
    |--------------------------------------------------------------------------
    |
    | Authoritative rules injected into the system prompt. Administrators can
    | override these in Admin → AI Agent. Keep this list short and factual.
    |
    */

    'knowledge' => env('AI_ASSISTANT_KNOWLEDGE', implode("\n", [
        '- New projects start with four board lists: To Do, On Progress, Revision, Done. Done marks completion.',
        '- Task priorities are low, medium, high, and critical. Use medium when the user does not specify one.',
        '- Only project or workspace managers may change a project timeline.',
        '- The assistant cannot delete data; ask the user to delete from the app UI.',
        '- Use parent_id only for tasks in the same project to create subtasks.',
    ])),

];
