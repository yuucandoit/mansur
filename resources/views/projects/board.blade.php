@extends('layouts.app')

@section('title', $project->title . ' — Board')

@section('content')
<div class="flex flex-col h-full overflow-hidden bg-background">

    <!-- ── PROJECT HEADER ── -->
    @include('projects.partials.header')

    <!-- ── TOOLBAR ── -->
    <div class="px-6 py-2.5 border-b border-border bg-card/20 flex flex-wrap items-center justify-between gap-3 shrink-0">
        <div class="flex items-center gap-2 flex-1 max-w-sm">
            <div class="relative w-full">
                <i data-lucide="search" class="w-3.5 h-3.5 text-muted-foreground absolute left-2.5 top-1/2 -translate-y-1/2"></i>
                <input type="text" 
                    id="tableSearchInput" 
                    placeholder="Filter task rows..." 
                    oninput="filterCards(this.value)"
                    class="form-input text-xs pl-8 pr-12 h-8">
            </div>
        </div>

        <div class="flex items-center gap-1.5">
            <button type="button" 
                    onclick="window.showToast('Filter options')" 
                    class="btn btn-outline btn-sm h-8 text-xs text-muted-foreground hover:text-foreground">
                <i data-lucide="filter" class="w-3.5 h-3.5"></i>
                <span>Filter</span>
            </button>
            <button type="button" 
                    onclick="window.showToast('Sort options')" 
                    class="btn btn-outline btn-sm h-8 text-xs text-muted-foreground hover:text-foreground">
                <i data-lucide="arrow-up-down" class="w-3.5 h-3.5"></i>
                <span>Sort</span>
            </button>
            <div class="h-4 w-px bg-border mx-1"></div>
            <button type="button" 
                    class="btn btn-secondary btn-sm h-8 text-xs font-medium">
                <i data-lucide="layers" class="w-3.5 h-3.5"></i>
                <span>Group by Status</span>
            </button>
        </div>
    </div>

    <!-- ── BOARD COLUMNS AREA ── -->
    <div class="flex-1 overflow-x-auto overflow-y-hidden p-6">
        <div class="flex items-start gap-4 h-full min-w-max pb-2" id="boardColumns">

            @foreach($boardLists as $listIndex => $list)
            @php
                $doneCount = $list->tasks->where('is_done', 1)->count();
            @endphp

            <div class="board-column w-72 shrink-0 rounded-xl border border-border bg-muted/40 flex flex-col max-h-full transition-colors" 
                 data-list-id="{{ $list->id }}" 
                 data-is-done="{{ $list->is_done }}">

                <!-- Column Header -->
                <div class="p-3 border-b border-border bg-card rounded-t-xl flex items-center justify-between gap-2 shrink-0">
                    <div class="flex items-center gap-2 min-w-0">
                        <span class="w-2 h-2 rounded-full {{ $list->is_done ? 'bg-emerald-500' : 'bg-primary/80' }}"></span>
                        <h2 class="column-title text-xs font-semibold tracking-tight text-foreground truncate px-1 rounded hover:bg-muted/50 cursor-text outline-none focus:ring-1 focus:ring-ring"
                              contenteditable="true"
                              onkeydown="if(event.key==='Enter'){event.preventDefault();this.blur()}"
                              onblur="saveListTitle(this, {{ $list->id }})">{{ $list->title }}</h2>
                        <span class="column-count badge badge-secondary text-[10px] px-1.5 py-0 font-mono">
                            {{ $doneCount }}/{{ $list->tasks->count() }}
                        </span>
                    </div>

                    <div class="flex items-center gap-0.5">
                        <button type="button" 
                                onclick="openAddTask({{ $list->id }})" 
                                title="Add task to column"
                                class="btn btn-ghost p-1 h-6 w-6 text-muted-foreground hover:text-foreground">
                            <i data-lucide="plus" class="w-3.5 h-3.5"></i>
                        </button>
                        
                        <div class="relative">
                            <button type="button" 
                                    onclick="toggleColMenu({{ $list->id }})" 
                                    title="Column options"
                                    class="btn btn-ghost p-1 h-6 w-6 text-muted-foreground hover:text-foreground">
                                <i data-lucide="more-horizontal" class="w-3.5 h-3.5"></i>
                            </button>
                            <div class="dropdown-menu right-0 top-7" id="col-menu-{{ $list->id }}">
                                @if($listIndex > 0)
                                <div class="dropdown-item" onclick="moveList({{ $list->id }}, 'left')">
                                    <i data-lucide="arrow-left" class="w-3.5 h-3.5"></i>
                                    <span>Move Left</span>
                                </div>
                                @endif
                                @if($listIndex < $boardLists->count() - 1)
                                <div class="dropdown-item" onclick="moveList({{ $list->id }}, 'right')">
                                    <i data-lucide="arrow-right" class="w-3.5 h-3.5"></i>
                                    <span>Move Right</span>
                                </div>
                                @endif
                                <div class="dropdown-item danger" onclick="archiveList({{ $list->id }})">
                                    <i data-lucide="archive" class="w-3.5 h-3.5"></i>
                                    <span>Archive List</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Column Cards (Drop Container) -->
                <div class="column-cards p-2 flex-1 overflow-y-auto space-y-2 min-h-[50px] transition-all"
                     id="cards-{{ $list->id }}"
                     ondragover="allowDrop(event)"  
                     ondrop="dropTask(event, {{ $list->id }})"
                     ondragleave="dragLeave(event)">

                    @foreach($list->tasks as $task)
                    @php
                        $dueCls = 'text-muted-foreground bg-muted';
                        $dueLabel = '';
                        if ($task->due_date) {
                            $now = now();
                            if ($task->is_done) { 
                                $dueCls = 'text-emerald-700 bg-emerald-50 dark:bg-emerald-950/40 dark:text-emerald-400'; 
                            } elseif ($now->gt($task->due_date)) { 
                                $dueCls = 'text-destructive bg-destructive/10 dark:bg-destructive/20'; 
                            } elseif ($now->diffInHours($task->due_date) <= 24) { 
                                $dueCls = 'text-amber-700 bg-amber-50 dark:bg-amber-950/40 dark:text-amber-400'; 
                            } else { 
                                $dueCls = 'text-muted-foreground bg-muted'; 
                            }
                            $dueLabel = $task->due_date->format('M j');
                        }
                    @endphp

                    <div class="task-card group relative rounded-lg border border-border bg-card p-3 shadow-2xs hover:border-border hover:shadow-xs hover:-translate-y-0.5 transition-all cursor-pointer select-none"
                         draggable="true"
                         data-task-id="{{ $task->id }}"
                         data-list-id="{{ $list->id }}"
                         data-done="{{ $task->is_done ? 1 : 0 }}"
                         ondragstart="dragStart(event, {{ $task->id }}, {{ $list->id }})"
                         ondragend="dragEnd(event)"
                         onclick="openTaskPanel({{ $task->id }})">

                        <!-- Context Menu Trigger -->
                        <div class="absolute top-2.5 right-2 opacity-0 group-hover:opacity-100 transition-opacity">
                            <button type="button" 
                                    class="btn btn-ghost p-1 h-6 w-6 text-muted-foreground hover:text-foreground" 
                                    onclick="event.stopPropagation();toggleTaskMenu({{ $task->id }})">
                                <i data-lucide="more-horizontal" class="w-3.5 h-3.5"></i>
                            </button>
                            <div class="dropdown-menu right-0 top-7" id="task-menu-{{ $task->id }}">
                                <div class="dropdown-item" onclick="event.stopPropagation();openTaskPanel({{ $task->id }})">
                                    <i data-lucide="eye" class="w-3.5 h-3.5"></i>
                                    <span>View Details</span>
                                </div>
                                <div class="dropdown-item danger" onclick="event.stopPropagation();archiveTask({{ $task->id }}, this)">
                                    <i data-lucide="archive" class="w-3.5 h-3.5"></i>
                                    <span>Archive Task</span>
                                </div>
                            </div>
                        </div>

                        <!-- Labels / Flags -->
                        <div class="flex items-center gap-1.5 flex-wrap mb-1.5 pr-6">
                            @if($task->is_critical)
                            <span class="badge bg-destructive/10 text-destructive text-[10px] px-1.5 py-0">
                                <i data-lucide="flag" class="w-2.5 h-2.5"></i> Critical
                            </span>
                            @endif

                            @foreach($task->labels as $label)
                            <span class="badge text-[10px] px-1.5 py-0 border border-border" style="background-color: {{ $label->color ?? '#6366f1' }}20; color: {{ $label->color ?? '#6366f1' }}">
                                {{ $label->name }}
                            </span>
                            @endforeach
                        </div>

                        <!-- Title -->
                        <h3 class="card-title text-xs font-medium text-foreground leading-snug line-clamp-2">
                            {{ $task->title }}
                        </h3>

                        <!-- Description Snippet -->
                        @if($task->description)
                        <p class="card-desc text-[11px] text-muted-foreground line-clamp-2 mt-1 leading-relaxed">
                            {{ strip_tags($task->description) }}
                        </p>
                        @endif

                        <!-- Card Footer -->
                        <div class="mt-3 pt-2 border-t border-border/40 flex items-center justify-between gap-2 text-xs">
                            <!-- Assignees Stack -->
                            <div class="flex items-center -space-x-1.5 overflow-hidden">
                                @foreach($task->assignees->take(3) as $assignee)
                                <div class="w-5 h-5 rounded-full border border-background bg-secondary text-foreground text-[10px] font-semibold flex items-center justify-center shrink-0" 
                                     title="{{ $assignee->user?->name }}">
                                    {{ strtoupper(substr($assignee->user?->name ?? 'U', 0, 1)) }}
                                </div>
                                @endforeach
                                @if($task->assignees->count() > 3)
                                <div class="w-5 h-5 rounded-full border border-background bg-muted text-muted-foreground text-[9px] font-medium flex items-center justify-center shrink-0">
                                    +{{ $task->assignees->count() - 3 }}
                                </div>
                                @endif
                            </div>

                            <!-- Meta Pills -->
                            <div class="flex items-center gap-1.5 text-[10px] text-muted-foreground">
                                @if($task->checklists->count())
                                @php $done = $task->checklists->where('is_done', 1)->count(); @endphp
                                <span class="flex items-center gap-0.5 {{ $done === $task->checklists->count() ? 'text-emerald-600 font-medium' : '' }}" title="Checklist progress">
                                    <i data-lucide="check-square" class="w-3 h-3"></i>
                                    <span>{{ $done }}/{{ $task->checklists->count() }}</span>
                                </span>
                                @endif

                                @if($task->comments->count())
                                <span class="flex items-center gap-0.5" title="Comments">
                                    <i data-lucide="message-square" class="w-3 h-3"></i>
                                    <span>{{ $task->comments->count() }}</span>
                                </span>
                                @endif

                                @if($dueLabel)
                                <span class="flex items-center gap-0.5 px-1.5 py-0.5 rounded text-[10px] font-medium {{ $dueCls }}" title="Due Date">
                                    <i data-lucide="clock" class="w-2.5 h-2.5"></i>
                                    <span>{{ $dueLabel }}</span>
                                </span>
                                @endif
                            </div>
                        </div>
                    </div>
                    @endforeach

                </div>

                <!-- Add Task Inline Form -->
                <div class="p-2 border-t border-border/50 shrink-0">
                    <div id="add-form-{{ $list->id }}" class="hidden space-y-2 p-1 bg-card rounded-lg border border-border shadow-xs">
                        <textarea class="form-textarea text-xs resize-none"
                                  id="task-input-{{ $list->id }}"
                                  rows="2"
                                  placeholder="What needs to be done?..."
                                  onkeydown="if(event.key==='Enter' && !event.shiftKey){event.preventDefault();submitNewTask({{ $list->id }}, {{ $project->id }})}"></textarea>
                        <div class="flex items-center justify-between gap-1 pt-1">
                            <button type="button" 
                                    class="btn btn-primary btn-sm text-xs" 
                                    onclick="submitNewTask({{ $list->id }}, {{ $project->id }})">
                                Add task
                            </button>
                            <button type="button" 
                                    class="btn btn-ghost p-1 h-7 w-7 text-muted-foreground hover:text-foreground" 
                                    onclick="closeAddTask({{ $list->id }})">
                                <i data-lucide="x" class="w-3.5 h-3.5"></i>
                            </button>
                        </div>
                    </div>

                    <button type="button" 
                            id="add-btn-{{ $list->id }}" 
                            onclick="openAddTask({{ $list->id }})"
                            class="w-full flex items-center justify-center gap-1.5 py-1.5 px-2 rounded-lg text-xs font-medium text-muted-foreground hover:bg-muted/80 hover:text-foreground transition-colors">
                        <i data-lucide="plus" class="w-3.5 h-3.5"></i>
                        <span>Add card</span>
                    </button>
                </div>

            </div>
            @endforeach

            <!-- ── ADD NEW LIST COLUMN ── -->
            <div class="w-72 shrink-0">
                <button type="button" 
                        id="addListBtn" 
                        onclick="openNewList()"
                        class="w-full h-11 flex items-center justify-center gap-2 rounded-xl border border-dashed border-border bg-card/50 text-xs font-medium text-muted-foreground hover:border-primary/50 hover:text-foreground hover:bg-card transition-all">
                    <i data-lucide="plus" class="w-4 h-4"></i>
                    <span>Add another list</span>
                </button>

                <div id="addListForm" class="hidden rounded-xl border border-border bg-card p-3 shadow-xs space-y-3">
                    <div class="space-y-1">
                        <input class="form-input text-xs" 
                               id="newListInput" 
                               placeholder="Enter list title..."
                               onkeydown="if(event.key==='Enter') submitNewList({{ $project->id }})">
                    </div>
                    <label class="flex items-center gap-2 text-xs text-muted-foreground cursor-pointer select-none">
                        <input type="checkbox" id="newListIsDone" class="shadcn-checkbox">
                        <span>Mark as 'Done' column</span>
                    </label>
                    <div class="flex items-center gap-2">
                        <button type="button" 
                                class="btn btn-primary btn-sm flex-1" 
                                onclick="submitNewList({{ $project->id }})">
                            Add list
                        </button>
                        <button type="button" 
                                class="btn btn-outline btn-sm" 
                                onclick="closeNewList()">
                            Cancel
                        </button>
                    </div>
                </div>
            </div>

        </div>
    </div>

