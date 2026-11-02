@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<div class="flex-1 flex flex-col items-center justify-center p-6 bg-background">
    <div class="w-full max-w-md space-y-4">
        <div class="rounded-xl border border-border bg-card p-6 shadow-sm space-y-4 text-center">
            <div class="w-12 h-12 rounded-full bg-primary/10 text-primary flex items-center justify-center mx-auto">
                <i data-lucide="check-circle" class="w-6 h-6"></i>
            </div>
            
            <div class="space-y-1">
                <h1 class="text-lg font-semibold tracking-tight text-foreground">Welcome to ProTask</h1>
                <p class="text-xs text-muted-foreground">You are logged in and your workspaces are ready.</p>
            </div>

            @if (session('status'))
            <div class="rounded-lg bg-emerald-500/10 border border-emerald-500/20 p-3 text-xs text-emerald-600 dark:text-emerald-400">
                {{ session('status') }}
            </div>
            @endif

            <div class="pt-2 flex items-center justify-center gap-2">
                <a href="{{ route('projects.index') }}" class="btn btn-primary btn-sm text-xs">
                    <i data-lucide="layout-grid" class="w-3.5 h-3.5"></i>
                    <span>Go to All Projects</span>
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
