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
        <form onsubmit="sendAiMessage(event)" class="max-w-4xl mx-auto space-y-2 relative">
            <!-- Tagged @Mentions Chips -->
            <div id="aiMentionTags" class="hidden flex-wrap items-center gap-1.5 pb-1"></div>

            <!-- @Mention Autocomplete Popover -->
            <div id="aiMentionMenu"
                 class="hidden absolute bottom-full left-0 right-0 mb-2 z-40 rounded-xl border border-border bg-popover text-popover-foreground shadow-lg max-h-64 overflow-y-auto p-1.5">
            </div>

            <div class="flex items-end gap-2 rounded-2xl border border-border bg-card p-2 shadow-xs focus-within:border-ring focus-within:ring-1 focus-within:ring-ring transition-colors">
                <button type="button"
                        id="aiMentionTriggerBtn"
                        onclick="aiTriggerMentionAtCursor()"
                        title="Tag project or task (@)"
                        class="btn btn-ghost h-9 w-9 p-0 shrink-0 rounded-xl text-muted-foreground hover:text-foreground"
                        {{ $configured ? '' : 'disabled' }}>
                    <i data-lucide="at-sign" class="w-4 h-4"></i>
                </button>
                <textarea id="aiInput"
                          rows="1"
                          placeholder="Ask {{ $assistantName }}... Type @ to tag a project or task, then add your todo list (1. ... 2. ...)"
                          class="w-full bg-transparent border-0 outline-none focus:ring-0 text-sm text-foreground placeholder:text-muted-foreground resize-none min-h-[36px] max-h-40 py-2 px-1 leading-relaxed"
                          {{ $configured ? '' : 'disabled' }}></textarea>
                <button type="submit"
                        id="aiSendBtn"
                        class="btn btn-primary h-9 px-3.5 rounded-xl shrink-0 text-xs"
                        {{ $configured ? '' : 'disabled' }}>
                    <i data-lucide="send" class="w-3.5 h-3.5"></i>
                    <span class="hidden sm:inline">Send</span>
                </button>
            </div>
            <p class="text-[10px] text-muted-foreground">
                Type <kbd class="px-1 py-0.5 text-[9px] bg-muted border border-border rounded font-mono">@</kbd> to tag a project or task · Enter to send · Shift+Enter for a new line.
            </p>
        </form>
    </div>
</div>

