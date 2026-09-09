<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'ProTask') — Task Manager</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    @stack('head')
</head>
<body id="app-body">

<!-- ── SIDEBAR ── -->
<aside class="sidebar" id="sidebar">
    <div class="sidebar-brand">
        <div class="brand-icon">PT</div>
        <div class="brand-text">
            <h1 class="app-name">ProTask</h1>
            <p>Team Plan</p>
        </div>
        <button class="col-btn" style="margin-left:auto;background:#f1f5f9;color:#475569;" onclick="document.getElementById('newProjectModal').classList.add('open')">
            <i class="fas fa-plus"></i>
        </button>
    </div>

    <!-- Menu -->
    <nav id="sidebar-nav">
        <div class="sidebar-label">Menu</div>
        <a href="/" class="sidebar-link {{ request()->is('/') || request()->is('projects/*/board') ? 'active' : '' }}">
            <i class="fas fa-th-large" style="width:1rem"></i> Dashboard
        </a>
        <a href="{{ route('projects.index') }}" class="sidebar-link {{ request()->is('projects') ? 'active' : '' }}">
            <i class="fas fa-project-diagram" style="width:1rem"></i> All Projects
        </a>

        <!-- Workspaces -->
        <div class="sidebar-label" style="margin-top:.75rem">Workspaces</div>

        @php
            $workspaces = \App\Models\Workspace::with('projects')->orderBy('name')->get();
            $currentProjectId = request()->route('project')?->id ?? null;
        @endphp

        @forelse($workspaces as $ws)
        <div class="workspace-item">
            <div class="workspace-header {{ $ws->projects->contains('id', $currentProjectId) ? 'expanded' : '' }}"
                 onclick="toggleWorkspace(this)">
                <i class="fas fa-briefcase ws-icon" style="color:#6366f1; font-size:.7rem"></i>
                <span style="flex:1;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">{{ $ws->name }}</span>
                <i class="fas fa-chevron-down" style="font-size:.6rem;margin-left:.25rem;transition:transform .2s"
                   class="{{ $ws->projects->contains('id', $currentProjectId) ? 'rotated' : '' }}"></i>
            </div>
            <div class="workspace-submenu {{ $ws->projects->contains('id', $currentProjectId) ? 'open' : '' }}">
                @foreach($ws->projects as $p)
                <a href="{{ route('projects.board', $p) }}"
                   class="submenu-link {{ ($currentProjectId == $p->id) ? 'active' : '' }}">
                    {{ $p->title }}
                </a>
                @endforeach
            </div>
        </div>
        @empty
        <p style="padding:.5rem 1.25rem;font-size:.78rem;color:#94a3b8">No workspaces yet</p>
        @endforelse

        <div class="sidebar-label" style="margin-top:.75rem">Support</div>
        <a href="#" class="sidebar-link"><i class="fas fa-info-circle" style="width:1rem"></i> Help Center</a>
        <a href="#" class="sidebar-link"><i class="fas fa-cog" style="width:1rem"></i> Settings</a>
    </nav>

    <!-- Theme toggle -->
    <div class="sidebar-theme-toggle">
        <div class="theme-switch">
            <button id="themeLight" class="active" onclick="setTheme('light')">
                <i class="fas fa-sun" style="color:#f59e0b"></i> Light
            </button>
            <button id="themeDark" onclick="setTheme('dark')">
                <i class="fas fa-moon"></i> Dark
            </button>
        </div>
    </div>

    <div>
        <button onclick="logout()" class="btn-logout"><i class="fa-solid fa-power-off"></i> Logout </button>
    </div>
</aside>

<!-- ── MAIN ── -->
<main class="main">
    @yield('content')
</main>

<!-- ── TOAST CONTAINER ── -->
<div id="toast-container"></div>