</div>

<!-- ── SHARED TASK DETAIL SHEET ── -->
@include('projects.partials.task-sheet')

@endsection

@push('scripts')
<script>
const PROJ_ID = {{ $project->id }};
let draggedTaskId = null;
let draggedFromListId = null;

// ── SEARCH / FILTER ──
function filterCards(q) {
    q = q.toLowerCase();
    document.querySelectorAll('.task-card').forEach(card => {
        const title = card.querySelector('.card-title')?.textContent.toLowerCase() ?? '';
        const desc  = card.querySelector('.card-desc')?.textContent.toLowerCase() ?? '';
        card.style.display = (!q || title.includes(q) || desc.includes(q)) ? '' : 'none';
    });
}

// ── DRAG & DROP ──
function dragStart(e, taskId, listId) {
    draggedTaskId = taskId;
    draggedFromListId = listId;
    e.currentTarget.classList.add('opacity-40', 'scale-95');
    e.dataTransfer.effectAllowed = 'move';
}

function dragEnd(e) {
    e.currentTarget.classList.remove('opacity-40', 'scale-95');
}

function allowDrop(e) {
    e.preventDefault();
    e.currentTarget.classList.add('bg-primary/5', 'ring-1', 'ring-primary/20', 'rounded-lg');
}

function dragLeave(e) {
    e.currentTarget.classList.remove('bg-primary/5', 'ring-1', 'ring-primary/20', 'rounded-lg');
}

