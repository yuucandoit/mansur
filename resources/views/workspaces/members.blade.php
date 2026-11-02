@extends('layouts.app')

@section('title', $workspace->name . ' · Members')

@section('content')
<div class="flex-1 overflow-y-auto bg-background">
    <div class="max-w-5xl mx-auto px-6 py-8 space-y-7">

        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div class="min-w-0">
                <a href="{{ route('projects.index') }}" class="text-xs text-muted-foreground hover:text-foreground inline-flex items-center gap-1.5 mb-2">
                    <i data-lucide="arrow-left" class="w-3.5 h-3.5"></i>
                    <span>All projects</span>
                </a>
                <h1 class="text-xl font-bold tracking-tight text-foreground truncate">{{ $workspace->name }}</h1>
                <p class="text-xs text-muted-foreground mt-0.5">
                    Manage members, roles and pending invitations for this workspace.
                </p>
            </div>
            @if($canManage)
            <button type="button"
                    data-workspace-id="{{ $workspace->id }}"
                    data-workspace-name="{{ $workspace->name }}"
                    onclick="window.openInviteModal('workspace', this.dataset.workspaceId, this.dataset.workspaceName)"
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

            <!-- Members -->
            <section class="lg:col-span-2 space-y-3">
                <div class="flex items-center justify-between">
                    <h2 class="text-sm font-semibold text-foreground flex items-center gap-2">
                        <i data-lucide="users" class="w-4 h-4 text-muted-foreground"></i>
                        <span>Members</span>
                    </h2>
                    <span class="badge badge-secondary text-[11px]">{{ $members->count() }}</span>
                </div>

                <div class="rounded-xl border border-border bg-card divide-y divide-border/60">
                    @forelse($members as $member)
                    @php
                        $isOwner = $member->id === $workspace->user_id;
                        $role = $isOwner ? 'owner' : ($member->pivot->role ?? 'member');
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

                        @if($canManage && !$isOwner)
                        <form method="POST"
                              action="{{ route('workspaces.members.update', [$workspace, $member]) }}"
                              class="hidden sm:block">
                            @csrf
                            @method('PATCH')
                            <select name="role"
                                    onchange="this.form.submit()"
                                    class="form-select text-[11px] py-1 h-7 w-24">
                                <option value="member" @selected($role === 'member')>Member</option>
                                <option value="admin" @selected($role === 'admin')>Admin</option>
                            </select>
                        </form>

                        <form method="POST"
                              action="{{ route('workspaces.members.remove', [$workspace, $member]) }}"
                              onsubmit="return confirm('Remove {{ addslashes($member->name) }} from this workspace and its projects?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-ghost p-1.5 h-8 w-8 text-muted-foreground hover:text-destructive" title="Remove member">
                                <i data-lucide="user-minus" class="w-3.5 h-3.5"></i>
                            </button>
                        </form>
                        @else
                        <span class="badge badge-outline text-[10px] capitalize">{{ $role }}</span>
                        @endif
                    </div>
                    @empty
                    <div class="p-6 text-center text-xs text-muted-foreground">No members yet.</div>
                    @endforelse
                </div>
            </section>

            <!-- Invite form + pending invitations -->
            <aside class="space-y-6">
                @if($canManage)
                <section class="space-y-3">
                    <h2 class="text-sm font-semibold text-foreground flex items-center gap-2">
                        <i data-lucide="send" class="w-4 h-4 text-muted-foreground"></i>
                        <span>Invite by email</span>
                    </h2>

                    <form method="POST" action="{{ route('invitations.store') }}" class="rounded-xl border border-border bg-card p-4 space-y-3">
                        @csrf
                        <input type="hidden" name="workspace_id" value="{{ $workspace->id }}">

                        <div class="space-y-1.5">
                            <label class="text-[11px] font-medium text-foreground">Email</label>
                            <input type="email" name="email" value="{{ old('email') }}" class="form-input text-xs" placeholder="teammate@example.com" required>
                        </div>

                        <div class="space-y-1.5">
                            <label class="text-[11px] font-medium text-foreground">Workspace role</label>
                            <select name="role" class="form-select text-xs">
                                <option value="member">Member</option>
                                <option value="admin">Admin</option>
                            </select>
                        </div>

                        <div class="space-y-1.5">
                            <label class="text-[11px] font-medium text-foreground">Project (optional)</label>
                            <select name="project_id" class="form-select text-xs">
                                <option value="">Invitee picks a project after joining</option>
                                @foreach($projects as $project)
                                <option value="{{ $project->id }}">{{ $project->title }}</option>
                                @endforeach
                            </select>
                            <p class="text-[11px] text-muted-foreground">
                                Inviting directly to a project adds the person to this workspace too.
                            </p>
                        </div>

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
                                <div class="text-[11px] text-muted-foreground">
                                    {{ $invitation->project?->title ?? 'Workspace access' }} · <span class="capitalize">{{ $invitation->role }}</span>
                                </div>
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

        @if(Auth::id() === $workspace->user_id)
        <!-- Danger zone: owner only -->
        <section class="rounded-xl border border-destructive/30 bg-destructive/5 p-5 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h2 class="text-sm font-semibold text-destructive flex items-center gap-2">
                    <i data-lucide="triangle-alert" class="w-4 h-4"></i>
                    <span>Delete workspace</span>
                </h2>
                <p class="text-xs text-muted-foreground mt-1 max-w-xl">
                    Deleting this workspace permanently removes all of its projects, tasks,
                    members, and pending invitations. This action cannot be undone.
                </p>
            </div>
            <button type="button"
                    data-workspace-id="{{ $workspace->id }}"
                    data-workspace-name="{{ $workspace->name }}"
                    data-delete-action="{{ route('workspaces.destroy', $workspace) }}"
                    onclick="openDeleteWorkspaceModal(this)"
                    class="btn btn-destructive btn-sm shrink-0">
                <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                <span>Delete Workspace</span>
            </button>
        </section>
        @endif
    </div>
