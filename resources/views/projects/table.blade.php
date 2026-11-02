@extends('layouts.app')

@section('title', $project->title . ' — Table View')

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
                       placeholder="Filter table rows..." 
                       oninput="filterTableRows(this.value)"
                       class="form-input text-xs pl-8 pr-12 h-8">
                <kbd class="pointer-events-none absolute right-2 top-1/2 -translate-y-1/2 text-[10px] font-mono text-muted-foreground bg-muted px-1.5 py-0.5 rounded border border-border">⌘F</kbd>
            </div>
        </div>

        <div class="flex items-center gap-2">
            <!-- Filter by Status -->
            <select id="statusFilter" onchange="filterByStatus(this.value)" class="form-select text-xs h-8 py-0 px-2.5 w-auto">
                <option value="">All Statuses</option>
                @foreach($boardLists as $list)
                <option value="{{ $list->id }}">{{ $list->title }}</option>
                @endforeach
            </select>

            <span class="text-xs text-muted-foreground font-mono" id="tableCountBadge">
                {{ $allTasks->count() }} {{ Str::plural('task', $allTasks->count()) }}
            </span>
        </div>
    </div>

    <!-- ── TABLE CONTENT ── -->
    <div class="flex-1 overflow-auto p-6">
        <div class="rounded-xl border border-border bg-card shadow-2xs overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs border-collapse" id="tasksTable">
                    <thead>
                        <tr class="border-b border-border bg-muted/40 font-medium text-muted-foreground text-[11px] uppercase tracking-wider">
                            <th class="py-3 px-4 w-10 text-center">
                                <input type="checkbox" id="selectAllCheckbox" onchange="toggleSelectAll(this)" class="shadcn-checkbox">
                            </th>
                            <th class="py-3 px-4 min-w-[240px]">Task Title</th>
                            <th class="py-3 px-4 min-w-[130px]">Status</th>
                            <th class="py-3 px-4 min-w-[100px]">Priority</th>
                            <th class="py-3 px-4 min-w-[110px]">Assignees</th>
                            <th class="py-3 px-4 min-w-[120px]">Due Date</th>
                            <th class="py-3 px-4 min-w-[100px]">Checklist</th>
                            <th class="py-3 px-4 w-12 text-right"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border/60">
                        @forelse($allTasks as $task)
                        @php
                            $dueCls = 'text-muted-foreground bg-muted';
                            $dueLabel = '—';
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
                                $dueLabel = $task->due_date->format('M j, Y');
                            }
                        @endphp
                        <tr class="table-row group hover:bg-muted/40 transition-colors" 
                            data-task-id="{{ $task->id }}"
                            data-list-id="{{ $task->list_id }}">
                            <!-- Checkbox -->
                            <td class="py-2.5 px-4 text-center">
                                <input type="checkbox" 
                                       {{ $task->is_done ? 'checked' : '' }}
                                       onchange="toggleTaskDone({{ $task->id }}, this)"
                                       class="row-checkbox shadcn-checkbox">
                            </td>

                            <!-- Title -->
                            <td class="py-2.5 px-4 font-medium text-foreground">
                                <div class="flex items-center gap-2">
                                    @if($task->is_critical)
                                    <i data-lucide="flag" class="w-3.5 h-3.5 text-destructive shrink-0" title="Critical"></i>
                                    @endif
                                    <span class="cursor-pointer hover:underline hover:text-primary transition-colors truncate {{ $task->is_done ? 'line-through text-muted-foreground' : '' }}"
                                          onclick="openTaskPanel({{ $task->id }})">
                                        {{ $task->title }}
                                    </span>
                                </div>
                            </td>

                            <!-- Status Dropdown / Badge -->
                            <td class="py-2.5 px-4">
                                <select onchange="changeTaskStatus({{ $task->id }}, this.value)" 
                                        class="form-select text-[11px] py-1 px-2 h-7 w-auto bg-transparent border border-border/80 rounded-md text-foreground cursor-pointer">
                                    @foreach($boardLists as $list)
                                    <option value="{{ $list->id }}" {{ $task->list_id == $list->id ? 'selected' : '' }}>
                                        {{ $list->title }}
                                    </option>
                                    @endforeach
                                </select>
                            </td>

                            <!-- Priority -->
                            <td class="py-2.5 px-4">
                                @if($task->is_critical || $task->priority === 'critical')
                                <span class="badge bg-destructive/10 text-destructive text-[10px] font-medium">Critical</span>
                                @elseif($task->priority === 'high')
                                <span class="badge bg-amber-500/10 text-amber-600 dark:text-amber-400 text-[10px] font-medium">High</span>
                                @elseif($task->priority === 'medium')
                                <span class="badge bg-blue-500/10 text-blue-600 dark:text-blue-400 text-[10px] font-medium">Medium</span>
                                @else
                                <span class="badge badge-secondary text-[10px] font-medium">Normal</span>
                                @endif
                            </td>

                            <!-- Assignees -->
                            <td class="py-2.5 px-4">
                                <div class="flex items-center -space-x-1.5 overflow-hidden">
                                    @forelse($task->assignees->take(2) as $assignee)
                                    <div class="w-5 h-5 rounded-full border border-background bg-secondary text-foreground text-[9px] font-semibold flex items-center justify-center shrink-0" 
                                         title="{{ $assignee->user?->name }}">
                                        {{ strtoupper(substr($assignee->user?->name ?? 'U', 0, 1)) }}
                                    </div>
                                    @empty
                                    <span class="text-[11px] text-muted-foreground/60">—</span>
                                    @endforelse
                                </div>
                            </td>

                            <!-- Due Date -->
                            <td class="py-2.5 px-4">
                                @if($task->due_date)
                                <span class="px-2 py-0.5 rounded text-[10px] font-medium {{ $dueCls }} inline-flex items-center gap-1">
                                    <i data-lucide="calendar" class="w-3 h-3"></i>
                                    <span>{{ $dueLabel }}</span>
                                </span>
                                @else
                                <span class="text-[11px] text-muted-foreground/60">—</span>
                                @endif
                            </td>

                            <!-- Checklist -->
                            <td class="py-2.5 px-4">
                                @if($task->checklists->count())
                                @php $done = $task->checklists->where('is_done', 1)->count(); @endphp
                                <span class="flex items-center gap-1 text-[11px] {{ $done === $task->checklists->count() ? 'text-emerald-600 font-medium' : 'text-muted-foreground' }}">
                                    <i data-lucide="check-square" class="w-3 h-3"></i>
                                    <span>{{ $done }}/{{ $task->checklists->count() }}</span>
                                </span>
                                @else
                                <span class="text-[11px] text-muted-foreground/60">—</span>
                                @endif
                            </td>

                            <!-- Action -->
                            <td class="py-2.5 px-4 text-right">
                                <div class="relative inline-block">
                                    <button type="button" 
                                            class="btn btn-ghost p-1 h-6 w-6 text-muted-foreground hover:text-foreground opacity-0 group-hover:opacity-100 transition-opacity"
                                            onclick="toggleTableRowMenu({{ $task->id }})">
                                        <i data-lucide="more-horizontal" class="w-3.5 h-3.5"></i>
                                    </button>
                                    <div class="dropdown-menu right-0 top-7" id="tbl-menu-{{ $task->id }}">
                                        <div class="dropdown-item" onclick="openTaskPanel({{ $task->id }})">
                                            <i data-lucide="eye" class="w-3.5 h-3.5"></i>
                                            <span>View Details</span>
                                        </div>
                                        <div class="dropdown-item danger" onclick="archiveTableRow({{ $task->id }})">
                                            <i data-lucide="archive" class="w-3.5 h-3.5"></i>
                                            <span>Archive Task</span>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="py-12 text-center text-xs text-muted-foreground">
                                No tasks found in this project.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Table Footer -->
            <div class="p-3 bg-muted/30 border-t border-border flex items-center justify-between text-xs text-muted-foreground">
                <div class="flex items-center gap-4">
                    <span>Total: <strong class="text-foreground">{{ $allTasks->count() }}</strong></span>
                    <span>Done: <strong class="text-emerald-600">{{ $allTasks->where('is_done', 1)->count() }}</strong></span>
                    <span>Pending: <strong class="text-foreground">{{ $allTasks->where('is_done', 0)->count() }}</strong></span>
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

