<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Invitation — {{ config('app.name', 'ProTask') }}</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <script>
        const theme = localStorage.getItem('theme') || (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
        if (theme === 'dark') document.documentElement.classList.add('dark');
    </script>
</head>
<body class="h-full bg-background text-foreground antialiased font-sans flex items-center justify-center p-4">

    <div class="w-full max-w-lg space-y-6">

        <!-- Brand -->
        <div class="text-center space-y-2">
            <div class="w-11 h-11 rounded-xl bg-primary text-primary-foreground flex items-center justify-center font-bold text-sm mx-auto shadow-xs">
                PT
            </div>
            <h1 class="text-xl font-bold tracking-tight text-foreground">
                @if($invitation->project)
                    You are invited to a project
                @else
                    You are invited to a workspace
                @endif
            </h1>
            <p class="text-xs text-muted-foreground">
                {{ $invitation->inviter?->name ?? 'A teammate' }} invited
                <span class="font-medium text-foreground">{{ $invitation->email }}</span>
                to collaborate on ProTask.
            </p>
        </div>

        <!-- Card -->
        <div class="rounded-xl border border-border bg-card p-6 shadow-sm space-y-5">

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

            <!-- Invitation details -->
            <div class="rounded-lg border border-border/70 bg-muted/30 p-4 space-y-3">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-lg bg-secondary flex items-center justify-center shrink-0">
                        <i data-lucide="{{ $invitation->project_id ? 'kanban' : 'briefcase' }}" class="w-5 h-5 text-muted-foreground"></i>
                    </div>
                    <div class="min-w-0">
                        <div class="text-sm font-semibold text-foreground truncate">
                            {{ $invitation->project?->title ?? $invitation->workspace->name }}
                        </div>
                        <div class="text-[11px] text-muted-foreground truncate">
                            @if($invitation->project)
                                Project in {{ $invitation->workspace->name }}
                            @else
                                Workspace · you will choose a project after joining
                            @endif
                        </div>
                    </div>
                </div>

                <div class="h-px bg-border/60"></div>

                <div class="flex items-center justify-between text-[11px]">
                    <span class="text-muted-foreground">Role</span>
                    <span class="badge badge-outline text-[10px] capitalize">{{ $invitation->role }}</span>
                </div>
                <div class="flex items-center justify-between text-[11px]">
                    <span class="text-muted-foreground">Expires</span>
                    <span class="text-foreground font-medium">
                        {{ $invitation->expires_at?->format('M j, Y') ?? 'Never' }}
                    </span>
                </div>
            </div>

            @if($invitation->isAccepted())
                <div class="rounded-lg bg-emerald-500/10 border border-emerald-500/20 p-3 text-xs text-emerald-600 dark:text-emerald-400 text-center">
                    This invitation has already been accepted.
                </div>
                <a href="{{ $invitation->project ? route('projects.board', $invitation->project) : route('onboarding') }}"
                   class="btn btn-primary w-full">
                    <i data-lucide="arrow-right" class="w-4 h-4"></i>
                    <span>{{ $invitation->project ? 'Open project' : 'Go to dashboard' }}</span>
                </a>

            @elseif($invitation->isExpired())
                <div class="rounded-lg bg-destructive/10 border border-destructive/20 p-3 text-xs text-destructive text-center">
                    This invitation has expired. Ask a teammate to send a new one.
                </div>
                <a href="{{ route('onboarding') }}" class="btn btn-outline w-full">
                    <i data-lucide="home" class="w-4 h-4"></i>
                    <span>Back to dashboard</span>
                </a>

            @elseif(!Auth::check())
                <div class="space-y-2">
                    <a href="{{ route('login', ['redirect' => route('invitations.show', $invitation->token, false)]) }}" class="btn btn-primary w-full">
                        <i data-lucide="log-in" class="w-4 h-4"></i>
                        <span>Sign in to accept</span>
                    </a>
                    <a href="{{ route('register', ['redirect' => route('invitations.show', $invitation->token, false)]) }}" class="btn btn-outline w-full">
                        <i data-lucide="user-plus" class="w-4 h-4"></i>
                        <span>Create an account</span>
                    </a>
                    <p class="text-[11px] text-muted-foreground text-center pt-1">
                        Use the email address this invitation was sent to.
                    </p>
                </div>

            @elseif(!$invitation->isForEmail(Auth::user()->email))
                <div class="rounded-lg bg-amber-500/10 border border-amber-500/25 p-3 text-xs text-amber-700 dark:text-amber-400">
                    You are signed in as <span class="font-medium">{{ Auth::user()->email }}</span>,
                    but this invitation was sent to <span class="font-medium">{{ $invitation->email }}</span>.
                    Sign in with the invited email address to accept.
                </div>
                <div class="space-y-2">
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="btn btn-outline w-full">
                            <i data-lucide="log-out" class="w-4 h-4"></i>
                            <span>Sign out</span>
                        </button>
                    </form>
                    <a href="{{ route('onboarding') }}" class="btn btn-ghost w-full text-xs text-muted-foreground">
                        Back to dashboard
                    </a>
                </div>

            @else
                <form method="POST" action="{{ route('invitations.accept', $invitation->token) }}" class="space-y-2">
                    @csrf
                    <button type="submit" class="btn btn-primary w-full">
                        <i data-lucide="check" class="w-4 h-4"></i>
                        <span>
                            @if($invitation->project)
                                Join {{ $invitation->project->title }}
                            @else
                                Join {{ $invitation->workspace->name }}
                            @endif
                        </span>
                    </button>
                    <a href="{{ route('onboarding') }}" class="btn btn-ghost w-full text-xs text-muted-foreground">
                        Not now
                    </a>
                </form>
            @endif
        </div>

        <p class="text-[11px] text-muted-foreground text-center">
            Invited by {{ $invitation->inviter?->name ?? 'a teammate' }} · ProTask
        </p>
    </div>
</body>
</html>
