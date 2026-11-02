@extends('layouts.app')

@section('title', 'Confirm Password')

@section('content')
<div class="flex-1 flex items-center justify-center p-6 bg-background">
    <div class="w-full max-w-md space-y-6">
        <div class="text-center space-y-1.5">
            <h1 class="text-2xl font-bold tracking-tight text-foreground">Confirm Password</h1>
            <p class="text-xs text-muted-foreground">
                Please confirm your password before continuing.
            </p>
        </div>

        <div class="rounded-xl border border-border bg-card p-6 shadow-sm">
            <form method="POST" action="{{ route('password.confirm') }}" class="space-y-4">
                @csrf

                <div class="space-y-1.5">
                    <label for="password" class="text-xs font-medium text-foreground">Password</label>
                    <input id="password" 
                           type="password" 
                           name="password" 
                           required 
                           autocomplete="current-password"
                           placeholder="••••••••"
                           class="form-input text-xs @error('password') border-destructive @enderror">
                    @error('password')
                    <p class="text-[11px] text-destructive mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex items-center justify-between pt-2">
                    @if (Route::has('password.request'))
                    <a href="{{ route('password.request') }}" class="text-xs text-muted-foreground hover:text-foreground">
                        Forgot password?
                    </a>
                    @endif
                    <button type="submit" class="btn btn-primary btn-sm text-xs">
                        Confirm Password
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
