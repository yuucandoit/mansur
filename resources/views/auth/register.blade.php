<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Create Account — {{ config('app.name', 'ProTask') }}</title>
    
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
<body class="h-full bg-background text-foreground antialiased font-sans flex items-center justify-center p-4">

    <!-- Top Right Theme Toggle -->
    <div class="fixed top-4 right-4">
        <button type="button" 
                onclick="toggleThemeMode()" 
                id="authThemeBtn"
                class="btn btn-outline btn-icon h-9 w-9 text-muted-foreground hover:text-foreground">
            <i data-lucide="sun" class="w-4 h-4 hidden dark:block"></i>
            <i data-lucide="moon" class="w-4 h-4 block dark:hidden"></i>
        </button>
    </div>

    <!-- Register Container / shadcn Card -->
    <div class="w-full max-w-sm space-y-6">
        <!-- Brand Header -->
        <div class="text-center space-y-2">
            <div class="w-10 h-10 rounded-xl bg-primary text-primary-foreground flex items-center justify-center font-bold text-sm mx-auto shadow-xs">
                PT
            </div>
            <h1 class="text-2xl font-bold tracking-tight text-foreground">Create an account</h1>
            <p class="text-xs text-muted-foreground">
                Enter your details below to create your workspace.
            </p>
        </div>

        <!-- Auth Card -->
        <div class="rounded-xl border border-border bg-card p-6 shadow-sm">
            @if ($errors->any())
            <div class="rounded-lg bg-destructive/10 border border-destructive/20 p-3 text-xs text-destructive mb-4 space-y-1">
                @foreach ($errors->all() as $error)
                <p class="flex items-center gap-1.5">
                    <i data-lucide="alert-circle" class="w-3.5 h-3.5 shrink-0"></i>
                    <span>{{ $error }}</span>
                </p>
                @endforeach
            </div>
            @endif

            <form method="POST" action="{{ route('register') }}" class="space-y-4">
                @csrf
                @if(request()->filled('redirect'))
                <input type="hidden" name="redirect" value="{{ request('redirect') }}">
                @endif

                <!-- Name -->
                <div class="space-y-1.5">
                    <label for="name" class="text-xs font-medium text-foreground">Full Name</label>
                    <input id="name" 
                           type="text" 
                           name="name" 
                           value="{{ old('name') }}" 
                           required 
                           autofocus 
                           placeholder="John Doe"
                           class="form-input text-xs @error('name') border-destructive @enderror">
                </div>

                <!-- Email -->
                <div class="space-y-1.5">
                    <label for="email" class="text-xs font-medium text-foreground">Email Address</label>
                    <input id="email" 
                           type="email" 
                           name="email" 
                           value="{{ old('email') }}" 
                           required 
                           placeholder="name@example.com"
                           class="form-input text-xs @error('email') border-destructive @enderror">
                </div>

                <!-- Password -->
                <div class="space-y-1.5">
                    <label for="password" class="text-xs font-medium text-foreground">Password</label>
                    <div class="relative">
                        <input id="password" 
                               type="password" 
                               name="password" 
                               required 
                               placeholder="Min. 8 characters"
                               class="form-input text-xs pr-10 @error('password') border-destructive @enderror">
                        <button type="button" 
                                onclick="togglePasswordVisibility('password', this)"
                                class="btn btn-ghost p-1 h-7 w-7 text-muted-foreground hover:text-foreground absolute right-1.5 top-1/2 -translate-y-1/2">
                            <i data-lucide="eye" class="w-3.5 h-3.5"></i>
                        </button>
                    </div>
                </div>

                <!-- Confirm Password -->
                <div class="space-y-1.5">
                    <label for="password_confirmation" class="text-xs font-medium text-foreground">Confirm Password</label>
                    <div class="relative">
                        <input id="password_confirmation" 
                               type="password" 
                               name="password_confirmation" 
                               required 
                               placeholder="Repeat password"
                               class="form-input text-xs pr-10">
                        <button type="button" 
                                onclick="togglePasswordVisibility('password_confirmation', this)"
                                class="btn btn-ghost p-1 h-7 w-7 text-muted-foreground hover:text-foreground absolute right-1.5 top-1/2 -translate-y-1/2">
                            <i data-lucide="eye" class="w-3.5 h-3.5"></i>
                        </button>
                    </div>
                </div>

                <!-- Submit Button -->
                <button type="submit" class="btn btn-primary w-full text-xs py-2">
                    <span>Create Account</span>
                </button>
            </form>
        </div>

        <!-- Footer Signin Link -->
        <p class="text-center text-xs text-muted-foreground">
            Already have an account? 
            <a href="{{ route('login', request()->filled('redirect') ? ['redirect' => request('redirect')] : []) }}" class="font-medium text-foreground underline-offset-4 hover:underline">
                Sign in
            </a>
        </p>
    </div>

<script>
function togglePasswordVisibility(inputId, btn) {
    const input = document.getElementById(inputId);
    const icon = btn.querySelector('i');
    if (input.type === 'password') {
        input.type = 'text';
        icon.setAttribute('data-lucide', 'eye-off');
    } else {
        input.type = 'password';
        icon.setAttribute('data-lucide', 'eye');
    }
    window.lucide?.render();
}

function toggleThemeMode() {
    const isDark = document.documentElement.classList.contains('dark');
    window.setTheme(isDark ? 'light' : 'dark');
}
</script>
</body>
</html>