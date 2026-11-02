@extends('layouts.app')

@section('title', 'Choose a project')

@section('content')
<div class="flex-1 overflow-y-auto bg-background">
    <div class="max-w-3xl mx-auto px-6 py-10 space-y-6">

        <div class="space-y-2">
            <div class="w-12 h-12 rounded-2xl bg-primary/10 text-primary flex items-center justify-center">
                <i data-lucide="briefcase" class="w-6 h-6"></i>
            </div>
            <h1 class="text-2xl font-bold tracking-tight text-foreground">Pick a project to start</h1>
            <p class="text-sm text-muted-foreground">
                You are a member of {{ $workspaces->count() }} workspace{{ $workspaces->count() === 1 ? '' : 's' }}
                but have not joined a project yet. In ProTask, workspace access always comes with project access —
                choose a project below to continue.
            </p>
        </div>

        @if(session('status'))
        <div class="rounded-lg bg-emerald-500/10 border border-emerald-500/20 p-3 text-xs text-emerald-600 dark:text-emerald-400">
            {{ session('status') }}
        </div>
        @endif

        <div class="space-y-4">
            @foreach($workspaces as $workspace)
            <div class="rounded-xl border border-border bg-card p-5">
                <div class="flex items-center justify-between gap-3 mb-3">
                    <div class="flex items-center gap-3 min-w-0">
                        <div class="w-9 h-9 rounded-lg bg-secondary flex items-center justify-center font-semibold text-xs shrink-0">
                            {{ strtoupper(substr($workspace->name, 0, 1)) }}
                        </div>
                        <div class="min-w-0">
                            <div class="text-sm font-semibold text-foreground truncate">{{ $workspace->name }}</div>
                            <div class="text-[11px] text-muted-foreground">
                                {{ $workspace->projects->count() }} {{ Str::plural('project', $workspace->projects->count()) }}
                            </div>
                        </div>
                    </div>
                    <a href="{{ route('workspaces.join-project', $workspace) }}" class="btn btn-outline btn-sm shrink-0">
                        <i data-lucide="arrow-right" class="w-3.5 h-3.5"></i>
                        <span>Choose project</span>
                    </a>
                </div>

                @if($workspace->projects->isNotEmpty())
                <div class="flex flex-wrap gap-1.5">
                    @foreach($workspace->projects->take(6) as $project)
                    <span class="badge badge-secondary text-[10px]">{{ $project->title }}</span>
                    @endforeach
                </div>
                @else
                <p class="text-[11px] text-muted-foreground italic">
                    This workspace has no projects yet. Create one to get started.
                </p>
                @endif
            </div>
            @endforeach
        </div>

        <!-- Pending invitations -->
        @if($invitations->isNotEmpty())
        <div class="space-y-3 pt-2">
            <h2 class="text-sm font-semibold text-foreground flex items-center gap-2">
                <i data-lucide="mail" class="w-4 h-4 text-muted-foreground"></i>
                <span>Pending invitations</span>
            </h2>
            @foreach($invitations as $invitation)
            <div class="rounded-xl border border-border bg-card p-4 flex items-center justify-between gap-3">
                <div class="min-w-0">
                    <div class="text-sm font-medium text-foreground truncate">
                        {{ $invitation->project?->title ?? $invitation->workspace->name }}
                    </div>
                    <div class="text-[11px] text-muted-foreground">
                        {{ $invitation->project ? 'Project in '.$invitation->workspace->name : 'Workspace invitation' }}
                    </div>
                </div>
                <a href="{{ $invitation->accept_url }}" class="btn btn-outline btn-sm shrink-0">Review</a>
            </div>
            @endforeach
        </div>
        @endif
    </div>
</div>
@endsection