</div>

<!-- ── DELETE WORKSPACE CONFIRMATION MODAL ── -->
<div class="dialog-overlay" id="deleteWorkspaceModal" onclick="window.closeOnOverlay(event, 'deleteWorkspaceModal')">
    <div class="dialog-content">
        <div class="flex items-center justify-between pb-3 border-b border-border mb-4">
            <div>
                <h3 class="font-semibold text-base leading-none tracking-tight text-destructive">Delete Workspace</h3>
                <p class="text-xs text-muted-foreground mt-1">This action is permanent and cannot be undone.</p>
            </div>
            <button type="button" class="btn btn-ghost p-1 h-8 w-8 text-muted-foreground hover:text-foreground" onclick="window.closeModal('deleteWorkspaceModal')">
                <i data-lucide="x" class="w-4 h-4"></i>
            </button>
        </div>

        <div class="rounded-lg border border-destructive/30 bg-destructive/10 p-3.5 flex items-start gap-3 mb-4">
            <i data-lucide="triangle-alert" class="w-5 h-5 text-destructive shrink-0 mt-0.5"></i>
            <div class="text-xs text-destructive space-y-1">
                <p class="font-semibold">This will permanently delete:</p>
                <ul class="list-disc list-inside space-y-0.5 opacity-90">
                    <li>All projects in this workspace</li>
                    <li>All tasks, checklists, comments, and attachments</li>
                    <li>All members and pending invitations</li>
                </ul>
            </div>
        </div>

        <form id="deleteWorkspaceForm" method="POST" class="space-y-4" onsubmit="return validateDeleteWorkspaceSubmit()">
            @csrf
            @method('DELETE')

            <div class="space-y-1.5">
                <label class="text-xs font-medium text-foreground">
                    Type <code id="deleteWorkspaceExpected" class="px-1.5 py-0.5 rounded bg-muted font-mono text-[11px] text-destructive"></code> to confirm
                </label>
                <input type="text"
                       id="deleteWorkspaceInput"
                       class="form-input text-xs font-mono"
                       autocomplete="off"
                       oninput="checkDeleteWorkspace()"
                       required>
            </div>

            <div class="flex items-center justify-end gap-2 pt-3 border-t border-border mt-5">
                <button type="button" class="btn btn-outline" onclick="window.closeModal('deleteWorkspaceModal')">Cancel</button>
                <button type="submit" id="deleteWorkspaceSubmit" class="btn btn-destructive" disabled>
                    <i data-lucide="trash-2" class="w-4 h-4"></i>
                    <span>Delete Workspace</span>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openDeleteWorkspaceModal(button) {
    const name = button.dataset.workspaceName || '';
    const expected = 'DELETE ' + name;

    const form = document.getElementById('deleteWorkspaceForm');
    form.action = button.dataset.deleteAction;

    const expectedEl = document.getElementById('deleteWorkspaceExpected');
    const input = document.getElementById('deleteWorkspaceInput');
    const submit = document.getElementById('deleteWorkspaceSubmit');

    expectedEl.textContent = expected;
    input.value = '';
    input.placeholder = expected;
    submit.disabled = true;

    window.openModal('deleteWorkspaceModal');
    setTimeout(() => input.focus(), 120);
}

function checkDeleteWorkspace() {
    const expected = document.getElementById('deleteWorkspaceExpected').textContent;
    const input = document.getElementById('deleteWorkspaceInput');
    const submit = document.getElementById('deleteWorkspaceSubmit');

    submit.disabled = input.value !== expected;
}

function validateDeleteWorkspaceSubmit() {
    checkDeleteWorkspace();

    const expected = document.getElementById('deleteWorkspaceExpected').textContent;
    const input = document.getElementById('deleteWorkspaceInput');

    if (input.value !== expected) {
        window.showToast('Confirmation text does not match.', 'error');
        return false;
    }

    return true;
}
</script>
@endsection
