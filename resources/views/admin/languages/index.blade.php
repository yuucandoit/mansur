@extends('layouts.app')

@section('title', 'Languages')

@section('content')
<div class="flex-1 overflow-y-auto bg-background">
    <div class="w-full max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">

        @include('admin.partials.header', [
            'title' => 'Languages',
            'subtitle' => 'Available language options for the application and email templates.',
            'icon' => 'languages',
        ])

        @include('admin.partials.flash')

        <!-- Add -->
        <form method="POST" action="{{ route('admin.languages.store') }}"
              class="rounded-xl border border-border bg-card p-5 space-y-4">
            @csrf
            <div class="flex items-center gap-2">
                <i data-lucide="plus" class="w-4 h-4 text-muted-foreground"></i>
                <h2 class="text-sm font-semibold text-foreground">Add language</h2>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-[160px_1fr_auto] gap-3 sm:items-end">
                <div class="space-y-1.5">
                    <label class="text-[11px] font-medium text-foreground">Code</label>
                    <input name="code" value="{{ old('code') }}" class="form-input text-xs uppercase" placeholder="en" maxlength="5" required>
                </div>
                <div class="space-y-1.5">
                    <label class="text-[11px] font-medium text-foreground">Name</label>
                    <input name="name" value="{{ old('name') }}" class="form-input text-xs" placeholder="English" required>
                </div>
                <button type="submit" class="btn btn-primary btn-sm h-9">
                    <i data-lucide="plus" class="w-3.5 h-3.5"></i>
                    <span>Add</span>
                </button>
            </div>
        </form>

        <!-- List -->
        <div class="space-y-3">
            <div class="flex items-center justify-between px-1">
                <h2 class="text-sm font-semibold text-foreground">Languages</h2>
                <span class="badge badge-secondary text-[11px]">{{ $languages->count() }}</span>
            </div>

            @forelse($languages as $language)
            <div class="rounded-xl border border-border bg-card p-4 flex flex-col sm:flex-row sm:items-end gap-3">
                <form method="POST" action="{{ route('admin.languages.update', $language) }}"
                      class="flex-1 grid grid-cols-1 sm:grid-cols-[140px_1fr_auto] gap-3 sm:items-end">
                    @csrf
                    @method('PUT')
                    <div class="space-y-1.5">
                        <label class="text-[11px] font-medium text-foreground">Code</label>
                        <input name="code" value="{{ $language->code }}" class="form-input text-xs uppercase" maxlength="5" required>
                    </div>
                    <div class="space-y-1.5">
                        <label class="text-[11px] font-medium text-foreground">Name</label>
                        <input name="name" value="{{ $language->name }}" class="form-input text-xs" required>
                    </div>
                    <button type="submit" class="btn btn-outline btn-sm h-9">
                        <i data-lucide="save" class="w-3.5 h-3.5"></i>
                        <span>Save</span>
                    </button>
                </form>

                <form method="POST" action="{{ route('admin.languages.destroy', $language) }}"
                      onsubmit="return confirm('Delete this language?')" class="shrink-0">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-ghost btn-sm h-9 w-9 text-muted-foreground hover:text-destructive" title="Delete language">
                        <i data-lucide="trash-2" class="w-4 h-4"></i>
                    </button>
                </form>
            </div>
            @empty
            <div class="rounded-xl border border-dashed border-border p-10 text-center">
                <div class="w-11 h-11 rounded-full bg-secondary flex items-center justify-center text-muted-foreground mx-auto mb-3">
                    <i data-lucide="languages" class="w-5 h-5"></i>
                </div>
                <p class="text-sm font-medium text-foreground">No languages configured</p>
                <p class="text-xs text-muted-foreground mt-1">Add a language using the form above.</p>
            </div>
            @endforelse
        </div>
    </div>
</div>
@endsection
