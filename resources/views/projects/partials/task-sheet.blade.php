<!-- ── TASK DETAIL CARD POPUP MODAL (shadcn Dialog / Card Modal) ── -->
<div class="dialog-overlay" id="taskModalOverlay" onclick="window.closeOnOverlay(event, 'taskModalOverlay')">
    <div class="dialog-content !max-w-[60rem] w-full p-0 flex flex-col max-h-[90vh] overflow-hidden rounded-xl border border-border bg-card shadow-2xl transition-all" id="taskModalContent" style="max-width: 60rem;">
        
        <!-- Modal Top Bar -->
        <div class="px-6 py-3.5 border-b border-border flex items-center justify-between shrink-0 bg-muted/20">
            <div class="flex items-center gap-2.5 min-w-0">
                <span class="w-2.5 h-2.5 rounded-full bg-primary" id="modalStatusDot"></span>
                <span class="text-xs text-muted-foreground truncate" id="modalBreadcrumb">Project / Task</span>
            </div>

            <div class="flex items-center gap-2">
                <!-- Is Done Toggle Button -->
                <button type="button" 
                        id="modalDoneBtn"
                        onclick="toggleCurrentTaskDone()"
                        class="btn btn-outline btn-sm text-xs gap-1.5 h-7">
                    <i data-lucide="check-circle" class="w-3.5 h-3.5"></i>
                    <span id="modalDoneBtnText">Mark Done</span>
                </button>

                <div class="h-4 w-px bg-border mx-0.5"></div>

                <!-- Close Button -->
                <button type="button" 
                        onclick="closeTaskPanel()" 
                        class="btn btn-ghost p-1.5 h-8 w-8 text-muted-foreground hover:text-foreground rounded-md">
                    <i data-lucide="x" class="w-4 h-4"></i>
                </button>
            </div>
        </div>

        <!-- Modal Body (Scrollable) -->
        <div class="flex-1 overflow-y-auto p-6 space-y-6" id="panelBody">
            <div class="flex flex-col items-center justify-center py-20 text-muted-foreground">
                <i data-lucide="loader-2" class="w-6 h-6 animate-spin mb-2"></i>
                <span class="text-xs">Loading task details...</span>
            </div>
        </div>

        <!-- Modal Footer -->
        <div class="px-6 py-3 border-t border-border bg-muted/20 flex items-center justify-between shrink-0">
            <div class="flex items-center gap-2">
                <button type="button" 
                        id="modalArchiveBtn"
                        class="btn btn-ghost btn-sm text-destructive hover:bg-destructive/10 text-xs gap-1.5 h-8">
                    <i data-lucide="archive" class="w-3.5 h-3.5"></i>
                    <span id="modalArchiveBtnText">Archive Task</span>
                </button>
                <span class="text-[11px] text-muted-foreground font-medium hidden sm:inline" id="modalSaveIndicator">
                    Changes saved automatically
                </span>
            </div>
            <button type="button" 
                    onclick="closeTaskPanel()" 
                    class="btn btn-outline btn-sm text-xs h-8 px-4">
                Close
            </button>
        </div>
    </div>
</div>

<script>
// ── Shared Project & Task JS ──
let currentTask = null;
let editingLabelId = null;
const currentUserId = {{ Auth::id() ?? 'null' }};

function formatRelativeTime(dateStr) {
    if (!dateStr) return 'Just now';
    const date = new Date(dateStr);
    if (isNaN(date.getTime())) return 'Just now';
    const now = new Date();
    const diffSec = Math.floor((now - date) / 1000);
    if (diffSec < 45) return 'Just now';
    const diffMin = Math.floor(diffSec / 60);
    if (diffMin < 60) return `${diffMin}m ago`;
    const diffHr = Math.floor(diffMin / 60);
    if (diffHr < 24) return `${diffHr}h ago`;
    const diffDays = Math.floor(diffHr / 24);
    if (diffDays < 7) return `${diffDays}d ago`;
    return date.toLocaleDateString(undefined, { month: 'short', day: 'numeric' });
}

function escapeHtml(str) {
    if (!str) return '';
    return str.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;").replace(/'/g, "&#039;");
}

async function saveProjectTitle(el) {
    const title = el.innerText.trim();
    const id = el.dataset.id;
    if (!title) return;
    await fetch(`/projects/${id}`, {
        method: 'PUT', headers, body: JSON.stringify({ title })
    });
    document.title = title;
    window.showToast('Project title updated', 'success');
}

async function toggleStar(projectId, btn) {
    const res = await fetch(`/projects/${projectId}/star`, { method: 'POST', headers });
    const data = await res.json();
    const icon = btn.querySelector('[data-lucide="star"]');
    if (data.is_starred) {
        icon.classList.add('text-amber-500', 'fill-amber-500');
        icon.classList.remove('text-muted-foreground');
        window.showToast('Project added to stars ⭐');
    } else {
        icon.classList.remove('text-amber-500', 'fill-amber-500');
        icon.classList.add('text-muted-foreground');
        window.showToast('Project removed from stars');
    }
}

// ── OPEN TASK CARD POPUP MODAL ──
async function openTaskPanel(taskId) {
    editingLabelId = null;

    const overlay = document.getElementById('taskModalOverlay');
    if (overlay) {
        overlay.classList.add('open');
    }
    
    document.getElementById('panelBody').innerHTML = `
        <div class="flex flex-col items-center justify-center py-20 text-muted-foreground">
            <i data-lucide="loader-2" class="w-6 h-6 animate-spin mb-2"></i>
            <span class="text-xs">Loading task details...</span>
        </div>`;
    window.lucide?.render();

    try {
        const res = await fetch(`/tasks/${taskId}`, { headers });
        const task = await res.json();
        currentTask = task;
        renderTaskPanel(task);
    } catch {
        document.getElementById('panelBody').innerHTML = `
            <div class="p-6 text-center text-xs text-destructive">
                Failed to load task details.
            </div>`;
    }
}

