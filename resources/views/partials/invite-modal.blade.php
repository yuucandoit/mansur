<!-- ── INVITE MODAL (workspace / project) ── -->
<div class="dialog-overlay" id="inviteModal" onclick="window.closeOnOverlay(event, 'inviteModal')">
    <div class="dialog-content">
        <div class="flex items-center justify-between pb-3 border-b border-border mb-4">
            <div class="min-w-0">
                <h3 class="font-semibold text-base leading-none tracking-tight">Invite People</h3>
                <p class="text-xs text-muted-foreground mt-1 truncate" id="inviteContextLabel">Invite a teammate</p>
            </div>
            <button type="button" class="btn btn-ghost p-1 h-8 w-8 text-muted-foreground hover:text-foreground" onclick="window.closeModal('inviteModal')">
                <i data-lucide="x" class="w-4 h-4"></i>
            </button>
        </div>

        <form id="inviteForm" onsubmit="submitInvitation(event)" class="space-y-4">
            <input type="hidden" name="workspace_id" id="inviteWorkspaceId">
            <input type="hidden" name="project_id" id="inviteProjectId">

            <div class="space-y-1.5">
                <label class="text-xs font-medium text-foreground">Email address <span class="text-destructive">*</span></label>
                <input type="email" name="email" class="form-input text-xs" placeholder="teammate@example.com" required>
            </div>

            <div class="space-y-1.5">
                <label class="text-xs font-medium text-foreground">Role</label>
                <select name="role" id="inviteRole" class="form-select text-xs">
                    <option value="member">Member — can work on tasks</option>
                </select>
            </div>

            <div class="space-y-1.5 hidden" id="inviteProjectField">
                <label class="text-xs font-medium text-foreground">Project</label>
                <select name="project_id_select" id="inviteProjectSelect" class="form-select text-xs">
                    <option value="">No project — invitee picks one after joining</option>
                </select>
                <p class="text-[11px] text-muted-foreground">
                    Choosing a project also adds the invitee to this workspace automatically.
                </p>
            </div>

            <div id="inviteResult" class="hidden rounded-lg border border-emerald-500/25 bg-emerald-500/10 p-3 space-y-2">
                <p class="text-[11px] text-emerald-700 dark:text-emerald-400 font-medium">Invitation created. Share this link:</p>
                <div class="flex items-center gap-1.5">
                    <input type="text" id="inviteResultUrl" class="form-input text-[11px] font-mono" readonly>
                    <button type="button" onclick="copyInviteLink()" class="btn btn-outline btn-sm shrink-0" title="Copy link">
                        <i data-lucide="copy" class="w-3.5 h-3.5"></i>
                    </button>
                </div>
            </div>

            <div class="flex items-center justify-end gap-2 pt-3 border-t border-border mt-5">
                <button type="button" class="btn btn-outline" onclick="window.closeModal('inviteModal')">Close</button>
                <button type="submit" class="btn btn-primary" id="inviteSubmitBtn">
                    <i data-lucide="send" class="w-4 h-4"></i>
                    <span>Send Invitation</span>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// ── Invitation Modal ──
window.openInviteModal = async function (context, id, name) {
    const form = document.getElementById('inviteForm');
    const label = document.getElementById('inviteContextLabel');
    const workspaceId = document.getElementById('inviteWorkspaceId');
    const projectId = document.getElementById('inviteProjectId');
    const role = document.getElementById('inviteRole');
    const projectField = document.getElementById('inviteProjectField');
    const projectSelect = document.getElementById('inviteProjectSelect');
    const result = document.getElementById('inviteResult');

    form.reset();
    result.classList.add('hidden');
    label.textContent = name ? `Invite people to ${name}` : 'Invite a teammate';
    workspaceId.value = '';
    projectId.value = '';
    projectSelect.innerHTML = '<option value="">No project — invitee picks one after joining</option>';

    if (context === 'project') {
        projectId.value = id;
        projectField.classList.add('hidden');
        role.innerHTML = `
            <option value="member">Member — can work on tasks</option>
            <option value="manager">Manager — can invite and manage</option>
        `;
    } else {
        workspaceId.value = id;
        projectField.classList.remove('hidden');
        role.innerHTML = `
            <option value="member">Member — can work on tasks</option>
            <option value="admin">Admin — can manage workspace members</option>
        `;

        // Load projects for the optional direct project assignment.
        try {
            const res = await fetch(`/workspaces/${id}/projects`, {
                headers: { 'Accept': 'application/json' }
            });
            if (res.ok) {
                const projects = await res.json();
                projects.forEach(p => {
                    const opt = document.createElement('option');
                    opt.value = p.id;
                    opt.textContent = p.title;
                    projectSelect.appendChild(opt);
                });
            }
        } catch (e) {
            console.error('Failed to load workspace projects', e);
        }
    }

    window.openModal('inviteModal');
};

async function submitInvitation(e) {
    e.preventDefault();
    const form = e.target;
    const btn = document.getElementById('inviteSubmitBtn');
    const result = document.getElementById('inviteResult');
    const resultUrl = document.getElementById('inviteResultUrl');

    btn.disabled = true;
    result.classList.add('hidden');

    const workspaceId = document.getElementById('inviteWorkspaceId').value;
    const directProjectId = document.getElementById('inviteProjectSelect').value;
    const projectId = document.getElementById('inviteProjectId').value || directProjectId;

    const payload = {
        email: form.email.value.trim(),
        role: form.role.value,
        workspace_id: workspaceId || null,
        project_id: projectId || null
    };

    try {
        const res = await fetch('/invitations', {
            method: 'POST',
            headers,
            body: JSON.stringify(payload)
        });
        const data = await res.json();

        if (!res.ok) {
            window.showToast(data.message || 'Could not create invitation.', 'error');
            return;
        }

        resultUrl.value = data.accept_url;
        result.classList.remove('hidden');
        window.showToast(data.message || 'Invitation created!', 'success');
        window.lucide.render();
    } catch (err) {
        window.showToast('Network error, please try again.', 'error');
    } finally {
        btn.disabled = false;
    }
}

function copyInviteLink() {
    const input = document.getElementById('inviteResultUrl');
    if (!input || !input.value) return;
    navigator.clipboard.writeText(input.value)
        .then(() => window.showToast('Invitation link copied!', 'success'))
        .catch(() => window.showToast('Could not copy link.', 'error'));
}
</script>
