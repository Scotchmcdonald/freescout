/**
 * Alpine.js component for theme toggling.
 *
 * Handles dark/light mode switching with server persistence.
 */
export function themeToggle() {
    return {
        isDarkMode: false,

        init() {
            // Check current icon state to determine mode
            const lightIcon = document.getElementById('theme-toggle-light-icon');
            this.isDarkMode = lightIcon?.classList.contains('hidden') ?? false;
        },

        async toggle() {
            this.isDarkMode = !this.isDarkMode;

            // Toggle icon visibility
            const darkIcon = document.getElementById('theme-toggle-dark-icon');
            const lightIcon = document.getElementById('theme-toggle-light-icon');
            
            if (darkIcon && lightIcon) {
                darkIcon.classList.toggle('hidden');
                lightIcon.classList.toggle('hidden');
            }

            // Persist preference to server
            try {
                const response = await fetch(window.themeUpdateUrl || '/themes', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        dark_mode: this.isDarkMode
                    })
                });

                const data = await response.json();
                
                if (data.success) {
                    window.location.reload();
                }
            } catch (error) {
                console.error('Failed to update theme:', error);
            }
        }
    };
}

/**
 * Alpine.js component for conversation status updates.
 *
 * Handles status changes via AJAX with loading states.
 */
export function conversationStatus(conversationId, currentStatus, ajaxUrl, csrfToken) {
    return {
        status: currentStatus,
        loading: false,
        conversationId: conversationId,

        async updateStatus(newStatus) {
            if (this.status === newStatus || this.loading) {
                return;
            }

            this.loading = true;

            try {
                const response = await fetch(ajaxUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        action: 'change_status',
                        conversation_id: this.conversationId,
                        status: parseInt(newStatus)
                    })
                });

                const data = await response.json();
                
                if (data.success) {
                    this.status = parseInt(newStatus);
                    location.reload();
                } else {
                    console.error('Failed to update status:', data.message);
                    alert('Failed to update status: ' + (data.message || 'Unknown error'));
                }
            } catch (error) {
                console.error('Error updating status:', error);
                alert('Error updating status: ' + error.message);
            } finally {
                this.loading = false;
            }
        }
    };
}

/**
 * Alpine.js component for dynamic favicon color.
 *
 * Updates the favicon SVG color based on theme primary color.
 */
export function dynamicFavicon() {
    return {
        init() {
            this.updateFavicon();
        },

        updateFavicon() {
            const primaryColor = getComputedStyle(document.documentElement)
                .getPropertyValue('--theme-primary-600')
                .trim() || '#22c55e';
            
            const svgContent = `<svg fill="${primaryColor}" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path d="M20 10c0-1.361-.758-2.616-2.031-3.622-.002-.001-.004-.001-.005-.003C17.602 2.803 14.177 0 10 0S2.398 2.803 2.036 6.375c-.001.002-.003.002-.005.003C.758 7.384 0 8.639 0 10c0 3.112 3.947 5.669 9 5.97V17c0 1-1.821 1.911-1.821 1.911a.227.227 0 0 0-.109.277S7.375 20 8 20s1.124-.5 2.374-.5 2.439.432 2.439.432a.342.342 0 0 0 .329-.073l.717-.717c.078-.078.058-.173-.046-.212 0 0-1.812-.68-1.812-1.93v-1.121C16.565 15.324 20 12.903 20 10zM2 10c0-1.019.768-1.945 2.022-2.651C4.012 7.233 4 7.117 4 7c0-2.762 2.687-5 6-5s6 2.238 6 5c0 .117-.012.233-.021.349C17.232 8.055 18 8.981 18 10c0 1.864-2.551 3.424-5.999 3.869v-.668a.53.53 0 0 1 .145-.337l1.833-1.726a.534.534 0 0 0 .146-.337V9.95c0-.11-.078-.155-.172-.099l-1.779 1.047c-.096.056-.173.012-.173-.099V7.2c0-.11-.085-.172-.19-.137l-2.621.874a.297.297 0 0 0-.189.263v2.6c0 .11-.079.158-.177.107L6.802 9.843a.289.289 0 0 0-.318.048l-.342.342a.185.185 0 0 0 .009.273l2.7 2.361c.083.073.15.222.15.332v.765C5.056 13.719 2 12.04 2 10z"/></svg>`;
            
            let link = document.querySelector('link[rel="icon"]');
            
            if (!link) {
                link = document.createElement('link');
                link.rel = 'icon';
                document.head.appendChild(link);
            }
            
            link.type = 'image/svg+xml';
            link.href = `data:image/svg+xml;base64,${btoa(svgContent)}`;
        }
    };
}

/**
 * Alpine.js component for dropdown menus.
 * 
 * Usage: x-data="dropdown()"
 */
