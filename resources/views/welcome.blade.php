<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'ProTask') }} — Modern Task & Project Management</title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script>
        const theme = localStorage.getItem('theme') || (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
        if (theme === 'dark') document.documentElement.classList.add('dark');
    </script>
</head>
<body class="h-full bg-background text-foreground antialiased font-sans flex flex-col justify-between">

    <!-- Top Navigation -->
    <header class="border-b border-border bg-card/50 backdrop-blur-xs">
        <div class="max-w-6xl mx-auto px-6 h-14 flex items-center justify-between">
            <div class="flex items-center gap-2.5">
                <div class="w-12 h-12 rounded-lg bg-primary text-primary-foreground flex items-center justify-center font-bold text-xs shadow-xs">
                    PT
                </div>
                <span class="font-semibold text-sm tracking-tight text-foreground">ProTask</span>
            </div>

            <nav class="flex items-center gap-3">
                @if (Route::has('login'))
                    @auth
                        <a href="{{ url('/') }}" class="btn btn-primary btn-sm text-xs">
                            <span>Open Dashboard</span>
                            <i data-lucide="arrow-right" class="w-3.5 h-3.5"></i>
                        </a>
                    @else
                        <a href="{{ route('login') }}" class="btn btn-ghost btn-sm text-xs">
                            Sign in
                        </a>
                        @if (Route::has('register'))
                            <a href="{{ route('register') }}" class="btn btn-primary btn-sm text-xs">
                                <span>Get Started</span>
                                <i data-lucide="arrow-right" class="w-3.5 h-3.5"></i>
                            </a>
                        @endif
                    @endauth
                @endif
            </nav>
        </div>
    </header>

    <!-- Hero Content -->
    <main class="flex-1 flex items-center justify-center p-6">
        <div class="max-w-2xl mx-auto text-center space-y-6">
            <div class="inline-flex items-center gap-2 rounded-full border border-border bg-muted/60 px-3 py-1 text-xs text-muted-foreground">
                <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                <span>ProTask 2.0 with shadcn design system</span>
            </div>

            <h1 class="text-4xl sm:text-5xl font-extrabold tracking-tight text-foreground leading-tight">
                Manage projects and team tasks with <span class="text-primary underline decoration-primary/40 underline-offset-4">clarity</span>.
            </h1>

            <p class="text-sm sm:text-base text-muted-foreground max-w-xl mx-auto leading-relaxed">
                A minimalist, lightning-fast workspace manager with kanban boards, drag-and-drop workflow, and real-time project organization.
            </p>

            <div class="flex items-center justify-center gap-3 pt-2">
                @auth
                    <a href="{{ url('/') }}" class="btn btn-primary text-xs py-2 px-4 shadow-sm">
                        <span>Go to your workspace</span>
                        <i data-lucide="arrow-right" class="w-4 h-4"></i>
                    </a>
                @else
                    <a href="{{ route('register') }}" class="btn btn-primary text-xs py-2 px-4 shadow-sm">
                        <span>Get Started Free</span>
                        <i data-lucide="arrow-right" class="w-4 h-4"></i>
                    </a>
                    <a href="{{ route('login') }}" class="btn btn-outline text-xs py-2 px-4">
                        Sign in
                    </a>
                @endauth
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="border-t border-border py-4 text-center text-xs text-muted-foreground">
        <p>&copy; {{ date('Y') }} ProTask. Built with Laravel and shadcn/ui.</p>
    </footer>

</body>
</html>