@extends('layouts.app')

@section('title', 'Get started')

@section('content')
<div class="flex-1 overflow-y-auto bg-background">
    <div class="max-w-4xl mx-auto px-6 py-10 space-y-8">

        <!-- Hero -->
        <div class="text-center space-y-3 pt-4">
            <div class="w-14 h-14 rounded-2xl bg-primary text-primary-foreground flex items-center justify-center font-bold text-lg mx-auto shadow-sm">
                PT
            </div>
            <h1 class="text-2xl font-bold tracking-tight text-foreground">
                Welcome, {{ $user->name }}
            </h1>
            <p class="text-sm text-muted-foreground max-w-lg mx-auto">
                You are not part of any workspace yet. Create your own workspace to start
                organizing projects, or accept an invitation from a teammate.
            </p>
        </div>

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

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">

            <!-- Create workspace -->
            <div class="rounded-xl border border-border bg-card p-6 flex flex-col">
                <div class="w-10 h-10 rounded-lg bg-primary/10 text-primary flex items-center justify-center mb-4">
                    <i data-lucide="folder-plus" class="w-5 h-5"></i>
                </div>
                <h2 class="text-base font-semibold text-foreground">Create a workspace</h2>
                <p class="text-xs text-muted-foreground mt-1 mb-5 flex-1">
                    A workspace groups your projects and the people working on them.
                    You will be its owner and can invite teammates afterwards.
                </p>
                <button type="button"
                        onclick="window.openModal('newWorkspaceModal')"
                        class="btn btn-primary w-full">
                    <i data-lucide="plus" class="w-4 h-4"></i>
                    <span>Create Workspace</span>
                </button>
            </div>

            <!-- Paste invitation code -->
            <div class="rounded-xl border border-border bg-card p-6 flex flex-col">
                <div class="w-10 h-10 rounded-lg bg-primary/10 text-primary flex items-center justify-center mb-4">
                    <i data-lucide="ticket" class="w-5 h-5"></i>
                </div>
                <h2 class="text-base font-semibold text-foreground">Have an invitation?</h2>
                <p class="text-xs text-muted-foreground mt-1 mb-4">
                    Paste the invitation code or link a teammate shared with you.
                </p>
                <form method="POST" action="{{ route('invitations.paste') }}" class="space-y-3">
                    @csrf
                    <input type="text"
                           name="code"
                           value="{{ old('code') }}"
                           class="form-input text-xs"
                           placeholder="Paste invitation code or link"
                           required>
                    <button type="submit" class="btn btn-outline w-full">
                        <i data-lucide="arrow-right" class="w-4 h-4"></i>
                        <span>Open Invitation</span>
                    </button>
                </form>
            </div>
        </div>

        <!-- Pending invitations for this email -->
        <section class="space-y-3">
            <div class="flex items-center justify-between">
                <h2 class="text-sm font-semibold text-foreground flex items-center gap-2">
                    <i data-lucide="mail" class="w-4 h-4 text-muted-foreground"></i>
                    <span>Invitations for {{ $user->email }}</span>
                </h2>
                <span class="badge badge-secondary text-[11px]">{{ $invitations->count() }}</span>
            </div>

            @forelse($invitations as $invitation)
            <div class="rounded-xl border border-border bg-card p-4 flex flex-col sm:flex-row sm:items-center gap-4">
                <div class="w-10 h-10 rounded-lg bg-secondary flex items-center justify-center shrink-0">
                    <i data-lucide="{{ $invitation->project_id ? 'kanban' : 'briefcase' }}" class="w-5 h-5 text-muted-foreground"></i>
                </div>
                <div class="flex-1 min-w-0">
                    <div class="text-sm font-medium text-foreground truncate">
                        {{ $invitation->project?->title ?? $invitation->workspace->name }}
                    </div>
                    <div class="text-[11px] text-muted-foreground mt-0.5">
                        @if($invitation->project)
                            Project in <span class="font-medium">{{ $invitation->workspace->name }}</span>
                        @else
                            Workspace invitation
                        @endif
                        · from {{ $invitation->inviter?->name ?? 'a teammate' }}
                    </div>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    <span class="badge badge-outline text-[10px] capitalize">{{ $invitation->role }}</span>
                    <a href="{{ $invitation->accept_url }}" class="btn btn-primary btn-sm">
                        <i data-lucide="check" class="w-3.5 h-3.5"></i>
                        <span>Review</span>
                    </a>
                </div>
            </div>
            @empty
            <div class="rounded-xl border border-dashed border-border p-6 text-center">
                <p class="text-xs text-muted-foreground">
                    No pending invitations for your email address.
                </p>
            </div>
            @endforelse
        </section>
    </div>
</div>
@endsection
