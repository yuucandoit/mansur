@extends('layouts.app')

@section('title', $assistantName)

@section('content')
<div class="flex flex-col h-full bg-background overflow-hidden">

    <!-- Header -->
    <div class="px-4 sm:px-6 py-4 border-b border-border bg-card/40 shrink-0">
        <div class="max-w-4xl mx-auto flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-primary/10 border border-primary/15 text-primary flex items-center justify-center shrink-0">
                    <i data-lucide="sparkles" class="w-5 h-5"></i>
                </div>
                <div>
                    <h1 class="text-lg font-bold tracking-tight text-foreground">{{ $assistantName }}</h1>
                    <p class="text-xs text-muted-foreground mt-0.5">
                        Your project management assistant. Create tasks, workspaces, assignments, and timelines with natural language.
                    </p>
                </div>
            </div>
            <div class="flex items-center gap-2 shrink-0">
                <span class="badge badge-secondary text-[10px] font-mono">{{ $model }}</span>
                @if($configured)
                <span class="badge badge-outline text-[10px] text-emerald-600 dark:text-emerald-400 border-emerald-500/30">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                    Online
                </span>
                @else
                <span class="badge badge-muted text-[10px]">Not configured</span>
                @endif
            </div>
        </div>
    </div>

    @if(!$configured)
    <div class="px-4 sm:px-6 pt-4 shrink-0">
        <div class="max-w-4xl mx-auto rounded-xl border border-amber-500/25 bg-amber-500/10 p-4 text-xs text-amber-700 dark:text-amber-400 flex items-start gap-2.5">
            <i data-lucide="triangle-alert" class="w-4 h-4 shrink-0 mt-0.5"></i>
            <div>
                <p class="font-semibold">The AI assistant is not configured yet.</p>
                <p class="mt-0.5 opacity-90">
                    @if(Auth::user()->isAdmin())
                    Add an OpenRouter API key in
                    <a href="{{ route('admin.ai.index') }}" class="underline font-medium">Admin → AI Agent</a>
                    to turn on {{ $assistantName }}.
                    @else
                    Ask an administrator to configure the OpenRouter API key in Admin → AI Agent.
                    @endif
                </p>
            </div>
        </div>
    </div>
    @endif

    <!-- Messages -->
    <div id="aiChatScroll" class="flex-1 overflow-y-auto px-4 sm:px-6 py-6">
        <div class="max-w-4xl mx-auto space-y-5" id="aiMessages">

            <!-- Initial assistant message -->
            <div class="flex items-start gap-3">
                <div class="w-8 h-8 rounded-full bg-primary/10 border border-primary/20 text-primary flex items-center justify-center shrink-0">
                    <i data-lucide="sparkles" class="w-4 h-4"></i>
                </div>
                <div class="rounded-2xl rounded-tl-md border border-border bg-card px-4 py-3 max-w-[85%]">
                    <p class="text-sm text-foreground leading-relaxed">
                        Hi {{ Auth::user()->name }}! I'm {{ $assistantName }}. I can help you {{ $capabilitySentence }}.
                        What would you like to work on?
                    </p>
                </div>
            </div>

            <!-- Suggestions -->
            <div class="pl-11">
                <p class="text-[11px] font-medium uppercase tracking-wider text-muted-foreground mb-2">Try one of these</p>
                <div class="flex flex-wrap gap-2">
                    @if($workspaceCount === 0)
                    <button type="button" onclick="aiUseSuggestion(this)" data-prompt="Create a workspace called Marketing" class="btn btn-outline btn-sm text-[11px]">Create a workspace called Marketing</button>
                    <button type="button" onclick="aiUseSuggestion(this)" data-prompt="Create a workspace called Product and add a project called Website Redesign" class="btn btn-outline btn-sm text-[11px]">Create a workspace and project</button>
                    @else
                    <button type="button" onclick="aiUseSuggestion(this)" data-prompt="Show me what I can do" class="btn btn-outline btn-sm text-[11px]">What can you do?</button>
                    <button type="button" onclick="aiUseSuggestion(this)" data-prompt="Create a task in my first project called Review homepage design, due tomorrow, high priority" class="btn btn-outline btn-sm text-[11px]">Create a task</button>
                    <button type="button" onclick="aiUseSuggestion(this)" data-prompt="Assign the task Review homepage design to a project member" class="btn btn-outline btn-sm text-[11px]">Assign a task</button>
                    <button type="button" onclick="aiUseSuggestion(this)" data-prompt="Update the timeline of my first project: start today and end in two weeks" class="btn btn-outline btn-sm text-[11px]">Update timeline</button>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Composer -->
    <div class="border-t border-border bg-card/40 px-4 sm:px-6 py-4 shrink-0">
        <form onsubmit="sendAiMessage(event)" class="max-w-4xl mx-auto space-y-2">
            <div class="flex items-end gap-2">
                <textarea id="aiInput"
                          rows="1"
                          placeholder="Ask {{ $assistantName }} to create, assign, or update something..."
                          class="form-textarea text-sm resize-none min-h-[44px] max-h-40 py-2.5"
                          onkeydown="if(event.key==='Enter' && !event.shiftKey){event.preventDefault();sendAiMessage(event);}"
                          {{ $configured ? '' : 'disabled' }}></textarea>
                <button type="submit"
                        id="aiSendBtn"
                        class="btn btn-primary h-11 px-4 shrink-0"
                        {{ $configured ? '' : 'disabled' }}>
                    <i data-lucide="send" class="w-4 h-4"></i>
                    <span class="hidden sm:inline">Send</span>
                </button>
            </div>
            <p class="text-[10px] text-muted-foreground">
                Enter to send · Shift+Enter for a new line. The agent only performs actions you are permitted to do.
            </p>
        </form>
    </div>