export function dropdown() {
    return {
        open: false,
        
        toggle() {
            this.open = !this.open;
        },
        
        close() {
            this.open = false;
        }
    };
}

/**
 * Alpine.js component for modal dialogs.
 * 
 * Usage: x-data="modal()" @keydown.escape.window="close()"
 */
export function modal() {
    return {
        show: false,
        
        open() {
            this.show = true;
            document.body.classList.add('overflow-hidden');
        },
        
        close() {
            this.show = false;
            document.body.classList.remove('overflow-hidden');
        },
        
        toggle() {
            this.show ? this.close() : this.open();
        }
    };
}

/**
 * Alpine.js component for confirmation dialogs.
 * 
 * Usage: x-data="confirmDialog()" 
 */
export function confirmDialog() {
    return {
        show: false,
        message: '',
        onConfirm: null,
        
        confirm(message, callback) {
            this.message = message;
            this.onConfirm = callback;
            this.show = true;
        },
        
        proceed() {
            if (this.onConfirm) {
                this.onConfirm();
            }
            this.close();
        },
        
        close() {
            this.show = false;
            this.message = '';
            this.onConfirm = null;
        }
    };
}

/**
 * Alpine.js component for AJAX form submission.
 * 
 * Usage: x-data="ajaxForm('url', 'method')"
 */
export function ajaxForm(url, method = 'POST') {
    return {
        loading: false,
        errors: {},
        success: false,
        message: '',
        
        async submit(formData) {
            this.loading = true;
            this.errors = {};
            this.success = false;
            this.message = '';
            
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            if (!csrfToken) {
                console.error('CSRF token not found. Ensure meta[name="csrf-token"] is present in the page head.');
                this.message = 'Security token missing. Please refresh the page.';
                this.loading = false;
                return null;
            }
            
            try {
                const response = await fetch(url, {
                    method: method,
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    },
                    body: formData
                });
                
                const data = await response.json();
                
                if (response.ok) {
                    this.success = true;
                    this.message = data.message || 'Success';
                    return data;
                } else {
                    this.errors = data.errors || {};
                    this.message = data.message || 'An error occurred';
                    return null;
                }
            } catch (error) {
                this.message = error.message;
                return null;
            } finally {
                this.loading = false;
            }
        },
        
        hasError(field) {
            return field in this.errors;
        },
        
        getError(field) {
            return this.errors[field]?.[0] || '';
        }
    };
}

/**
 * Alpine.js component for select all checkboxes.
 * 
 * Usage: x-data="selectAll()"
 */
export function selectAll() {
    return {
        allSelected: false,
        selected: [],
        
        toggleAll(items) {
            if (this.allSelected) {
                this.selected = [];
            } else {
                this.selected = items.map(item => item.id || item);
            }
            this.allSelected = !this.allSelected;
        },
        
        toggle(id) {
            const index = this.selected.indexOf(id);
            if (index > -1) {
                this.selected.splice(index, 1);
            } else {
                this.selected.push(id);
            }
        },
        
        isSelected(id) {
            return this.selected.includes(id);
        },
        
        get count() {
            return this.selected.length;
        }
    };
}

/**
 * Alpine.js component for search/filter input.
 * 
 * Usage: x-data="searchFilter()"
 */
export function searchFilter() {
    return {
        query: '',
        debounceTimer: null,
        
        search(callback, delay = 300) {
            clearTimeout(this.debounceTimer);
            this.debounceTimer = setTimeout(() => {
                callback(this.query);
            }, delay);
        },
        
        clear(callback) {
            this.query = '';
            if (callback) callback('');
        }
    };
}

/**
 * Alpine.js component for tabs.
 * 
 * Usage: x-data="tabs('default-tab')"
 */
export function tabs(defaultTab = '') {
    return {
        activeTab: defaultTab,
        
        isActive(tab) {
            return this.activeTab === tab;
        },
        
        select(tab) {
            this.activeTab = tab;
        }
    };
}

/**
 * Alpine.js component for subscription table column toggling.
 * 
 * Usage: x-data="subscriptionTable()"
 */
export function subscriptionTable() {
    return {
        toggleColumn(columnType, checked) {
            const selector = `.subscription-${columnType}:not(:disabled)`;
            document.querySelectorAll(selector).forEach(cb => {
                cb.checked = checked;
            });
        }
    };
}

/**
 * Alpine.js component for failed jobs management.
 * 
 * Usage: x-data="failedJobs()"
 */