async function dropTask(e, toListId) {
    e.preventDefault();
    e.currentTarget.classList.remove('bg-primary/5', 'ring-1', 'ring-primary/20', 'rounded-lg');
    if (!draggedTaskId || toListId === draggedFromListId) return;

    const card = document.querySelector(`[data-task-id="${draggedTaskId}"]`);
    if (!card) return;

    e.currentTarget.insertBefore(card, null);
    card.dataset.listId = toListId;

    const items = buildOrderItems();

    await fetch(`/tasks/${draggedTaskId}`, {
        method: 'PUT', headers,
        body: JSON.stringify({ list_id: toListId })
    });

    await fetch('/tasks/order', {
        method: 'POST', headers,
        body: JSON.stringify({ items })
    });

    updateColumnCounts();
    draggedTaskId = null;
    draggedFromListId = null;
}

function buildOrderItems() {
    const items = [];
    document.querySelectorAll('.column-cards').forEach(col => {
        const listId = col.id.replace('cards-', '');
        col.querySelectorAll('.task-card').forEach((card, i) => {
            items.push({ id: card.dataset.taskId, order: i + 1, list_id: parseInt(listId) });
        });
    });
    return items;
}

function updateColumnCounts() {
    document.querySelectorAll('.board-column').forEach(col => {
        const all  = col.querySelectorAll('.task-card').length;
        const done = col.querySelectorAll('.task-card[data-done="1"]').length;
        const badge = col.querySelector('.column-count');
        if (badge) badge.textContent = done + '/' + all;
    });
}

