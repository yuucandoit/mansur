@extends('layouts.app')

@section('title', 'All Projects')

@section('content')

<div class="project-header" style="border-bottom:none">
    <div class="top-row">
        <div>
            <h2 class="h-mode" style="font-size:1.25rem;font-weight:700;">All Projects</h2>
            <p style="font-size:.82rem;color:#64748b;margin-top:.2rem">{{ $projects->count() }} projects across {{ $workspaces->count() }} workspaces</p>
        </div>
        <div style="display:flex;gap:.625rem">
            <button class="toolbar-btn" onclick="document.getElementById('newWorkspaceModal').classList.add('open')">
                <i class="fas fa-folder-plus"></i> New Workspace
            </button>
            <button class="btn-primary" style="flex:none;padding:.5rem 1.125rem;font-size:.82rem"
                    onclick="document.getElementById('newProjectModal').classList.add('open')">
                <i class="fas fa-plus"></i> New Project
            </button>
        </div>
    </div>
</div>

<div style="padding:1.5rem;overflow-y:auto;height:calc(100vh - 90px)">
    @forelse($workspaces as $ws)
    <div style="margin-bottom:2.5rem">
        <div style="display:flex;align-items:center;gap:.75rem;margin-bottom:1rem">
            <div style="width:2rem;height:2rem;background:linear-gradient(135deg,#6366f1,#8b5cf6);border-radius:.5rem;display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;font-size:.85rem">
                {{ strtoupper(substr($ws->name, 0, 1)) }}
            </div>
            <h3 class="h-mode"  style="font-size:.95rem;font-weight:600;>{{ $ws->name }}</h3>
            <span style="background:#f1f5f9;color:#64748b;font-size:.72rem;padding:.15rem .5rem;border-radius:99px">{{ $ws->projects->count() }} projects</span>
        </div>

        @if($ws->projects->count())
        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:1rem">
            @php
                $colors = ['#ef4444','#f59e0b','#8b5cf6','#10b981','#3b82f6','#ec4899','#6366f1','#14b8a6'];
            @endphp
            @foreach($ws->projects as $pi => $proj)
            @php $pc = $colors[$pi % count($colors)]; @endphp
            <a href="{{ route('projects.board', $proj) }}"
               class="card-mode"
               style="text-decoration:none;border-radius:.875rem;overflow:hidden;transition:transform .15s,box-shadow .15s;cursor:pointer;display:block"
               onmouseover="this.style.transform='translateY(-3px)';this.style.boxShadow='0 8px 20px rgba(0,0,0,.08)'"
               onmouseout="this.style.transform='';this.style.boxShadow=''">
                <div style="height:.35rem;background:{{ $pc }}"></div>
                <div style="padding:1rem">
                    <div style="display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:.5rem">
                        <h4 class="h-mode" style="font-size:.88rem;font-weight:600;line-height:1.3">{{ $proj->title }}</h4>
                        @if($proj->is_starred)
                        <i class="fas fa-star" style="color:#f59e0b;font-size:.75rem;flex-shrink:0;margin-top:.2rem"></i>
                        @endif
                    </div>
                    @if($proj->description)
                    <p style="font-size:.78rem;color:#64748b;margin-bottom:.75rem;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden">{{ $proj->description }}</p>
                    @endif
                    <div style="display:flex;align-items:center;justify-content:space-between;margin-top:.75rem">
                        <span class="span-task-mode" style="font-size:.72rem;padding:.15rem .5rem;border-radius:99px">
                            <i class="fas fa-tasks"></i> {{ $proj->tasks()->count() }} tasks
                        </span>
                        @if($proj->end_date)
                        <span style="font-size:.72rem;color:#94a3b8">
                            <i class="fas fa-calendar"></i> {{ $proj->end_date->format('M j, Y') }}
                        </span>
                        @endif
                    </div>
                </div>
            </a>
            @endforeach
        </div>
        @else
        <p style="font-size:.82rem;color:#94a3b8;padding:.75rem 0">No projects in this workspace yet.</p>
        @endif
    </div>
    @empty
    <div style="text-align:center;padding:4rem 2rem">
        <div style="font-size:3rem;margin-bottom:1rem">📋</div>
        <h3 style="font-size:1.1rem;font-weight:600;color:#1e293b;margin-bottom:.5rem">No projects yet</h3>
        <p style="color:#64748b;font-size:.85rem;margin-bottom:1.5rem">Create your first workspace and project to get started.</p>
        <button class="btn-primary" style="padding:.625rem 1.5rem"
                onclick="document.getElementById('newProjectModal').classList.add('open')">
            <i class="fas fa-plus"></i> Create Project
        </button>
    </div>
    @endforelse
</div>

@endsection
