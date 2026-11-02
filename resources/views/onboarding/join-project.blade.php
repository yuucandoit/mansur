@extends('layouts.app')

@section('title', 'Join a project')

@section('content')
@php
    $available = $projects->whereNotIn('id', $joinedProjectIds);
@endphp
<div class="flex-1 overflow-y-auto bg-background">
    <div class="max-w-3xl mx-auto px-6 py-10 space-y-6">

        <div class="space-y-2">
            <a href="{{ route('projects.index') }}" class="text-xs text-muted-foreground hover:text-foreground inline-flex items-center gap-1.5">
                <i data-lucide="arrow-left" class="w-3.5 h-3.5"></i>
                <span>All projects</span>
            </a>
            <h1 class="text-2xl font-bold tracking-tight text-foreground">
                Join a project in {{ $workspace->name }}
            </h1>
            <p class="text-sm text-muted-foreground">
                Workspace membership always comes with project access. Please select the
                project you want to work on to continue.
            </p>
        </div>

        @if(session('status'))
        <div class="rounded-lg bg-emerald-500/10 border border-emerald-500/20 p-3 text-xs text-emerald-600 dark:text-emerald-400">
            {{ session('status') }}
        </div>
        @endif

        @if($available->isEmpty())
        <div class="rounded-xl border border-dashed border-border p-8 text-center">
            <div class="w-11 h-11 rounded-full bg-secondary flex items-center justify-center text-muted-foreground mx-auto mb-3">
                <i data-lucide="folder-kanban" class="w-5 h-5"></i>
            </div>
            <h2 class="text-sm font-semibold text-foreground">No projects to join yet</h2>
            <p class="text-xs text-muted-foreground mt-1 mb-4">
                This workspace does not have any projects available for you.
                Create the first project and you will be added automatically.
            </p>
            <button type="button"
                    onclick="openNewProjectForWorkspace({{ $workspace->id }})"
                    class="btn btn-primary btn-sm">
                <i data-lucide="plus" class="w-3.5 h-3.5"></i>
                <span>Create a project</span>
            </button>
        </div>
        @else
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            @foreach($available as $project)
            <form method="POST"
                  action="{{ route('workspaces.join-project.store', $workspace) }}"
                  class="rounded-xl border border-border bg-card p-5 flex flex-col gap-4 hover:border-primary/40 transition-colors">
                @csrf
                <input type="hidden" name="project_id" value="{{ $project->id }}">
                <div>
                    <div class="flex items-start justify-between gap-2">
                        <h2 class="text-sm font-semibold text-foreground leading-snug">{{ $project->title }}</h2>
                        @if($project->is_starred)
                        <i data-lucide="star" class="w-3.5 h-3.5 text-amber-500 fill-amber-500 shrink-0"></i>
                        @endif
                    </div>
                    <p class="text-xs text-muted-foreground mt-1 line-clamp-2">
                        {{ $project->description ?: 'No description provided.' }}
                    </p>
                </div>
                <div class="flex items-center justify-between gap-2 mt-auto pt-3 border-t border-border/50">
                    <span class="text-[11px] text-muted-foreground flex items-center gap-1">
                        <i data-lucide="check-square" class="w-3 h-3"></i>
                        {{ $project->tasks()->count() }} {{ Str::plural('task', $project->tasks()->count()) }}
                    </span>
                    <button type="submit" class="btn btn-primary btn-sm">
                        <i data-lucide="log-in" class="w-3.5 h-3.5"></i>
                        <span>Join project</span>
                    </button>
                </div>
            </form>
            @endforeach
        </div>
        @endif
    </div>
</div>

<script>
function openNewProjectForWorkspace(workspaceId) {
    window.openModal('newProjectModal');
    const select = document.querySelector('#newProjectForm select[name="workspace_id"]');
    if (select) {
        select.value = String(workspaceId);
    }
}
</script>
@endsection
