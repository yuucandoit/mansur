@extends('layouts.app')

@section('title', 'User Management')

@section('content')
<div class="flex-1 overflow-y-auto bg-background">
    <div class="w-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">

        @include('admin.partials.header', [
            'title' => 'User Management',
            'subtitle' => 'Manage every registered account, role, password, and access status.',
            'icon' => 'users',
        ])

        @include('admin.partials.flash')

        <!-- Stats -->
        <div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-5 gap-3">
            <div class="rounded-xl border border-border bg-card p-4">
                <div class="text-[10px] uppercase tracking-wider text-muted-foreground font-medium">Total users</div>
                <div class="mt-1.5 text-2xl font-bold tracking-tight text-foreground">{{ $counts['all'] }}</div>
            </div>
            <div class="rounded-xl border border-border bg-card p-4">
                <div class="text-[10px] uppercase tracking-wider text-muted-foreground font-medium">Administrators</div>
                <div class="mt-1.5 text-2xl font-bold tracking-tight text-foreground">{{ $counts['admin'] }}</div>
            </div>
            <div class="rounded-xl border border-border bg-card p-4">
                <div class="text-[10px] uppercase tracking-wider text-muted-foreground font-medium">Regular users</div>
                <div class="mt-1.5 text-2xl font-bold tracking-tight text-foreground">{{ $counts['user'] }}</div>
            </div>
            <div class="rounded-xl border border-border bg-card p-4">
                <div class="text-[10px] uppercase tracking-wider text-muted-foreground font-medium">Active</div>
                <div class="mt-1.5 text-2xl font-bold tracking-tight text-emerald-600 dark:text-emerald-400">{{ $counts['active'] }}</div>
            </div>
            <div class="rounded-xl border border-border bg-card p-4">
                <div class="text-[10px] uppercase tracking-wider text-muted-foreground font-medium">Inactive</div>
                <div class="mt-1.5 text-2xl font-bold tracking-tight {{ $counts['inactive'] > 0 ? 'text-destructive' : 'text-foreground' }}">{{ $counts['inactive'] }}</div>
            </div>
        </div>

        <!-- Filters -->
        <form method="GET" action="{{ route('admin.users.index') }}"
              class="rounded-xl border border-border bg-card p-4 flex flex-col lg:flex-row lg:items-center gap-3">
            <div class="relative flex-1 min-w-0">
                <i data-lucide="search" class="w-3.5 h-3.5 text-muted-foreground absolute left-3 top-1/2 -translate-y-1/2"></i>
                <input type="text"
                       name="q"
                       value="{{ $search }}"
                       placeholder="Search by name or email..."
                       class="form-input text-xs pl-9">
            </div>
            <div class="flex flex-col sm:flex-row sm:items-center gap-2">
                <select name="role" class="form-select text-xs sm:w-40" onchange="this.form.submit()">
                    <option value="">All roles</option>
                    <option value="admin" @selected($role === 'admin')>Administrators</option>
                    <option value="user" @selected($role === 'user')>Users</option>
                </select>
                <select name="status" class="form-select text-xs sm:w-36" onchange="this.form.submit()">
                    <option value="">All status</option>
                    <option value="active" @selected($status === 'active')>Active</option>
                    <option value="inactive" @selected($status === 'inactive')>Inactive</option>
                </select>
                <button type="submit" class="btn btn-outline btn-sm shrink-0">
                    <i data-lucide="filter" class="w-3.5 h-3.5"></i>
                    <span>Filter</span>
                </button>
                @if($search !== '' || $role || $status)
                <a href="{{ route('admin.users.index') }}" class="btn btn-ghost btn-sm text-muted-foreground shrink-0">
                    Reset
                </a>
                @endif
            </div>
        </form>

        <!-- Users -->
        <div class="rounded-xl border border-border bg-card overflow-hidden shadow-2xs">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs min-w-[880px]">
                    <thead class="bg-muted/40 text-[10px] uppercase tracking-wider text-muted-foreground">
                        <tr>
                            <th class="px-5 py-3.5 font-medium">User</th>
                            <th class="px-5 py-3.5 font-medium">Global role</th>
                            <th class="px-5 py-3.5 font-medium">Status</th>
                            <th class="px-5 py-3.5 font-medium">Workspaces</th>
                            <th class="px-5 py-3.5 font-medium">Projects</th>
                            <th class="px-5 py-3.5 font-medium">Joined</th>
                            <th class="px-5 py-3.5 font-medium text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border/60">
                        @forelse($users as $user)
                        @php($isSelf = $user->id === Auth::id())
                        <tr class="hover:bg-muted/20 transition-colors {{ $user->is_active ? '' : 'opacity-70' }}">
                            <td class="px-5 py-4">
                                <div class="flex items-center gap-3 min-w-0">
                                    <div class="w-9 h-9 rounded-full {{ $user->is_active ? 'bg-primary/10 border-primary/20 text-primary' : 'bg-muted border-border text-muted-foreground' }} border flex items-center justify-center font-semibold text-[11px] shrink-0">
                                        {{ strtoupper(substr($user->name, 0, 1)) }}
                                    </div>
                                    <div class="min-w-0">
                                        <div class="flex items-center gap-1.5">
                                            <span class="font-medium text-foreground truncate">{{ $user->name }}</span>
                                            @if($isSelf)
                                            <span class="badge badge-secondary text-[9px] px-1.5">You</span>
                                            @endif
                                        </div>
                                        <div class="text-[11px] text-muted-foreground truncate mt-0.5">{{ $user->email }}</div>
                                    </div>
                                </div>
                            </td>

                            <td class="px-5 py-4">
                                @if($isSelf)
                                <span class="badge {{ $user->isAdmin() ? 'badge-default' : 'badge-secondary' }} text-[10px] capitalize">
                                    {{ $user->role }}
                                </span>
                                @else
                                <form method="POST" action="{{ route('admin.users.update-role', $user) }}">
                                    @csrf
                                    @method('PATCH')
                                    <select name="role"
                                            onchange="this.form.submit()"
                                            class="form-select text-[11px] py-1 h-8 w-28">
                                        <option value="user" @selected($user->role === 'user')>User</option>
                                        <option value="admin" @selected($user->role === 'admin')>Admin</option>
                                    </select>
                                </form>
                                @endif
                            </td>

                            <td class="px-5 py-4">
                                <div class="flex items-center gap-2">
                                    @if($user->is_active)
                                    <span class="badge badge-outline text-[10px] text-emerald-600 dark:text-emerald-400 border-emerald-500/30">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                        Active
                                    </span>
                                    @else
                                    <span class="badge badge-muted text-[10px]">
                                        <span class="w-1.5 h-1.5 rounded-full bg-muted-foreground"></span>
                                        Inactive
                                    </span>
                                    @endif

                                    @if(!$isSelf)
                                    <form method="POST" action="{{ route('admin.users.update-status', $user) }}">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="is_active" value="{{ $user->is_active ? 0 : 1 }}">
                                        <button type="submit"
                                                class="btn btn-ghost p-1 h-7 w-7 {{ $user->is_active ? 'text-muted-foreground hover:text-destructive' : 'text-emerald-600 hover:text-emerald-500' }}"
                                                title="{{ $user->is_active ? 'Deactivate account' : 'Activate account' }}">
                                            <i data-lucide="{{ $user->is_active ? 'user-x' : 'user-check' }}" class="w-3.5 h-3.5"></i>
                                        </button>
                                    </form>
                                    @endif
                                </div>
                            </td>

                            <td class="px-5 py-4 text-muted-foreground">{{ $user->workspaces_count }}</td>
                            <td class="px-5 py-4 text-muted-foreground">{{ $user->projects_count }}</td>
                            <td class="px-5 py-4 text-muted-foreground whitespace-nowrap">
                                {{ $user->created_at?->format('M j, Y') }}
                            </td>

                            <td class="px-5 py-4">
                                <div class="flex items-center justify-end gap-1.5">
                                    <button type="button"
                                            data-user-id="{{ $user->id }}"
                                            data-user-name="{{ $user->name }}"
                                            onclick="openPasswordModal(this.dataset.userId, this.dataset.userName)"
                                            class="btn btn-outline btn-sm h-8 px-2.5 text-muted-foreground hover:text-foreground"
                                            title="Change password">
                                        <i data-lucide="key-round" class="w-3.5 h-3.5"></i>
                                        <span class="hidden xl:inline text-[11px]">Password</span>
                                    </button>

                                    @if(!$isSelf)
                                    <form method="POST"
                                          action="{{ route('admin.users.destroy', $user) }}"
                                          onsubmit="return confirm('Delete {{ addslashes($user->name) }}\'s account? This cannot be undone.')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                                class="btn btn-ghost p-2 h-8 w-8 text-muted-foreground hover:text-destructive"
                                                title="Delete user">
                                            <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                                        </button>
                                    </form>
                                    @else
                                    <span class="text-[10px] text-muted-foreground/60 italic px-2">Current account</span>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="px-5 py-14 text-center">
                                <div class="w-11 h-11 rounded-full bg-secondary flex items-center justify-center text-muted-foreground mx-auto mb-3">
                                    <i data-lucide="users" class="w-5 h-5"></i>
                                </div>
                                <p class="text-sm font-medium text-foreground">No users found</p>
                                <p class="text-xs text-muted-foreground mt-1">Try adjusting your search or filters.</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div>{{ $users->links() }}</div>
    </div>
