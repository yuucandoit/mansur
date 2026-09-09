@extends('layouts.app')

@section('title', $project->title . ' — Board')

@section('content')

{{-- ── PROJECT HEADER ── --}}
<div class="project-header">
    <div class="top-row">
        <div class="flex items-center gap-2">
            <h2 class="project-title"
                id="projectTitleEl"
                contenteditable="true"
                data-id="{{ $project->id }}"
                onkeydown="if(event.key==='Enter'){event.preventDefault();this.blur()}"
                onblur="saveProjectTitle(this)">{{ $project->title }}</h2>
            <button onclick="toggleStar({{ $project->id }}, this)"
                    title="Star project"
                    style="background:none;border:none;cursor:pointer;font-size:.9rem;color:{{ $project->is_starred ? '#f59e0b' : '#cbd5e1' }};transition:color .15s"
                    id="starBtn">
                <i class="{{ $project->is_starred ? 'fas' : 'far' }} fa-star"></i>
            </button>
        </div>
        <button onclick="document.getElementById('newProjectModal').classList.add('open')"
                style="display:flex;align-items:center;gap:.4rem;padding:.4rem .875rem;background:#f1f5f9;border:none;border-radius:.5rem;font-size:.8rem;font-weight:500;color:#475569;cursor:pointer;font-family:inherit;transition:background .15s"
                onmouseover="this.style.background='#e2e8f0'" onmouseout="this.style.background='#f1f5f9'">
            <i class="fas fa-plus" style="font-size:.7rem"></i> New Project
        </button>
    </div>

    {{-- ── NAV TABS ── --}}
    <div class="nav-tabs">
        <a href="{{ route('projects.board', $project) }}" class="nav-tab active">
            <i class="fas fa-columns"></i> Board
        </a>
        <a href="#" class="nav-tab" onclick="showToast('List view coming soon','')">
            <i class="fas fa-list-ul"></i> List
        </a>
        <a href="#" class="nav-tab" onclick="showToast('Calendar coming soon','')">
            <i class="fas fa-calendar"></i> Calendar
        </a>
        <a href="#" class="nav-tab" onclick="showToast('Gantt coming soon','')">
            <i class="fas fa-chart-gantt"></i> Gantt
        </a>
        <a href="#" class="nav-tab" onclick="showToast('Table view coming soon','')">
            <i class="fas fa-table"></i> Table
        </a>
    </div>
</div>

{{-- ── TOOLBAR ── --}}
<div class="toolbar">
    <div class="search-box">
        <i class="fas fa-search"></i>
        <input type="text" id="searchInput" placeholder="Search task..." oninput="filterCards(this.value)">
    </div>
    <button class="toolbar-btn">
        <i class="fas fa-keyboard"></i> ⌘ F
    </button>
    <div style="width:1px;height:1.25rem;background:#e2e8f0"></div>
    <button class="toolbar-btn" onclick="showToast('Filter coming soon','')">
        <i class="fas fa-filter"></i> Filter
    </button>
    <button class="toolbar-btn" onclick="showToast('Sort coming soon','')">
        <i class="fas fa-sort"></i> Sort by
    </button>
    <button class="toolbar-btn active">
        <i class="fas fa-layer-group"></i> Group by status
    </button>
    <button class="toolbar-btn" onclick="showToast('Assignee filter coming soon','')">
        <i class="fas fa-user-friends"></i> Assignees
    </button>
</div>

