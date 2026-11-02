@extends('layouts.app')

@section('title', 'Settings')

@section('content')
<div class="flex-1 overflow-y-auto bg-background">
    <div class="w-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">

        @include('admin.partials.header', [
            'title' => 'Settings',
            'subtitle' => 'Global application settings stored as key/value entries.',
            'icon' => 'settings',
        ])

        @include('admin.partials.flash')

        <!-- Create -->
        <form method="POST" action="{{ route('admin.settings.store') }}"
              class="rounded-xl border border-border bg-card p-5 space-y-4">
            @csrf
            <div class="flex items-center gap-2">
                <i data-lucide="plus" class="w-4 h-4 text-muted-foreground"></i>
                <h2 class="text-sm font-semibold text-foreground">Add setting</h2>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-3">
                <div class="space-y-1.5">
                    <label class="text-[11px] font-medium text-foreground">Name</label>
                    <input name="name" value="{{ old('name') }}" class="form-input text-xs" placeholder="Site name" required>
                </div>
                <div class="space-y-1.5">
                    <label class="text-[11px] font-medium text-foreground">Slug</label>
                    <input name="slug" value="{{ old('slug') }}" class="form-input text-xs" placeholder="site_name" required>
                </div>
                <div class="space-y-1.5">
                    <label class="text-[11px] font-medium text-foreground">Type</label>
                    <select name="type" class="form-select text-xs">
                        <option value="string">String</option>
                        <option value="text">Text</option>
                        <option value="boolean">Boolean</option>
                        <option value="number">Number</option>
                        <option value="json">JSON</option>
                    </select>
                </div>
                <div class="space-y-1.5">
                    <label class="text-[11px] font-medium text-foreground">Value</label>
                    <input name="value" value="{{ old('value') }}" class="form-input text-xs" placeholder="ProTask">
                </div>
            </div>

            <div class="flex justify-end">
                <button type="submit" class="btn btn-primary btn-sm">
                    <i data-lucide="plus" class="w-3.5 h-3.5"></i>
                    <span>Add Setting</span>
                </button>
            </div>
        </form>

        <!-- List -->
        <div class="space-y-3">
            <div class="flex items-center justify-between px-1">
                <h2 class="text-sm font-semibold text-foreground">Existing settings</h2>
                <span class="badge badge-secondary text-[11px]">{{ $settings->count() }}</span>
            </div>

            @forelse($settings as $setting)
            <div class="rounded-xl border border-border bg-card p-5">
                <div class="flex flex-col xl:flex-row xl:items-end gap-3">
                    <form method="POST" action="{{ route('admin.settings.update', $setting) }}"
                          class="flex-1 grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-3">
                        @csrf
                        @method('PUT')

                        <div class="space-y-1.5">
                            <label class="text-[11px] font-medium text-foreground">Name</label>
                            <input name="name" value="{{ $setting->name }}" class="form-input text-xs" required>
                        </div>
                        <div class="space-y-1.5">
                            <label class="text-[11px] font-medium text-foreground">Slug</label>
                            <input name="slug" value="{{ $setting->slug }}" class="form-input text-xs" required>
                        </div>
                        <div class="space-y-1.5">
                            <label class="text-[11px] font-medium text-foreground">Type</label>
                            <select name="type" class="form-select text-xs">
                                @foreach(['string', 'text', 'boolean', 'number', 'json'] as $type)
                                <option value="{{ $type }}" @selected($setting->type === $type)>{{ ucfirst($type) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="space-y-1.5">
                            <label class="text-[11px] font-medium text-foreground">Value</label>
                            <input name="value" value="{{ $setting->value }}" class="form-input text-xs">
                        </div>

                        <div class="sm:col-span-2 xl:col-span-4 flex justify-end">
                            <button type="submit" class="btn btn-outline btn-sm">
                                <i data-lucide="save" class="w-3.5 h-3.5"></i>
                                <span>Save</span>
                            </button>
                        </div>
                    </form>

                    <form method="POST" action="{{ route('admin.settings.destroy', $setting) }}"
                          onsubmit="return confirm('Delete this setting?')"
                          class="shrink-0">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-ghost btn-sm h-9 w-9 text-muted-foreground hover:text-destructive" title="Delete setting">
                            <i data-lucide="trash-2" class="w-4 h-4"></i>
                        </button>
                    </form>
                </div>
            </div>
            @empty
            <div class="rounded-xl border border-dashed border-border p-10 text-center">
                <div class="w-11 h-11 rounded-full bg-secondary flex items-center justify-center text-muted-foreground mx-auto mb-3">
                    <i data-lucide="settings" class="w-5 h-5"></i>
                </div>
                <p class="text-sm font-medium text-foreground">No settings yet</p>
                <p class="text-xs text-muted-foreground mt-1">Add your first global setting using the form above.</p>
            </div>
            @endforelse
        </div>
    </div>
</div>
@endsection
