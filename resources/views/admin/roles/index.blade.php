@extends('layouts.app')

@section('title', 'Legacy Roles')

@section('content')
<div class="flex-1 overflow-y-auto bg-background">
    <div class="w-full max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">

        @include('admin.partials.header', [
            'title' => 'Legacy Roles',
            'subtitle' => 'Manage records from the old roles table. Current permissions use global and workspace/project roles.',
            'icon' => 'shield',
        ])

        @include('admin.partials.flash')

        <div class="rounded-xl border border-amber-500/25 bg-amber-500/10 p-4 text-xs text-amber-700 dark:text-amber-400">
            <div class="flex items-start gap-2">
                <i data-lucide="info" class="w-4 h-4 shrink-0 mt-0.5"></i>
                <p>
                    These legacy role records are not used by the active permission system.
                    Active access is controlled by <strong>users.role</strong> and workspace/project memberships.
                </p>
            </div>
        </div>

        <!-- Add -->
        <form method="POST" action="{{ route('admin.roles.store') }}"
              class="rounded-xl border border-border bg-card p-5 space-y-4">
            @csrf
            <div class="flex items-center gap-2">
                <i data-lucide="plus" class="w-4 h-4 text-muted-foreground"></i>
                <h2 class="text-sm font-semibold text-foreground">Add legacy role</h2>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                <div class="space-y-1.5">
                    <label class="text-[11px] font-medium text-foreground">Name</label>
                    <input name="name" value="{{ old('name') }}" class="form-input text-xs" placeholder="Manager" required>
                </div>
                <div class="space-y-1.5">
                    <label class="text-[11px] font-medium text-foreground">Slug</label>
                    <input name="slug" value="{{ old('slug') }}" class="form-input text-xs" placeholder="manager" required>
                </div>
                <label class="flex items-center gap-2 text-xs text-foreground h-9 mt-5">
                    <input type="checkbox" name="create_workspace" value="1" class="shadcn-checkbox">
                    <span>Can create workspace</span>
                </label>
                <label class="flex items-center gap-2 text-xs text-foreground h-9 mt-5">
                    <input type="checkbox" name="create_project" value="1" class="shadcn-checkbox">
                    <span>Can create project</span>
                </label>
            </div>

            <div class="space-y-1.5">
                <label class="text-[11px] font-medium text-foreground">Access JSON (optional)</label>
                <textarea name="access" rows="3" class="form-textarea text-xs font-mono" placeholder='{"tasks":["create","update"],"projects":["view"]}'>{{ old('access') }}</textarea>
            </div>

            <div class="flex justify-end">
                <button type="submit" class="btn btn-primary btn-sm">
                    <i data-lucide="plus" class="w-3.5 h-3.5"></i>
                    <span>Add Role</span>
                </button>
            </div>
        </form>

        <!-- List -->
        <div class="space-y-3">
            <div class="flex items-center justify-between px-1">
                <h2 class="text-sm font-semibold text-foreground">Legacy role records</h2>
                <span class="badge badge-secondary text-[11px]">{{ $roles->count() }}</span>
            </div>

            @forelse($roles as $role)
            <div class="rounded-xl border border-border bg-card p-5">
                <div class="flex flex-col xl:flex-row xl:items-start gap-4">
                    <form method="POST" action="{{ route('admin.roles.update', $role) }}" class="flex-1 space-y-4">
                        @csrf
                        @method('PUT')

                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                            <div class="space-y-1.5">
                                <label class="text-[11px] font-medium text-foreground">Name</label>
                                <input name="name" value="{{ $role->name }}" class="form-input text-xs" required>
                            </div>
                            <div class="space-y-1.5">
                                <label class="text-[11px] font-medium text-foreground">Slug</label>
                                <input name="slug" value="{{ $role->slug }}" class="form-input text-xs" required>
                            </div>
                            <label class="flex items-center gap-2 text-xs text-foreground h-9 mt-5">
                                <input type="checkbox" name="create_workspace" value="1" class="shadcn-checkbox" @checked($role->create_workspace)>
                                <span>Create workspace</span>
                            </label>
                            <label class="flex items-center gap-2 text-xs text-foreground h-9 mt-5">
                                <input type="checkbox" name="create_project" value="1" class="shadcn-checkbox" @checked($role->create_project)>
                                <span>Create project</span>
                            </label>
                        </div>

                        <div class="space-y-1.5">
                            <label class="text-[11px] font-medium text-foreground">Access JSON</label>
                            <textarea name="access" rows="3" class="form-textarea text-xs font-mono">{{ $role->access ? json_encode($role->access, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) : '' }}</textarea>
                        </div>

                        <div class="flex justify-end">
                            <button type="submit" class="btn btn-outline btn-sm">
                                <i data-lucide="save" class="w-3.5 h-3.5"></i>
                                <span>Save Role</span>
                            </button>
                        </div>
                    </form>

                    <form method="POST" action="{{ route('admin.roles.destroy', $role) }}"
                          onsubmit="return confirm('Delete this legacy role?')" class="shrink-0">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-ghost btn-sm h-9 w-9 text-muted-foreground hover:text-destructive" title="Delete role">
                            <i data-lucide="trash-2" class="w-4 h-4"></i>
                        </button>
                    </form>
                </div>
            </div>
            @empty
            <div class="rounded-xl border border-dashed border-border p-10 text-center">
                <div class="w-11 h-11 rounded-full bg-secondary flex items-center justify-center text-muted-foreground mx-auto mb-3">
                    <i data-lucide="shield" class="w-5 h-5"></i>
                </div>
                <p class="text-sm font-medium text-foreground">No legacy roles recorded</p>
                <p class="text-xs text-muted-foreground mt-1">The active permission system does not depend on this table.</p>
            </div>
            @endforelse
        </div>
    </div>
</div>
@endsection