{{-- ── BOARD AREA ── --}}
<div class="board-area">
    <div class="board-columns" id="boardColumns">

        @foreach($boardLists as $listIndex => $list)
        @php
            $colors = ['#ef4444','#f59e0b','#8b5cf6','#10b981','#3b82f6','#ec4899','#6366f1','#14b8a6'];
            $colColor = $colors[$listIndex % count($colors)];
            $doneCount = $list->tasks->where('is_done', 1)->count();
        @endphp

        <div class="board-column" data-list-id="{{ $list->id }}" data-is-done="{{ $list->is_done }}">

            {{-- Column Header --}}
            <div class="column-header" style="background:linear-gradient(135deg, {{ $colColor }} 0%, {{ $colColor }}dd 100%)">
                <div class="column-header-left">
                    <span class="column-title"
                          contenteditable="true"
                          onkeydown="if(event.key==='Enter'){event.preventDefault();this.blur()}"
                          onblur="saveListTitle(this, {{ $list->id }})">{{ $list->title }}</span>
                    <span class="column-count">{{ $doneCount }}/{{ $list->tasks->count() }}</span>
                </div>
                <div class="column-actions">
                    <button class="col-btn" onclick="openAddTask({{ $list->id }})" title="Add task">
                        <i class="fas fa-plus"></i>
                    </button>
                    <div style="position:relative">
                        <button class="col-btn" onclick="toggleColMenu({{ $list->id }})" title="Options">
                            <i class="fas fa-ellipsis-h"></i>
                        </button>
                        <div class="dropdown-menu" id="col-menu-{{ $list->id }}">
                            @if($listIndex > 0)
                            <div class="dropdown-item" onclick="moveList({{ $list->id }}, 'left')">
                                <i class="fas fa-arrow-left"></i> Move Left
                            </div>
                            @endif
                            @if($listIndex < $boardLists->count() - 1)
                            <div class="dropdown-item" onclick="moveList({{ $list->id }}, 'right')">
                                Move Right <i class="fas fa-arrow-right"></i>
                            </div>
                            @endif
                            <div class="dropdown-item danger" onclick="archiveList({{ $list->id }})">
                                <i class="fas fa-archive"></i> Archive List
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Task Cards --}}
            <div class="column-cards"
                 id="cards-{{ $list->id }}"
                 ondragover="allowDrop(event)"  
                 ondrop="dropTask(event, {{ $list->id }})"
                 ondragleave="dragLeave(event)">

                @foreach($list->tasks as $task)
                @php
                    $dueCls = '';
                    $dueLabel = '';
                    if ($task->due_date) {
                        $now = now();
                        if ($task->is_done) { $dueCls = 'ok'; }
                        elseif ($now->gt($task->due_date)) { $dueCls = 'overdue'; }
                        elseif ($now->diffInHours($task->due_date) <= 24) { $dueCls = 'soon'; }
                        else { $dueCls = 'ok'; }
                        $dueLabel = $task->due_date->format('M j');
                    }

                    // Assignee color helper (simple hash)
                    $assigneeColors = ['#6366f1','#8b5cf6','#ec4899','#f59e0b','#10b981','#3b82f6','#ef4444'];
                @endphp

                <div class="task-card"
                     draggable="true"
                     data-task-id="{{ $task->id }}"
                     data-list-id="{{ $list->id }}"
                     ondragstart="dragStart(event, {{ $task->id }}, {{ $list->id }})"
                     ondragend="dragEnd(event)"
                     onclick="openTaskPanel({{ $task->id }})">

                    {{-- More button --}}
                    <button class="card-more-btn" onclick="event.stopPropagation();toggleTaskMenu({{ $task->id }})">
                        <i class="fas fa-ellipsis-h"></i>
                    </button>

                    {{-- Task context menu --}}
                    <div class="dropdown-menu" id="task-menu-{{ $task->id }}" style="top:2rem;right:.5rem">
                        <div class="dropdown-item" onclick="event.stopPropagation();openTaskPanel({{ $task->id }})">
                            <i class="fas fa-eye"></i> View Details
                        </div>
                        <div class="dropdown-item danger" onclick="event.stopPropagation();archiveTask({{ $task->id }}, this)">
                            <i class="fas fa-archive"></i> Archive
                        </div>
                    </div>

                    {{-- Breadcrumb --}}
                    <div class="card-breadcrumb">
                        <span>{{ $project->workspace?->name ?? 'Workspace' }}</span>
                        <i class="fas fa-chevron-right"></i>
                        <span>{{ $project->title }}</span>
                        @if($task->is_critical)
                        <i class="fas fa-flag card-flag" title="Critical"></i>
                        @endif
                    </div>

                    {{-- Labels --}}
                    @if($task->labels->count())
                    <div class="card-labels">
                        @foreach($task->labels as $label)
                        <span class="card-label-pill" style="background: {{ $label->color ?? '#6366f1' }}">
                            {{ $label->name }}
                        </span>
                        @endforeach
                    </div>
                    @endif

                    {{-- Title --}}
                    <h3 class="card-title">{{ $task->title }}</h3>

                    {{-- Description --}}
                    @if($task->description)
                    <p class="card-desc">{{ strip_tags($task->description) }}</p>
                    @endif

                    {{-- Footer --}}
                    <div class="card-footer">
                        {{-- Assignees --}}
                        <div class="card-assignees">
                            @foreach($task->assignees->take(3) as $idx => $assignee)
                            @php
                                $aColor = $assigneeColors[crc32($assignee->user?->name ?? 'U') % count($assigneeColors)];
                                $initial = strtoupper(substr($assignee->user?->name ?? 'U', 0, 1));
                            @endphp
                            <div class="assignee-avatar" style="background:{{ $aColor }}" title="{{ $assignee->user?->name }}">
                                {{ $initial }}
                            </div>
                            @endforeach
                            @if($task->assignees->count() > 3)
                            <div class="assignee-avatar" style="background:#94a3b8">+{{ $task->assignees->count() - 3 }}</div>
                            @endif
                        </div>

                        {{-- Meta --}}
                        <div class="card-meta">
                            @if($task->comments->count())
                            <span class="card-meta-item" title="Comments">
                                <i class="fas fa-comment"></i> {{ $task->comments->count() }}
                            </span>
                            @endif
                            @if($task->attachments->count())
                            <span class="card-meta-item" title="Attachments">
                                <i class="fas fa-paperclip"></i> {{ $task->attachments->count() }}
                            </span>
                            @endif
                            @if($task->checklists->count())
                            @php $done = $task->checklists->where('is_done', 1)->count(); @endphp
                            <span class="card-meta-item" title="Checklist" style="{{ $done === $task->checklists->count() ? 'color:#10b981' : '' }}">
                                <i class="fas fa-check-square"></i> {{ $done }}/{{ $task->checklists->count() }}
                            </span>
                            @endif
                            @if($dueLabel)
                            <span class="card-due {{ $dueCls }}" title="Due date">
                                <i class="fas fa-clock"></i> {{ $dueLabel }}
                            </span>
                            @endif
                        </div>
                    </div>
                </div>
                @endforeach
            </div>

            {{-- Add Task --}}
            <div id="add-form-{{ $list->id }}" style="display:none">
                <div class="new-task-form">
                    <input class="new-task-input"
                           id="task-input-{{ $list->id }}"
                           type="text"
                           placeholder="Task title..."
                           onkeydown="if(event.key==='Enter') submitNewTask({{ $list->id }}, {{ $project->id }})">
                    <div class="new-task-actions">
                        <button class="btn-primary" onclick="submitNewTask({{ $list->id }}, {{ $project->id }})">Add task</button>
                        <button class="btn-ghost" onclick="closeAddTask({{ $list->id }})"><i class="fas fa-times"></i></button>
                    </div>
                </div>
            </div>

            <button class="add-card-btn" id="add-btn-{{ $list->id }}" onclick="openAddTask({{ $list->id }})">
                <i class="fas fa-plus"></i> Add card
            </button>
        </div>
        @endforeach

        {{-- ADD NEW LIST column --}}
        <div class="add-list-col">
            <button class="add-list-btn" id="addListBtn" onclick="openNewList()">
                <i class="fas fa-plus"></i> Add a new list
            </button>
            <div class="add-list-form" id="addListForm" style="display:none">
                <div class="form-group" style="margin-bottom:.625rem">
                    <input class="form-input new-task-input"
                           id="newListInput"
                           placeholder="List title..."
                           onkeydown="if(event.key==='Enter') submitNewList({{ $project->id }})">
                </div>
                <label style="display:flex;align-items:center;gap:.5rem;font-size:.8rem;color:#475569;cursor:pointer;margin-bottom:.625rem">
                    <input type="checkbox" id="newListIsDone" style="accent-color:#6366f1"> Mark as Done column
                </label>
                <div style="display:flex;gap:.5rem">
                    <button class="btn-primary" onclick="submitNewList({{ $project->id }})">Add list</button>
                    <button class="btn-ghost" onclick="closeNewList()">Cancel</button>
                </div>
            </div>
        </div>

    </div><!-- /board-columns -->