function renderTaskPanel(task) {
    currentTask = task;
    
    // Status Dot Color
    const dot = document.getElementById('modalStatusDot');
    if (dot) {
        dot.className = `w-2.5 h-2.5 rounded-full ${task.is_done ? 'bg-emerald-500' : 'bg-primary'}`;
    }

    // Breadcrumb
    const bc = document.getElementById('modalBreadcrumb');
    if (bc) {
        bc.textContent = `${task.project?.title ?? 'Project'} / ${task.list?.title ?? 'Column'}`;
    }

    // Mark Done / In Progress Button
    const doneBtn = document.getElementById('modalDoneBtn');
    const doneBtnText = document.getElementById('modalDoneBtnText');
    if (doneBtn && doneBtnText) {
        if (task.is_done) {
            doneBtn.className = 'btn btn-secondary btn-sm text-xs gap-1.5 h-7 text-emerald-600 dark:text-emerald-400';
            doneBtnText.textContent = 'Completed ✓';
        } else {
            doneBtn.className = 'btn btn-outline btn-sm text-xs gap-1.5 h-7';
            doneBtnText.textContent = 'Mark Done';
        }
    }

    // Archive Button
    const archiveBtn = document.getElementById('modalArchiveBtn');
    const archiveBtnText = document.getElementById('modalArchiveBtnText');
    if (archiveBtn && archiveBtnText) {
        if (task.is_archive) {
            archiveBtn.className = 'btn btn-ghost btn-sm text-foreground hover:bg-muted text-xs gap-1.5 h-8';
            archiveBtnText.textContent = 'Unarchive Task';
            archiveBtn.onclick = () => updateTaskField('is_archive', 0);
        } else {
            archiveBtn.className = 'btn btn-ghost btn-sm text-destructive hover:bg-destructive/10 text-xs gap-1.5 h-8';
            archiveBtnText.textContent = 'Archive Task';
            archiveBtn.onclick = () => {
                if (confirm('Are you sure you want to archive this task?')) {
                    updateTaskField('is_archive', 1);
                }
            };
        }
    }

    // Date formatted for date input: YYYY-MM-DD
    let dateInputValue = '';
    if (task.due_date) {
        const d = new Date(task.due_date);
        if (!isNaN(d.getTime())) {
            dateInputValue = d.toISOString().split('T')[0];
        }
    }

    // Potential Parent Tasks Options
    const potentialParents = task.potential_parents || [];
    let parentOptionsHtml = `<option value="">None (Top-level Task)</option>`;
    potentialParents.forEach(p => {
        const isSelected = task.parent_id == p.id ? 'selected' : '';
        parentOptionsHtml += `<option value="${p.id}" ${isSelected}>${escapeHtml(p.title)}</option>`;
    });

    // Column / List Options
    const projectLists = task.project_lists || [];
    let listOptionsHtml = '';
    projectLists.forEach(l => {
        const isSelected = task.list_id == l.id ? 'selected' : '';
        listOptionsHtml += `<option value="${l.id}" ${isSelected}>${escapeHtml(l.title)}</option>`;
    });

    // Subtasks (Children)
    const children = task.children || [];
    const childrenCount = children.length;
    const completedChildren = children.filter(c => c.is_done).length;

    let subtasksHtml = children.map(c => `
        <div class="flex items-center justify-between p-2.5 rounded-lg border border-border/70 bg-card hover:bg-muted/30 transition-colors gap-3 group/subtask">
            <div class="flex items-center gap-2.5 min-w-0 flex-1">
                <input type="checkbox" 
                       ${c.is_done ? 'checked' : ''} 
                       onchange="toggleSubtaskDone(${c.id}, this)"
                       class="shadcn-checkbox">
                <span class="text-xs font-medium truncate cursor-pointer hover:underline hover:text-primary ${c.is_done ? 'line-through text-muted-foreground' : 'text-foreground'}"
                      onclick="openTaskPanel(${c.id})">
                    ${escapeHtml(c.title)}
                </span>
            </div>
            <div class="flex items-center gap-2 shrink-0">
                <span class="badge badge-secondary text-[10px] px-1.5 py-0">
                    ${escapeHtml(c.list?.title ?? 'Task')}
                </span>
                <button type="button" 
                        onclick="unlinkSubtask(${c.id})" 
                        title="Detach subtask"
                        class="text-muted-foreground hover:text-destructive p-1 rounded opacity-0 group-hover/subtask:opacity-100 transition-opacity">
                    <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                </button>
            </div>
        </div>
    `).join('');

    // Checklists
    const checklistItems = task.checklists || [];
    const totalChecklists = checklistItems.length;
    const completedChecklists = checklistItems.filter(c => c.is_done).length;
    const checklistPercent = totalChecklists > 0 ? Math.round((completedChecklists / totalChecklists) * 100) : 0;

    const checklistsHtml = checklistItems.map(c => `
        <div class="flex items-center justify-between p-2 rounded-lg hover:bg-muted/40 transition-colors border border-transparent hover:border-border/60 gap-2.5 group/chk">
            <label class="flex items-center gap-2.5 min-w-0 flex-1 cursor-pointer select-none">
                <input type="checkbox" 
                       ${c.is_done ? 'checked' : ''} 
                       onchange="toggleChecklistItem(${c.id}, this)"
                       class="shadcn-checkbox">
                <span class="text-xs ${c.is_done ? 'line-through text-muted-foreground' : 'font-medium text-foreground'} truncate">
                    ${escapeHtml(c.title)}
                </span>
            </label>
            <button type="button" 
                    onclick="deleteChecklistItem(${c.id})" 
                    title="Delete item"
                    class="text-muted-foreground hover:text-destructive p-1 rounded opacity-0 group-hover/chk:opacity-100 transition-opacity shrink-0">
                <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
            </button>
        </div>
    `).join('');

    // Comments
    const commentsHtml = (task.comments || []).map(c => {
        const isOwner = currentUserId && (c.user_id === currentUserId);
        const initial = (c.user?.name ? c.user.name.charAt(0).toUpperCase() : 'U');
        const timeStr = formatRelativeTime(c.created_at);
        return `
            <div class="p-3.5 rounded-xl border border-border/80 bg-card space-y-2 group/comment hover:border-border transition-colors">
                <div class="flex items-center justify-between text-xs">
                    <div class="flex items-center gap-2.5 min-w-0">
                        <div class="w-6 h-6 rounded-full bg-primary/10 border border-primary/20 text-primary font-semibold text-[10px] flex items-center justify-center shrink-0">
                            ${initial}
                        </div>
                        <div class="flex items-center gap-1.5 min-w-0 truncate">
                            <span class="font-semibold text-foreground text-xs truncate">${escapeHtml(c.user?.name ?? 'User')}</span>
                            <span class="text-[10px] text-muted-foreground shrink-0">•</span>
                            <span class="text-[11px] text-muted-foreground shrink-0">${timeStr}</span>
                        </div>
                    </div>
                    ${isOwner ? `
                    <button type="button" 
                            onclick="deleteComment(${c.id})" 
                            title="Delete comment"
                            class="text-muted-foreground hover:text-destructive p-1 rounded opacity-0 group-hover/comment:opacity-100 transition-opacity shrink-0">
                        <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                    </button>` : ''}
                </div>
                <p class="text-xs text-foreground/90 leading-relaxed pl-8.5 whitespace-pre-line">${escapeHtml(c.details ?? '')}</p>
            </div>
        `;
    }).join('');

    // Assignees
    const assignees = task.assignees || [];
    const assignableUsers = task.assignable_users || [];
    const assignedUserIds = new Set(assignees.map(a => a.user_id));
    const assignableOptions = assignableUsers.filter(u => !assignedUserIds.has(u.id));

    const assigneesHtml = assignees.map(a => {
        const canRemove = task.can_manage
            || a.assigned_by === currentUserId
            || a.user_id === currentUserId;
        const initial = (a.user?.name ? a.user.name.charAt(0).toUpperCase() : 'U');
        const assignedBy = a.assigned_by
            ? `Assigned by ${escapeHtml(a.assigner?.name ?? 'Unknown')}`
            : 'Assignee';

        return `
            <div class="flex items-center justify-between gap-3 p-2.5 rounded-lg border border-border/70 bg-card">
                <div class="flex items-center gap-2.5 min-w-0">
                    <div class="w-7 h-7 rounded-full bg-primary/10 border border-primary/20 text-primary font-semibold text-[10px] flex items-center justify-center shrink-0">
                        ${initial}
                    </div>
                    <div class="min-w-0">
                        <div class="text-xs font-medium text-foreground truncate">
                            ${escapeHtml(a.user?.name ?? 'User')}${a.user_id === currentUserId ? ' (you)' : ''}
                        </div>
                        <div class="text-[10px] text-muted-foreground truncate">${assignedBy}</div>
                    </div>
                </div>
                ${canRemove ? `
                <button type="button"
                        onclick="unassignUser(${a.id})"
                        title="Remove assignee"
                        class="text-muted-foreground hover:text-destructive p-1 rounded shrink-0">
                    <i data-lucide="x" class="w-3.5 h-3.5"></i>
                </button>` : ''}
            </div>
        `;
    }).join('');

    const assignSelectHtml = assignableOptions.length
        ? `
        <select id="assigneeSelect" class="form-select text-xs h-9 px-3 py-1.5 bg-card border border-border rounded-lg flex-1 focus:ring-1 focus:ring-ring">
            <option value="">Select project member...</option>
            ${assignableOptions.map(u => `<option value="${u.id}">${escapeHtml(u.name)}</option>`).join('')}
        </select>
        <button type="button"
                onclick="const v = document.getElementById('assigneeSelect').value; if (v) assignUser(v);"
                class="btn btn-outline btn-sm h-9 px-3 text-xs shrink-0 gap-1.5 font-medium rounded-lg">
            <i data-lucide="user-plus" class="w-3.5 h-3.5"></i>
            <span>Assign</span>
        </button>`
        : `<p class="text-xs text-muted-foreground/60 italic">All available project members are already assigned.</p>`;

    // Labels
    const labels = task.labels || [];

    const labelsHtml = labels.map(l => {
        const color = l.color || '#6366f1';

        return `
            <div class="flex items-center justify-between gap-2 p-2 rounded-lg border border-border/70 bg-card">
                <div class="flex items-center gap-2 min-w-0">
                    <span class="w-2.5 h-2.5 rounded-full shrink-0" style="background-color: ${escapeHtml(color)}"></span>
                    <span class="text-xs font-medium text-foreground truncate">${escapeHtml(l.name)}</span>
                </div>
                <div class="flex items-center gap-1 shrink-0">
                    <button type="button"
                            onclick="startEditLabel(${l.id})"
                            title="Edit label"
                            class="text-muted-foreground hover:text-foreground p-1 rounded">
                        <i data-lucide="pencil" class="w-3.5 h-3.5"></i>
                    </button>
                    <button type="button"
                            onclick="deleteLabel(${l.id})"
                            title="Delete label"
                            class="text-muted-foreground hover:text-destructive p-1 rounded">
                        <i data-lucide="x" class="w-3.5 h-3.5"></i>
                    </button>
                </div>
            </div>
        `;
    }).join('');

    // Attachments
    const attachments = task.attachments || [];
    const attachmentsHtml = attachments.map(a => {
        const canDelete = task.can_manage || a.user_id === currentUserId;
        const url = a.url || ('/storage/' + a.path);

        return `
            <div class="flex items-center justify-between gap-3 p-2.5 rounded-lg border border-border/70 bg-card">
                <div class="flex items-center gap-2.5 min-w-0">
                    <div class="w-8 h-8 rounded-lg bg-secondary border border-border flex items-center justify-center shrink-0">
                        <i data-lucide="paperclip" class="w-3.5 h-3.5 text-muted-foreground"></i>
                    </div>
                    <div class="min-w-0">
                        <a href="${url}" target="_blank" rel="noopener"
                           class="text-xs font-medium text-foreground truncate hover:underline block">
                            ${escapeHtml(a.name ?? 'Attachment')}
                        </a>
                        <div class="text-[10px] text-muted-foreground truncate">
                            ${formatFileSize(a.size)} · ${escapeHtml(a.user?.name ?? 'User')} · ${formatRelativeTime(a.created_at)}
                        </div>
                    </div>
                </div>
                <div class="flex items-center gap-1 shrink-0">
                    <a href="${url}" download
                       class="btn btn-ghost p-1.5 h-7 w-7 text-muted-foreground hover:text-foreground"
                       title="Download">
                        <i data-lucide="download" class="w-3.5 h-3.5"></i>
                    </a>
                    ${canDelete ? `
                    <button type="button"
                            onclick="deleteAttachment(${a.id})"
                            title="Delete attachment"
                            class="btn btn-ghost p-1.5 h-7 w-7 text-muted-foreground hover:text-destructive">
                        <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                    </button>` : ''}
                </div>
            </div>
        `;
    }).join('');

    document.getElementById('panelBody').innerHTML = `
        <div class="space-y-6">
            
            <!-- 1. Editable Title -->
            <div class="space-y-1.5">
                <label class="text-[11px] font-semibold uppercase tracking-wider text-muted-foreground">Task Title</label>
                <input type="text" 
                       id="editTaskTitle"
                       value="${escapeHtml(task.title)}"
                       onblur="updateTaskField('title', this.value.trim())"
                       onkeydown="if(event.key==='Enter'){event.preventDefault();this.blur();}"
                       placeholder="Task title..."
                       class="form-input text-base font-bold text-foreground h-11 px-3.5 py-2.5 bg-card border border-border rounded-lg focus:bg-background focus:ring-1 focus:ring-ring">
            </div>

            <!-- 2. Attributes Grid (2x2: Status/Column & Priority on top, Due Date & Parent Task below) -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 p-4 rounded-xl border border-border bg-muted/20">
                
                <!-- Status / Column -->
                <div class="space-y-1.5">
                    <label class="text-[11px] font-semibold uppercase tracking-wider text-muted-foreground">Status / Column</label>
                    <select onchange="updateTaskField('list_id', parseInt(this.value))" 
                            class="form-select text-xs h-9.5 px-3 py-1.5 bg-card border border-border rounded-lg text-foreground focus:ring-1 focus:ring-ring cursor-pointer">
                        ${listOptionsHtml}
                    </select>
                </div>

                <!-- Priority -->
                <div class="space-y-1.5">
                    <label class="text-[11px] font-semibold uppercase tracking-wider text-muted-foreground">Priority</label>
                    <select onchange="updateTaskField('priority', this.value)" 
                            class="form-select text-xs h-9.5 px-3 py-1.5 bg-card border border-border rounded-lg text-foreground capitalize focus:ring-1 focus:ring-ring cursor-pointer">
                        <option value="low" ${task.priority === 'low' ? 'selected' : ''}>Low</option>
                        <option value="medium" ${task.priority === 'medium' || !task.priority ? 'selected' : ''}>Medium (Normal)</option>
                        <option value="high" ${task.priority === 'high' ? 'selected' : ''}>High</option>
                        <option value="critical" ${task.priority === 'critical' || task.is_critical ? 'selected' : ''}>Critical</option>
                    </select>
                </div>

                <!-- Due Date -->
                <div class="space-y-1.5">
                    <label class="text-[11px] font-semibold uppercase tracking-wider text-muted-foreground">Due Date</label>
                    <input type="date" 
                           value="${dateInputValue}"
                           onchange="updateTaskField('due_date', this.value ? this.value : null)"
                           class="form-input text-xs h-9.5 px-3 py-1.5 bg-card border border-border rounded-lg text-foreground focus:ring-1 focus:ring-ring">
                </div>

                <!-- Parent Task -->
                <div class="space-y-1.5">
                    <label class="text-[11px] font-semibold uppercase tracking-wider text-muted-foreground">Parent Task</label>
                    <select onchange="updateTaskField('parent_id', this.value ? parseInt(this.value) : null)" 
                            class="form-select text-xs h-9.5 px-3 py-1.5 bg-card border border-border rounded-lg text-foreground truncate focus:ring-1 focus:ring-ring cursor-pointer">
                        ${parentOptionsHtml}
                    </select>
                </div>
            </div>

            <!-- Parent Task Badge (if child) -->
            ${task.parent ? `
            <div class="flex items-center gap-2 p-2.5 rounded-lg border border-primary/20 bg-primary/5 text-xs">
                <i data-lucide="corner-down-right" class="w-4 h-4 text-primary shrink-0"></i>
                <span class="text-muted-foreground">Subtask of:</span>
                <span class="font-semibold text-foreground hover:underline cursor-pointer" onclick="openTaskPanel(${task.parent.id})">
                    ${escapeHtml(task.parent.title)}
                </span>
            </div>` : ''}

            <!-- Assignees -->
            <div class="space-y-3 pt-3 border-t border-border/60">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <i data-lucide="user-check" class="w-4 h-4 text-muted-foreground"></i>
                        <span class="text-xs font-semibold text-foreground">Assignees (${assignees.length})</span>
                    </div>
                </div>

                <div class="space-y-1.5">
                    ${assigneesHtml || '<p class="text-xs text-muted-foreground/60 italic p-3 rounded-lg border border-dashed border-border text-center">No one is assigned yet.</p>'}
                </div>

                <div class="flex items-center gap-2 pt-1">
                    ${assignSelectHtml}
                </div>
            </div>

            <!-- Labels -->
            <div class="space-y-3 pt-3 border-t border-border/60">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <i data-lucide="tags" class="w-4 h-4 text-muted-foreground"></i>
                        <span class="text-xs font-semibold text-foreground">Labels (${labels.length})</span>
                    </div>
                </div>

                <div class="space-y-1.5">
                    ${labelsHtml || '<p class="text-xs text-muted-foreground/60 italic p-3 rounded-lg border border-dashed border-border text-center">No labels added yet.</p>'}
                </div>

                <div class="flex items-center gap-2 pt-1">
                    <input type="text"
                           id="newLabelName"
                           placeholder="Label name..."
                           onkeydown="if(event.key==='Enter') submitLabel()"
                           class="form-input text-xs h-9 px-3 py-1.5 bg-card border border-border rounded-lg flex-1 focus:ring-1 focus:ring-ring">
                    <input type="color"
                           id="newLabelColor"
                           value="#6366f1"
                           title="Label color"
                           class="h-9 w-10 rounded-lg border border-border bg-card p-1 cursor-pointer shrink-0">
                    <button type="button"
                            onclick="submitLabel()"
                            class="btn btn-outline btn-sm h-9 px-3 text-xs shrink-0 gap-1.5 font-medium rounded-lg">
                        <i data-lucide="plus" class="w-3.5 h-3.5"></i>
                        <span id="labelSubmitText">Add</span>
                    </button>
                </div>
            </div>

            <!-- 3. Editable Description -->
            <div class="space-y-1.5">
                <div class="flex items-center justify-between">
                    <label class="text-xs font-semibold text-foreground">Description</label>
                    <span class="text-[10px] text-muted-foreground">Auto-saves on blur</span>
                </div>
                <textarea id="editTaskDescription"
                          rows="4"
                          placeholder="Add more detailed requirements, instructions, or notes..."
                          onblur="updateTaskField('description', this.value.trim())"
                          class="form-textarea text-xs p-3.5 bg-card border border-border rounded-lg leading-relaxed focus:bg-background focus:ring-1 focus:ring-ring resize-y min-h-[100px] shadow-2xs">${escapeHtml(task.description ?? '')}</textarea>
            </div>

            <!-- 4. Subtasks (Parent-Child) Section -->
            <div class="space-y-3 pt-3 border-t border-border/60">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <i data-lucide="git-branch" class="w-4 h-4 text-muted-foreground"></i>
                        <span class="text-xs font-semibold text-foreground">Subtasks (${completedChildren}/${childrenCount})</span>
                    </div>
                </div>

                <!-- Subtasks List -->
                <div class="space-y-1.5">
                    ${subtasksHtml || '<p class="text-xs text-muted-foreground/60 italic p-3 rounded-lg border border-dashed border-border text-center">No subtasks added yet.</p>'}
                </div>

                <!-- Inline Quick Add Subtask -->
                <div class="flex items-center gap-2 pt-1">
                    <input type="text" 
                           id="newSubtaskTitle"
                           placeholder="Add a new subtask..."
                           onkeydown="if(event.key==='Enter') submitNewSubtask()"
                           class="form-input text-xs h-9 px-3 py-1.5 bg-card border border-border rounded-lg flex-1 focus:ring-1 focus:ring-ring">
                    <button type="button" 
                            onclick="submitNewSubtask()"
                            class="btn btn-outline btn-sm h-9 px-3 text-xs shrink-0 gap-1.5 font-medium rounded-lg">
                        <i data-lucide="plus" class="w-3.5 h-3.5"></i>
                        <span>Add Subtask</span>
                    </button>
                </div>
            </div>

            <!-- 5. Checklist -->
            <div class="space-y-3 pt-3 border-t border-border/60">
                <div class="flex items-center justify-between text-xs">
                    <span class="font-semibold text-foreground">Checklist (${completedChecklists}/${totalChecklists})</span>
                    <span class="text-[11px] text-muted-foreground font-mono">${checklistPercent}%</span>
                </div>
                ${totalChecklists > 0 ? `
                <div class="w-full bg-muted rounded-full h-1.5 overflow-hidden">
                    <div class="bg-primary h-1.5 rounded-full transition-all duration-300" style="width: ${checklistPercent}%"></div>
                </div>` : ''}
                
                <!-- Checklist Items -->
                <div class="space-y-1 rounded-xl border border-border bg-card p-2">
                    ${checklistsHtml || '<p class="text-xs text-muted-foreground/60 italic p-3 text-center">No checklist items yet.</p>'}
                </div>

                <!-- Add Checklist Item -->
                <div class="flex items-center gap-2 pt-1">
                    <input type="text" 
                           id="newChecklistTitle"
                           placeholder="Add a checklist item..."
                           onkeydown="if(event.key==='Enter') submitNewChecklist()"
                           class="form-input text-xs h-9 px-3 py-1.5 bg-card border border-border rounded-lg flex-1 focus:ring-1 focus:ring-ring">
                    <button type="button" 
                            onclick="submitNewChecklist()"
                            class="btn btn-outline btn-sm h-9 px-3 text-xs shrink-0 gap-1.5 font-medium rounded-lg">
                        <i data-lucide="plus" class="w-3.5 h-3.5"></i>
                        <span>Add Item</span>
                    </button>
                </div>
            </div>

            <!-- Attachments -->
            <div class="space-y-3 pt-3 border-t border-border/60">
                <div class="flex items-center justify-between gap-3">
                    <div class="flex items-center gap-2">
                        <i data-lucide="paperclip" class="w-4 h-4 text-muted-foreground"></i>
                        <span class="text-xs font-semibold text-foreground">Attachments (${attachments.length})</span>
                    </div>
                    <button type="button"
                            onclick="document.getElementById('attachmentInput').click()"
                            class="btn btn-outline btn-sm h-8 px-3 text-xs gap-1.5 rounded-lg">
                        <i data-lucide="upload" class="w-3.5 h-3.5"></i>
                        <span>Attach file</span>
                    </button>
                    <input type="file" id="attachmentInput" class="hidden" onchange="uploadAttachment(this)">
                </div>

                <div class="space-y-1.5">
                    ${attachmentsHtml || '<p class="text-xs text-muted-foreground/60 italic p-3 rounded-lg border border-dashed border-border text-center">No attachments yet.</p>'}
                </div>

                <p class="text-[10px] text-muted-foreground">
                    Files larger than 20 MB are compressed automatically before being stored.
                </p>
            </div>

            <!-- 6. Comments & Discussion -->
            <div class="space-y-3 pt-3 border-t border-border/60">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <i data-lucide="message-square" class="w-4 h-4 text-muted-foreground"></i>
                        <span class="text-xs font-semibold text-foreground">Discussion (${(task.comments || []).length})</span>
                    </div>
                </div>

                <!-- Comments List -->
                <div class="space-y-2.5">
                    ${commentsHtml || '<p class="text-xs text-muted-foreground/60 italic text-center p-4 rounded-xl border border-dashed border-border">No comments yet. Leave a note or update for the team!</p>'}
                </div>

                <!-- New Comment Form -->
                <div class="rounded-xl border border-border bg-card p-3 shadow-2xs space-y-2.5">
                    <textarea id="commentInput" 
                              rows="2" 
                              placeholder="Write a comment or update for the team... (Press Enter or click Send)"
                              onkeydown="if(event.key==='Enter' && !event.shiftKey){event.preventDefault();submitComment(${task.id});}"
                              class="form-textarea text-xs p-3 bg-muted/30 border border-border/80 rounded-lg leading-relaxed focus:bg-background focus:ring-1 focus:ring-ring resize-y min-h-[64px]"></textarea>
                    <div class="flex items-center justify-between">
                        <span class="text-[11px] text-muted-foreground hidden sm:inline">Press <kbd class="px-1.5 py-0.5 text-[10px] bg-muted border border-border rounded font-mono">Enter</kbd> to submit, <kbd class="px-1.5 py-0.5 text-[10px] bg-muted border border-border rounded font-mono">Shift+Enter</kbd> for new line</span>
                        <button type="button" 
                                id="commentSubmitBtn"
                                class="btn btn-primary btn-sm h-8 px-3.5 gap-1.5 text-xs font-medium ml-auto" 
                                onclick="submitComment(${task.id})">
                            <i data-lucide="send" class="w-3.5 h-3.5"></i>
                            <span>Send</span>
                        </button>
                    </div>
                </div>
            </div>

        </div>
    `;

    window.lucide?.render();
}

