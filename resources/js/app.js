import './bootstrap';
import Alpine from 'alpinejs';

window.Alpine = Alpine;
Alpine.start();

// Global helpers
window.fsReplyChanged = false;

// Lazy load heavy modules only when needed
const lazyLoadEditor = () => import('./editor').then(m => m.default || m);
const lazyLoadUploader = () => import('./uploader').then(m => m.default || m);
const lazyLoadNotifications = () => import('./notifications').then(m => m.default || m);
const lazyLoadConversation = () => import('./conversation').then(m => m.default || m);

// Load UI helpers (lightweight, needed everywhere)
import('./ui-helpers').then(module => {
    window.UIHelpers = module.UIHelpers || module.default;
});

// Initialize keyboard shortcuts and UI enhancements
document.addEventListener('DOMContentLoaded', () => {
    // Auto-focus search inputs
    const searchInput = document.querySelector('#search-input');
    if (searchInput && !document.querySelector('[autofocus]')) {
        setTimeout(() => searchInput.focus(), 100);
    }

    // Initialize tooltips (if needed)
    initTooltips();

    // Lazy load modules based on page content
    const pageModules = [];
    
    // Load editor if reply form exists
    if (document.querySelector('[data-editor]') || document.querySelector('.reply-form')) {
        pageModules.push(lazyLoadEditor());
    }
    
    // Load uploader if file upload area exists
    if (document.querySelector('[data-uploader]') || document.querySelector('.dropzone')) {
        pageModules.push(lazyLoadUploader());
    }
    
    // Load notifications if user is authenticated
    if (document.querySelector('meta[name="user-id"]')) {
        pageModules.push(lazyLoadNotifications());
    }
    
    // Load conversation module if on conversation page
    if (document.querySelector('[data-conversation-id]')) {
        pageModules.push(lazyLoadConversation());
    }
    
    // Load all needed modules
    if (pageModules.length > 0) {
        Promise.all(pageModules).catch(err => {
            console.error('Failed to load modules:', err);
        });
    }

    // Handle form submissions with loading states
    document.querySelectorAll('form[data-loading]').forEach(form => {
        form.addEventListener('submit', function() {
            const btn = this.querySelector('[type="submit"]');
            if (btn) {
                btn.disabled = true;
                btn.dataset.originalText = btn.textContent;
                btn.textContent = 'Processing...';
            }
        });
    });

    // Confirm delete actions
    document.querySelectorAll('[data-confirm]').forEach(element => {
        element.addEventListener('click', async function(e) {
            e.preventDefault();
            const message = this.dataset.confirm || 'Are you sure?';
            const confirmed = await window.UIHelpers?.confirm('Confirm', message);
            if (confirmed) {
                if (this.tagName === 'FORM') {
                    this.submit();
                } else if (this.form) {
                    this.form.submit();
                } else {
                    window.location.href = this.href;
                }
            }
        });
    });
});

function initTooltips() {
    document.querySelectorAll('[data-tooltip]').forEach(element => {
        element.addEventListener('mouseenter', function() {
            const text = this.dataset.tooltip;
            const tooltip = document.createElement('div');
            tooltip.className = 'fixed bg-gray-900 text-white text-xs px-2 py-1 rounded shadow-lg z-50';
            tooltip.textContent = text;
            tooltip.id = 'tooltip-' + Math.random().toString(36).substr(2, 9);
            
            document.body.appendChild(tooltip);
            
            const rect = this.getBoundingClientRect();
            tooltip.style.left = rect.left + (rect.width / 2) - (tooltip.offsetWidth / 2) + 'px';
            tooltip.style.top = rect.bottom + 5 + 'px';
            
            this.dataset.tooltipId = tooltip.id;
        });
        
        element.addEventListener('mouseleave', function() {
            const tooltipId = this.dataset.tooltipId;
            if (tooltipId) {
                document.getElementById(tooltipId)?.remove();
            }
        });
    });
}