</div>

<!-- ── CHANGE PASSWORD MODAL ── -->
<div class="dialog-overlay" id="passwordModal" onclick="window.closeOnOverlay(event, 'passwordModal')">
    <div class="dialog-content">
        <div class="flex items-center justify-between pb-4 border-b border-border mb-5">
            <div>
                <h3 class="font-semibold text-base leading-none tracking-tight">Change Password</h3>
                <p class="text-xs text-muted-foreground mt-1.5" id="passwordModalLabel">Set a new password for this user.</p>
            </div>
            <button type="button" class="btn btn-ghost p-1 h-8 w-8 text-muted-foreground hover:text-foreground" onclick="window.closeModal('passwordModal')">
                <i data-lucide="x" class="w-4 h-4"></i>
            </button>
        </div>

        <form id="passwordForm" method="POST" class="space-y-4">
            @csrf
            @method('PUT')

            <div class="space-y-1.5">
                <label class="text-xs font-medium text-foreground">New password <span class="text-destructive">*</span></label>
                <input type="password" name="password" class="form-input text-xs" placeholder="Minimum 8 characters" minlength="8" required>
            </div>

            <div class="space-y-1.5">
                <label class="text-xs font-medium text-foreground">Confirm new password <span class="text-destructive">*</span></label>
                <input type="password" name="password_confirmation" class="form-input text-xs" placeholder="Repeat the new password" minlength="8" required>
            </div>

            <div class="flex items-center justify-end gap-2 pt-4 border-t border-border mt-5">
                <button type="button" class="btn btn-outline" onclick="window.closeModal('passwordModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">
                    <i data-lucide="key-round" class="w-4 h-4"></i>
                    <span>Update Password</span>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openPasswordModal(userId, userName) {
    const form = document.getElementById('passwordForm');
    const label = document.getElementById('passwordModalLabel');

    form.action = '/admin/users/' + userId + '/password';
    form.reset();
    label.textContent = 'Set a new password for ' + userName + '.';

    window.openModal('passwordModal');
}
</script>
@endsection
