@extends('layouts.app')

@section('title', 'Verify Email Address')

@section('content')
<div class="flex-1 flex items-center justify-center p-6 bg-background">
    <div class="w-full max-w-md space-y-6">
        <div class="text-center space-y-1.5">
            <h1 class="text-2xl font-bold tracking-tight text-foreground">Verify Your Email</h1>
            <p class="text-xs text-muted-foreground">
                Before proceeding, please check your email for a verification link.
            </p>
        </div>

        <div class="rounded-xl border border-border bg-card p-6 shadow-sm space-y-4">
            @if (session('resent'))
            <div class="rounded-lg bg-emerald-500/10 border border-emerald-500/20 p-3 text-xs text-emerald-600 dark:text-emerald-400 flex items-center gap-2">
                <i data-lucide="check-circle-2" class="w-4 h-4 shrink-0"></i>
                <span>A fresh verification link has been sent to your email address.</span>
            </div>
            @endif

            <p class="text-xs text-muted-foreground leading-relaxed">
                If you did not receive the email, you can request another link below.
            </p>

            <form method="POST" action="{{ route('verification.resend') }}">
                @csrf
                <button type="submit" class="btn btn-outline btn-sm text-xs w-full">
                    Resend Verification Email
                </button>
            </form>
        </div>
    </div>
</div>
@endsection
