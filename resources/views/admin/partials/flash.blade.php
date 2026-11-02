@if(session('status'))
<div class="rounded-xl bg-emerald-500/10 border border-emerald-500/20 p-3.5 text-xs text-emerald-700 dark:text-emerald-400">
    <div class="flex items-start gap-2">
        <i data-lucide="check-circle-2" class="w-4 h-4 shrink-0 mt-0.5"></i>
        <span>{{ session('status') }}</span>
    </div>
</div>
@endif

@if($errors->any())
<div class="rounded-xl bg-destructive/10 border border-destructive/20 p-3.5 text-xs text-destructive">
    <div class="flex items-start gap-2">
        <i data-lucide="alert-circle" class="w-4 h-4 shrink-0 mt-0.5"></i>
        <div class="space-y-1">
            @foreach($errors->all() as $error)
            <p>{{ $error }}</p>
            @endforeach
        </div>
    </div>
</div>
@endif
