@extends('layouts.app')

@section('title', $project->title . ' — List View')

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
                    oninput="filterListTasks(this.value)"
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
                    onclick="toggleAllGroups()" 
                    id="toggleAllBtn"
                    class="btn btn-outline btn-sm h-8 text-xs text-muted-foreground hover:text-foreground">
                <i data-lucide="chevrons-up-down" class="w-3.5 h-3.5"></i>
                <span>Collapse All</span>
            </button>
        </div>
    </div>

    <!-- ── LIST CONTENT AREA ── -->
    <div class="flex-1 overflow-y-auto px-6 py-6 space-y-6">

        @forelse($boardLists as $listIndex => $list)
        @php
            $doneCount = $list->tasks->where('is_done', 1)->count();
        @endphp

        <div class="list-group rounded-xl border border-border bg-card shadow-2xs overflow-hidden" 
             id="group-{{ $list->id }}"
             data-list-id="{{ $list->id }}">

            <!-- Group Header -->
            <div class="px-4 py-2.5 bg-muted/40 border-b border-border/70 flex items-center justify-between select-none cursor-pointer hover:bg-muted/60 transition-colors"
                 onclick="toggleGroup({{ $list->id }})">
                <div class="flex items-center gap-2.5 min-w-0">
                    <i data-lucide="chevron-down" 
                       class="w-4 h-4 text-muted-foreground transition-transform duration-200" 
                       id="chevron-{{ $list->id }}"></i>
                    <span class="w-2.5 h-2.5 rounded-full {{ $list->is_done ? 'bg-emerald-500' : 'bg-primary' }}"></span>
                    <span class="text-xs font-semibold tracking-tight text-foreground">{{ $list->title }}</span>
                    <span class="badge badge-secondary text-[10px] px-1.5 py-0 font-mono">
                        {{ $doneCount }}/{{ $list->tasks->count() }}
                    </span>
                </div>

                <div class="flex items-center gap-1" onclick="event.stopPropagation()">
                    <button type="button" 
                            onclick="openAddListTask({{ $list->id }})" 
                            title="Add task to this list"
                            class="btn btn-ghost p-1 h-7 w-7 text-muted-foreground hover:text-foreground">
                        <i data-lucide="plus" class="w-3.5 h-3.5"></i>
                    </button>
                </div>
            </div>

            <!-- Group Task Items -->
            <div class="divide-y divide-border/50 group-items" id="items-{{ $list->id }}">
                @forelse($list->tasks as $task)
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

                <div class="task-row group/row px-4 py-2.5 flex items-center justify-between gap-3 hover:bg-muted/30 transition-colors"
                     data-task-id="{{ $task->id }}">
                    <!-- Left: Checkbox & Title & Labels -->
                    <div class="flex items-center gap-3 min-w-0 flex-1">
                        <input type="checkbox" 
                               {{ $task->is_done ? 'checked' : '' }}
                               onchange="toggleTaskDone({{ $task->id }}, this)"
                               title="Mark done"
                               class="shadcn-checkbox">
                        
                        <div class="flex items-center gap-2 min-w-0 flex-1">
                            @if($task->is_critical)
                            <i data-lucide="flag" class="w-3.5 h-3.5 text-destructive shrink-0" title="Critical"></i>
                            @endif

                            <span class="text-xs font-medium text-foreground truncate cursor-pointer hover:underline hover:text-primary transition-colors {{ $task->is_done ? 'line-through text-muted-foreground' : '' }}"
                                  onclick="openTaskPanel({{ $task->id }})">
                                {{ $task->title }}
                            </span>

                            @foreach($task->labels as $label)
                            <span class="badge text-[10px] px-1.5 py-0 border border-border shrink-0 hidden sm:inline-flex" style="background-color: {{ $label->color ?? '#6366f1' }}20; color: {{ $label->color ?? '#6366f1' }}">
                                {{ $label->name }}
                            </span>
                            @endforeach
                        </div>
                    </div>

                    <!-- Right: Meta, Assignees, Due Date, Options -->
                    <div class="flex items-center gap-3 shrink-0 text-xs">
                        <!-- Checklist & Comments -->
                        <div class="flex items-center gap-2 text-muted-foreground text-[11px] hidden md:flex">
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
                        </div>

                        <!-- Due Date Badge -->
                        @if($dueLabel)
                        <span class="px-2 py-0.5 rounded text-[10px] font-medium {{ $dueCls }} flex items-center gap-1">
                            <i data-lucide="calendar" class="w-3 h-3"></i>
                            <span>{{ $dueLabel }}</span>
                        </span>
                        @endif

                        <!-- Assignees Stack -->
                        <div class="flex items-center -space-x-1.5 overflow-hidden">
                            @foreach($task->assignees->take(2) as $assignee)
                            <div class="w-5 h-5 rounded-full border border-background bg-secondary text-foreground text-[9px] font-semibold flex items-center justify-center shrink-0" 
                                 title="{{ $assignee->user?->name }}">
                                {{ strtoupper(substr($assignee->user?->name ?? 'U', 0, 1)) }}
                            </div>
                            @endforeach
                        </div>

                        <!-- Row Actions -->
                        <div class="relative">
                            <button type="button" 
                                    class="btn btn-ghost p-1 h-6 w-6 text-muted-foreground hover:text-foreground opacity-0 group-hover/row:opacity-100 transition-opacity" 
                                    onclick="toggleListRowMenu({{ $task->id }})">
                                <i data-lucide="more-horizontal" class="w-3.5 h-3.5"></i>
                            </button>
                            <div class="dropdown-menu right-0 top-7" id="row-menu-{{ $task->id }}">
                                <div class="dropdown-item" onclick="openTaskPanel({{ $task->id }})">
                                    <i data-lucide="eye" class="w-3.5 h-3.5"></i>
                                    <span>View Details</span>
                                </div>
                                <div class="dropdown-item danger" onclick="archiveListTask({{ $task->id }})">
                                    <i data-lucide="archive" class="w-3.5 h-3.5"></i>
                                    <span>Archive Task</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                @empty
                <div class="px-4 py-3 text-xs text-muted-foreground/60 italic empty-list-msg">
                    No tasks in this list yet.
                </div>
                @endforelse

                <!-- Inline Quick Add Task Row -->
                <div class="p-2 bg-muted/20">
                    <div id="add-list-input-box-{{ $list->id }}" class="hidden">
                        <div class="flex items-center gap-2">
                            <input type="text" 
                                   id="input-task-{{ $list->id }}" 
                                   placeholder="Add task title, press Enter..." 
                                   onkeydown="if(event.key==='Enter') submitQuickTask({{ $list->id }})"
                                   class="form-input text-xs h-8">
                            <button type="button" 
                                    class="btn btn-primary btn-sm h-8 text-xs shrink-0" 
                                    onclick="submitQuickTask({{ $list->id }})">
                                Add
                            </button>
                            <button type="button" 
                                    class="btn btn-ghost p-1 h-8 w-8 text-muted-foreground hover:text-foreground" 
                                    onclick="closeAddListTask({{ $list->id }})">
                                <i data-lucide="x" class="w-3.5 h-3.5"></i>
                            </button>
                        </div>
                    </div>
                    <button type="button" 
                            id="add-list-trigger-{{ $list->id }}"
                            onclick="openAddListTask({{ $list->id }})"
                            class="w-full flex items-center gap-2 py-1 px-2 rounded text-xs text-muted-foreground hover:text-foreground hover:bg-muted/40 transition-colors">
                        <i data-lucide="plus" class="w-3.5 h-3.5"></i>
                        <span>Add task</span>
                    </button>
                </div>
            </div>
        </div>
        @empty
        <div class="h-64 flex flex-col items-center justify-center text-center p-8 rounded-xl border border-dashed border-border">
            <i data-lucide="list" class="w-8 h-8 text-muted-foreground mb-3"></i>
            <p class="text-xs text-muted-foreground">No lists created for this project yet.</p>
        </div>
        @endforelse

    </div>