// ── UPDATE INDIVIDUAL TASK FIELD (AJAX) ──
async function updateTaskField(field, value) {
    if (!currentTask) return;
    
    const indicator = document.getElementById('modalSaveIndicator');
    if (indicator) {
        indicator.textContent = 'Saving changes...';
        indicator.classList.remove('text-muted-foreground', 'text-destructive');
        indicator.classList.add('text-primary');
    }

    const payload = {};
    payload[field] = value;
    
    // If critical priority is set, also sync is_critical boolean
    if (field === 'priority') {
        payload['is_critical'] = (value === 'critical') ? 1 : 0;
    }

    try {
        const res = await fetch(`/tasks/${currentTask.id}`, {
            method: 'PUT',
            headers,
            body: JSON.stringify(payload)
        });

        if (res.ok) {
            const updatedTask = await res.json();
            currentTask = Object.assign(currentTask, updatedTask);

            if (indicator) {
                indicator.textContent = 'All changes saved ✓';
                indicator.classList.remove('text-primary');
                indicator.classList.add('text-muted-foreground');
            }

            // If is_archive was updated, close modal and reload
            if (field === 'is_archive') {
                closeTaskPanel();
                window.showToast(value ? 'Task archived' : 'Task restored', 'success');
                setTimeout(() => location.reload(), 400);
                return;
            }

            // Update header status dot & done button
            if (field === 'is_done') {
                const dot = document.getElementById('modalStatusDot');
                if (dot) dot.className = `w-2.5 h-2.5 rounded-full ${currentTask.is_done ? 'bg-emerald-500' : 'bg-primary'}`;

                const doneBtn = document.getElementById('modalDoneBtn');
                const doneBtnText = document.getElementById('modalDoneBtnText');
                if (doneBtn && doneBtnText) {
                    if (currentTask.is_done) {
                        doneBtn.className = 'btn btn-secondary btn-sm text-xs gap-1.5 h-7 text-emerald-600 dark:text-emerald-400';
                        doneBtnText.textContent = 'Completed ✓';
                    } else {
                        doneBtn.className = 'btn btn-outline btn-sm text-xs gap-1.5 h-7';
                        doneBtnText.textContent = 'Mark Done';
                    }
                }
            }

            window.showToast('Task updated successfully', 'success');
        } else {
            if (indicator) {
                indicator.textContent = 'Failed to save';
                indicator.classList.add('text-destructive');
            }
            window.showToast('Failed to update task', 'error');
        }
    } catch {
        if (indicator) {
            indicator.textContent = 'Network error';
            indicator.classList.add('text-destructive');
        }
        window.showToast('Network error, please try again', 'error');
    }
}