</div><!-- /board-area -->

{{-- ── TASK DETAIL PANEL ── --}}
<div class="task-panel-overlay" id="taskPanelOverlay" onclick="closeTaskPanel()"></div>
<div class="task-panel" id="taskPanel">
    <div class="task-panel-header">
        <span class="font-semibold" id="panelTitle" style="color:#0f172a;font-size:1rem">Task Details</span>
        <button class="modal-close" onclick="closeTaskPanel()"><i class="fas fa-times"></i></button>
    </div>
    <div class="task-panel-body" id="panelBody">
        <div style="text-align:center;color:#94a3b8;padding:2rem">
            <i class="fas fa-spinner fa-spin" style="font-size:1.5rem;margin-bottom:.75rem"></i>
            <p>Loading...</p>
        </div>
    </div>
</div>

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
        const title = card.querySelector('.card-title').textContent.toLowerCase();
        const desc  = card.querySelector('.card-desc')?.textContent.toLowerCase() ?? '';
        card.style.display = (!q || title.includes(q) || desc.includes(q)) ? '' : 'none';
    });
}

// ── DRAG & DROP ──
function dragStart(e, taskId, listId) {
    draggedTaskId = taskId;
    draggedFromListId = listId;
    e.currentTarget.classList.add('dragging');
    e.dataTransfer.effectAllowed = 'move';
}