// ── Search in Table ──
function filterTableRows(q) {
    q = q.toLowerCase();
    const rows = document.querySelectorAll('.table-row');
    let visibleCount = 0;
    
    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        const matches = !q || text.includes(q);
        row.style.display = matches ? '' : 'none';
        if (matches) visibleCount++;
    });

    const badge = document.getElementById('tableCountBadge');
    if (badge) badge.textContent = `${visibleCount} tasks`;
}

// ── Filter by Status Dropdown ──
function filterByStatus(listId) {
    const rows = document.querySelectorAll('.table-row');
    let visibleCount = 0;

    rows.forEach(row => {
        const matches = !listId || row.dataset.listId === listId;
        row.style.display = matches ? '' : 'none';
        if (matches) visibleCount++;
    });

    const badge = document.getElementById('tableCountBadge');
    if (badge) badge.textContent = `${visibleCount} tasks`;
}

// ── Select All Checkbox ──
function toggleSelectAll(master) {
    document.querySelectorAll('.row-checkbox').forEach(cb => {
        cb.checked = master.checked;
    });
}

// ── Toggle Done ──
async function toggleTaskDone(taskId, checkbox) {
    const isDone = checkbox.checked ? 1 : 0;
    const row = checkbox.closest('.table-row');
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

// ── Change Task Status via Table Dropdown ──
async function changeTaskStatus(taskId, newListId) {
    try {
        const res = await fetch(`/tasks/${taskId}`, {
            method: 'PUT',
            headers,
            body: JSON.stringify({ list_id: parseInt(newListId) })
        });
        if (res.ok) {
            const row = document.querySelector(`.table-row[data-task-id="${taskId}"]`);
            if (row) row.dataset.listId = newListId;
            window.showToast('Status updated successfully!', 'success');
        } else {
            window.showToast('Failed to update status', 'error');
        }
    } catch {
        window.showToast('Network error, please try again.', 'error');
    }
}

// ── Archive Row ──
async function archiveTableRow(taskId) {
    if (!confirm('Are you sure you want to archive this task?')) return;
    const res = await fetch(`/tasks/${taskId}`, { method: 'DELETE', headers });
    if (res.ok) {
        document.querySelector(`.table-row[data-task-id="${taskId}"]`)?.remove();
        closeAllMenus();
        window.showToast('Task archived', 'success');
    }
}

// ── Row Context Menu ──
function toggleTableRowMenu(taskId) {
    closeAllMenus();
    document.getElementById('tbl-menu-' + taskId)?.classList.toggle('open');
    event.stopPropagation();
}

function closeAllMenus() {
    document.querySelectorAll('.dropdown-menu.open').forEach(m => m.classList.remove('open'));
}

document.addEventListener('click', closeAllMenus);
</script>
@endpush