// ── TOGGLE IS_DONE FOR CURRENT TASK ──
function toggleCurrentTaskDone() {
    if (!currentTask) return;
    const newDone = currentTask.is_done ? 0 : 1;
    updateTaskField('is_done', newDone);
}

// ── TOGGLE SUBTASK DONE ──
async function toggleSubtaskDone(subtaskId, checkbox) {
    const isDone = checkbox.checked ? 1 : 0;
    try {
        await fetch(`/tasks/${subtaskId}`, {
            method: 'PUT',
            headers,
            body: JSON.stringify({ is_done: isDone })
        });
        window.showToast(isDone ? 'Subtask completed' : 'Subtask marked pending', 'success');
        // Refresh modal data
        if (currentTask) openTaskPanel(currentTask.id);
    } catch {
        checkbox.checked = !isDone;
        window.showToast('Failed to update subtask', 'error');
    }
}

// ── SUBMIT NEW SUBTASK ──
async function submitNewSubtask() {
    if (!currentTask) return;
    const input = document.getElementById('newSubtaskTitle');
    const title = input?.value.trim();
    if (!title) return input?.focus();

    try {
        const res = await fetch('/tasks', {
            method: 'POST',
            headers,
            body: JSON.stringify({
                title,
                parent_id: currentTask.id,
                project_id: currentTask.project_id,
                list_id: currentTask.list_id
            })
        });

        if (res.ok) {
            input.value = '';
            window.showToast('Subtask added!', 'success');
            // Refresh modal to show new subtask
            openTaskPanel(currentTask.id);
        } else {
            window.showToast('Failed to add subtask', 'error');
        }
    } catch {
        window.showToast('Network error', 'error');
    }
}