<!-- ── NEW PROJECT MODAL ── -->
<div class="modal-overlay" id="newProjectModal" onclick="closeOnOverlay(event, 'newProjectModal')">
    <div class="modal">
        <div class="modal-header">
            <span class="modal-title">Create New Project</span>
            <button class="modal-close" onclick="document.getElementById('newProjectModal').classList.remove('open')">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <form id="newProjectForm" onsubmit="submitNewProject(event)">
            <div class="form-group">
                <label class="form-label">Project Name *</label>
                <input class="form-input" name="title" placeholder="e.g. Restaurant Web App" required>
            </div>
            <div class="form-group">
                <label class="form-label">Workspace</label>
                <select class="form-select" name="workspace_id">
                    <option value="">— Select workspace —</option>
                    @foreach(\App\Models\Workspace::orderBy('name')->get() as $ws)
                    <option value="{{ $ws->id }}">{{ $ws->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Description</label>
                <textarea class="form-textarea" name="description" placeholder="Brief project description..."></textarea>
            </div>
            <div class="form-group" style="display:flex;gap:.75rem">
                <div style="flex:1">
                    <label class="form-label">Start Date</label>
                    <input class="form-input" type="date" name="start_date">
                </div>
                <div style="flex:1">
                    <label class="form-label">End Date</label>
                    <input class="form-input" type="date" name="end_date">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-ghost" onclick="document.getElementById('newProjectModal').classList.remove('open')">Cancel</button>
                <button type="submit" class="btn-primary" style="flex:none;padding:.5rem 1.5rem">Create Project</button>
            </div>
        </form>
    </div>
</div>

<!-- ── NEW WORKSPACE MODAL ── -->
<div class="modal-overlay" id="newWorkspaceModal" onclick="closeOnOverlay(event, 'newWorkspaceModal')">
    <div class="modal">
        <div class="modal-header">
            <span class="modal-title">Create Workspace</span>
            <button class="modal-close" onclick="document.getElementById('newWorkspaceModal').classList.remove('open')">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <form onsubmit="submitNewWorkspace(event)">
            <div class="form-group">
                <label class="form-label">Workspace Name *</label>
                <input class="form-input" name="ws_name" placeholder="e.g. Client Projects" required>
            </div>
            <div class="form-group">
                <label class="form-label">Description</label>
                <textarea class="form-textarea" name="ws_description" placeholder="Optional..."></textarea>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-ghost" onclick="document.getElementById('newWorkspaceModal').classList.remove('open')">Cancel</button>
                <button type="submit" class="btn-primary" style="flex:none;padding:.5rem 1.5rem">Create</button>
            </div>
        </form>
    </div>
</div>

<script>
// ── CSRF ──
const CSRF = document.querySelector('meta[name="csrf-token"]').content;
const headers = { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' };

// ── THEME ──
function setTheme(mode) {
    document.getElementById('app-body').classList.toggle('dark', mode === 'dark');
    document.getElementById('themeLight').classList.toggle('active', mode === 'light');
    document.getElementById('themeDark').classList.toggle('active', mode === 'dark');
    localStorage.setItem('theme', mode);
}

// Apply saved theme
if (localStorage.getItem('theme') === 'dark') setTheme('dark');

// ── SIDEBAR ACCORDION ──
function toggleWorkspace(el) {
    const submenu = el.nextElementSibling;
    const chevron = el.querySelector('.fa-chevron-down, .fa-chevron-up');

    const isOpen = submenu.classList.contains('open');
    submenu.classList.toggle('open', !isOpen);
    el.classList.toggle('expanded', !isOpen);
    if (chevron) chevron.style.transform = isOpen ? '' : 'rotate(180deg)';
}

// Initialize chevrons on load
document.querySelectorAll('.workspace-header.expanded .fa-chevron-down').forEach(el => {
    el.style.transform = 'rotate(180deg)';
});

// ── TOAST ──
function showToast(msg, type = '') {
    const c = document.getElementById('toast-container');
    const t = document.createElement('div');
    t.className = `toast ${type}`;
    t.innerHTML = `<i class="fas ${type === 'success' ? 'fa-check-circle' : type === 'error' ? 'fa-exclamation-circle' : 'fa-info-circle'}"></i> ${msg}`;
    c.appendChild(t);
    setTimeout(() => t.remove(), 3500);
}

// ── MODAL OVERLAY CLOSE ──
function closeOnOverlay(e, id) {
    if (e.target === e.currentTarget) document.getElementById(id).classList.remove('open');
}

// __ Logout Function __
function logout(e) {
    fetch('/logout', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            // Jika kamu menggunakan Laravel atau framework dengan proteksi CSRF, 
            // sertakan token di sini:
            // 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({
            // Data tambahan jika diperlukan, misal: id: id 
        })
    })
    .then(response => {
        if (response.ok) {
            // Jika sukses, arahkan user ke halaman login atau beranda
            window.location.href = '/login';
        } else {
            console.error('Logout gagal');
        }
    })
    .catch(error => {
        console.error('Terjadi kesalahan jaringan:', error);
    });
}

// ── NEW PROJECT ──
async function submitNewProject(e) {
    e.preventDefault();
    const form = e.target;
    const data = {
        title: form.title.value,
        workspace_id: form.workspace_id.value || null,
        description: form.description.value,
        start_date: form.start_date.value || null,
        end_date: form.end_date.value || null,
    };
    try {
        const res = await fetch('/projects', { method: 'POST', headers, body: JSON.stringify(data) });
        const proj = await res.json();
        if (res.ok) {
            document.getElementById('newProjectModal').classList.remove('open');
            window.location.href = `/projects/${proj.id}/board`;
        } else {
            showToast('Failed to create project', 'error');
        }
    } catch(err) {
        showToast('Network error', 'error');
    }
}

// ── NEW WORKSPACE ──
async function submitNewWorkspace(e) {
    e.preventDefault();
    const form = e.target;
    const data = { name: form.ws_name.value, description: form.ws_description.value };
    try {
        const res = await fetch('/workspaces', { method: 'POST', headers, body: JSON.stringify(data) });
        if (res.ok) {
            showToast('Workspace created!', 'success');
            document.getElementById('newWorkspaceModal').classList.remove('open');
            setTimeout(() => location.reload(), 800);
        }
    } catch(err) {
        showToast('Network error', 'error');
    }
}
</script>

@stack('scripts')
</body>
</html>
