<div class="px-6 py-3.5 border-b border-border bg-card/40 flex flex-col gap-3 shrink-0">
    <div class="flex items-center justify-between gap-4">
        <div class="flex items-center gap-2.5 min-w-0">
            <h1 class="text-lg font-bold tracking-tight text-foreground truncate px-1 -mx-1 rounded hover:bg-muted/50 cursor-text outline-none focus:ring-1 focus:ring-ring"
                id="projectTitleEl"
                contenteditable="true"
                data-id="{{ $project->id }}"
                onkeydown="if(event.key==='Enter'){event.preventDefault();this.blur()}"
                onblur="saveProjectTitle(this)">{{ $project->title }}</h1>
            
            <button type="button" 
                    onclick="toggleStar({{ $project->id }}, this)"
                    title="Star project"
                    class="btn btn-ghost p-1 h-8 w-8 text-muted-foreground hover:text-amber-500 transition-colors"
                    id="starBtn">
                <i data-lucide="star" class="w-4 h-4 {{ $project->is_starred ? 'text-amber-500 fill-amber-500' : 'text-muted-foreground' }}"></i>
            </button>

            @if($project->workspace)
            <span class="badge badge-secondary text-[11px] font-normal hidden sm:inline-flex">
                <i data-lucide="briefcase" class="w-3 h-3 text-muted-foreground"></i>
                <span>{{ $project->workspace->name }}</span>
            </span>
            @endif
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ route('projects.members', $project) }}" class="btn btn-outline btn-sm">
                <i data-lucide="users" class="w-3.5 h-3.5"></i>
                <span class="hidden sm:inline">Members</span>
            </a>
            @if(auth()->user()->canManageProject($project))
            <button type="button"
                    data-project-id="{{ $project->id }}"
                    data-project-title="{{ $project->title }}"
                    onclick="window.openInviteModal('project', this.dataset.projectId, this.dataset.projectTitle)"
                    class="btn btn-outline btn-sm">
                <i data-lucide="user-plus" class="w-3.5 h-3.5"></i>
                <span class="hidden sm:inline">Invite</span>
            </button>
            @endif
            <button type="button" 
                    onclick="window.openModal('newProjectModal')"
                    class="btn btn-outline btn-sm">
                <i data-lucide="plus" class="w-3.5 h-3.5"></i>
                <span>New Project</span>
            </button>
            @if(auth()->user()->canDeleteProject($project))
            <form method="POST"
                  action="{{ route('projects.destroy', $project) }}"
                  onsubmit="return confirm('Delete {{ addslashes($project->title) }} and all of its tasks? This cannot be undone.')">
                @csrf
                @method('DELETE')
                <button type="submit"
                        class="btn btn-ghost btn-sm text-muted-foreground hover:text-destructive"
                        title="Delete project">
                    <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                </button>
            </form>
            @endif
        </div>
    </div>

    <!-- Navigation Tabs (shadcn Segmented Tabs style) -->
    <div class="flex items-center justify-between border-t border-border/40 pt-2 -mb-1">
        <div class="inline-flex h-8 items-center justify-center rounded-lg bg-muted/60 p-1 text-muted-foreground text-xs font-medium">
            <a href="{{ route('projects.board', $project) }}" 
               class="inline-flex items-center gap-1.5 px-3 py-1 rounded-md text-xs font-medium {{ request()->routeIs('projects.board') ? 'bg-background text-foreground shadow-2xs font-semibold' : 'hover:text-foreground transition-colors' }}">
                <i data-lucide="kanban" class="w-3.5 h-3.5"></i>
                <span>Board</span>
            </a>
            <a href="{{ route('projects.list', $project) }}" 
               class="inline-flex items-center gap-1.5 px-3 py-1 rounded-md text-xs font-medium {{ request()->routeIs('projects.list') ? 'bg-background text-foreground shadow-2xs font-semibold' : 'hover:text-foreground transition-colors' }}">
                <i data-lucide="list" class="w-3.5 h-3.5"></i>
                <span>List</span>
            </a>
            <a href="{{ route('projects.calendar', $project) }}" 
               class="inline-flex items-center gap-1.5 px-3 py-1 rounded-md text-xs font-medium {{ request()->routeIs('projects.calendar') ? 'bg-background text-foreground shadow-2xs font-semibold' : 'hover:text-foreground transition-colors' }}">
                <i data-lucide="calendar" class="w-3.5 h-3.5"></i>
                <span>Calendar</span>
            </a>
            <a href="{{ route('projects.gantt', $project) }}" 
               class="inline-flex items-center gap-1.5 px-3 py-1 rounded-md text-xs font-medium {{ request()->routeIs('projects.gantt') ? 'bg-background text-foreground shadow-2xs font-semibold' : 'hover:text-foreground transition-colors' }}">
                <i data-lucide="chart-gantt" class="w-3.5 h-3.5"></i>
                <span>Gantt</span>
            </a>
            <a href="{{ route('projects.table', $project) }}" 
               class="inline-flex items-center gap-1.5 px-3 py-1 rounded-md text-xs font-medium {{ request()->routeIs('projects.table') ? 'bg-background text-foreground shadow-2xs font-semibold' : 'hover:text-foreground transition-colors' }}">
                <i data-lucide="table" class="w-3.5 h-3.5"></i>
                <span>Table</span>
            </a>
        </div>
    </div>
</div>