// ── ADD TASK ──
function openAddTask(listId) {
    document.getElementById('add-btn-' + listId).classList.add('hidden');
    document.getElementById('add-form-' + listId).classList.remove('hidden');
    const input = document.getElementById('task-input-' + listId);
    input.focus();
}

function closeAddTask(listId) {
    document.getElementById('add-btn-' + listId).classList.remove('hidden');
    document.getElementById('add-form-' + listId).classList.add('hidden');
    document.getElementById('task-input-' + listId).value = '';
}

async function submitNewTask(listId, projectId) {
    const input = document.getElementById('task-input-' + listId);
    const title = input.value.trim();
    if (!title) return input.focus();

    try {
        const res = await fetch('/tasks', {
            method: 'POST', headers,
            body: JSON.stringify({ title, list_id: listId, project_id: projectId })
        });
        const task = await res.json();

        if (res.ok) {
            const col = document.getElementById('cards-' + listId);
            col.insertAdjacentHTML('beforeend', buildTaskCard(task));
            closeAddTask(listId);
            updateColumnCounts();
            window.lucide?.render();
            window.showToast('Task added successfully!', 'success');
        } else {
            window.showToast('Failed to add task', 'error');
        }
    } catch {
        window.showToast('Network error, please try again.', 'error');
    }
}

