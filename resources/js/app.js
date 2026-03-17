import './bootstrap';
import Alpine from 'alpinejs';
import { 
    themeToggle, 
    conversationStatus, 
    dynamicFavicon,
    dropdown,
    modal,
    confirmDialog,
    ajaxForm,
    selectAll,
    searchFilter,
    tabs,
    subscriptionTable,
    failedJobs,
    replyForm,
    printPage,
    themeSelector,
    adminActions,
    customerForm,
    mailboxSettings,
    customerMerge,
    systemTools,
    advancedMailboxSettings,
    mergeConversationSearch,
    licenseAssignment,
    subscriptionCreation,
    assignmentDeletion
} from './components';
import { guidedTour } from './guided-tour';
import rbacMatrix from './rbac-matrix';

// Register Alpine.js components
Alpine.data('themeToggle', themeToggle);
Alpine.data('conversationStatus', conversationStatus);
Alpine.data('dynamicFavicon', dynamicFavicon);
Alpine.data('dropdown', dropdown);
Alpine.data('modal', modal);
Alpine.data('confirmDialog', confirmDialog);
Alpine.data('ajaxForm', ajaxForm);
Alpine.data('selectAll', selectAll);
Alpine.data('searchFilter', searchFilter);
Alpine.data('tabs', tabs);
Alpine.data('subscriptionTable', subscriptionTable);
Alpine.data('failedJobs', failedJobs);
Alpine.data('replyForm', replyForm);
Alpine.data('printPage', printPage);
Alpine.data('themeSelector', themeSelector);
Alpine.data('adminActions', adminActions);
Alpine.data('customerForm', customerForm);
Alpine.data('mailboxSettings', mailboxSettings);
Alpine.data('customerMerge', customerMerge);
Alpine.data('systemTools', systemTools);
Alpine.data('advancedMailboxSettings', advancedMailboxSettings);
Alpine.data('mergeConversationSearch', mergeConversationSearch);
Alpine.data('licenseAssignment', licenseAssignment);
Alpine.data('subscriptionCreation', subscriptionCreation);
Alpine.data('assignmentDeletion', assignmentDeletion);
Alpine.data('guidedTour', guidedTour);
Alpine.data('rbacMatrix', rbacMatrix);

window.Alpine = Alpine;

// Expose components to window for x-data="component()" usage
window.themeToggle = themeToggle;
window.conversationStatus = conversationStatus;
window.dynamicFavicon = dynamicFavicon;
window.dropdown = dropdown;
window.modal = modal;
window.confirmDialog = confirmDialog;
window.ajaxForm = ajaxForm;
window.selectAll = selectAll;
window.searchFilter = searchFilter;
window.tabs = tabs;
window.subscriptionTable = subscriptionTable;
window.failedJobs = failedJobs;
window.replyForm = replyForm;
window.printPage = printPage;
window.themeSelector = themeSelector;
window.adminActions = adminActions;
window.customerForm = customerForm;
window.mailboxSettings = mailboxSettings;
window.customerMerge = customerMerge;
window.systemTools = systemTools;
window.advancedMailboxSettings = advancedMailboxSettings;
window.mergeConversationSearch = mergeConversationSearch;
window.licenseAssignment = licenseAssignment;
window.subscriptionCreation = subscriptionCreation;
window.assignmentDeletion = assignmentDeletion;
window.guidedTour = guidedTour;
window.rbacMatrix = rbacMatrix;

document.addEventListener('DOMContentLoaded', () => {
    Alpine.start();
});

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
    console.log('UIHelpers loaded');
});

// Initialize keyboard shortcuts and UI enhancements
document.addEventListener('DOMContentLoaded', () => {
    console.log('DOM fully loaded and parsed');
    // Auto-focus search inputs
    const searchInput = document.querySelector('#search-input');
    if (searchInput && !document.querySelector('[autofocus]')) {
        setTimeout(() => searchInput.focus(), 100);
        console.log('Search input focused');
    }

    // Initialize tooltips (if needed)
    initTooltips();

    // Lazy load modules based on page content
    const pageModules = [];
    console.log('Page modules initialization:', { 
        editor: document.querySelector('[data-editor]'), 
        replyForm: document.querySelector('.reply-form'),
        uploader: document.querySelector('[data-uploader]'),
        dropzone: document.querySelector('.dropzone'),
        userIdMeta: document.querySelector('meta[name="user-id"]'),
        conversationId: document.querySelector('[data-conversation-id]')
    });
    
    // Load editor if reply form exists
    if (document.querySelector('[data-editor]') || document.querySelector('.reply-form')) {
        pageModules.push(lazyLoadEditor());
        console.log('Editor module queued for loading');
    }
    
    // Load uploader if file upload area exists
    if (document.querySelector('[data-uploader]') || document.querySelector('.dropzone')) {
        pageModules.push(lazyLoadUploader());
        console.log('Uploader module queued for loading');
    }
    
    // Load notifications if user is authenticated
    if (document.querySelector('meta[name="user-id"]')) {
        pageModules.push(lazyLoadNotifications());
        console.log('Notifications module queued for loading');
    }
    
    // Load conversation module if on conversation page
    if (document.querySelector('[data-conversation-id]')) {
        pageModules.push(lazyLoadConversation());
        console.log('Conversation module queued for loading');
    }
    
    // Load all needed modules
    if (pageModules.length > 0) {
        Promise.all(pageModules).then(() => {
            console.log('All page modules loaded successfully');
        }).catch(err => {
            console.error('Failed to load modules:', err);
        });
    } else {
        console.log('No page modules to load');
    }

    // Handle form submissions with loading states
    document.querySelectorAll('form[data-loading]').forEach(form => {
        form.addEventListener('submit', function() {
            const btn = this.querySelector('[type="submit"]');
            if (btn) {
                btn.disabled = true;
                btn.dataset.originalText = btn.textContent;
                btn.textContent = 'Processing...';
                console.log('Form submitted, button text changed to Processing...');
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
                    console.log('Form submitted via confirm dialog');
                } else if (this.form) {
                    this.form.submit();
                    console.log('Form submitted via confirm dialog (alternative)');
                } else {
                    window.location.href = this.href;
                    console.log('Navigating to:', this.href);
                }
            } else {
                console.log('Action cancelled by the user');
            }
        });
    });
});

function initTooltips() {
    document.querySelectorAll('[data-tooltip]').forEach(element => {
        element.addEventListener('mouseenter', function() {
            const text = this.dataset.tooltip;
            const tooltip = document.createElement('div');
            tooltip.className = 'fixed bg-neutral-900 text-white text-xs px-2 py-1 rounded shadow-lg z-50';
            tooltip.textContent = text;
            tooltip.id = 'tooltip-' + Math.random().toString(36).substr(2, 9);
            
            document.body.appendChild(tooltip);
            
            const rect = this.getBoundingClientRect();
            tooltip.style.left = rect.left + (rect.width / 2) - (tooltip.offsetWidth / 2) + 'px';
            tooltip.style.top = rect.bottom + 5 + 'px';
            
            this.dataset.tooltipId = tooltip.id;
            console.log('Tooltip shown:', { text, id: tooltip.id });
        });
        
        element.addEventListener('mouseleave', function() {
            const tooltipId = this.dataset.tooltipId;
            if (tooltipId) {
                document.getElementById(tooltipId)?.remove();
                console.log('Tooltip hidden:', { id: tooltipId });
            }
        });
    });
}
