@extends('layouts.app')

@section('title', $project->title . ' · Members')

@section('content')
<div class="flex-1 overflow-y-auto bg-background">
    <div class="max-w-4xl mx-auto px-6 py-8 space-y-7">

        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div class="min-w-0">
                <a href="{{ route('projects.board', $project) }}" class="text-xs text-muted-foreground hover:text-foreground inline-flex items-center gap-1.5 mb-2">
                    <i data-lucide="arrow-left" class="w-3.5 h-3.5"></i>
                    <span>Back to board</span>
                </a>
                <h1 class="text-xl font-bold tracking-tight text-foreground truncate">{{ $project->title }}</h1>
                <p class="text-xs text-muted-foreground mt-0.5">
                    Project members · {{ $project->workspace?->name }}
                </p>
            </div>
            @if($canManage)
            <button type="button"
                    data-project-id="{{ $project->id }}"
                    data-project-title="{{ $project->title }}"
                    onclick="window.openInviteModal('project', this.dataset.projectId, this.dataset.projectTitle)"
                    class="btn btn-primary btn-sm shrink-0">
                <i data-lucide="user-plus" class="w-3.5 h-3.5"></i>
                <span>Invite people</span>
            </button>
            @endif
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

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <section class="lg:col-span-2 space-y-3">
                <div class="flex items-center justify-between">
                    <h2 class="text-sm font-semibold text-foreground flex items-center gap-2">
                        <i data-lucide="users" class="w-4 h-4 text-muted-foreground"></i>
                        <span>Project members</span>
                    </h2>
                    <span class="badge badge-secondary text-[11px]">{{ $members->count() }}</span>
                </div>

                <div class="rounded-xl border border-border bg-card divide-y divide-border/60">
                    @forelse($members as $member)
                    @php
                        $isOwner = $member->id === $project->user_id;
                        $role = $isOwner ? 'manager' : ($member->pivot->role ?? 'member');
                    @endphp
                    <div class="p-4 flex items-center gap-3">
                        <div class="w-9 h-9 rounded-full bg-primary/10 border border-primary/20 text-primary flex items-center justify-center font-semibold text-xs shrink-0">
                            {{ strtoupper(substr($member->name, 0, 1)) }}
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center gap-1.5">
                                <span class="text-sm font-medium text-foreground truncate">{{ $member->name }}</span>
                                @if($member->id === Auth::id())
                                <span class="badge badge-secondary text-[9px] px-1.5">You</span>
                                @endif
                                @if(!$member->isActive())
                                <span class="badge badge-muted text-[9px] px-1.5">Inactive</span>
                                @endif
                            </div>
                            <div class="text-[11px] text-muted-foreground truncate">{{ $member->email }}</div>
                        </div>
                        <span class="badge badge-outline text-[10px] capitalize">{{ $role }}</span>
                    </div>
                    @empty
                    <div class="p-6 text-center text-xs text-muted-foreground">No members yet.</div>
                    @endforelse
                </div>
            </section>

            <aside class="space-y-6">
                @if($canManage)
                <section class="space-y-3">
                    <h2 class="text-sm font-semibold text-foreground flex items-center gap-2">
                        <i data-lucide="send" class="w-4 h-4 text-muted-foreground"></i>
                        <span>Invite to this project</span>
                    </h2>

                    <form method="POST" action="{{ route('invitations.store') }}" class="rounded-xl border border-border bg-card p-4 space-y-3">
                        @csrf
                        <input type="hidden" name="project_id" value="{{ $project->id }}">

                        <div class="space-y-1.5">
                            <label class="text-[11px] font-medium text-foreground">Email</label>
                            <input type="email" name="email" value="{{ old('email') }}" class="form-input text-xs" placeholder="teammate@example.com" required>
                        </div>

                        <div class="space-y-1.5">
                            <label class="text-[11px] font-medium text-foreground">Project role</label>
                            <select name="role" class="form-select text-xs">
                                <option value="member">Member</option>
                                <option value="manager">Manager</option>
                            </select>
                        </div>

                        <p class="text-[11px] text-muted-foreground">
                            Accepting a project invitation automatically adds the person to
                            <span class="font-medium text-foreground">{{ $project->workspace?->name }}</span>.
                        </p>

                        <button type="submit" class="btn btn-primary btn-sm w-full">
                            <i data-lucide="send" class="w-3.5 h-3.5"></i>
                            <span>Create Invitation</span>
                        </button>
                    </form>
                </section>
                @endif

                <section class="space-y-3">
                    <h2 class="text-sm font-semibold text-foreground flex items-center gap-2">
                        <i data-lucide="mail-plus" class="w-4 h-4 text-muted-foreground"></i>
                        <span>Pending invitations</span>
                    </h2>

                    @forelse($invitations as $invitation)
                    <div class="rounded-xl border border-border bg-card p-4 space-y-2.5">
                        <div class="flex items-start justify-between gap-2">
                            <div class="min-w-0">
                                <div class="text-xs font-medium text-foreground truncate">{{ $invitation->email }}</div>
                                <div class="text-[11px] text-muted-foreground capitalize">{{ $invitation->role }}</div>
                            </div>
                            @if($canManage)
                            <form method="POST" action="{{ route('invitations.destroy', $invitation) }}">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-ghost p-1 h-7 w-7 text-muted-foreground hover:text-destructive" title="Revoke invitation">
                                    <i data-lucide="x" class="w-3.5 h-3.5"></i>
                                </button>
                            </form>
                            @endif
                        </div>
                        <p class="text-[10px] text-muted-foreground">
                            Expires {{ $invitation->expires_at?->diffForHumans() ?? 'never' }}
                        </p>
                        <div class="flex items-center gap-1.5">
                            <input type="text"
                                   value="{{ $invitation->accept_url }}"
                                   readonly
                                   class="form-input text-[10px] font-mono py-1"
                                   onclick="this.select()">
                            <button type="button"
                                    class="btn btn-outline btn-sm shrink-0"
                                    onclick="navigator.clipboard.writeText('{{ $invitation->accept_url }}').then(() => window.showToast('Invite link copied!', 'success'))"
                                    title="Copy link">
                                <i data-lucide="copy" class="w-3 h-3"></i>
                            </button>
                        </div>
                    </div>
                    @empty
                    <div class="rounded-xl border border-dashed border-border p-5 text-center text-[11px] text-muted-foreground">
                        No pending invitations.
                    </div>
                    @endforelse
                </section>
            </aside>
        </div>
    </div>
</div>
@endsection