// ── UNLINK / DETACH SUBTASK ──
async function unlinkSubtask(subtaskId) {
    if (!confirm('Remove this subtask from parent?')) return;
    try {
        await fetch(`/tasks/${subtaskId}`, {
            method: 'PUT',
            headers,
            body: JSON.stringify({ parent_id: null })
        });
        window.showToast('Subtask detached', 'success');
        if (currentTask) openTaskPanel(currentTask.id);
    } catch {
        window.showToast('Failed to detach subtask', 'error');
    }
}

// ── SUBMIT NEW CHECKLIST ITEM ──
async function submitNewChecklist() {
    if (!currentTask) return;
    const input = document.getElementById('newChecklistTitle');
    const title = input?.value.trim();
    if (!title) return input?.focus();

    try {
        const res = await fetch(`/tasks/${currentTask.id}/checklists`, {
            method: 'POST',
            headers,
            body: JSON.stringify({ title })
        });

        if (res.ok) {
            input.value = '';
            window.showToast('Checklist item added!', 'success');
            openTaskPanel(currentTask.id);
        } else {
            window.showToast('Failed to add checklist item', 'error');
        }
    } catch {
        window.showToast('Network error, please try again.', 'error');
    }
}

// ── TOGGLE CHECKLIST ITEM DONE ──
async function toggleChecklistItem(checklistId, checkbox) {
    const isDone = checkbox.checked ? 1 : 0;
    try {
        const res = await fetch(`/checklists/${checklistId}`, {
            method: 'PUT',
            headers,
            body: JSON.stringify({ is_done: isDone })
        });
        if (res.ok) {
            window.showToast(isDone ? 'Item completed ✓' : 'Item marked pending', 'success');
            // Refresh to update progress bar and ratio
            if (currentTask) openTaskPanel(currentTask.id);
        } else {
            checkbox.checked = !isDone;
            window.showToast('Failed to update item', 'error');
        }
    } catch {
        checkbox.checked = !isDone;
        window.showToast('Network error', 'error');
    }
}