function buildTaskCard(task) {
    return `
    <div class="task-card group relative rounded-lg border border-border bg-card p-3 shadow-2xs hover:border-border hover:shadow-xs hover:-translate-y-0.5 transition-all cursor-pointer select-none"
         draggable="true"
         data-task-id="${task.id}"
         data-list-id="${task.list_id}"
         data-done="${task.is_done ? 1 : 0}"
         ondragstart="dragStart(event, ${task.id}, ${task.list_id})"
         ondragend="dragEnd(event)"
         onclick="openTaskPanel(${task.id})">
        
        <div class="absolute top-2.5 right-2 opacity-0 group-hover:opacity-100 transition-opacity">
            <button type="button" class="btn btn-ghost p-1 h-6 w-6 text-muted-foreground hover:text-foreground" onclick="event.stopPropagation();toggleTaskMenu(${task.id})">
                <i data-lucide="more-horizontal" class="w-3.5 h-3.5"></i>
            </button>
            <div class="dropdown-menu right-0 top-7" id="task-menu-${task.id}">
                <div class="dropdown-item" onclick="event.stopPropagation();openTaskPanel(${task.id})">
                    <i data-lucide="eye" class="w-3.5 h-3.5"></i>
                    <span>View Details</span>
                </div>
                <div class="dropdown-item danger" onclick="event.stopPropagation();archiveTask(${task.id}, this)">
                    <i data-lucide="archive" class="w-3.5 h-3.5"></i>
                    <span>Archive Task</span>
                </div>
            </div>
        </div>

        <h3 class="card-title text-xs font-medium text-foreground leading-snug line-clamp-2">
            ${escapeHtml(task.title)}
        </h3>

        <div class="mt-3 pt-2 border-t border-border/40 flex items-center justify-between gap-2 text-xs">
            <div class="flex items-center -space-x-1.5 overflow-hidden"></div>
            <div class="flex items-center gap-1.5 text-[10px] text-muted-foreground"></div>
        </div>
    </div>`;
}