export function failedJobs() {
    return {
        loading: false,
        
        async retryJob(uuid) {
            if (this.loading) return;
            this.loading = true;
            
            try {
                const response = await fetch(`/system/failed-jobs/${uuid}/retry`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                        'Accept': 'application/json'
                    }
                });
                const data = await response.json();
                if (data.success) {
                    location.reload();
                } else {
                    alert(data.message || 'Failed to retry job');
                }
            } catch (error) {
                alert('Error: ' + error.message);
            } finally {
                this.loading = false;
            }
        },
        
        async deleteJob(uuid) {
            if (this.loading) return;
            if (!confirm('Are you sure you want to delete this job?')) return;
            
            this.loading = true;
            
            try {
                const response = await fetch(`/system/failed-jobs/${uuid}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                        'Accept': 'application/json'
                    }
                });
                const data = await response.json();
                if (data.success) {
                    location.reload();
                } else {
                    alert(data.message || 'Failed to delete job');
                }
            } catch (error) {
                alert('Error: ' + error.message);
            } finally {
                this.loading = false;
            }
        }
    };
}

/**
 * Alpine.js component for reply form submission.
 * 
 * Usage: x-data="replyForm('routeUrl', 'csrfToken')"
 */
export function replyForm(routeUrl, csrfToken) {
    return {
        loading: false,
        
        async submit(form) {
            if (this.loading) return;
            this.loading = true;
            
            const formData = new FormData(form);
            
            try {
                const response = await fetch(routeUrl, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    }
                });
                const data = await response.json();
                if (data.success) {
                    location.reload();
                } else {
                    alert('Error: ' + (data.message || 'Failed to send reply'));
                }
            } catch (error) {
                alert('Error: ' + error.message);
            } finally {
                this.loading = false;
            }
        }
    };
}

/**
 * Alpine.js component for print functionality.
 * 
 * Usage: x-data="printPage()"
 */
export function printPage() {
    return {
        print() {
            window.print();
        }
    };
}

/**
 * Alpine.js component for theme selector with scroll position restore.
 * 
 * Usage: x-data="themeSelector('{{ route('themes.update') }}', '{{ csrf_token() }}')"
 */
export function themeSelector(formAction, csrfToken) {
    return {
        init() {
            // Restore scroll position if it exists
            const savedScrollPos = sessionStorage.getItem('themeScrollPos');
            if (savedScrollPos) {
                window.scrollTo(0, parseInt(savedScrollPos));
                sessionStorage.removeItem('themeScrollPos');
            }
        },
        
        async selectTheme(themeName) {
            // Save scroll position
            sessionStorage.setItem('themeScrollPos', window.scrollY.toString());
            
            const formData = new FormData();
            formData.append('theme', themeName);
            formData.append('_token', csrfToken);
            
            try {
                const response = await fetch(formAction, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    }
                });
                
                if (response.ok) {
                    window.location.reload();
                } else {
                    console.error('Theme update failed');
                }
            } catch (error) {
                console.error('Error:', error);
            }
        }
    };
}

/**
 * Alpine.js component for admin system tools (cache clear, migrations).
 * 
 * Usage: x-data="adminActions('{{ route('settings.cache.clear') }}', '{{ route('settings.migrate') }}', '{{ csrf_token() }}')"
 */
export function adminActions(clearCacheUrl, migrateUrl, csrfToken) {
    return {
        loading: false,
        message: '',
        messageType: '', // 'success' or 'error'
        
        async clearCache() {
            if (!confirm('Are you sure you want to clear all caches?')) return;
            
            this.loading = true;
            this.message = '';
            
            try {
                const response = await fetch(clearCacheUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                    }
                });
                const data = await response.json();
                this.showMessage(data.success ? 'success' : 'error', data.message);
            } catch (error) {
                this.showMessage('error', 'Failed to clear cache: ' + error.message);
            } finally {
                this.loading = false;
            }
        },
        
        async runMigrations() {
            if (!confirm('Are you sure you want to run database migrations?')) return;
            
            this.loading = true;
            this.message = '';
            
            try {
                const response = await fetch(migrateUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                    }
                });
                const data = await response.json();
                this.showMessage(data.success ? 'success' : 'error', data.message);
            } catch (error) {
                this.showMessage('error', 'Failed to run migrations: ' + error.message);
            } finally {
                this.loading = false;
            }
        },
        
        showMessage(type, msg) {
            this.messageType = type;
            this.message = msg;
            
            setTimeout(() => {
                this.message = '';
                this.messageType = '';
            }, 5000);
        }
    };
}

/**
 * Alpine.js component for customer edit form with dynamic email fields.
 * 
 * Usage: x-data="customerForm(initialEmailCount)"
 */
export function customerForm(initialEmailCount = 1) {
    return {
        emailIndex: initialEmailCount,
        
        addEmail() {
            const container = document.getElementById('emails-container');
            const newRow = document.createElement('div');
            newRow.className = 'email-row flex gap-2 mb-2';
            
            // Create input element
            const input = document.createElement('input');
            input.type = 'email';
            input.name = `emails[${this.emailIndex}][email]`;
            input.placeholder = 'email@example.com';
            input.className = 'flex-1 border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500';
            
            // Create select element
            const select = document.createElement('select');
            select.name = `emails[${this.emailIndex}][type]`;
            select.className = 'border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500';
            select.innerHTML = '<option value="work">Work</option><option value="home">Home</option><option value="other">Other</option>';
            
            // Create remove button
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'px-3 py-2 text-red-600 hover:text-red-800';
            button.innerHTML = '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>';
            button.addEventListener('click', (e) => this.removeEmail(e));
            
            // Append all elements
            newRow.appendChild(input);
            newRow.appendChild(select);
            newRow.appendChild(button);
            
            container.insertBefore(newRow, container.querySelector('button[type="button"]'));
            this.emailIndex++;
        },
        
        removeEmail(event) {
            event.target.closest('.email-row').remove();
        }
    };
}

/**
 * Alpine.js component for mailbox settings (SMTP/IMAP testing).
 * 
 * Usage: x-data="mailboxSettings(mailboxId, smtpTestUrl, imapTestUrl, fetchEmailsUrl, csrfToken)"
 */
export function mailboxSettings(mailboxId, smtpTestUrl, imapTestUrl, fetchEmailsUrl, csrfToken) {
    return {
        showSmtpTestForm: false,
        testEmail: '',
        smtpResult: '',
        smtpResultType: '',
        imapResult: '',
        imapResultType: '',
        fetchResult: '',
        fetchResultType: '',
        loading: false,
        
        async sendTestEmail() {
            if (!this.testEmail) {
                alert('Please enter a test email address');
                return;
            }
            
            this.loading = true;
            this.smtpResult = 'Sending test email...';
            this.smtpResultType = '';
            
            try {
                const response = await fetch(smtpTestUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify({
                        mailbox_id: mailboxId,
                        test_email: this.testEmail
                    })
                });
                
                const data = await response.json();
                this.smtpResult = data.message;
                this.smtpResultType = data.success ? 'success' : 'error';
                
                if (data.success) {
                    this.showSmtpTestForm = false;
                }
            } catch (error) {
                this.smtpResult = 'Error: ' + error.message;
                this.smtpResultType = 'error';
            } finally {
                this.loading = false;
            }
        },
        
        async testImap() {
            this.loading = true;
            this.imapResult = 'Testing IMAP connection...';
            this.imapResultType = '';
            
            try {
                const response = await fetch(imapTestUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify({
                        mailbox_id: mailboxId
                    })
                });
                
                const data = await response.json();
                this.imapResult = data.message;
                this.imapResultType = data.success ? 'success' : 'error';
            } catch (error) {
                this.imapResult = 'Error: ' + error.message;
                this.imapResultType = 'error';
            } finally {
                this.loading = false;
            }
        },
        
        async fetchEmails() {
            this.loading = true;
            this.fetchResult = 'Fetching emails from mailbox...';
            this.fetchResultType = '';
            
            try {
                const response = await fetch(fetchEmailsUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    }
                });
                
                const data = await response.json();
                this.fetchResult = data.success ? ('Success! ' + data.message) : ('Error: ' + data.message);
                this.fetchResultType = data.success ? 'success' : 'error';
            } catch (error) {
                this.fetchResult = 'Error: ' + error.message;
                this.fetchResultType = 'error';
            } finally {
                this.loading = false;
            }
        },
        
        cancelSmtpTest() {
            this.showSmtpTestForm = false;
            this.testEmail = '';
        }
    };
}

/**
 * Alpine.js component for customer merge form.
 * 
 * Usage: x-data="customerMerge('{{ route('customers.ajax') }}', '{{ csrf_token() }}')"
 */
export function customerMerge(searchUrl, csrfToken) {
    return {
        selectedCustomer: null,
        
        init() {
            // Initialize Select2 for customer search (if jQuery/Select2 available)
            if (window.$ && window.$.fn.select2) {
                const self = this;
                $('#target_id').select2({
                    ajax: {
                        url: searchUrl,
                        dataType: 'json',
                        delay: 250,
                        data: function(params) {
                            return {
                                action: 'search',
                                q: params.term,
                                _token: csrfToken
                            };
                        },
                        processResults: function(data) {
                            return {
                                results: data.results
                            };
                        },
                        cache: true
                    },
                    minimumInputLength: 2,
                    placeholder: 'Search for a customer by name or email...'
                }).on('select2:select', function(e) {
                    self.selectedCustomer = {
                        name: e.params.data.text.split('(')[0].trim(),
                        email: e.params.data.text.match(/\(([^)]+)\)/)?.[1] || ''
                    };
                });
            }
        }
    };
}

// Export all components
export default {
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
    customerMerge
};