// ── DELETE CHECKLIST ITEM ──
async function deleteChecklistItem(checklistId) {
    if (!confirm('Delete this checklist item?')) return;
    try {
        const res = await fetch(`/checklists/${checklistId}`, {
            method: 'DELETE',
            headers
        });
        if (res.ok) {
            window.showToast('Checklist item deleted', 'success');
            if (currentTask) openTaskPanel(currentTask.id);
        } else {
            window.showToast('Failed to delete item', 'error');
        }
    } catch {
        window.showToast('Network error', 'error');
    }
}

function closeTaskPanel() {
    document.getElementById('taskModalOverlay')?.classList.remove('open');
    currentTask = null;
}

// ── SUBMIT NEW COMMENT ──
async function submitComment(taskId) {
    const input = document.getElementById('commentInput');
    const details = input?.value.trim();
    if (!details) return input?.focus();

    const submitBtn = document.getElementById('commentSubmitBtn');
    if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i data-lucide="loader-2" class="w-3.5 h-3.5 animate-spin"></i><span>Sending...</span>';
        window.lucide?.render();
    }

    try {
        const res = await fetch(`/tasks/${taskId}/comments`, {
            method: 'POST',
            headers,
            body: JSON.stringify({ details })
        });

        if (res.ok) {
            input.value = '';
            window.showToast('Comment posted! 💬', 'success');
            openTaskPanel(taskId);
        } else {
            const err = await res.json();
            window.showToast(err.message || 'Failed to submit comment', 'error');
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i data-lucide="send" class="w-3.5 h-3.5"></i><span>Send</span>';
                window.lucide?.render();
            }
        }
    } catch {
        window.showToast('Network error, please try again.', 'error');
        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i data-lucide="send" class="w-3.5 h-3.5"></i><span>Send</span>';
            window.lucide?.render();
        }
    }
}

