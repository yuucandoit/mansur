@extends('layouts.app')

@section('title', 'Email Templates')

@section('content')
<div class="flex-1 overflow-y-auto bg-background">
    <div class="w-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">

        @include('admin.partials.header', [
            'title' => 'Email Templates',
            'subtitle' => 'Reusable HTML templates for outgoing email notifications.',
            'icon' => 'mail',
        ])

        @include('admin.partials.flash')

        <div class="flex items-center justify-between gap-3">
            <div>
                <h2 class="text-sm font-semibold text-foreground">Templates</h2>
                <p class="text-xs text-muted-foreground mt-0.5">{{ $templates->count() }} template{{ $templates->count() === 1 ? '' : 's' }}</p>
            </div>
            <a href="{{ route('admin.email-templates.create') }}" class="btn btn-primary btn-sm">
                <i data-lucide="plus" class="w-3.5 h-3.5"></i>
                <span>New Template</span>
            </a>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
            @forelse($templates as $template)
            <div class="rounded-xl border border-border bg-card p-5 flex flex-col gap-4">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <h3 class="text-sm font-semibold text-foreground truncate">{{ $template->name }}</h3>
                        <p class="text-xs text-muted-foreground mt-1 line-clamp-2">
                            {{ $template->details ?: 'No description provided.' }}
                        </p>
                    </div>
                    <div class="flex items-center gap-1.5 shrink-0">
                        <span class="badge badge-outline text-[10px] uppercase">{{ $template->language ?: 'en' }}</span>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-2 text-[11px] text-muted-foreground">
                    <span class="badge badge-secondary text-[10px] font-mono">{{ $template->slug ?: 'no-slug' }}</span>
                    @if($template->html)
                    <span class="flex items-center gap-1">
                        <i data-lucide="code" class="w-3 h-3"></i>
                        {{ number_format(strlen($template->html)) }} chars
                    </span>
                    @else
                    <span class="italic">No HTML body</span>
                    @endif
                </div>

                <div class="flex items-center justify-end gap-2 pt-3 border-t border-border/60 mt-auto">
                    <a href="{{ route('admin.email-templates.edit', $template) }}" class="btn btn-outline btn-sm">
                        <i data-lucide="pencil" class="w-3.5 h-3.5"></i>
                        <span>Edit</span>
                    </a>
                    <form method="POST" action="{{ route('admin.email-templates.destroy', $template) }}"
                          onsubmit="return confirm('Delete this email template?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-ghost btn-sm text-muted-foreground hover:text-destructive">
                            <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                        </button>
                    </form>
                </div>
            </div>
            @empty
            <div class="lg:col-span-2 rounded-xl border border-dashed border-border p-12 text-center">
                <div class="w-12 h-12 rounded-full bg-secondary flex items-center justify-center text-muted-foreground mx-auto mb-3">
                    <i data-lucide="mail" class="w-5 h-5"></i>
                </div>
                <p class="text-sm font-medium text-foreground">No email templates yet</p>
                <p class="text-xs text-muted-foreground mt-1 mb-4">Create your first template to start building email notifications.</p>
                <a href="{{ route('admin.email-templates.create') }}" class="btn btn-primary btn-sm">
                    <i data-lucide="plus" class="w-3.5 h-3.5"></i>
                    <span>Create Template</span>
                </a>
            </div>
            @endforelse
        </div>
    </div>
</div>
@endsection