function dragEnd(e) {
    e.currentTarget.classList.remove('dragging');
}

function allowDrop(e) {
    e.preventDefault();
    e.currentTarget.classList.add('drag-over');
}

function dragLeave(e) {
    e.currentTarget.classList.remove('drag-over');
}

async function dropTask(e, toListId) {
    e.preventDefault();
    e.currentTarget.classList.remove('drag-over');
    if (!draggedTaskId || toListId === draggedFromListId) return;

    const card = document.querySelector(`[data-task-id="${draggedTaskId}"]`);
    if (!card) return;

    // Move card in DOM
    e.currentTarget.insertBefore(card, null);
    card.dataset.listId = toListId;

    // Update order
    const items = buildOrderItems();

    // Save to server
    await fetch(`/tasks/${draggedTaskId}`, {
        method: 'PUT', headers,
        body: JSON.stringify({ list_id: toListId })
    });

    await fetch('/tasks/order', {
        method: 'POST', headers,
        body: JSON.stringify({ items })
    });

    // Update column counts
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
        const listId = col.dataset.listId;
        const all  = col.querySelectorAll('.task-card').length;
        const done = col.querySelectorAll('.task-card[data-done="1"]').length;
        const badge = col.querySelector('.column-count');
        if (badge) badge.textContent = done + '/' + all;
    });
}

// ── ADD TASK ──
function openAddTask(listId) {
    document.getElementById('add-btn-' + listId).style.display = 'none';
    document.getElementById('add-form-' + listId).style.display = 'block';
    document.getElementById('task-input-' + listId).focus();
}

function closeAddTask(listId) {
    document.getElementById('add-btn-' + listId).style.display = '';
    document.getElementById('add-form-' + listId).style.display = 'none';
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
            // Build and inject card
            const col = document.getElementById('cards-' + listId);
            col.insertAdjacentHTML('beforeend', buildTaskCard(task));
            closeAddTask(listId);
            updateColumnCounts();
            showToast('Task added!', 'success');
        } else {
            showToast('Failed to add task', 'error');
        }
    } catch {
        showToast('Network error', 'error');
    }
}

function buildTaskCard(task) {
    const ws = "{{ $project->workspace?->name ?? 'Workspace' }}";
    const proj = "{{ $project->title }}";
    return `
    <div class="task-card"
         draggable="true"
         data-task-id="${task.id}"
         data-list-id="${task.list_id}"
         ondragstart="dragStart(event, ${task.id}, ${task.list_id})"
         ondragend="dragEnd(event)"
         onclick="openTaskPanel(${task.id})">
        <button class="card-more-btn" onclick="event.stopPropagation();toggleTaskMenu(${task.id})">
            <i class="fas fa-ellipsis-h"></i>
        </button>
        <div class="dropdown-menu" id="task-menu-${task.id}" style="top:2rem;right:.5rem">
            <div class="dropdown-item" onclick="event.stopPropagation();openTaskPanel(${task.id})"><i class="fas fa-eye"></i> View Details</div>
            <div class="dropdown-item danger" onclick="event.stopPropagation();archiveTask(${task.id}, this)"><i class="fas fa-archive"></i> Archive</div>
        </div>
        <div class="card-breadcrumb">
            <span>${ws}</span> <i class="fas fa-chevron-right"></i> <span>${proj}</span>
        </div>
        <h3 class="card-title">${task.title}</h3>
        <div class="card-footer">
            <div class="card-assignees"></div>
            <div class="card-meta"></div>
        </div>
    </div>`;
}

