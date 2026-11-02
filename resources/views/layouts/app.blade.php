<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'ProTask') — Task & Project Manager</title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Scripts & Styles via Vite -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    
    <script>
        // Apply saved theme immediately to prevent FOUC (flash of unstyled content)
        const theme = localStorage.getItem('theme') || (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
        if (theme === 'dark') {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }

        // Apply saved sidebar collapsed state immediately on desktop
        if (window.innerWidth >= 768 && localStorage.getItem('sidebar_collapsed') === 'true') {
            document.documentElement.classList.add('sidebar-collapsed');
        }
    </script>
    @stack('head')
</head>
<body id="app-body" class="h-full bg-background text-foreground antialiased font-sans flex overflow-hidden @auth {{ Auth::user()->compact_cards ? 'compact-cards' : '' }} @endauth">

<!-- ── MOBILE OVERLAY ── -->
<div id="sidebar-backdrop" class="fixed inset-0 z-40 bg-black/50 backdrop-blur-xs md:hidden hidden transition-opacity" onclick="toggleSidebar()"></div>

<!-- ── SHADCN SIDEBAR ── -->
<aside id="sidebar" class="w-64 border-r border-border bg-card flex flex-col shrink-0 h-screen transition-all duration-200 z-50 fixed md:static -translate-x-full md:translate-x-0">
    <!-- Brand / Workspace Switcher Header -->
    <div class="h-14 px-4 border-b border-border flex items-center justify-between">
        <a href="/" class="flex items-center gap-2.5 font-semibold text-sm tracking-tight text-foreground hover:opacity-85 transition-opacity">
            <div class="w-7 h-7 rounded-lg bg-primary text-primary-foreground flex items-center justify-center font-bold text-xs shadow-xs">
                PT
            </div>
            <div class="flex flex-col">
                <span class="leading-none text-foreground font-semibold">ProTask</span>
                <span class="text-[11px] text-muted-foreground font-normal">Workspace</span>
            </div>
        </a>
        <div class="flex items-center gap-1">
            <button type="button" 
                    class="btn btn-ghost p-1.5 h-9 w-9 text-muted-foreground hover:text-foreground"
                    title="Create new project"
                    onclick="window.openModal('newProjectModal')">
                <i data-lucide="plus" class="w-12 h-12"></i>
            </button>
            <button type="button" 
                    class="btn btn-ghost p-1.5 h-9 w-9 text-muted-foreground hover:text-foreground" 
                    onclick="toggleSidebar()"
                    title="Toggle sidebar">
                <i data-lucide="x" class="w-12 h-12 md:hidden"></i>
                <i data-lucide="menu" class="w-12 h-12 hidden md:block"></i>
            </button>
        </div>
    </div>

    <!-- Navigation Scroll Area -->
    <div class="flex-1 overflow-y-auto px-3 py-3 space-y-4">
        <!-- Main Menu -->
        <div class="space-y-1">
            <div class="px-2 pb-1.5 text-[11px] font-medium uppercase tracking-wider text-muted-foreground">
                Menu
            </div>
            <a href="{{ route('ai.index') }}"
               class="flex items-center gap-2.5 px-2.5 py-1.5 rounded-md text-sm font-medium transition-colors {{ request()->is('ai') ? 'bg-secondary text-secondary-foreground shadow-2xs' : 'text-muted-foreground hover:bg-secondary/60 hover:text-foreground' }}">
                <i data-lucide="sparkles" class="w-4 h-4 shrink-0"></i>
                <span>{{ \App\Services\AiSettings::name() }}</span>
            </a>
            <a href="/" 
               class="flex items-center gap-2.5 px-2.5 py-1.5 rounded-md text-sm font-medium transition-colors {{ request()->is('/') || request()->is('projects/*/board') ? 'bg-secondary text-secondary-foreground shadow-2xs' : 'text-muted-foreground hover:bg-secondary/60 hover:text-foreground' }}">
                <i data-lucide="kanban" class="w-4 h-4 shrink-0"></i>
                <span>Active Board</span>
            </a>
            <a href="{{ route('projects.index') }}" 
               class="flex items-center gap-2.5 px-2.5 py-1.5 rounded-md text-sm font-medium transition-colors {{ request()->is('projects') ? 'bg-secondary text-secondary-foreground shadow-2xs' : 'text-muted-foreground hover:bg-secondary/60 hover:text-foreground' }}">
                <i data-lucide="layout-grid" class="w-4 h-4 shrink-0"></i>
                <span>All Projects</span>
            </a>
        </div>

        <!-- Workspaces Collapsible Accordion -->
        <div class="space-y-1">
            <div class="px-2 pb-1.5 flex items-center justify-between text-[11px] font-medium uppercase tracking-wider text-muted-foreground">
                <span>Workspaces</span>
                <button type="button" 
                        class="hover:text-foreground p-0.5 rounded transition-colors" 
                        title="Add Workspace" 
                        onclick="window.openModal('newWorkspaceModal')">
                    <i data-lucide="folder-plus" class="w-3.5 h-3.5"></i>
                </button>
            </div>

            @php
                $sidebarUser = Auth::user();
                $workspaces = $sidebarUser
                    ? \App\Models\Workspace::forUser($sidebarUser)
                        ->withCount('projects')
                        ->with(['projects' => fn ($q) => $q->visibleTo($sidebarUser)])
                        ->orderBy('name')
                        ->get()
                    : collect();
                $currentProjectId = request()->route('project')?->id ?? null;
            @endphp

            @forelse($workspaces as $ws)
            @php
                $isWsActive = $ws->projects->contains('id', $currentProjectId);
            @endphp
            <div class="workspace-group">
                <button type="button" 
                        onclick="toggleWorkspaceGroup(this)" 
                        class="w-full flex items-center justify-between px-2.5 py-1.5 rounded-md text-sm font-medium text-muted-foreground hover:bg-secondary/50 hover:text-foreground transition-colors group">
                    <div class="flex items-center gap-2.5 min-w-0">
                        <i data-lucide="briefcase" class="w-3.5 h-3.5 shrink-0 text-muted-foreground group-hover:text-foreground transition-colors"></i>
                        <span class="truncate text-xs">{{ $ws->name }}</span>
                    </div>
                    <div class="flex items-center gap-1">
                        <span class="text-[10px] text-muted-foreground/70">{{ $ws->projects->count() }}</span>
                        <i data-lucide="chevron-right" class="w-3.5 h-3.5 text-muted-foreground transition-transform duration-200 {{ $isWsActive ? 'rotate-90' : '' }}"></i>
                    </div>
                </button>
                <div class="ws-projects-list ml-4 pl-2 border-l border-border space-y-0.5 mt-0.5 {{ $isWsActive ? '' : 'hidden' }}">
                    @forelse($ws->projects as $p)
                    <a href="{{ route('projects.board', $p) }}" 
                       class="flex items-center gap-2 px-2 py-1 rounded-md text-xs font-medium transition-colors {{ $currentProjectId == $p->id ? 'bg-secondary text-foreground font-semibold' : 'text-muted-foreground hover:bg-secondary/40 hover:text-foreground' }}">
                        <span class="w-1.5 h-1.5 rounded-full {{ $currentProjectId == $p->id ? 'bg-primary' : 'bg-muted-foreground/40' }}"></span>
                        <span class="truncate">{{ $p->title }}</span>
                    </a>
                    @empty
                    <div class="px-2 py-1 text-[11px] text-muted-foreground/60 italic">
                        {{ $ws->projects_count > 0 ? 'No projects joined yet' : 'No projects yet' }}
                    </div>
                    @if($ws->projects_count > 0)
                    <a href="{{ route('workspaces.join-project', $ws) }}"
                       class="flex items-center gap-2 px-2 py-1 rounded-md text-[11px] font-medium text-primary hover:bg-secondary/40 transition-colors">
                        <i data-lucide="log-in" class="w-3 h-3"></i>
                        <span>Join a project</span>
                    </a>
                    @endif
                    @endforelse
                    <a href="{{ route('workspaces.members', $ws) }}"
                       class="flex items-center gap-2 px-2 py-1 rounded-md text-[11px] font-medium text-muted-foreground/80 hover:bg-secondary/40 hover:text-foreground transition-colors">
                        <i data-lucide="users" class="w-3 h-3"></i>
                        <span>Members</span>
                    </a>
                </div>
            </div>
            @empty
            <div class="px-2.5 py-2 text-xs text-muted-foreground">
                No workspaces created yet
            </div>
            @endforelse
        </div>

        @auth
        @if(Auth::user()->isAdmin())
        <!-- Administration Section -->
        <div class="space-y-1">
            <div class="px-2 pb-1.5 text-[11px] font-medium uppercase tracking-wider text-muted-foreground">
                Administration
            </div>
            <a href="{{ route('admin.ai.index') }}"
               class="flex items-center gap-2.5 px-2.5 py-1.5 rounded-md text-sm font-medium transition-colors {{ request()->is('admin/ai*') ? 'bg-secondary text-secondary-foreground shadow-2xs' : 'text-muted-foreground hover:bg-secondary/60 hover:text-foreground' }}">
                <i data-lucide="sparkles" class="w-4 h-4 shrink-0"></i>
                <span>AI Agent</span>
            </a>
            <a href="{{ route('admin.users.index') }}"
               class="flex items-center gap-2.5 px-2.5 py-1.5 rounded-md text-sm font-medium transition-colors {{ request()->is('admin/users*') ? 'bg-secondary text-secondary-foreground shadow-2xs' : 'text-muted-foreground hover:bg-secondary/60 hover:text-foreground' }}">
                <i data-lucide="shield-check" class="w-4 h-4 shrink-0"></i>
                <span>User Management</span>
            </a>
            <a href="{{ route('admin.settings.index') }}"
               class="flex items-center gap-2.5 px-2.5 py-1.5 rounded-md text-sm font-medium transition-colors {{ request()->is('admin/settings*') ? 'bg-secondary text-secondary-foreground shadow-2xs' : 'text-muted-foreground hover:bg-secondary/60 hover:text-foreground' }}">
                <i data-lucide="settings" class="w-4 h-4 shrink-0"></i>
                <span>Settings</span>
            </a>
            <a href="{{ route('admin.email-templates.index') }}"
               class="flex items-center gap-2.5 px-2.5 py-1.5 rounded-md text-sm font-medium transition-colors {{ request()->is('admin/email-templates*') ? 'bg-secondary text-secondary-foreground shadow-2xs' : 'text-muted-foreground hover:bg-secondary/60 hover:text-foreground' }}">
                <i data-lucide="mail" class="w-4 h-4 shrink-0"></i>
                <span>Email Templates</span>
            </a>
            <a href="{{ route('admin.languages.index') }}"
               class="flex items-center gap-2.5 px-2.5 py-1.5 rounded-md text-sm font-medium transition-colors {{ request()->is('admin/languages*') ? 'bg-secondary text-secondary-foreground shadow-2xs' : 'text-muted-foreground hover:bg-secondary/60 hover:text-foreground' }}">
                <i data-lucide="languages" class="w-4 h-4 shrink-0"></i>
                <span>Languages</span>
            </a>
            <a href="{{ route('admin.backups.index') }}"
               class="flex items-center gap-2.5 px-2.5 py-1.5 rounded-md text-sm font-medium transition-colors {{ request()->is('admin/backups*') ? 'bg-secondary text-secondary-foreground shadow-2xs' : 'text-muted-foreground hover:bg-secondary/60 hover:text-foreground' }}">
                <i data-lucide="database-backup" class="w-4 h-4 shrink-0"></i>
                <span>Backups</span>
            </a>
            <a href="{{ route('admin.roles.index') }}"
               class="flex items-center gap-2.5 px-2.5 py-1.5 rounded-md text-sm font-medium transition-colors {{ request()->is('admin/roles*') ? 'bg-secondary text-secondary-foreground shadow-2xs' : 'text-muted-foreground hover:bg-secondary/60 hover:text-foreground' }}">
                <i data-lucide="shield" class="w-4 h-4 shrink-0"></i>
                <span>Legacy Roles</span>
            </a>
        </div>
        @endif
        @endauth

        <!-- System & Support Section -->
        <div class="space-y-1">
            <div class="px-2 pb-1.5 text-[11px] font-medium uppercase tracking-wider text-muted-foreground">
                Preferences
            </div>
            <a href="#" onclick="window.showToast('Documentation & Help Center')" class="flex items-center gap-2.5 px-2.5 py-1.5 rounded-md text-sm font-medium text-muted-foreground hover:bg-secondary/60 hover:text-foreground transition-colors">
                <i data-lucide="help-circle" class="w-4 h-4 shrink-0"></i>
                <span>Help Center</span>
            </a>
            <a href="javascript:void(0)" onclick="openSettingsSheet()" class="flex items-center gap-2.5 px-2.5 py-1.5 rounded-md text-sm font-medium text-muted-foreground hover:bg-secondary/60 hover:text-foreground transition-colors">
                <i data-lucide="settings" class="w-4 h-4 shrink-0"></i>
                <span>Settings</span>
            </a>
        </div>
    </div>

    <!-- Theme Switcher & User Footer -->
    <div class="p-3 border-t border-border space-y-3">
        <!-- Theme Toggle (shadcn segmented style) -->
        <div class="flex items-center justify-between bg-muted/60 p-1 rounded-lg border border-border/50 text-xs">
            <button id="themeLight" 
                    type="button"
                    onclick="window.setTheme('light')" 
                    class="flex-1 flex items-center justify-center gap-1.5 py-1 rounded-md font-medium transition-all text-muted-foreground">
                <i data-lucide="sun" class="w-3.5 h-3.5"></i>
                <span>Light</span>
            </button>
            <button id="themeDark" 
                    type="button"
                    onclick="window.setTheme('dark')" 
                    class="flex-1 flex items-center justify-center gap-1.5 py-1 rounded-md font-medium transition-all text-muted-foreground">
                <i data-lucide="moon" class="w-3.5 h-3.5"></i>
                <span>Dark</span>
            </button>
        </div>

        <!-- User Profile Card -->
        @auth
        <div class="flex items-center justify-between pt-1">
            <div class="flex items-center gap-2.5 min-w-0">
                <div class="w-8 h-8 rounded-full bg-primary/10 border border-primary/20 text-primary flex items-center justify-center font-semibold text-xs shrink-0">
                    {{ strtoupper(substr(Auth::user()->name ?? 'U', 0, 1)) }}
                </div>
                <div class="flex flex-col min-w-0">
                    <span class="text-xs font-medium text-foreground truncate leading-tight flex items-center gap-1.5">
                        {{ Auth::user()->name }}
                        @if(Auth::user()->isAdmin())
                        <span class="badge badge-default text-[9px] px-1.5 py-0">Admin</span>
                        @endif
                    </span>
                    <span class="text-[10px] text-muted-foreground truncate">{{ Auth::user()->email }}</span>
                </div>
            </div>
            <button type="button" 
                    onclick="logout()" 
                    class="btn btn-ghost p-1.5 h-8 w-8 text-muted-foreground hover:text-destructive"
                    title="Sign Out">
                <i data-lucide="log-out" class="w-4 h-4"></i>
            </button>
        </div>
        @endauth
    </div>
</aside>

<!-- ── MAIN CONTENT WRAPPER ── -->
<div class="flex-1 flex flex-col h-screen overflow-hidden min-w-0">
    <!-- Topbar for Mobile Toggle & Breadcrumbs (and Desktop when Sidebar is Collapsed) -->
    <header id="main-topbar" class="h-14 border-b border-border bg-card/60 backdrop-blur-xs px-4 flex items-center justify-between shrink-0 md:hidden">
        <div class="flex items-center gap-3">
            <button type="button" onclick="toggleSidebar()" class="btn  h-9 w-9 text-muted-foreground hover:text-foreground" title="Toggle sidebar">
                <i data-lucide="menu" class="w-4 h-4"></i>
            </button>
            <span class="font-semibold text-sm">ProTask</span>
        </div>
        <button type="button" onclick="window.openModal('newProjectModal')" class="btn btn-primary btn-sm">
            <i data-lucide="plus" class="w-3.5 h-3.5"></i>
            <span>Project</span>
        </button>
    </header>

    <!-- Main View Content -->
    <main class="flex-1 overflow-hidden flex flex-col min-w-0">
        @yield('content')
    </main>
</div>

<!-- ── TOAST CONTAINER ── -->
<div id="toast-container"></div>

<!-- ── NEW PROJECT MODAL (shadcn Dialog) ── -->
<div class="dialog-overlay" id="newProjectModal" onclick="window.closeOnOverlay(event, 'newProjectModal')">
    <div class="dialog-content">
        <div class="flex items-center justify-between pb-3 border-b border-border mb-4">
            <div>
                <h3 class="font-semibold text-base leading-none tracking-tight">Create New Project</h3>
                <p class="text-xs text-muted-foreground mt-1">Start organizing your tasks in a dedicated board.</p>
            </div>
            <button type="button" class="btn btn-ghost p-1 h-8 w-8 text-muted-foreground hover:text-foreground" onclick="window.closeModal('newProjectModal')">
                <i data-lucide="x" class="w-4 h-4"></i>
            </button>
        </div>

        <form id="newProjectForm" onsubmit="submitNewProject(event)" class="space-y-4">
            <div class="space-y-1.5">
                <label class="text-xs font-medium text-foreground">Project Title <span class="text-destructive">*</span></label>
                <input class="form-input" name="title" placeholder="e.g. Mobile App Redesign" required>
            </div>

            <div class="space-y-1.5">
                <label class="text-xs font-medium text-foreground">Workspace</label>
                <select class="form-select" name="workspace_id">
                    <option value="">Select workspace...</option>
                    @foreach(\App\Models\Workspace::forUser(auth()->user())->orderBy('name')->get() as $ws)
                    <option value="{{ $ws->id }}">{{ $ws->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="space-y-1.5">
                <label class="text-xs font-medium text-foreground">Description</label>
                <textarea class="form-textarea" name="description" rows="3" placeholder="Brief project summary and goals..."></textarea>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div class="space-y-1.5">
                    <label class="text-xs font-medium text-foreground">Start Date</label>
                    <input class="form-input text-xs" type="date" name="start_date">
                </div>
                <div class="space-y-1.5">
                    <label class="text-xs font-medium text-foreground">End Date</label>
                    <input class="form-input text-xs" type="date" name="end_date">
                </div>
            </div>

            <div class="flex items-center justify-end gap-2 pt-3 border-t border-border mt-5">
                <button type="button" class="btn btn-outline" onclick="window.closeModal('newProjectModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Create Project</button>
            </div>
        </form>
    </div>
</div>

<!-- ── NEW WORKSPACE MODAL (shadcn Dialog) ── -->
<div class="dialog-overlay" id="newWorkspaceModal" onclick="window.closeOnOverlay(event, 'newWorkspaceModal')">
    <div class="dialog-content">
        <div class="flex items-center justify-between pb-3 border-b border-border mb-4">
            <div>
                <h3 class="font-semibold text-base leading-none tracking-tight">Create Workspace</h3>
                <p class="text-xs text-muted-foreground mt-1">Group related projects, teams, and departments together.</p>
            </div>
            <button type="button" class="btn btn-ghost p-1 h-8 w-8 text-muted-foreground hover:text-foreground" onclick="window.closeModal('newWorkspaceModal')">
                <i data-lucide="x" class="w-4 h-4"></i>
            </button>
        </div>

        <form id="newWorkspaceForm" onsubmit="submitNewWorkspace(event)" class="space-y-4">
            <div class="space-y-1.5">
                <label class="text-xs font-medium text-foreground">Workspace Name <span class="text-destructive">*</span></label>
                <input class="form-input" name="ws_name" placeholder="e.g. Marketing & Growth" required>
            </div>

            <div class="space-y-1.5">
                <label class="text-xs font-medium text-foreground">Description</label>
                <textarea class="form-textarea" name="ws_description" rows="3" placeholder="Optional notes about this workspace..."></textarea>
            </div>

            <div class="flex items-center justify-end gap-2 pt-3 border-t border-border mt-5">
                <button type="button" class="btn btn-outline" onclick="window.closeModal('newWorkspaceModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Create Workspace</button>
            </div>
        </form>
    </div>
</div>

@include('partials.invite-modal')

<!-- ── SETTINGS SIDE DRAWER (shadcn Sheet for Settings) ── -->
<div class="sheet-overlay" id="settingsSheetOverlay" onclick="closeSettingsSheet()"></div>
<div class="sheet-panel" id="settingsSheet">
    <!-- Header -->
    <div class="h-14 px-5 border-b border-border flex items-center justify-between shrink-0 bg-muted/10">
        <div class="flex items-center gap-2">
            <i data-lucide="settings" class="w-4 h-4 text-muted-foreground"></i>
            <span class="font-semibold text-sm text-foreground">Workspace Settings</span>
        </div>
        <button type="button" 
                onclick="closeSettingsSheet()" 
                class="btn btn-ghost p-1.5 h-8 w-8 text-muted-foreground hover:text-foreground">
            <i data-lucide="x" class="w-4 h-4"></i>
        </button>
    </div>

    <!-- Body -->
    <div class="flex-1 overflow-y-auto p-5 space-y-6 text-xs">
        <!-- Appearance Settings -->
        <div class="space-y-3">
            <h3 class="text-xs font-semibold uppercase tracking-wider text-muted-foreground">Appearance</h3>
            <div class="p-3.5 rounded-xl border border-border bg-card space-y-3">
                <div>
                    <div class="font-medium text-foreground">Theme Mode</div>
                    <div class="text-[11px] text-muted-foreground">Choose interface appearance</div>
                </div>
                <div class="flex items-center gap-2 pt-1">
                    <button type="button" onclick="window.setTheme('light')" class="btn btn-outline btn-sm flex-1 text-xs">
                        <i data-lucide="sun" class="w-3.5 h-3.5"></i> Light
                    </button>
                    <button type="button" onclick="window.setTheme('dark')" class="btn btn-outline btn-sm flex-1 text-xs">
                        <i data-lucide="moon" class="w-3.5 h-3.5"></i> Dark
                    </button>
                </div>
            </div>
        </div>

        <!-- Account / Profile -->
        <div class="space-y-3">
            <h3 class="text-xs font-semibold uppercase tracking-wider text-muted-foreground">Account Information</h3>
            <div class="p-3.5 rounded-xl border border-border bg-card space-y-2.5">
                <div>
                    <label class="text-[11px] text-muted-foreground">Name</label>
                    <div class="font-medium text-foreground text-xs">{{ Auth::user()->name ?? 'User' }}</div>
                </div>
                <div>
                    <label class="text-[11px] text-muted-foreground">Email</label>
                    <div class="font-medium text-foreground text-xs">{{ Auth::user()->email ?? '—' }}</div>
                </div>
            </div>
        </div>

        <!-- Preferences -->
        <div class="space-y-3">
            <h3 class="text-xs font-semibold uppercase tracking-wider text-muted-foreground">Preferences</h3>
            <div class="p-3.5 rounded-xl border border-border bg-card space-y-3">
                <label class="flex items-center justify-between cursor-pointer">
                    <div>
                        <div class="font-medium text-foreground">Task Notifications</div>
                        <div class="text-[11px] text-muted-foreground">Receive updates for task assignments</div>
                    </div>
                    <input type="checkbox"
                           id="prefTaskNotifications"
                           @checked(Auth::user()->task_notifications)
                           onchange="savePreference('task_notifications', this.checked)"
                           class="shadcn-checkbox">
                </label>
                <div class="h-px bg-border/60"></div>
                <label class="flex items-center justify-between cursor-pointer">
                    <div>
                        <div class="font-medium text-foreground">Compact Cards</div>
                        <div class="text-[11px] text-muted-foreground">Hide descriptions and tighten task cards</div>
                    </div>
                    <input type="checkbox"
                           id="prefCompactCards"
                           @checked(Auth::user()->compact_cards)
                           onchange="savePreference('compact_cards', this.checked)"
                           class="shadcn-checkbox">
                </label>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <div class="p-4 border-t border-border bg-muted/20 flex items-center justify-between shrink-0">
        <button type="button" onclick="logout()" class="btn btn-ghost btn-sm text-destructive hover:bg-destructive/10 text-xs">
            <i data-lucide="log-out" class="w-3.5 h-3.5"></i>
            <span>Log out</span>
        </button>
        <button type="button" onclick="closeSettingsSheet()" class="btn btn-primary btn-sm text-xs">
            Done
        </button>
    </div>
</div>

<script>
// ── Settings Sheet Helper ──
function openSettingsSheet() {
    document.getElementById('settingsSheetOverlay')?.classList.add('open');
    document.getElementById('settingsSheet')?.classList.add('open');
    window.lucide?.render();
}

function closeSettingsSheet() {
    document.getElementById('settingsSheetOverlay')?.classList.remove('open');
    document.getElementById('settingsSheet')?.classList.remove('open');
}

// ── CSRF & Headers ──
const CSRF = document.querySelector('meta[name="csrf-token"]').content;
const headers = {
    'Content-Type': 'application/json',
    'X-CSRF-TOKEN': CSRF,
    'Accept': 'application/json'
};

// ── User Preferences ──
async function savePreference(key, value) {
    try {
        const res = await fetch('/preferences', {
            method: 'PATCH',
            headers,
            body: JSON.stringify({ key, value: value ? 1 : 0 })
        });

        const data = await res.json().catch(() => ({}));

        if (!res.ok) {
            window.showToast(data.message || 'Could not save preference.', 'error');
            return;
        }

        if (key === 'compact_cards') {
            document.body.classList.toggle('compact-cards', value);
        }

        window.showToast('Preference saved.', 'success');
    } catch {
        window.showToast('Network error, please try again.', 'error');
    }
}

// ── Sidebar Toggle (Mobile Drawer & Desktop Collapse) ──
function toggleSidebar() {
    const isMobile = window.innerWidth < 768;
    const sidebar = document.getElementById('sidebar');
    const backdrop = document.getElementById('sidebar-backdrop');
    
    if (isMobile) {
        const isClosed = sidebar.classList.contains('-translate-x-full');
        if (isClosed) {
            sidebar.classList.remove('-translate-x-full');
            backdrop?.classList.remove('hidden');
        } else {
            sidebar.classList.add('-translate-x-full');
            backdrop?.classList.add('hidden');
        }
    } else {
        const isCollapsed = document.documentElement.classList.toggle('sidebar-collapsed');
        document.body.classList.toggle('sidebar-collapsed', isCollapsed);
        localStorage.setItem('sidebar_collapsed', isCollapsed ? 'true' : 'false');
    }

    if (window.lucide) {
        window.lucide.createIcons();
    }
}

function toggleMobileSidebar() {
    toggleSidebar();
}

// Sync body class on load if html has sidebar-collapsed
document.addEventListener('DOMContentLoaded', () => {
    if (document.documentElement.classList.contains('sidebar-collapsed')) {
        document.body.classList.add('sidebar-collapsed');
    }
});

// Auto-close mobile drawer when resizing back to desktop
window.addEventListener('resize', () => {
    if (window.innerWidth >= 768) {
        document.getElementById('sidebar-backdrop')?.classList.add('hidden');
        document.getElementById('sidebar')?.classList.remove('-translate-x-full');
    }
});

// ── Collapsible Workspace Accordion ──
function toggleWorkspaceGroup(btn) {
    const group = btn.closest('.workspace-group');
    const list = group.querySelector('.ws-projects-list');
    const chevron = btn.querySelector('[data-lucide="chevron-right"]');
    
    const isHidden = list.classList.toggle('hidden');
    if (chevron) {
        chevron.style.transform = isHidden ? 'rotate(0deg)' : 'rotate(90deg)';
    }
}

// ── Logout ──
async function logout() {
    try {
        const res = await fetch('/logout', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': CSRF
            }
        });
        window.location.href = '/login';
    } catch (err) {
        console.error('Logout error:', err);
    }
}

// ── New Project Submission ──
async function submitNewProject(e) {
    e.preventDefault();
    const form = e.target;
    const submitBtn = form.querySelector('button[type="submit"]');
    submitBtn.disabled = true;

    const data = {
        title: form.title.value.trim(),
        workspace_id: form.workspace_id.value || null,
        description: form.description.value.trim(),
        start_date: form.start_date.value || null,
        end_date: form.end_date.value || null
    };

    try {
        const res = await fetch('/projects', {
            method: 'POST',
            headers,
            body: JSON.stringify(data)
        });
        const proj = await res.json();
        if (res.ok) {
            window.closeModal('newProjectModal');
            window.showToast('Project created successfully!', 'success');
            window.location.href = `/projects/${proj.id}/board`;
        } else {
            window.showToast(proj.message || 'Failed to create project', 'error');
        }
    } catch (err) {
        window.showToast('Network error, please try again.', 'error');
    } finally {
        submitBtn.disabled = false;
    }
}

// ── New Workspace Submission ──
async function submitNewWorkspace(e) {
    e.preventDefault();
    const form = e.target;
    const submitBtn = form.querySelector('button[type="submit"]');
    submitBtn.disabled = true;

    const data = {
        name: form.ws_name.value.trim(),
        description: form.ws_description.value.trim()
    };

    try {
        const res = await fetch('/workspaces', {
            method: 'POST',
            headers,
            body: JSON.stringify(data)
        });
        if (res.ok) {
            window.showToast('Workspace created!', 'success');
            window.closeModal('newWorkspaceModal');
            setTimeout(() => location.reload(), 600);
        } else {
            const err = await res.json();
            window.showToast(err.message || 'Failed to create workspace', 'error');
        }
    } catch (err) {
        window.showToast('Network error, please try again.', 'error');
    } finally {
        submitBtn.disabled = false;
    }
}
</script>

@stack('scripts')
</body>
</html>