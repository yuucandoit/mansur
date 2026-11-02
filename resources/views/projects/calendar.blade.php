@extends('layouts.app')

@section('title', $project->title . ' — Calendar')

@section('content')
<div class="flex flex-col h-full overflow-hidden bg-background">

    <!-- ── PROJECT HEADER ── -->
    @include('projects.partials.header')

    <!-- ── TOOLBAR ── -->
    <div class="px-6 py-2.5 border-b border-border bg-card/20 flex flex-wrap items-center justify-between gap-3 shrink-0">
        <!-- Month Navigation -->
        <div class="flex items-center gap-2">
            <h2 class="text-sm font-bold tracking-tight text-foreground min-w-[140px]" id="currentMonthYear">
                <!-- Javascript will populate e.g. October 2026 -->
            </h2>
            <div class="flex items-center gap-1 border border-border rounded-md p-0.5 bg-muted/40">
                <button type="button" 
                        onclick="prevMonth()" 
                        class="btn btn-ghost p-1 h-6 w-6 text-muted-foreground hover:text-foreground" 
                        title="Previous Month">
                    <i data-lucide="chevron-left" class="w-3.5 h-3.5"></i>
                </button>
                <button type="button" 
                        onclick="goToToday()" 
                        class="btn btn-ghost px-2 py-0.5 h-6 text-xs font-medium text-foreground hover:bg-background rounded" 
                        title="Today">
                    Today
                </button>
                <button type="button" 
                        onclick="nextMonth()" 
                        class="btn btn-ghost p-1 h-6 w-6 text-muted-foreground hover:text-foreground" 
                        title="Next Month">
                    <i data-lucide="chevron-right" class="w-3.5 h-3.5"></i>
                </button>
            </div>
        </div>

        <!-- Task Stats in Calendar -->
        <div class="flex items-center gap-3 text-xs text-muted-foreground">
            <span class="flex items-center gap-1.5">
                <span class="w-2 h-2 rounded-full bg-primary"></span>
                <span>Scheduled: <strong class="text-foreground" id="scheduledCount">0</strong></span>
            </span>
            <span class="flex items-center gap-1.5">
                <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                <span>Completed: <strong class="text-foreground" id="doneCount">0</strong></span>
            </span>
        </div>
    </div>

    <!-- ── CALENDAR GRID AREA ── -->
    <div class="flex-1 overflow-auto p-6">
        <div class="rounded-xl border border-border bg-card shadow-2xs overflow-hidden flex flex-col h-full min-h-[580px]">
            <!-- Day of Week Headers -->
            <div class="grid grid-cols-7 border-b border-border bg-muted/40 text-center text-[11px] font-semibold text-muted-foreground uppercase tracking-wider py-2 shrink-0">
                <div>Mon</div>
                <div>Tue</div>
                <div>Wed</div>
                <div>Thu</div>
                <div>Fri</div>
                <div class="text-primary font-bold">Sat</div>
                <div class="text-destructive font-bold">Sun</div>
            </div>

            <!-- Month Days Grid -->
            <div class="grid grid-cols-7 flex-1 divide-x divide-y divide-border/60" id="calendarDaysGrid">
                <!-- Javascript will populate days and tasks -->
            </div>
        </div>
    </div>

</div>

<!-- ── SHARED TASK DETAIL SHEET ── -->
@include('projects.partials.task-sheet')

@endsection