// ── ARCHIVE TASK ──
async function archiveTask(taskId, el) {
    const card = document.querySelector(`[data-task-id="${taskId}"]`);
    if (!confirm('Archive this task?')) return;
    const res = await fetch(`/tasks/${taskId}`, { method: 'DELETE', headers });
    if (res.ok) {
        card?.remove();
        closeAllMenus();
        updateColumnCounts();
        showToast('Task archived', 'success');
    }
}

// ── ADD LIST ──
function openNewList() {
    document.getElementById('addListBtn').style.display = 'none';
    document.getElementById('addListForm').style.display = 'block';
    document.getElementById('newListInput').focus();
}

function closeNewList() {
    document.getElementById('addListBtn').style.display = '';
    document.getElementById('addListForm').style.display = 'none';
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
        showToast('List added! Refreshing...', 'success');
        setTimeout(() => location.reload(), 800);
    } else {
        showToast('Failed to create list', 'error');
    }
}

// ── SAVE LIST TITLE ──
async function saveListTitle(el, listId) {
    const title = el.innerText.trim();
    if (!title) return;
    await fetch(`/board-lists/${listId}`, {
        method: 'PUT', headers, body: JSON.stringify({ title })
    });
    showToast('List renamed', 'success');
}

// ── SAVE PROJECT TITLE ──
async function saveProjectTitle(el) {
    const title = el.innerText.trim();
    const id = el.dataset.id;
    if (!title) return;
    await fetch(`/projects/${id}`, {
        method: 'PUT', headers, body: JSON.stringify({ title })
    });
    document.title = title + ' — Board';
}

// ── TOGGLE STAR ──
async function toggleStar(projectId, btn) {
    const res = await fetch(`/projects/${projectId}/star`, { method: 'POST', headers });
    const data = await res.json();
    btn.style.color = data.is_starred ? '#f59e0b' : '#cbd5e1';
    btn.querySelector('i').className = data.is_starred ? 'fas fa-star' : 'far fa-star';
    showToast(data.is_starred ? 'Project starred ⭐' : 'Removed from stars');
}

// ── MOVE LIST ──
async function moveList(listId, dir) {
    const cols = [...document.querySelectorAll('.board-column')];
    const idx = cols.findIndex(c => c.dataset.listId == listId);
    if (dir === 'left' && idx === 0) return;
    if (dir === 'right' && idx === cols.length - 1) return;

    const swapIdx = dir === 'left' ? idx - 1 : idx + 1;
    const parent = cols[0].parentElement;
    const addListCol = document.querySelector('.add-list-col');

    // Swap in DOM
    if (dir === 'left') {
        parent.insertBefore(cols[idx], cols[swapIdx]);
    } else {
        parent.insertBefore(cols[swapIdx], cols[idx]);
    }

    // Collect new order and send
    const newOrder = [...parent.querySelectorAll('.board-column')].map((c, i) => ({
        id: parseInt(c.dataset.listId), order: i
    }));

    await fetch('/board-lists/order', { method: 'POST', headers, body: JSON.stringify({ items: newOrder }) });
    closeAllMenus();
    showToast('List moved', 'success');
}

// ── ARCHIVE LIST ──
async function archiveList(listId) {
    if (!confirm('Archive this list and all its tasks?')) return;
    const res = await fetch(`/board-lists/${listId}`, { method: 'DELETE', headers });
    if (res.ok) {
        document.querySelector(`.board-column[data-list-id="${listId}"]`)?.remove();
        closeAllMenus();
        showToast('List archived', 'success');
    }
}

