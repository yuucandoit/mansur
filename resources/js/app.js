import './bootstrap';
import { createIcons, icons } from 'lucide';

window.lucide = {
    createIcons,
    icons,
    render: () => createIcons({ icons })
};

// Global theme handler
window.setTheme = function(mode) {
    if (mode === 'dark') {
        document.documentElement.classList.add('dark');
        document.body.classList.add('dark');
    } else {
        document.documentElement.classList.remove('dark');
        document.body.classList.remove('dark');
    }
    localStorage.setItem('theme', mode);
    
    const lightBtn = document.getElementById('themeLight');
    const darkBtn = document.getElementById('themeDark');
    if (lightBtn && darkBtn) {
        if (mode === 'dark') {
            lightBtn.classList.remove('bg-background', 'shadow-xs', 'text-foreground');
            lightBtn.classList.add('text-muted-foreground');
            darkBtn.classList.add('bg-background', 'shadow-xs', 'text-foreground');
            darkBtn.classList.remove('text-muted-foreground');
        } else {
            lightBtn.classList.add('bg-background', 'shadow-xs', 'text-foreground');
            lightBtn.classList.remove('text-muted-foreground');
            darkBtn.classList.remove('bg-background', 'shadow-xs', 'text-foreground');
            darkBtn.classList.add('text-muted-foreground');
        }
    }
};

// Global toast notification
window.showToast = function(msg, type = '') {
    const c = document.getElementById('toast-container');
    if (!c) return;
    const t = document.createElement('div');
    t.className = 'toast-card';
    
    let iconName = 'info';
    let iconClass = 'text-muted-foreground';
    if (type === 'success') {
        iconName = 'check-circle-2';
        iconClass = 'text-emerald-500';
    } else if (type === 'error') {
        iconName = 'alert-circle';
        iconClass = 'text-destructive';
    }
    
    t.innerHTML = `
        <i data-lucide="${iconName}" class="w-4 h-4 shrink-0 ${iconClass}"></i>
        <span class="flex-1 text-sm font-medium leading-tight">${msg}</span>
        <button type="button" onclick="this.parentElement.remove()" class="text-muted-foreground hover:text-foreground p-0.5 rounded transition-colors">
            <i data-lucide="x" class="w-3.5 h-3.5"></i>
        </button>
    `;
    c.appendChild(t);
    window.lucide.render();
    setTimeout(() => {
        t.style.opacity = '0';
        t.style.transform = 'translateY(8px)';
        t.style.transition = 'all 150ms ease';
        setTimeout(() => t.remove(), 150);
    }, 3500);
};

// Modal helpers
window.openModal = function(id) {
    const el = document.getElementById(id);
    if (el) {
        el.classList.add('open');
        window.lucide.render();
    }
};

window.closeModal = function(id) {
    const el = document.getElementById(id);
    if (el) el.classList.remove('open');
};

window.closeOnOverlay = function(e, id) {
    if (e.target === e.currentTarget) window.closeModal(id);
};

// Initialize on DOM load
document.addEventListener('DOMContentLoaded', () => {
    const savedTheme = localStorage.getItem('theme') || (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
    window.setTheme(savedTheme);
    window.lucide.render();
});