@php
    $calendarTasks = $allTasks->map(function($t) {
        return [
            'id' => $t->id,
            'title' => $t->title,
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
    const TASKS_DATA = @json($calendarTasks);

    let currentYear = new Date().getFullYear();
    let currentMonth = new Date().getMonth(); // 0-indexed

    function renderCalendar() {
        const monthNames = [
            "January", "February", "March", "April", "May", "June",
            "July", "August", "September", "October", "November", "December"
        ];

        document.getElementById('currentMonthYear').textContent = `${monthNames[currentMonth]} ${currentYear}`;

        const grid = document.getElementById('calendarDaysGrid');
        grid.innerHTML = '';

        // First day of current month
        const firstDay = new Date(currentYear, currentMonth, 1);
        // Day of week: 0 = Sunday, 1 = Monday, ... we want Monday = 0
        let startDayIndex = firstDay.getDay() - 1;
        if (startDayIndex === -1) startDayIndex = 6; // Sunday

        // Total days in current month
        const daysInMonth = new Date(currentYear, currentMonth + 1, 0).getDate();

        // Total days in previous month
        const daysInPrevMonth = new Date(currentYear, currentMonth, 0).getDate();

        const today = new Date();
        const todayStr = `${today.getFullYear()}-${String(today.getMonth() + 1).padStart(2, '0')}-${String(today.getDate()).padStart(2, '0')}`;

        let scheduledTasks = 0;
        let completedTasks = 0;

        // Days from previous month
        for (let i = startDayIndex - 1; i >= 0; i--) {
            const dayNum = daysInPrevMonth - i;
            const cell = createDayCell(dayNum, true, null);
            grid.appendChild(cell);
        }

        // Days of current month
        for (let day = 1; day <= daysInMonth; day++) {
            const dateStr = `${currentYear}-${String(currentMonth + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
            const isToday = dateStr === todayStr;
            
            // Find tasks due on this date
            const dayTasks = TASKS_DATA.filter(t => t.due_date === dateStr);
            if (dayTasks.length > 0) {
                scheduledTasks += dayTasks.length;
                completedTasks += dayTasks.filter(t => t.is_done).length;
            }

            const cell = createDayCell(day, false, dateStr, isToday, dayTasks);
            grid.appendChild(cell);
        }

        // Trailing days from next month to fill grid (total cells multiple of 7)
        const totalCells = startDayIndex + daysInMonth;
        const remaining = (7 - (totalCells % 7)) % 7;
        for (let day = 1; day <= remaining; day++) {
            const cell = createDayCell(day, true, null);
            grid.appendChild(cell);
        }

        document.getElementById('scheduledCount').textContent = scheduledTasks;
        document.getElementById('doneCount').textContent = completedTasks;
        window.lucide?.render();
    }

    function createDayCell(dayNumber, isOtherMonth, dateStr, isToday = false, tasks = []) {
        const cell = document.createElement('div');
        cell.className = `p-2 min-h-[90px] flex flex-col justify-between transition-colors ${
            isOtherMonth ? 'bg-muted/15 text-muted-foreground/40' : 'bg-card text-foreground hover:bg-muted/20'
        }`;

        // Top row of cell (Date Number)
        const topRow = document.createElement('div');
        topRow.className = 'flex items-center justify-between mb-1.5';

        const numSpan = document.createElement('span');
        if (isToday) {
            numSpan.className = 'w-6 h-6 rounded-full bg-primary text-primary-foreground text-xs font-bold flex items-center justify-center shadow-xs';
        } else {
            numSpan.className = `text-xs font-semibold ${isOtherMonth ? 'text-muted-foreground/40' : 'text-foreground'}`;
        }
        numSpan.textContent = dayNumber;
        topRow.appendChild(numSpan);

        if (tasks.length > 0 && !isOtherMonth) {
            const countSpan = document.createElement('span');
            countSpan.className = 'text-[10px] text-muted-foreground font-mono';
            countSpan.textContent = `${tasks.length}`;
            topRow.appendChild(countSpan);
        }

        cell.appendChild(topRow);

        // Tasks list inside cell
        const tasksContainer = document.createElement('div');
        tasksContainer.className = 'space-y-1 flex-1 overflow-y-auto max-h-24';

        tasks.forEach(t => {
            const taskPill = document.createElement('div');
            taskPill.className = `px-1.5 py-1 rounded text-[11px] font-medium border border-border/80 flex items-center gap-1.5 cursor-pointer hover:border-primary/50 transition-all truncate ${
                t.is_done ? 'bg-muted text-muted-foreground line-through' : 'bg-secondary/70 text-secondary-foreground shadow-2xs'
            }`;
            taskPill.title = `${t.title} (${t.list_title})`;
            taskPill.onclick = () => openTaskPanel(t.id);

            let iconHtml = '';
            if (t.is_critical) {
                iconHtml = '<i data-lucide="flag" class="w-3 h-3 text-destructive shrink-0"></i>';
            } else {
                iconHtml = `<span class="w-1.5 h-1.5 rounded-full ${t.is_done ? 'bg-emerald-500' : 'bg-primary'} shrink-0"></span>`;
            }

            taskPill.innerHTML = `${iconHtml}<span class="truncate">${escapeHtml(t.title)}</span>`;
            tasksContainer.appendChild(taskPill);
        });

        cell.appendChild(tasksContainer);
        return cell;
    }

    function prevMonth() {
        if (currentMonth === 0) {
            currentMonth = 11;
            currentYear--;
        } else {
            currentMonth--;
        }
        renderCalendar();
    }

    function nextMonth() {
        if (currentMonth === 11) {
            currentMonth = 0;
            currentYear++;
        } else {
            currentMonth++;
        }
        renderCalendar();
    }

    function goToToday() {
        const today = new Date();
        currentYear = today.getFullYear();
        currentMonth = today.getMonth();
        renderCalendar();
    }

    // Initial Render
    document.addEventListener('DOMContentLoaded', () => {
        renderCalendar();
    });
</script>
@endpush