// ── TASK PANEL ──
async function openTaskPanel(taskId) {
    document.getElementById('taskPanelOverlay').classList.add('open');
    document.getElementById('taskPanel').classList.add('open');
    document.getElementById('panelBody').innerHTML = `
        <div style="text-align:center;color:#94a3b8;padding:2rem">
            <i class="fas fa-spinner fa-spin" style="font-size:1.5rem;margin-bottom:.75rem;display:block"></i>
            Loading...
        </div>`;

    const res = await fetch(`/tasks/${taskId}`, { headers });
    const task = await res.json();
    renderTaskPanel(task);
}

function renderTaskPanel(task) {
    document.getElementById('panelTitle').textContent = task.title;

    const dueStr = task.due_date ? new Date(task.due_date).toLocaleDateString('en-GB') : '—';
    const assignees = (task.assignees || []).map(a =>
        `<span style="background:#6366f1;color:#fff;padding:.25rem .625rem;border-radius:99px;font-size:.75rem">${a.user?.name ?? 'User'}</span>`
    ).join(' ');

    const checklists = (task.checklists || []).map(c => `
        <label style="display:flex;align-items:center;gap:.5rem;padding:.375rem 0;font-size:.83rem;color:#475569">
            <input type="checkbox" ${c.is_done ? 'checked' : ''} style="accent-color:#6366f1">
            ${c.title}
        </label>`).join('');

    const comments = (task.comments || []).map(c => `
        <div style="padding:.625rem 0;border-bottom:1px solid #f1f5f9">
            <div style="font-size:.75rem;font-weight:600;color:#475569;margin-bottom:.25rem">${c.user?.name ?? 'User'}</div>
            <p style="font-size:.82rem;color:#1e293b">${c.details ?? c.comment ?? ''}</p>
        </div>`).join('');

    document.getElementById('panelBody').innerHTML = `
        <div style="margin-bottom:1.25rem">
            <div style="font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#94a3b8;margin-bottom:.375rem">Title</div>
            <p style="font-size:.95rem;font-weight:600;color:#0f172a">${task.title}</p>
        </div>

        ${task.description ? `
        <div style="margin-bottom:1.25rem">
            <div style="font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#94a3b8;margin-bottom:.375rem">Description</div>
            <p style="font-size:.83rem;color:#475569;line-height:1.6">${task.description}</p>
        </div>` : ''}

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;margin-bottom:1.25rem">
            <div>
                <div style="font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#94a3b8;margin-bottom:.375rem">Status</div>
                <span style="background:#eff6ff;color:#3b82f6;padding:.25rem .625rem;border-radius:.375rem;font-size:.78rem;font-weight:500">${task.list?.title ?? '—'}</span>
            </div>
            <div>
                <div style="font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#94a3b8;margin-bottom:.375rem">Due Date</div>
                <span style="font-size:.83rem;color:#475569">${dueStr}</span>
            </div>
        </div>

        ${assignees ? `
        <div style="margin-bottom:1.25rem">
            <div style="font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#94a3b8;margin-bottom:.5rem">Assignees</div>
            <div style="display:flex;flex-wrap:wrap;gap:.375rem">${assignees}</div>
        </div>` : ''}

        ${checklists ? `
        <div style="margin-bottom:1.25rem">
            <div style="font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#94a3b8;margin-bottom:.375rem">Checklist</div>
            ${checklists}
        </div>` : ''}

        <div>
            <div style="font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#94a3b8;margin-bottom:.5rem">Comments</div>
            ${comments || '<p style="font-size:.8rem;color:#94a3b8">No comments yet.</p>'}

            <div style="margin-top:.875rem;display:flex;gap:.5rem">
                <input id="commentInput" class="form-input" placeholder="Write a comment..." style="flex:1;font-size:.82rem;padding:.4rem .75rem">
                <button class="btn-primary" style="flex:none;white-space:nowrap;padding:.4rem 1rem"
                        onclick="submitComment(${task.id})">Send</button>
            </div>
        </div>`;
}

function closeTaskPanel() {
    document.getElementById('taskPanelOverlay').classList.remove('open');
    document.getElementById('taskPanel').classList.remove('open');
}

// ── COMMENT ──
async function submitComment(taskId) {
    const input = document.getElementById('commentInput');
    const details = input.value.trim();
    if (!details) return;
    showToast('Comment feature coming soon 🚧');
    input.value = '';
}

// ── DROPDOWN MENUS ──
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