</div>

<!-- ── SHARED TASK DETAIL SHEET ── -->
@include('projects.partials.task-sheet')

@endsection

@push('scripts')
<script>
const PROJ_ID = {{ $project->id }};

// ── Search in List View ──
function filterListTasks(q) {
    q = q.toLowerCase();
    document.querySelectorAll('.task-row').forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = (!q || text.includes(q)) ? '' : 'none';
    });
}

// ── Toggle Group Accordion ──
function toggleGroup(listId) {
    const items = document.getElementById('items-' + listId);
    const chevron = document.getElementById('chevron-' + listId);
    if (!items) return;
    
    const isHidden = items.classList.toggle('hidden');
    if (chevron) {
        chevron.style.transform = isHidden ? 'rotate(-90deg)' : 'rotate(0deg)';
    }
}

let allCollapsed = false;
function toggleAllGroups() {
    allCollapsed = !allCollapsed;
    document.querySelectorAll('.group-items').forEach(items => {
        items.classList.toggle('hidden', allCollapsed);
    });
    document.querySelectorAll('[id^="chevron-"]').forEach(chevron => {
        chevron.style.transform = allCollapsed ? 'rotate(-90deg)' : 'rotate(0deg)';
    });
    const btn = document.getElementById('toggleAllBtn');
    if (btn) {
        btn.querySelector('span').textContent = allCollapsed ? 'Expand All' : 'Collapse All';
    }
}

