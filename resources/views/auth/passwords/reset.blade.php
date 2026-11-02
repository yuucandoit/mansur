@extends('layouts.app')

@section('title', 'Set New Password')

@section('content')
<div class="flex-1 flex items-center justify-center p-6 bg-background">
    <div class="w-full max-w-md space-y-6">
        <div class="text-center space-y-1.5">
            <h1 class="text-2xl font-bold tracking-tight text-foreground">Set New Password</h1>
            <p class="text-xs text-muted-foreground">
                Enter your email address and new password below.
            </p>
        </div>

        <div class="rounded-xl border border-border bg-card p-6 shadow-sm">
            <form method="POST" action="{{ route('password.update') }}" class="space-y-4">
                @csrf
                <input type="hidden" name="token" value="{{ $token }}">

                <div class="space-y-1.5">
                    <label for="email" class="text-xs font-medium text-foreground">Email Address</label>
                    <input id="email" 
                           type="email" 
                           name="email" 
                           value="{{ $email ?? old('email') }}" 
                           required 
                           autofocus 
                           class="form-input text-xs @error('email') border-destructive @enderror">
                    @error('email')
                    <p class="text-[11px] text-destructive mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="space-y-1.5">
                    <label for="password" class="text-xs font-medium text-foreground">New Password</label>
                    <input id="password" 
                           type="password" 
                           name="password" 
                           required 
                           placeholder="••••••••"
                           class="form-input text-xs @error('password') border-destructive @enderror">
                    @error('password')
                    <p class="text-[11px] text-destructive mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="space-y-1.5">
                    <label for="password_confirmation" class="text-xs font-medium text-foreground">Confirm New Password</label>
                    <input id="password_confirmation" 
                           type="password" 
                           name="password_confirmation" 
                           required 
                           placeholder="••••••••"
                           class="form-input text-xs">
                </div>

                <div class="flex items-center justify-end gap-2 pt-2">
                    <button type="submit" class="btn btn-primary btn-sm text-xs w-full">
                        Reset Password
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
