@extends('layouts.app')

@section('title', $project->title . ' — Gantt Timeline')

@section('content')
<div class="flex flex-col h-full overflow-hidden bg-background">

    <!-- ── PROJECT HEADER ── -->
    @include('projects.partials.header')

    <!-- ── TOOLBAR ── -->
    <div class="px-6 py-2.5 border-b border-border bg-card/20 flex flex-wrap items-center justify-between gap-3 shrink-0">
        <div class="flex items-center gap-2">
            <h2 class="text-sm font-bold tracking-tight text-foreground min-w-[140px]" id="ganttTimelineLabel">
                <!-- Timeline Range -->
            </h2>
            <div class="flex items-center gap-1 border border-border rounded-md p-0.5 bg-muted/40">
                <button type="button" 
                        onclick="prevTimelineWindow()" 
                        class="btn btn-ghost p-1 h-6 w-6 text-muted-foreground hover:text-foreground" 
                        title="Scroll Back">
                    <i data-lucide="chevron-left" class="w-3.5 h-3.5"></i>
                </button>
                <button type="button" 
                        onclick="jumpToToday()" 
                        class="btn btn-ghost px-2 py-0.5 h-6 text-xs font-medium text-foreground hover:bg-background rounded" 
                        title="Today">
                    Today
                </button>
                <button type="button" 
                        onclick="nextTimelineWindow()" 
                        class="btn btn-ghost p-1 h-6 w-6 text-muted-foreground hover:text-foreground" 
                        title="Scroll Forward">
                    <i data-lucide="chevron-right" class="w-3.5 h-3.5"></i>
                </button>
            </div>
        </div>

        <!-- Legend -->
        <div class="flex items-center gap-4 text-xs text-muted-foreground">
            <span class="flex items-center gap-1.5">
                <span class="w-3 h-2 rounded-xs bg-primary"></span>
                <span>Active Task</span>
            </span>
            <span class="flex items-center gap-1.5">
                <span class="w-3 h-2 rounded-xs bg-emerald-500"></span>
                <span>Completed</span>
            </span>
            <span class="flex items-center gap-1.5">
                <span class="w-3 h-2 rounded-xs bg-destructive"></span>
                <span>Critical / Overdue</span>
            </span>
        </div>
    </div>

    <!-- ── GANTT CHART CONTAINER ── -->
    <div class="flex-1 overflow-hidden p-6 flex flex-col">
        <div class="rounded-xl border border-border bg-card shadow-2xs overflow-hidden flex flex-1">
            
            <!-- Left Task Sidebar -->
            <div class="w-64 border-r border-border bg-card shrink-0 flex flex-col z-10">
                <div class="h-10 px-4 border-b border-border bg-muted/40 flex items-center font-semibold text-[11px] text-muted-foreground uppercase tracking-wider shrink-0">
                    Tasks ({{ $allTasks->count() }})
                </div>
                <div class="flex-1 overflow-y-auto divide-y divide-border/50" id="ganttTaskSidebar">
                    @forelse($allTasks as $task)
                    <div class="h-10 px-4 flex items-center justify-between gap-2 text-xs font-medium text-foreground hover:bg-muted/40 transition-colors cursor-pointer"
                         onclick="openTaskPanel({{ $task->id }})"
                         title="{{ $task->title }}">
                        <div class="flex items-center gap-2 truncate">
                            @if($task->is_critical)
                            <i data-lucide="flag" class="w-3.5 h-3.5 text-destructive shrink-0"></i>
                            @else
                            <span class="w-2 h-2 rounded-full {{ $task->is_done ? 'bg-emerald-500' : 'bg-primary' }} shrink-0"></span>
                            @endif
                            <span class="truncate {{ $task->is_done ? 'line-through text-muted-foreground' : '' }}">{{ $task->title }}</span>
                        </div>
                    </div>
                    @empty
                    <div class="p-4 text-xs text-muted-foreground/60 italic">No tasks created.</div>
                    @endforelse
                </div>
            </div>

            <!-- Right Timeline Grid & Bars -->
            <div class="flex-1 overflow-x-auto overflow-y-hidden flex flex-col min-w-0" id="ganttTimelineScroll">
                <!-- Timeline Dates Header -->
                <div class="h-10 border-b border-border bg-muted/40 flex items-center shrink-0 min-w-max" id="ganttHeaderDays">
                    <!-- Populated by JavaScript -->
                </div>

                <!-- Timeline Rows with Bars -->
                <div class="flex-1 overflow-y-auto relative min-w-max" id="ganttBarsContainer">
                    <!-- Populated by JavaScript -->
                </div>
            </div>

        </div>
    </div>

</div>

<!-- ── SHARED TASK DETAIL SHEET ── -->
@include('projects.partials.task-sheet')

@endsection

@php
    $ganttTasks = $allTasks->map(function($t) {
        return [
            'id' => $t->id,
            'title' => $t->title,
            'created_at' => $t->created_at ? $t->created_at->format('Y-m-d') : null,
            'due_date' => $t->due_date ? $t->due_date->format('Y-m-d') : null,
            'is_done' => $t->is_done ? 1 : 0,
            'is_critical' => $t->is_critical ? 1 : 0,
            'list_id' => $t->list_id,
            'list_title' => $t->list?->title ?? '',
        ];
    });
@endphp

@push('scripts')
<script>
const PROJ_ID = {{ $project->id }};
const TASKS_DATA = @json($ganttTasks);

