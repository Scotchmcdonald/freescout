<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-neutral-800 leading-tight">
            Mailbox Settings: {{ $mailbox->name }}
        </h2>
    </x-slot>

    <div class="py-12" x-data="mailboxSettings({{ $mailbox->id }}, '{{ route('settings.test-smtp') }}', '{{ route('settings.test-imap') }}', '{{ route('mailboxes.fetch-emails', $mailbox) }}', '{{ csrf_token() }}')">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <!-- Connection Settings -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-neutral-900">
                    <h3 class="text-lg font-semibold mb-4">Connection Settings</h3>
                    <div class="flex space-x-4">
                        <a href="{{ route('mailboxes.connection.incoming', $mailbox) }}"
                           class="px-4 py-2 bg-neutral-600 text-white rounded hover:bg-neutral-700">
                            Incoming (IMAP)
                        </a>
                        <a href="{{ route('mailboxes.connection.outgoing', $mailbox) }}"
                           class="px-4 py-2 bg-neutral-600 text-white rounded hover:bg-neutral-700">
                            Outgoing (SMTP)
                        </a>
                    </div>
                </div>
            </div>

            <!-- SMTP Settings -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-neutral-900">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold">Test Outgoing Mail (SMTP)</h3>
                        @if(!empty($mailbox->out_server))
                            <button @click="showSmtpTestForm = true"
                                    class="px-4 py-2 bg-primary-600 text-white rounded hover:bg-primary-700">
                                Test Connection
                            </button>
                        @else
                            <p class="text-sm text-neutral-500">Configure outgoing mail to test.</p>
                        @endif
                    </div>

                    <!-- SMTP Result -->
                    <template x-if="smtpResultType === 'error'">
                        <x-troubleshooting-card 
                            title="Connection Failed" 
                            type="error"
                            alpine-body="smtpResult"
                            action-text="Check Documentation"
                            action-url="https://freescout.net/module/email-migration/"
                        />
                    </template>
                    <template x-if="smtpResultType === 'success'">
                        <div class="mt-4">
                            <x-status-badge status="success" alpine-text="smtpResult" />
                        </div>
                    </template>
                    <template x-if="smtpResultType === 'info'">
                        <div class="mt-4 p-4 rounded bg-neutral-100 text-neutral-600" x-text="smtpResult"></div>
                    </template>

                    <!-- Test Email Form -->
                    <div x-show="showSmtpTestForm" x-cloak class="mt-4">
                        <div class="flex items-center space-x-2">
                            <input type="email"
                                   x-model="testEmail"
                                   placeholder="Enter test email address"
                                   class="flex-1 px-3 py-2 border border-neutral-300 rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500">
                            <button @click="sendTestEmail()"
                                    :disabled="loading"
                                    class="px-4 py-2 bg-success-600 text-white rounded hover:bg-success-700 disabled:opacity-50">
                                <span x-show="!loading">Send Test Email</span>
                                <span x-show="loading" class="inline-block animate-spin">⟳</span>
                            </button>
                            <button @click="cancelSmtpTest()"
                                    class="px-4 py-2 bg-neutral-300 text-neutral-700 dark:bg-neutral-600 dark:text-neutral-200 rounded hover:bg-neutral-400 dark:hover:bg-neutral-500">
                                Cancel
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- IMAP Settings -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-neutral-900">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold">Test Incoming Mail (IMAP)</h3>
                        @if(!empty($mailbox->in_server))
                            <button @click="testImap()"
                                    :disabled="loading"
                                    class="px-4 py-2 bg-primary-600 text-white rounded hover:bg-primary-700 disabled:opacity-50">
                                <span x-show="!loading">Test Connection</span>
                                <span x-show="loading" class="inline-block animate-spin">⟳</span>
                            </button>
                        @else
                            <p class="text-sm text-neutral-500">Configure incoming mail to test.</p>
                        @endif
                    </div>

                    <!-- IMAP Result -->
                    <template x-if="imapResultType === 'error'">
                        <x-troubleshooting-card 
                            title="Connection Failed" 
                            type="error"
                            alpine-body="imapResult"
                            action-text="Check Documentation"
                            action-url="https://freescout.net/module/email-migration/"
                        />
                    </template>
                    <template x-if="imapResultType === 'success'">
                        <div class="mt-4">
                            <x-status-badge status="success" alpine-text="imapResult" />
                        </div>
                    </template>
                    <template x-if="imapResultType === 'info'">
                        <div class="mt-4 p-4 rounded bg-neutral-100 text-neutral-600" x-text="imapResult"></div>
                    </template>
                </div>
            </div>
            
            <!-- Quick Actions -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-neutral-900">
                    <h3 class="text-lg font-semibold mb-4">Quick Actions</h3>
                    
                    <div class="flex space-x-4">
                        <button @click="fetchEmails()" 
                                :disabled="loading"
                                class="px-4 py-2 bg-success-600 text-white rounded hover:bg-success-700 disabled:opacity-50">
                            <span x-show="!loading">Fetch Emails Now</span>
                            <span x-show="loading" class="inline-block animate-spin">⟳</span>
                        </button>
                        
                        <a href="{{ route('mailboxes.view', $mailbox) }}" 
                           class="px-4 py-2 bg-neutral-600 text-white rounded hover:bg-neutral-700">
                            View Conversations
                        </a>
                    </div>
                    
                    <!-- Fetch Result -->
                    <div x-show="fetchResult" 
                         x-cloak
                         class="mt-4 p-4 rounded"
                         :class="fetchResultType === 'success' ? 'bg-success-100 text-success-800' : (fetchResultType === 'error' ? 'bg-danger-100 text-danger-800' : 'text-neutral-600')"
                         x-text="fetchResult">
                    </div>
                </div>
            </div>

            @action('mailbox.settings.menu', $mailbox)
        </div>
    </div>
</x-app-layout>