// ── DELETE COMMENT ──
async function deleteComment(commentId) {
    if (!confirm('Are you sure you want to delete this comment?')) return;

    try {
        const res = await fetch(`/comments/${commentId}`, {
            method: 'DELETE',
            headers
        });

        if (res.ok) {
            window.showToast('Comment deleted', 'success');
            if (currentTask) openTaskPanel(currentTask.id);
        } else {
            const err = await res.json();
            window.showToast(err.message || 'Failed to delete comment', 'error');
        }
    } catch {
        window.showToast('Network error, please try again.', 'error');
    }
}


// ── ASSIGNEES ──
async function assignUser(userId) {
    if (!currentTask) return;

    try {
        const res = await fetch(`/tasks/${currentTask.id}/assignees`, {
            method: 'POST',
            headers,
            body: JSON.stringify({ user_id: parseInt(userId) })
        });

        const data = await res.json().catch(() => ({}));

        if (res.ok) {
            window.showToast('Assignee added', 'success');
            openTaskPanel(currentTask.id);
        } else {
            window.showToast(data.message || 'Failed to add assignee', 'error');
        }
    } catch {
        window.showToast('Network error, please try again.', 'error');
    }
}

async function unassignUser(assigneeId) {
    if (!currentTask) return;

    try {
        const res = await fetch(`/assignees/${assigneeId}`, {
            method: 'DELETE',
            headers
        });

        const data = await res.json().catch(() => ({}));

        if (res.ok) {
            window.showToast('Assignee removed', 'success');
            openTaskPanel(currentTask.id);
        } else {
            window.showToast(data.message || 'Failed to remove assignee', 'error');
        }
    } catch {
        window.showToast('Network error, please try again.', 'error');
    }
}