<script>
const AI_CONFIGURED = @json($configured);
const AI_MENTION_ITEMS = @json($mentionItems);
let aiBusy = false;
let selectedMentions = [];
let mentionQueryState = null; // { start, end, query }
let filteredMentionItems = [];
let activeMentionIndex = 0;

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

    // Only include mentions whose @Title is still in the message (or keep explicit tags)
    const activeMentions = selectedMentions.filter(m => text.includes('@' + m.title));

    aiAppendMessage('user', text);
    aiInputEl.value = '';
    aiInputEl.style.height = 'auto';
    selectedMentions = [];
    renderMentionTags();
    closeMentionMenu();
    aiAppendTyping();

    try {
        const res = await fetch('{{ route('ai.chat') }}', {
            method: 'POST',
            headers,
            body: JSON.stringify({
                message: text,
                mentions: activeMentions.map(m => ({ type: m.type, id: m.id }))
            })
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

// ── @Mention Autocomplete Logic ──
const aiMentionMenuEl = document.getElementById('aiMentionMenu');
const aiMentionTagsEl = document.getElementById('aiMentionTags');

function escapeMentionHtml(str) {
    return String(str ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
}

function getMentionQueryAtCursor() {
    if (!aiInputEl) return null;
    const pos = aiInputEl.selectionStart ?? aiInputEl.value.length;
    const before = aiInputEl.value.slice(0, pos);
    const match = before.match(/(?:^|\s)@([^\s@]*)$/);
    if (!match) return null;
    const query = match[1] || '';
    const start = pos - query.length - 1; // index of '@'
    return { start, end: pos, query };
}

function openMentionMenu(state) {
    if (!aiMentionMenuEl) return;
    mentionQueryState = state;
    const q = state.query.toLowerCase();

    filteredMentionItems = AI_MENTION_ITEMS.filter(item =>
        item.title.toLowerCase().includes(q) ||
        (item.subtitle || '').toLowerCase().includes(q)
    ).slice(0, 15);

    activeMentionIndex = 0;

    if (filteredMentionItems.length === 0) {
        aiMentionMenuEl.innerHTML = '<div class="px-3 py-2.5 text-xs text-muted-foreground text-center">No matching projects or tasks found.</div>';
        aiMentionMenuEl.classList.remove('hidden');
        return;
    }

    renderMentionMenuItems();
    aiMentionMenuEl.classList.remove('hidden');
}

function renderMentionMenuItems() {
    if (!aiMentionMenuEl) return;
    const projects = filteredMentionItems.filter(i => i.type === 'project');
    const tasks = filteredMentionItems.filter(i => i.type === 'task');

    let html = '';
    let globalIdx = 0;

    const renderGroup = (label, items) => {
        if (!items.length) return '';
        let out = `<div class="px-2.5 py-1 text-[10px] font-semibold uppercase tracking-wider text-muted-foreground">${label}</div>`;
        items.forEach(item => {
            const idx = globalIdx++;
            const isActive = idx === activeMentionIndex;
            const iconName = item.type === 'project' ? 'folder-kanban' : 'check-square';
            out += `
                <button type="button"
                        data-mention-idx="${idx}"
                        onmousedown="event.preventDefault(); selectMentionByIndex(${idx});"
                        onmouseenter="activeMentionIndex=${idx}; updateMentionHighlight();"
                        class="w-full flex items-center justify-between gap-2.5 px-2.5 py-1.5 rounded-lg text-left text-xs transition-colors ${isActive ? 'bg-accent text-accent-foreground' : 'hover:bg-accent/60'}">
                    <div class="flex items-center gap-2 min-w-0">
                        <i data-lucide="${iconName}" class="w-3.5 h-3.5 shrink-0 text-primary"></i>
                        <span class="font-medium truncate">${escapeMentionHtml(item.title)}</span>
                    </div>
                    <span class="text-[10px] text-muted-foreground truncate shrink-0 max-w-[40%]">${escapeMentionHtml(item.subtitle)}</span>
                </button>
            `;
        });
        return out;
    };

    html += renderGroup('Projects', projects);
    html += renderGroup('Tasks', tasks);

    aiMentionMenuEl.innerHTML = html;
    window.lucide?.render();
}

function updateMentionHighlight() {
    if (!aiMentionMenuEl) return;
    aiMentionMenuEl.querySelectorAll('[data-mention-idx]').forEach(el => {
        const idx = Number(el.dataset.mentionIdx);
        if (idx === activeMentionIndex) {
            el.classList.add('bg-accent', 'text-accent-foreground');
            el.scrollIntoView({ block: 'nearest' });
        } else {
            el.classList.remove('bg-accent', 'text-accent-foreground');
        }
    });
}

function closeMentionMenu() {
    mentionQueryState = null;
    filteredMentionItems = [];
    aiMentionMenuEl?.classList.add('hidden');
}

function selectMentionByIndex(idx) {
    const item = filteredMentionItems[idx];
    if (!item || !aiInputEl || !mentionQueryState) return;

    const { start, end } = mentionQueryState;
    const value = aiInputEl.value;
    const inserted = `@${item.title} `;
    aiInputEl.value = value.slice(0, start) + inserted + value.slice(end);

    const newPos = start + inserted.length;
    aiInputEl.focus();
    aiInputEl.setSelectionRange(newPos, newPos);

    if (!selectedMentions.some(m => m.type === item.type && m.id === item.id)) {
        selectedMentions.push(item);
        renderMentionTags();
    }

    closeMentionMenu();
    aiInputEl.style.height = 'auto';
    aiInputEl.style.height = Math.min(aiInputEl.scrollHeight, 160) + 'px';
}

function removeMentionTag(type, id) {
    selectedMentions = selectedMentions.filter(m => !(m.type === type && m.id === id));
    renderMentionTags();
}

function renderMentionTags() {
    if (!aiMentionTagsEl) return;
    if (selectedMentions.length === 0) {
        aiMentionTagsEl.classList.add('hidden');
        aiMentionTagsEl.classList.remove('flex');
        aiMentionTagsEl.innerHTML = '';
        return;
    }

    aiMentionTagsEl.classList.remove('hidden');
    aiMentionTagsEl.classList.add('flex');
    aiMentionTagsEl.innerHTML = selectedMentions.map(m => {
        const iconName = m.type === 'project' ? 'folder-kanban' : 'check-square';
        return `
            <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-[11px] font-medium bg-primary/10 text-primary border border-primary/20">
                <i data-lucide="${iconName}" class="w-3 h-3"></i>
                <span>${escapeMentionHtml(m.title)}</span>
                <button type="button" onclick="removeMentionTag('${m.type}', ${m.id})" class="hover:text-destructive ml-0.5">
                    <i data-lucide="x" class="w-3 h-3"></i>
                </button>
            </span>
        `;
    }).join('');
    window.lucide?.render();
}

function aiTriggerMentionAtCursor() {
    if (!aiInputEl || aiInputEl.disabled) return;
    const pos = aiInputEl.selectionStart ?? aiInputEl.value.length;
    const before = aiInputEl.value.slice(0, pos);
    const needsSpace = before.length > 0 && !/\s$/.test(before);
    const insert = (needsSpace ? ' ' : '') + '@';
    aiInputEl.value = before + insert + aiInputEl.value.slice(pos);
    const newPos = pos + insert.length;
    aiInputEl.focus();
    aiInputEl.setSelectionRange(newPos, newPos);
    const state = getMentionQueryAtCursor();
    if (state) openMentionMenu(state);
}

// Auto-grow the composer & handle @mention trigger.
aiInputEl?.addEventListener('input', () => {
    aiInputEl.style.height = 'auto';
    aiInputEl.style.height = Math.min(aiInputEl.scrollHeight, 160) + 'px';

    const state = getMentionQueryAtCursor();
    if (state) {
        openMentionMenu(state);
    } else {
        closeMentionMenu();
    }
});

aiInputEl?.addEventListener('keydown', (e) => {
    const isMenuOpen = aiMentionMenuEl && !aiMentionMenuEl.classList.contains('hidden');

    if (isMenuOpen && filteredMentionItems.length > 0) {
        if (e.key === 'ArrowDown') {
            e.preventDefault();
            activeMentionIndex = (activeMentionIndex + 1) % filteredMentionItems.length;
            updateMentionHighlight();
            return;
        }
        if (e.key === 'ArrowUp') {
            e.preventDefault();
            activeMentionIndex = (activeMentionIndex - 1 + filteredMentionItems.length) % filteredMentionItems.length;
            updateMentionHighlight();
            return;
        }
        if (e.key === 'Enter' || e.key === 'Tab') {
            e.preventDefault();
            selectMentionByIndex(activeMentionIndex);
            return;
        }
        if (e.key === 'Escape') {
            e.preventDefault();
            closeMentionMenu();
            return;
        }
    }

    if (e.key === 'Enter' && !e.shiftKey) {
        e.preventDefault();
        sendAiMessage(e);
    }
});

document.addEventListener('click', (e) => {
    if (aiMentionMenuEl && !aiMentionMenuEl.contains(e.target) && e.target !== aiInputEl && e.target.id !== 'aiMentionTriggerBtn') {
        closeMentionMenu();
    }
});
</script>
@endsection
