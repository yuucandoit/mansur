@extends('layouts.app')

@section('title', 'All Projects')

@section('content')
<div class="flex flex-col h-full overflow-hidden bg-background">
    <!-- Header -->
    <div class="px-6 py-5 border-b border-border bg-card/40 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 shrink-0">
        <div>
            <h1 class="text-xl font-bold tracking-tight text-foreground">All Projects</h1>
            <p class="text-xs text-muted-foreground mt-0.5">
                {{ $projects->count() }} {{ Str::plural('project', $projects->count()) }} across {{ $workspaces->count() }} {{ Str::plural('workspace', $workspaces->count()) }}
            </p>
        </div>
        <div class="flex items-center gap-2">
            <button type="button" 
                    onclick="window.openModal('newWorkspaceModal')" 
                    class="btn btn-outline btn-sm">
                <i data-lucide="folder-plus" class="w-3.5 h-3.5"></i>
                <span>New Workspace</span>
            </button>
            <button type="button" 
                    onclick="window.openModal('newProjectModal')" 
                    class="btn btn-primary btn-sm">
                <i data-lucide="plus" class="w-3.5 h-3.5"></i>
                <span>New Project</span>
            </button>
        </div>
    </div>

    <!-- Projects Scroll Area -->
    <div class="flex-1 overflow-y-auto px-6 py-6 space-y-8">
        @if(session('status'))
        <div class="rounded-lg bg-emerald-500/10 border border-emerald-500/20 p-3 text-xs text-emerald-600 dark:text-emerald-400">
            {{ session('status') }}
        </div>
        @endif

        @if($errors->any())
        <div class="rounded-lg bg-destructive/10 border border-destructive/20 p-3 text-xs text-destructive space-y-1">
            @foreach($errors->all() as $error)
            <p class="flex items-center gap-1.5">
                <i data-lucide="alert-circle" class="w-3.5 h-3.5 shrink-0"></i>
                <span>{{ $error }}</span>
            </p>
            @endforeach
        </div>
        @endif
        @forelse($workspaces as $ws)
        <section class="space-y-3.5">
            <!-- Workspace Section Header -->
            <div class="flex items-center justify-between pb-2 border-b border-border/60">
                <div class="flex items-center gap-2.5">
                    <div class="w-12 h-12 rounded-md bg-secondary border border-border text-foreground flex items-center justify-center font-bold text-xs shrink-0">
                        {{ strtoupper(substr($ws->name, 0, 1)) }}
                    </div>
                    <h2 class="text-sm font-semibold tracking-tight text-foreground">{{ $ws->name }}</h2>
                    <span class="badge badge-secondary text-[11px] font-normal">
                        {{ $ws->projects->count() }} {{ Str::plural('project', $ws->projects->count()) }}
                    </span>
                </div>
                <a href="{{ route('workspaces.members', $ws) }}"
                   class="btn btn-ghost btn-sm text-[11px] text-muted-foreground hover:text-foreground">
                    <i data-lucide="users" class="w-3.5 h-3.5"></i>
                    <span>Members</span>
                </a>
            </div>

            <!-- Workspace Projects Grid -->
            @if($ws->projects->count())
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                @foreach($ws->projects as $proj)
                <a href="{{ route('projects.board', $proj) }}" 
                   class="group relative flex flex-col justify-between rounded-xl border border-border bg-card p-4 hover:border-border hover:shadow-sm hover:-translate-y-0.5 transition-all duration-150">
                    <div>
                        <div class="flex items-start justify-between gap-2 mb-2">
                            <h3 class="text-sm font-semibold text-foreground group-hover:text-primary transition-colors line-clamp-1 leading-snug">
                                {{ $proj->title }}
                            </h3>
                            @if($proj->is_starred)
                            <i data-lucide="star" class="w-3.5 h-3.5 text-amber-500 fill-amber-500 shrink-0"></i>
                            @endif
                        </div>

                        @if($proj->description)
                        <p class="text-xs text-muted-foreground line-clamp-2 leading-relaxed mb-4">
                            {{ $proj->description }}
                        </p>
                        @else
                        <p class="text-xs text-muted-foreground/50 italic mb-4">
                            No description provided.
                        </p>
                        @endif
                    </div>

                    <div class="pt-3 border-t border-border/50 flex items-center justify-between text-xs text-muted-foreground mt-2">
                        <span class="badge badge-secondary text-[11px] gap-1 px-2 py-0.5">
                            <i data-lucide="check-square" class="w-3 h-3 text-muted-foreground"></i>
                            <span>{{ $proj->tasks()->count() }} {{ Str::plural('task', $proj->tasks()->count()) }}</span>
                        </span>

                        @if($proj->end_date)
                        <span class="flex items-center gap-1 text-[11px]">
                            <i data-lucide="calendar" class="w-3 h-3 text-muted-foreground"></i>
                            <span>{{ $proj->end_date->format('M j, Y') }}</span>
                        </span>
                        @endif
                    </div>
                </a>
                @endforeach
            </div>
            @else
            <div class="rounded-lg border border-dashed border-border p-6 text-center">
                @if(($ws->projects_count ?? 0) > 0)
                <p class="text-xs text-muted-foreground">You have not joined any project in this workspace yet.</p>
                <a href="{{ route('workspaces.join-project', $ws) }}"
                   class="btn btn-outline btn-sm mt-2 text-primary hover:text-primary">
                    <i data-lucide="log-in" class="w-3.5 h-3.5"></i>
                    <span>Choose a project to join</span>
                </a>
                @else
                <p class="text-xs text-muted-foreground">No projects in this workspace yet.</p>
                <button type="button" 
                        onclick="window.openModal('newProjectModal')" 
                        class="btn btn-ghost btn-sm mt-2 text-primary hover:text-primary">
                    <i data-lucide="plus" class="w-3.5 h-3.5"></i>
                    <span>Create a project here</span>
                </button>
                @endif
            </div>
            @endif
        </section>
        @empty
        <!-- Empty State -->
        <div class="h-96 flex flex-col items-center justify-center text-center p-8 rounded-xl border border-dashed border-border">
            <div class="w-12 h-12 rounded-full bg-secondary flex items-center justify-center text-muted-foreground mb-4">
                <i data-lucide="folder-kanban" class="w-6 h-6"></i>
            </div>
            <h3 class="text-base font-semibold text-foreground">No projects yet</h3>
            <p class="text-xs text-muted-foreground max-w-sm mt-1 mb-5">
                Get started by creating your first workspace and project to start tracking your team's workflow.
            </p>
            <button type="button" 
                    onclick="window.openModal('newProjectModal')" 
                    class="btn btn-primary btn-sm">
                <i data-lucide="plus" class="w-4 h-4"></i>
                <span>Create Your First Project</span>
            </button>
        </div>
        @endforelse
    </div>
</div>
@endsection
