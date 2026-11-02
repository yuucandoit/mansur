@extends('layouts.app')

@section('title', 'AI Agent Settings')

@section('content')
<div class="flex-1 overflow-y-auto bg-background">
    <div class="w-full max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">

        @include('admin.partials.header', [
            'title' => 'AI Agent',
            'subtitle' => 'Configure ' . $assistantName . ', its personality, and the app knowledge it should follow.',
            'icon' => 'sparkles',
        ])

        @include('admin.partials.flash')

        <!-- Status -->
        <div class="rounded-xl border {{ $configured ? 'border-emerald-500/25 bg-emerald-500/10' : 'border-amber-500/25 bg-amber-500/10' }} p-5">
            <div class="flex items-start gap-3">
                <i data-lucide="{{ $configured ? 'check-circle-2' : 'triangle-alert' }}" class="w-5 h-5 shrink-0 mt-0.5 {{ $configured ? 'text-emerald-600 dark:text-emerald-400' : 'text-amber-600 dark:text-amber-400' }}"></i>
                <div class="text-xs {{ $configured ? 'text-emerald-700 dark:text-emerald-400' : 'text-amber-700 dark:text-amber-400' }}">
                    <p class="font-semibold">{{ $configured ? 'AI agent is ready' : 'AI agent is not configured' }}</p>
                    <p class="mt-1 opacity-90">
                        @if($configured)
                        <strong>{{ $assistantName }}</strong> · Provider <strong>OpenRouter</strong> · Model <strong>{{ $model }}</strong> ({{ $primaryLabel }})
                        @if($fallbackLabel) · Fallback <strong>{{ $fallbackLabel }}</strong> @endif
                        @if($maskedKey) · Key <strong>{{ $maskedKey }}</strong> @endif
                        @else
                        Add an OpenRouter API key below so users can use {{ $assistantName }}.
                        @endif
                    </p>
                </div>
            </div>
        </div>

        <!-- Form -->
        <form method="POST" action="{{ route('admin.ai.update') }}" class="rounded-xl border border-border bg-card p-6 space-y-5">
            @csrf

            <div class="space-y-1.5">
                <label class="text-xs font-medium text-foreground">Provider</label>
                <select name="provider" class="form-select text-xs">
                    <option value="openrouter" @selected($provider === 'openrouter')>OpenRouter — only option for now</option>
                </select>
                <p class="text-[11px] text-muted-foreground">
                    Additional providers can be added later. This build uses the OpenRouter chat completions API.
                </p>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div class="space-y-1.5">
                    <label class="text-xs font-medium text-foreground">Assistant name</label>
                    <input name="name"
                           value="{{ old('name', $assistantName) }}"
                           class="form-input text-xs"
                           placeholder="Luna"
                           maxlength="50">
                    <p class="text-[11px] text-muted-foreground">
                        Used in the chat UI and when the assistant introduces itself.
                    </p>
                </div>

                <div class="space-y-1.5">
                    <label class="text-xs font-medium text-foreground">Personality and tone</label>
                    <textarea name="persona"
                              rows="3"
                              class="form-textarea text-xs"
                              maxlength="2000"
                              placeholder="Warm, concise, and practical...">{{ old('persona', $persona) }}</textarea>
                    <p class="text-[11px] text-muted-foreground">
                        How {{ $assistantName }} should sound. Keep it short.
                    </p>
                </div>
            </div>

            <div class="space-y-1.5">
                <label class="text-xs font-medium text-foreground">App knowledge notes</label>
                <textarea name="knowledge"
                          rows="6"
                          class="form-textarea text-xs font-mono"
                          maxlength="8000"
                          placeholder="- New projects start with four lists: To Do, On Progress, Revision, Done.&#10;- Priorities are low, medium, high, critical.&#10;- Only project managers can change a timeline.">{{ old('knowledge', $knowledge) }}</textarea>
                <p class="text-[11px] text-muted-foreground">
                    Authoritative rules {{ $assistantName }} should follow. One rule per line works well.
                    These notes are sent in the system prompt, never taken from user chat.
                </p>
            </div>

            <div class="space-y-1.5">
                <label class="text-xs font-medium text-foreground">Google Gemma model</label>
                <input name="model"
                       value="{{ old('model', $model) }}"
                       list="gemmaModels"
                       class="form-input text-xs font-mono"
                       placeholder="google/gemma-4-26b-a4b-it:free"
                       required>
                <datalist id="gemmaModels">
                    <option value="google/gemma-4-26b-a4b-it:free"></option>
                    <option value="google/gemma-4-31b-it:free"></option>
                    <option value="google/gemma-4-31b-it"></option>
                    <option value="nvidia/nemotron-3-ultra-550b-a55b:free"></option>
                    <option value="nvidia/nemotron-3.5-lightning:free"></option>
                    <option value="nvidia/nemotron-3.5-super-vl:free"></option>
                    <option value="google/gemini-2.5-flash"></option>
                </datalist>
                <p class="text-[11px] text-muted-foreground">
                    Use the exact model ID from OpenRouter. Free Gemma models may be rate-limited.
                </p>
                @if($fallbackModel)
                <p class="text-[11px] text-muted-foreground">
                    If this model is rate-limited or unavailable, requests automatically retry with
                    <strong class="font-mono">{{ $fallbackModel }}</strong> ({{ $fallbackLabel }}).
                </p>
                @endif
            </div>

            <div class="space-y-1.5">
                <label class="text-xs font-medium text-foreground">OpenRouter API key</label>
                <input type="password"
                       name="api_key"
                       class="form-input text-xs font-mono"
                       placeholder="{{ $maskedKey ?: 'sk-or-v1-...' }}"
                       autocomplete="new-password">
                <p class="text-[11px] text-muted-foreground">
                    {{ $maskedKey ? 'A key is already saved. Leave this blank to keep it.' : 'Get a key from openrouter.ai/keys.' }}
                    Keys are encrypted before being stored.
                </p>
            </div>

            <label class="flex items-center justify-between rounded-lg border border-border bg-muted/20 p-3.5 cursor-pointer">
                <div>
                    <div class="text-xs font-medium text-foreground">Enable AI assistant</div>
                    <div class="text-[11px] text-muted-foreground">Allow users to open the AI Assistant and run actions.</div>
                </div>
                <input type="checkbox" name="enabled" value="1" class="shadcn-checkbox" @checked($enabled)>
            </label>

            <div class="flex items-center justify-between gap-3 pt-4 border-t border-border">
                @if($maskedKey)
                <button type="submit"
                        form="clearAiKeyForm"
                        class="btn btn-ghost btn-sm text-destructive hover:bg-destructive/10">
                    <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                    <span>Remove API key</span>
                </button>
                @else
                <span></span>
                @endif

                <button type="submit" class="btn btn-primary">
                    <i data-lucide="save" class="w-4 h-4"></i>
                    <span>Save AI Settings</span>
                </button>
            </div>
        </form>

        <form id="clearAiKeyForm" method="POST" action="{{ route('admin.ai.clear-key') }}" class="hidden">
            @csrf
            @method('DELETE')
        </form>

        <div class="rounded-xl border border-border bg-muted/20 p-5 text-xs text-muted-foreground space-y-1.5">
            <p class="font-medium text-foreground">What the agent can do</p>
            <p>Create workspaces, projects, board columns, tasks, labels, and comments; assign project members; update task details and due dates; and change project timelines.</p>
            <p>Actions are limited to what the signed-in user is allowed to do. Deletion still requires manual confirmation in the app.</p>
        </div>
    </div>
</div>
@endsection
