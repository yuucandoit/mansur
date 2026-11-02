@extends('layouts.app')

@section('title', 'Reset Password')

@section('content')
<div class="flex-1 flex items-center justify-center p-6 bg-background">
    <div class="w-full max-w-md space-y-6">
        <div class="text-center space-y-1.5">
            <h1 class="text-2xl font-bold tracking-tight text-foreground">Reset Password</h1>
            <p class="text-xs text-muted-foreground">
                Enter your email address and we'll send you a password reset link.
            </p>
        </div>

        <div class="rounded-xl border border-border bg-card p-6 shadow-sm">
            @if (session('status'))
            <div class="rounded-lg bg-emerald-500/10 border border-emerald-500/20 p-3 text-xs text-emerald-600 dark:text-emerald-400 mb-4 flex items-center gap-2">
                <i data-lucide="check-circle-2" class="w-4 h-4 shrink-0"></i>
                <span>{{ session('status') }}</span>
            </div>
            @endif

            <form method="POST" action="{{ route('password.email') }}" class="space-y-4">
                @csrf

                <div class="space-y-1.5">
                    <label for="email" class="text-xs font-medium text-foreground">Email Address</label>
                    <input id="email" 
                           type="email" 
                           name="email" 
                           value="{{ old('email') }}" 
                           required 
                           autofocus 
                           placeholder="name@example.com"
                           class="form-input text-xs @error('email') border-destructive @enderror">
                    @error('email')
                    <p class="text-[11px] text-destructive mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex items-center justify-end gap-2 pt-2">
                    <a href="{{ route('login') }}" class="btn btn-outline btn-sm text-xs">
                        Back to login
                    </a>
                    <button type="submit" class="btn btn-primary btn-sm text-xs">
                        Send Reset Link
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