// ── Toggle Task Done ──
async function toggleTaskDone(taskId, checkbox) {
    const isDone = checkbox.checked ? 1 : 0;
    const row = checkbox.closest('.task-row');
    const titleEl = row.querySelector('.cursor-pointer');
    
    if (isDone) {
        titleEl?.classList.add('line-through', 'text-muted-foreground');
    } else {
        titleEl?.classList.remove('line-through', 'text-muted-foreground');
    }

    try {
        await fetch(`/tasks/${taskId}`, {
            method: 'PUT',
            headers,
            body: JSON.stringify({ is_done: isDone })
        });
        window.showToast(isDone ? 'Task marked as completed' : 'Task marked as pending', 'success');
    } catch {
        checkbox.checked = !isDone;
        window.showToast('Failed to update task status', 'error');
    }
}

// ── Quick Add Task in List ──
function openAddListTask(listId) {
    document.getElementById('add-list-trigger-' + listId)?.classList.add('hidden');
    const box = document.getElementById('add-list-input-box-' + listId);
    box?.classList.remove('hidden');
    document.getElementById('input-task-' + listId)?.focus();
}

function closeAddListTask(listId) {
    document.getElementById('add-list-trigger-' + listId)?.classList.remove('hidden');
    document.getElementById('add-list-input-box-' + listId)?.classList.add('hidden');
    const input = document.getElementById('input-task-' + listId);
    if (input) input.value = '';
}

async function submitQuickTask(listId) {
    const input = document.getElementById('input-task-' + listId);
    const title = input.value.trim();
    if (!title) return input.focus();

    try {
        const res = await fetch('/tasks', {
            method: 'POST',
            headers,
            body: JSON.stringify({ title, list_id: listId, project_id: PROJ_ID })
        });
        if (res.ok) {
            closeAddListTask(listId);
            window.showToast('Task created successfully!', 'success');
            setTimeout(() => location.reload(), 400);
        } else {
            window.showToast('Failed to create task', 'error');
        }
    } catch {
        window.showToast('Network error, please try again.', 'error');
    }
}

// ── Archive Task ──
async function archiveListTask(taskId) {
    if (!confirm('Are you sure you want to archive this task?')) return;
    const res = await fetch(`/tasks/${taskId}`, { method: 'DELETE', headers });
    if (res.ok) {
        document.querySelector(`.task-row[data-task-id="${taskId}"]`)?.remove();
        closeAllMenus();
        window.showToast('Task archived', 'success');
    }
}

// ── Row Context Menu ──
function toggleListRowMenu(taskId) {
    closeAllMenus();
    document.getElementById('row-menu-' + taskId)?.classList.toggle('open');
    event.stopPropagation();
}

function closeAllMenus() {
    document.querySelectorAll('.dropdown-menu.open').forEach(m => m.classList.remove('open'));
}

document.addEventListener('click', closeAllMenus);
</script>
@endpush