const DAY_WIDTH = 48; // Width in px for each day column
let startDateOffset = 0; // Days offset from today
const TOTAL_DAYS_VIEW = 30; // Display 30 days window

function renderGantt() {
    const today = new Date();
    today.setHours(0,0,0,0);

    const baseStart = new Date(today);
    baseStart.setDate(baseStart.getDate() + startDateOffset - 3); // Start 3 days before today by default

    const baseEnd = new Date(baseStart);
    baseEnd.setDate(baseEnd.getDate() + TOTAL_DAYS_VIEW - 1);

    const monthNames = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];
    document.getElementById('ganttTimelineLabel').textContent = `${monthNames[baseStart.getMonth()]} ${baseStart.getDate()} – ${monthNames[baseEnd.getMonth()]} ${baseEnd.getDate()}, ${baseEnd.getFullYear()}`;

    // Render Timeline Header Days
    const headerEl = document.getElementById('ganttHeaderDays');
    headerEl.innerHTML = '';

    const daysArray = [];
    for (let i = 0; i < TOTAL_DAYS_VIEW; i++) {
        const d = new Date(baseStart);
        d.setDate(d.getDate() + i);
        daysArray.push(d);

        const isToday = d.getTime() === today.getTime();
        const isWeekend = d.getDay() === 0 || d.getDay() === 6;

        const dayCol = document.createElement('div');
        dayCol.className = `w-[48px] h-full flex flex-col items-center justify-center border-r border-border/60 text-[10px] select-none ${
            isToday ? 'bg-primary/10 font-bold text-primary' : (isWeekend ? 'bg-muted/30 text-muted-foreground/60' : 'text-muted-foreground')
        }`;
        dayCol.innerHTML = `
            <span class="leading-none">${['S','M','T','W','T','F','S'][d.getDay()]}</span>
            <span class="text-xs font-semibold mt-0.5 leading-none">${d.getDate()}</span>
        `;
        headerEl.appendChild(dayCol);
    }

    // Render Timeline Rows & Task Bars
    const container = document.getElementById('ganttBarsContainer');
    container.innerHTML = '';

    TASKS_DATA.forEach(task => {
        const row = document.createElement('div');
        row.className = 'h-10 flex items-center border-b border-border/50 relative hover:bg-muted/20 transition-colors';

        // Render background grid lines for days
        for (let i = 0; i < TOTAL_DAYS_VIEW; i++) {
            const d = daysArray[i];
            const isToday = d.getTime() === today.getTime();
            const isWeekend = d.getDay() === 0 || d.getDay() === 6;

            const gridCell = document.createElement('div');
            gridCell.className = `w-[48px] h-full border-r border-border/40 shrink-0 ${
                isToday ? 'bg-primary/5' : (isWeekend ? 'bg-muted/20' : '')
            }`;
            row.appendChild(gridCell);
        }

        // Render Task Bar if dates exist
        const taskCreated = task.created_at ? new Date(task.created_at) : new Date(today);
        taskCreated.setHours(0,0,0,0);

        let taskEnd = task.due_date ? new Date(task.due_date) : null;
        if (taskEnd) taskEnd.setHours(0,0,0,0);
        else {
            // Default 2 days duration if no due date specified
            taskEnd = new Date(taskCreated);
            taskEnd.setDate(taskEnd.getDate() + 2);
        }

        // Calculate offset in days from baseStart
        const startDiffDays = Math.round((taskCreated - baseStart) / (1000 * 60 * 60 * 24));
        const durationDays = Math.max(1, Math.round((taskEnd - taskCreated) / (1000 * 60 * 60 * 24)) + 1);

        // Only render bar if visible in current window
        if (startDiffDays + durationDays > 0 && startDiffDays < TOTAL_DAYS_VIEW) {
            const leftPx = Math.max(0, startDiffDays * DAY_WIDTH);
            const widthPx = Math.max(DAY_WIDTH * 0.8, durationDays * DAY_WIDTH - 6);

            const bar = document.createElement('div');
            let barBg = 'bg-primary text-primary-foreground';
            if (task.is_done) {
                barBg = 'bg-emerald-600 text-white';
            } else if (task.is_critical || (taskEnd < today && !task.is_done)) {
                barBg = 'bg-destructive text-destructive-foreground';
            }

            bar.className = `absolute h-6 rounded-md ${barBg} text-[11px] font-medium px-2 flex items-center justify-between shadow-2xs cursor-pointer hover:opacity-90 hover:shadow-xs transition-all truncate z-5`;
            bar.style.left = `${leftPx + 3}px`;
            bar.style.width = `${widthPx}px`;
            bar.onclick = () => openTaskPanel(task.id);
            bar.title = `${task.title} (${task.list_title})`;

            bar.innerHTML = `
                <span class="truncate">${escapeHtml(task.title)}</span>
                <span class="text-[9px] opacity-75 shrink-0 ml-1">${durationDays}d</span>
            `;

            row.appendChild(bar);
        }

        container.appendChild(row);
    });

    window.lucide?.render();
}

function prevTimelineWindow() {
    startDateOffset -= 7;
    renderGantt();
}

function nextTimelineWindow() {
    startDateOffset += 7;
    renderGantt();
}

function jumpToToday() {
    startDateOffset = 0;
    renderGantt();
}

// Initial Render
document.addEventListener('DOMContentLoaded', () => {
    renderGantt();
});
</script>
@endpush