// ── ARCHIVE TASK ──
async function archiveTask(taskId, el) {
    const card = document.querySelector(`[data-task-id="${taskId}"]`);
    if (!confirm('Are you sure you want to archive this task?')) return;
    const res = await fetch(`/tasks/${taskId}`, { method: 'DELETE', headers });
    if (res.ok) {
        card?.remove();
        closeAllMenus();
        updateColumnCounts();
        window.showToast('Task archived', 'success');
    }
}

// ── ADD LIST ──
function openNewList() {
    document.getElementById('addListBtn').classList.add('hidden');
    document.getElementById('addListForm').classList.remove('hidden');
    document.getElementById('newListInput').focus();
}

function closeNewList() {
    document.getElementById('addListBtn').classList.remove('hidden');
    document.getElementById('addListForm').classList.add('hidden');
    document.getElementById('newListInput').value = '';
    document.getElementById('newListIsDone').checked = false;
}

async function submitNewList(projectId) {
    const input = document.getElementById('newListInput');
    const title = input.value.trim();
    const isDone = document.getElementById('newListIsDone').checked;
    if (!title) return input.focus();

    const res = await fetch('/board-lists', {
        method: 'POST', headers,
        body: JSON.stringify({ title, project_id: projectId, is_done: isDone ? 1 : 0 })
    });

    if (res.ok) {
        closeNewList();
        window.showToast('List added successfully!', 'success');
        setTimeout(() => location.reload(), 500);
    } else {
        window.showToast('Failed to create list', 'error');
    }
}

// ── SAVE LIST TITLE ──
async function saveListTitle(el, listId) {
    const title = el.innerText.trim();
    if (!title) return;
    await fetch(`/board-lists/${listId}`, {
        method: 'PUT', headers, body: JSON.stringify({ title })
    });
    window.showToast('List title updated', 'success');
}

// ── MOVE LIST ──
async function moveList(listId, dir) {
    const cols = [...document.querySelectorAll('.board-column')];
    const idx = cols.findIndex(c => c.dataset.listId == listId);
    if (dir === 'left' && idx === 0) return;
    if (dir === 'right' && idx === cols.length - 1) return;

    const swapIdx = dir === 'left' ? idx - 1 : idx + 1;
    const parent = cols[0].parentElement;

    if (dir === 'left') {
        parent.insertBefore(cols[idx], cols[swapIdx]);
    } else {
        parent.insertBefore(cols[swapIdx], cols[idx]);
    }

    const newOrder = [...parent.querySelectorAll('.board-column')].map((c, i) => ({
        id: parseInt(c.dataset.listId), order: i
    }));

    await fetch('/board-lists/order', { method: 'POST', headers, body: JSON.stringify({ items: newOrder }) });
    closeAllMenus();
    window.showToast('List position updated', 'success');
}

// ── ARCHIVE LIST ──
async function archiveList(listId) {
    if (!confirm('Are you sure you want to archive this column and all its tasks?')) return;
    const res = await fetch(`/board-lists/${listId}`, { method: 'DELETE', headers });
    if (res.ok) {
        document.querySelector(`.board-column[data-list-id="${listId}"]`)?.remove();
        closeAllMenus();
        window.showToast('Column archived', 'success');
    }
}

// ── DROPDOWNS ──
function toggleColMenu(listId) {
    closeAllMenus();
    document.getElementById('col-menu-' + listId)?.classList.toggle('open');
    event.stopPropagation();
}

function toggleTaskMenu(taskId) {
    closeAllMenus();
    document.getElementById('task-menu-' + taskId)?.classList.toggle('open');
    event.stopPropagation();
}

function closeAllMenus() {
    document.querySelectorAll('.dropdown-menu.open').forEach(m => m.classList.remove('open'));
}

document.addEventListener('click', closeAllMenus);
</script>
@endpush