</div>

<script>
const AI_CONFIGURED = @json($configured);
let aiBusy = false;

const aiMessagesEl = document.getElementById('aiMessages');
const aiInputEl = document.getElementById('aiInput');
const aiSendBtn = document.getElementById('aiSendBtn');
const aiScrollEl = document.getElementById('aiChatScroll');

function aiScrollToBottom() {
    if (aiScrollEl) {
        aiScrollEl.scrollTop = aiScrollEl.scrollHeight;
    }
}

function aiAppendMessage(role, text, results = []) {
    const row = document.createElement('div');
    row.className = role === 'user'
        ? 'flex items-start justify-end gap-3'
        : 'flex items-start gap-3';

    if (role === 'assistant') {
        const avatar = document.createElement('div');
        avatar.className = 'w-8 h-8 rounded-full bg-primary/10 border border-primary/20 text-primary flex items-center justify-center shrink-0';
        avatar.innerHTML = '<i data-lucide="sparkles" class="w-4 h-4"></i>';
        row.appendChild(avatar);
    }

    const bubble = document.createElement('div');
    bubble.className = role === 'user'
        ? 'rounded-2xl rounded-tr-md bg-primary text-primary-foreground px-4 py-3 max-w-[85%]'
        : 'rounded-2xl rounded-tl-md border border-border bg-card px-4 py-3 max-w-[85%]';

    const body = document.createElement('p');
    body.className = 'text-sm leading-relaxed whitespace-pre-line';
    body.textContent = text;
    bubble.appendChild(body);

    if (Array.isArray(results) && results.length > 0) {
        const list = document.createElement('div');
        list.className = 'mt-3 pt-3 border-t border-border/60 space-y-1.5';

        results.forEach(result => {
            const item = document.createElement('div');
            item.className = 'flex items-start gap-2 text-[11px]';

            const icon = document.createElement('i');
            icon.setAttribute('data-lucide', result.status === 'ok' ? 'check-circle-2' : 'alert-circle');
            icon.className = 'w-3.5 h-3.5 shrink-0 mt-0.5 ' + (result.status === 'ok' ? 'text-emerald-500' : 'text-destructive');

            const message = document.createElement('span');
            message.className = result.status === 'ok' ? 'text-foreground/80' : 'text-destructive';
            message.textContent = result.message || result.type;

            item.appendChild(icon);
            item.appendChild(message);
            list.appendChild(item);
        });

        bubble.appendChild(list);
    }

    row.appendChild(bubble);
    aiMessagesEl.appendChild(row);

    window.lucide?.render();
    aiScrollToBottom();
}

function aiAppendTyping() {
    const row = document.createElement('div');
    row.id = 'aiTypingIndicator';
    row.className = 'flex items-start gap-3';

    const avatar = document.createElement('div');
    avatar.className = 'w-8 h-8 rounded-full bg-primary/10 border border-primary/20 text-primary flex items-center justify-center shrink-0';
    avatar.innerHTML = '<i data-lucide="sparkles" class="w-4 h-4"></i>';

    const bubble = document.createElement('div');
    bubble.className = 'rounded-2xl rounded-tl-md border border-border bg-card px-4 py-3';
    bubble.innerHTML = '<div class="flex items-center gap-2 text-xs text-muted-foreground"><i data-lucide="loader-2" class="w-3.5 h-3.5 animate-spin"></i><span>Thinking and preparing actions...</span></div>';

    row.appendChild(avatar);
    row.appendChild(bubble);
    aiMessagesEl.appendChild(row);

    window.lucide?.render();
    aiScrollToBottom();
}

function aiRemoveTyping() {
    document.getElementById('aiTypingIndicator')?.remove();
}

function aiUseSuggestion(button) {
    if (!aiInputEl || aiInputEl.disabled) return;
    aiInputEl.value = button.dataset.prompt || '';
    aiInputEl.focus();
}

async function sendAiMessage(event) {
    if (event) event.preventDefault();
    if (aiBusy || !AI_CONFIGURED) return;

    const text = (aiInputEl?.value || '').trim();
    if (!text) return;

    aiBusy = true;
    if (aiSendBtn) aiSendBtn.disabled = true;

    aiAppendMessage('user', text);
    aiInputEl.value = '';
    aiInputEl.style.height = 'auto';
    aiAppendTyping();

    try {
        const res = await fetch('{{ route('ai.chat') }}', {
            method: 'POST',
            headers,
            body: JSON.stringify({ message: text })
        });

        const data = await res.json().catch(() => ({}));

        aiRemoveTyping();

        if (!res.ok) {
            aiAppendMessage('assistant', data.message || 'Something went wrong. Please try again.');
            return;
        }

        aiAppendMessage('assistant', data.reply || 'Done.', data.results || []);
    } catch {
        aiRemoveTyping();
        aiAppendMessage('assistant', 'Network error. Please check your connection and try again.');
    } finally {
        aiBusy = false;
        if (aiSendBtn) aiSendBtn.disabled = false;
        aiInputEl?.focus();
    }
}

// Auto-grow the composer.
aiInputEl?.addEventListener('input', () => {
    aiInputEl.style.height = 'auto';
    aiInputEl.style.height = Math.min(aiInputEl.scrollHeight, 160) + 'px';
});
</script>
@endsection
