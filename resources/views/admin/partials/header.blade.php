@php
    $adminLinks = [
        ['route' => 'admin.users.index', 'match' => 'admin.users.*', 'label' => 'Users', 'icon' => 'users'],
        ['route' => 'admin.ai.index', 'match' => 'admin.ai.*', 'label' => 'AI Agent', 'icon' => 'sparkles'],
        ['route' => 'admin.settings.index', 'match' => 'admin.settings.*', 'label' => 'Settings', 'icon' => 'settings'],
        ['route' => 'admin.email-templates.index', 'match' => 'admin.email-templates.*', 'label' => 'Email Templates', 'icon' => 'mail'],
        ['route' => 'admin.languages.index', 'match' => 'admin.languages.*', 'label' => 'Languages', 'icon' => 'languages'],
        ['route' => 'admin.backups.index', 'match' => 'admin.backups.*', 'label' => 'Backups', 'icon' => 'database-backup'],
        ['route' => 'admin.roles.index', 'match' => 'admin.roles.*', 'label' => 'Legacy Roles', 'icon' => 'shield'],
    ];
@endphp

<div class="space-y-5">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div class="flex items-center gap-3 min-w-0">
            <div class="w-11 h-11 rounded-xl bg-primary/10 text-primary border border-primary/15 flex items-center justify-center shrink-0">
                <i data-lucide="{{ $icon ?? 'shield-check' }}" class="w-5 h-5"></i>
            </div>
            <div class="min-w-0">
                <h1 class="text-xl font-bold tracking-tight text-foreground truncate">{{ $title }}</h1>
                <p class="text-xs text-muted-foreground mt-0.5">{{ $subtitle }}</p>
            </div>
        </div>

        @isset($actions)
        <div class="flex items-center gap-2 shrink-0">{{ $actions }}</div>
        @endisset
    </div>

    <nav class="flex flex-wrap items-center gap-1 rounded-xl border border-border bg-card p-1.5 shadow-2xs">
        @foreach($adminLinks as $link)
        <a href="{{ route($link['route']) }}"
           class="inline-flex items-center gap-1.5 rounded-lg px-3 py-2 text-xs font-medium transition-colors {{ request()->routeIs($link['match']) ? 'bg-secondary text-foreground shadow-2xs' : 'text-muted-foreground hover:bg-secondary/60 hover:text-foreground' }}">
            <i data-lucide="{{ $link['icon'] }}" class="w-3.5 h-3.5"></i>
            <span>{{ $link['label'] }}</span>
        </a>
        @endforeach
    </nav>
</div>
