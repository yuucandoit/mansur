@extends('layouts.app')

@section('title', 'Backups')

@section('content')
<div class="flex-1 overflow-y-auto bg-background">
    <div class="w-full max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">

        @include('admin.partials.header', [
            'title' => 'Backups',
            'subtitle' => 'Create downloadable JSON snapshots of users, workspaces, projects, and tasks.',
            'icon' => 'database-backup',
        ])

        @include('admin.partials.flash')

        <!-- Create -->
        <form method="POST" action="{{ route('admin.backups.store') }}"
              class="rounded-xl border border-border bg-card p-5 space-y-4">
            @csrf
            <div class="flex items-center gap-2">
                <i data-lucide="hard-drive-download" class="w-4 h-4 text-muted-foreground"></i>
                <h2 class="text-sm font-semibold text-foreground">Create a new backup</h2>
            </div>
            <div class="flex flex-col sm:flex-row sm:items-end gap-3">
                <div class="flex-1 space-y-1.5">
                    <label class="text-[11px] font-medium text-foreground">Backup name (optional)</label>
                    <input name="name" value="{{ old('name') }}" class="form-input text-xs" placeholder="backup-{{ now()->format('Ymd-His') }}">
                </div>
                <button type="submit" class="btn btn-primary btn-sm h-9 shrink-0">
                    <i data-lucide="database-backup" class="w-3.5 h-3.5"></i>
                    <span>Create Backup</span>
                </button>
            </div>
            <p class="text-[11px] text-muted-foreground">
                The snapshot is stored in <code class="font-mono">storage/app/private/backups</code> and can be downloaded below.
            </p>
        </form>

        <!-- List -->
        <div class="space-y-3">
            <div class="flex items-center justify-between px-1">
                <h2 class="text-sm font-semibold text-foreground">Backup history</h2>
                <span class="badge badge-secondary text-[11px]">{{ $backups->count() }}</span>
            </div>

            @forelse($backups as $backup)
            @php
                $details = $backup->details ?? [];
                $size = $details['size'] ?? null;
                $counts = $details['counts'] ?? [];
            @endphp
            <div class="rounded-xl border border-border bg-card p-5 flex flex-col sm:flex-row sm:items-center gap-4">
                <div class="w-10 h-10 rounded-lg bg-secondary border border-border flex items-center justify-center shrink-0">
                    <i data-lucide="archive" class="w-4.5 h-4.5 text-muted-foreground"></i>
                </div>

                <div class="flex-1 min-w-0">
                    <h3 class="text-sm font-semibold text-foreground truncate">{{ $backup->name }}</h3>
                    <div class="text-[11px] text-muted-foreground mt-1 flex flex-wrap items-center gap-x-3 gap-y-1">
                        <span>{{ $backup->created_at?->format('M j, Y · H:i') }}</span>
                        @if($size)
                        <span>{{ number_format($size / 1024, 1) }} KB</span>
                        @endif
                        @if(!empty($counts))
                        <span>{{ $counts['users'] ?? 0 }} users · {{ $counts['projects'] ?? 0 }} projects · {{ $counts['tasks'] ?? 0 }} tasks</span>
                        @endif
                    </div>
                </div>

                <div class="flex items-center gap-2 shrink-0">
                    <a href="{{ route('admin.backups.download', $backup) }}" class="btn btn-outline btn-sm">
                        <i data-lucide="download" class="w-3.5 h-3.5"></i>
                        <span>Download</span>
                    </a>
                    <form method="POST" action="{{ route('admin.backups.destroy', $backup) }}"
                          onsubmit="return confirm('Delete this backup? This cannot be undone.')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-ghost btn-sm h-9 w-9 text-muted-foreground hover:text-destructive" title="Delete backup">
                            <i data-lucide="trash-2" class="w-4 h-4"></i>
                        </button>
                    </form>
                </div>
            </div>
            @empty
            <div class="rounded-xl border border-dashed border-border p-10 text-center">
                <div class="w-11 h-11 rounded-full bg-secondary flex items-center justify-center text-muted-foreground mx-auto mb-3">
                    <i data-lucide="database-backup" class="w-5 h-5"></i>
                </div>
                <p class="text-sm font-medium text-foreground">No backups yet</p>
                <p class="text-xs text-muted-foreground mt-1">Create your first snapshot using the form above.</p>
            </div>
            @endforelse
        </div>
    </div>
</div>
@endsection