// ── ATTACHMENTS ──
function formatFileSize(bytes) {
    const size = Number(bytes) || 0;
    if (size < 1024) return size + ' B';
    if (size < 1024 * 1024) return (size / 1024).toFixed(1) + ' KB';
    return (size / (1024 * 1024)).toFixed(2) + ' MB';
}

async function uploadAttachment(input) {
    if (!currentTask || !input.files || !input.files[0]) return;

    const file = input.files[0];
    const formData = new FormData();
    formData.append('file', file);

    window.showToast('Uploading ' + file.name + '...', '');

    try {
        const res = await fetch(`/tasks/${currentTask.id}/attachments`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': CSRF,
                'Accept': 'application/json'
            },
            body: formData
        });

        const data = await res.json().catch(() => ({}));

        if (res.ok) {
            window.showToast(
                data.compressed ? 'File uploaded and compressed.' : 'File uploaded.',
                'success'
            );
            openTaskPanel(currentTask.id);
        } else {
            window.showToast(data.message || 'Upload failed', 'error');
        }
    } catch {
        window.showToast('Upload failed, please try again.', 'error');
    } finally {
        input.value = '';
    }
}

async function deleteAttachment(attachmentId) {
    if (!confirm('Delete this attachment?')) return;

    try {
        const res = await fetch(`/attachments/${attachmentId}`, {
            method: 'DELETE',
            headers
        });

        const data = await res.json().catch(() => ({}));

        if (res.ok) {
            window.showToast('Attachment deleted', 'success');
            if (currentTask) openTaskPanel(currentTask.id);
        } else {
            window.showToast(data.message || 'Failed to delete attachment', 'error');
        }
    } catch {
        window.showToast('Network error, please try again.', 'error');
    }
}


// ── LABELS ──
function startEditLabel(labelId) {
    const label = (currentTask?.labels || []).find(l => l.id === labelId);
    if (!label) return;

    editingLabelId = labelId;

    const nameInput = document.getElementById('newLabelName');
    const colorInput = document.getElementById('newLabelColor');
    const submitText = document.getElementById('labelSubmitText');

    nameInput.value = label.name;
    colorInput.value = /^#[0-9a-f]{6}$/i.test(label.color || '') ? label.color : '#6366f1';
    submitText.textContent = 'Update';
    nameInput.focus();
}

async function submitLabel() {
    if (!currentTask) return;

    const nameInput = document.getElementById('newLabelName');
    const colorInput = document.getElementById('newLabelColor');
    const submitText = document.getElementById('labelSubmitText');

    const name = nameInput.value.trim();
    const color = colorInput.value || '#6366f1';

    if (!name) {
        window.showToast('Label name is required.', 'error');
        return;
    }

    const isEdit = !!editingLabelId;
    const url = isEdit ? `/labels/${editingLabelId}` : `/tasks/${currentTask.id}/labels`;

    try {
        const res = await fetch(url, {
            method: isEdit ? 'PUT' : 'POST',
            headers,
            body: JSON.stringify({ name, color })
        });

        const data = await res.json().catch(() => ({}));

        if (res.ok) {
            window.showToast(isEdit ? 'Label updated' : 'Label added', 'success');
            editingLabelId = null;
            submitText.textContent = 'Add';
            openTaskPanel(currentTask.id);
        } else {
            window.showToast(data.message || 'Failed to save label', 'error');
        }
    } catch {
        window.showToast('Network error, please try again.', 'error');
    }
}

async function deleteLabel(labelId) {
    if (!confirm('Delete this label?')) return;

    try {
        const res = await fetch(`/labels/${labelId}`, {
            method: 'DELETE',
            headers
        });

        const data = await res.json().catch(() => ({}));

        if (res.ok) {
            window.showToast('Label deleted', 'success');
            if (currentTask) openTaskPanel(currentTask.id);
        } else {
            window.showToast(data.message || 'Failed to delete label', 'error');
        }
    } catch {
        window.showToast('Network error, please try again.', 'error');
    }
}
</script>
