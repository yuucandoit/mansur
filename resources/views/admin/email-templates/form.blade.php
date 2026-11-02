@extends('layouts.app')

@section('title', $template->exists ? 'Edit Email Template' : 'New Email Template')

@section('content')
<div class="flex-1 overflow-y-auto bg-background">
    <div class="w-full max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">

        @include('admin.partials.header', [
            'title' => $template->exists ? 'Edit Email Template' : 'New Email Template',
            'subtitle' => 'Build reusable HTML email content for notifications and invitations.',
            'icon' => 'mail',
        ])

        @include('admin.partials.flash')

        <div class="flex items-center justify-between">
            <a href="{{ route('admin.email-templates.index') }}" class="text-xs text-muted-foreground hover:text-foreground inline-flex items-center gap-1.5">
                <i data-lucide="arrow-left" class="w-3.5 h-3.5"></i>
                <span>Back to templates</span>
            </a>
        </div>

        <form method="POST"
              action="{{ $template->exists ? route('admin.email-templates.update', $template) : route('admin.email-templates.store') }}"
              class="rounded-xl border border-border bg-card p-6 space-y-5">
            @csrf
            @if($template->exists)
            @method('PUT')
            @endif

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="space-y-1.5">
                    <label class="text-xs font-medium text-foreground">Name <span class="text-destructive">*</span></label>
                    <input name="name" value="{{ old('name', $template->name) }}" class="form-input text-xs" placeholder="Task assigned notification" required>
                </div>
                <div class="space-y-1.5">
                    <label class="text-xs font-medium text-foreground">Slug</label>
                    <input name="slug" value="{{ old('slug', $template->slug) }}" class="form-input text-xs" placeholder="task-assigned">
                </div>
                <div class="space-y-1.5">
                    <label class="text-xs font-medium text-foreground">Language</label>
                    <input name="language" value="{{ old('language', $template->language ?? 'en') }}" class="form-input text-xs" placeholder="en">
                </div>
                <div class="space-y-1.5">
                    <label class="text-xs font-medium text-foreground">Short description</label>
                    <input name="details" value="{{ old('details', $template->details) }}" class="form-input text-xs" placeholder="Sent when a task is assigned">
                </div>
            </div>

            <div class="space-y-1.5">
                <label class="text-xs font-medium text-foreground">HTML content</label>
                <textarea name="html" rows="14" class="form-textarea text-xs font-mono" placeholder="<h1>Hello world</h1>">{{ old('html', $template->html) }}</textarea>
                <p class="text-[11px] text-muted-foreground">You can use Blade-style placeholders in your email body.</p>
            </div>

            <div class="flex items-center justify-end gap-2 pt-4 border-t border-border">
                <a href="{{ route('admin.email-templates.index') }}" class="btn btn-outline">Cancel</a>
                <button type="submit" class="btn btn-primary">
                    <i data-lucide="save" class="w-4 h-4"></i>
                    <span>{{ $template->exists ? 'Save Changes' : 'Create Template' }}</span>
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
