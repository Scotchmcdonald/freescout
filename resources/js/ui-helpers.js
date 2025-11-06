/**
 * Modern UI Utilities
 * Replaces jQuery-based UI interactions with vanilla JS and Alpine.js
 */

import Swal from 'sweetalert2';

export class UIHelpers {
    /**
     * Show toast notification (replaces floating alerts)
     */
    static showToast(message, type = 'success', duration = 3000) {
        const Toast = Swal.mixin({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: duration,
            timerProgressBar: true,
            didOpen: (toast) => {
                toast.addEventListener('mouseenter', Swal.stopTimer);
                toast.addEventListener('mouseleave', Swal.resumeTimer);
            }
        });

        Toast.fire({
            icon: type,
            title: message
        });
    }

    /**
     * Show confirmation dialog (replaces window.confirm)
     */
    static async confirm(title, message, confirmText = 'Yes', cancelText = 'Cancel') {
        const result = await Swal.fire({
            title: title,
            text: message,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: confirmText,
            cancelButtonText: cancelText
        });

        return result.isConfirmed;
    }

    /**
     * Show loading indicator
     */
    static showLoading(message = 'Processing...') {
        Swal.fire({
            title: message,
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });
    }

    /**
     * Hide loading indicator
     */
    static hideLoading() {
        Swal.close();
    }

    /**
     * Initialize collapsible sidebar menus
     */
    static initSidebarMenus() {
        document.querySelectorAll('.sidebar-menu-toggle').forEach(toggle => {
            toggle.addEventListener('click', function(e) {
                e.stopPropagation();
                
                const menu = this.parentElement.querySelector('.sidebar-menu');
                if (menu) {
                    menu.classList.toggle('active');
                }
                this.classList.toggle('active');
            });
        });

        // Close menus when clicking outside
        document.addEventListener('click', function(e) {
            if (!e.target.closest('.sidebar-menu-toggle')) {
                document.querySelectorAll('.sidebar-menu, .sidebar-menu-toggle').forEach(el => {
                    el.classList.remove('active');
                });
            }
        });
    }

    /**
     * Initialize accordion headings
     */
    static initAccordions() {
        document.querySelectorAll('[data-accordion-toggle]').forEach(toggle => {
            toggle.addEventListener('click', function() {
                const target = document.querySelector(this.dataset.accordionToggle);
                if (target) {
                    target.classList.toggle('hidden');
                    this.querySelector('.accordion-icon')?.classList.toggle('rotate-180');
                }
            });
        });
    }

    /**
     * Auto-focus first input in forms
     */
    static autoFocusFirstInput(container = document) {
        const firstInput = container.querySelector('input:not([type="hidden"]):not([disabled]), textarea:not([disabled])');
        if (firstInput) {
            setTimeout(() => firstInput.focus(), 100);
        }
    }

    /**
     * Copy text to clipboard
     */
    static async copyToClipboard(text) {
        try {
            await navigator.clipboard.writeText(text);
            this.showToast('Copied to clipboard', 'success');
            return true;
        } catch (err) {
            this.showToast('Failed to copy', 'error');
            return false;
        }
    }

    /**
     * Format date/time
     */
    static formatDateTime(date, format = 'short') {
        const d = new Date(date);
        const options = format === 'short' 
            ? { month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' }
            : { year: 'numeric', month: 'long', day: 'numeric', hour: '2-digit', minute: '2-digit' };
        
        return new Intl.DateTimeFormat('en-US', options).format(d);
    }

    /**
     * Debounce function calls
     */
    static debounce(func, wait = 300) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }

    /**
     * Throttle function calls
     */
    static throttle(func, limit = 300) {
        let inThrottle;
        return function(...args) {
            if (!inThrottle) {
                func.apply(this, args);
                inThrottle = true;
                setTimeout(() => inThrottle = false, limit);
            }
        };
    }

    /**
     * Initialize keyboard shortcuts
     */
    static initKeyboardShortcuts() {
        document.addEventListener('keydown', (e) => {
            // Ctrl/Cmd + K for search
            if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
                e.preventDefault();
                document.querySelector('#search-input')?.focus();
            }

            // Ctrl/Cmd + / for help
            if ((e.ctrlKey || e.metaKey) && e.key === '/') {
                e.preventDefault();
                // Show help modal
            }

            // Escape to close modals/dropdowns
            if (e.key === 'Escape') {
                document.querySelectorAll('.modal, .dropdown-menu').forEach(el => {
                    el.classList.add('hidden');
                });
            }
        });
    }

    /**
     * Make tables responsive
     */
    static initResponsiveTables() {
        document.querySelectorAll('table').forEach(table => {
            if (!table.parentElement.classList.contains('table-responsive')) {
                const wrapper = document.createElement('div');
                wrapper.className = 'overflow-x-auto';
                table.parentNode.insertBefore(wrapper, table);
                wrapper.appendChild(table);
            }
        });
    }

    /**
     * Initialize drag and drop sorting
     */
    static initSortable(selector, onSort) {
        const elements = document.querySelectorAll(selector);
        elements.forEach(element => {
            let draggedItem = null;

            element.addEventListener('dragstart', function(e) {
                draggedItem = this;
                e.dataTransfer.effectAllowed = 'move';
                e.dataTransfer.setData('text/html', this.innerHTML);
                this.classList.add('opacity-50');
            });

            element.addEventListener('dragend', function() {
                this.classList.remove('opacity-50');
                if (onSort) onSort();
            });

            element.addEventListener('dragover', function(e) {
                e.preventDefault();
                e.dataTransfer.dropEffect = 'move';
            });

            element.addEventListener('drop', function(e) {
                e.preventDefault();
                if (this !== draggedItem) {
                    draggedItem.innerHTML = this.innerHTML;
                    this.innerHTML = e.dataTransfer.getData('text/html');
                }
            });
        });
    }
}

// Initialize on DOM ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        UIHelpers.initSidebarMenus();
        UIHelpers.initAccordions();
        UIHelpers.initKeyboardShortcuts();
        UIHelpers.initResponsiveTables();
    });
} else {
    UIHelpers.initSidebarMenus();
    UIHelpers.initAccordions();
    UIHelpers.initKeyboardShortcuts();
    UIHelpers.initResponsiveTables();
}

export default UIHelpers;
