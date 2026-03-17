<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-neutral-800 leading-tight">
            {{ __('System Logs') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Tab Navigation -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="border-b border-neutral-200">
                    <nav class="flex -mb-px">
                        <a href="{{ route('system.logs', ['type' => 'application']) }}" 
                           class="px-6 py-3 border-b-2 font-medium text-sm {{ $currentType === 'application' ? 'border-primary-500 text-primary-600' : 'border-transparent text-neutral-500 hover:text-neutral-700 hover:border-neutral-300' }}">
                            Application Logs
                        </a>
                        <a href="{{ route('system.logs', ['type' => 'email']) }}" 
                           class="px-6 py-3 border-b-2 font-medium text-sm {{ $currentType === 'email' ? 'border-primary-500 text-primary-600' : 'border-transparent text-neutral-500 hover:text-neutral-700 hover:border-neutral-300' }}">
                            Email Logs
                        </a>
                        <a href="{{ route('system.logs', ['type' => 'activity']) }}" 
                           class="px-6 py-3 border-b-2 font-medium text-sm {{ $currentType === 'activity' ? 'border-primary-500 text-primary-600' : 'border-transparent text-neutral-500 hover:text-neutral-700 hover:border-neutral-300' }}">
                            Activity Logs
                        </a>
                        <a href="{{ route('system.logs', ['type' => 'login']) }}" 
                           class="px-6 py-3 border-b-2 font-medium text-sm {{ $currentType === 'login' ? 'border-primary-500 text-primary-600' : 'border-transparent text-neutral-500 hover:text-neutral-700 hover:border-neutral-300' }}">
                            Login Logs
                        </a>
                    </nav>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-neutral-900">
                    @if($currentType === 'application')
                        <!-- Application Logs -->
                        <div class="mb-4 flex justify-between items-center">
                            <h3 class="text-lg font-semibold">{{ __('Application Logs') }}</h3>
                        </div>
                        
                        @if(empty($logs))
                            <div class="text-center py-12 text-neutral-500">
                                <svg class="mx-auto h-12 w-12 text-neutral-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                <p class="mt-2">No log entries found</p>
                            </div>
                        @else
                            <div x-data="logViewer(@js($logs))">
                                <!-- Controls -->
                                <div class="mb-4 flex flex-wrap gap-4 items-center bg-neutral-50 p-4 rounded-lg border border-neutral-200">
                                    <div class="flex items-center space-x-4">
                                        <label class="inline-flex items-center">
                                            <input type="checkbox" x-model="filters.info" class="rounded border-neutral-300 text-primary-600 shadow-sm focus:ring-primary-500">
                                            <span class="ml-2 text-sm text-neutral-600">Info</span>
                                        </label>
                                        <label class="inline-flex items-center">
                                            <input type="checkbox" x-model="filters.warning" class="rounded border-neutral-300 text-warning-600 shadow-sm focus:ring-warning-500">
                                            <span class="ml-2 text-sm text-neutral-600">Warning</span>
                                        </label>
                                        <label class="inline-flex items-center">
                                            <input type="checkbox" x-model="filters.error" class="rounded border-neutral-300 text-danger-600 shadow-sm focus:ring-danger-500">
                                            <span class="ml-2 text-sm text-neutral-600">Error</span>
                                        </label>
                                        <label class="inline-flex items-center">
                                            <input type="checkbox" x-model="filters.debug" class="rounded border-neutral-300 text-neutral-600 shadow-sm focus:ring-neutral-500">
                                            <span class="ml-2 text-sm text-neutral-600">Debug</span>
                                        </label>
                                    </div>
                                    
                                    <div class="border-l border-neutral-300 h-6 mx-2"></div>

                                    <label class="inline-flex items-center">
                                        <input type="checkbox" x-model="compressStackTraces" class="rounded border-neutral-300 text-primary-600 shadow-sm focus:ring-primary-500">
                                        <span class="ml-2 text-sm text-neutral-600">Compress Stack Traces</span>
                                    </label>
                                </div>

                                <!-- Logs List -->
                                <div class="bg-neutral-900 rounded-lg overflow-hidden shadow-inner">
                                    <div class="overflow-y-auto max-h-[800px] p-4 space-y-2 font-mono text-xs">
                                        <template x-for="(log, index) in filteredLogs" :key="index">
                                            <div class="border-b border-neutral-800 pb-2 last:border-0">
                                                <div class="flex items-start">
                                                    <span class="text-neutral-500 w-36 flex-shrink-0" x-text="log.timestamp"></span>
                                                    <span class="w-20 flex-shrink-0 font-bold" 
                                                          :class="{
                                                              'text-primary-400': log.level === 'INFO',
                                                              'text-warning-400': log.level === 'WARNING',
                                                              'text-danger-400': log.level === 'ERROR' || log.level === 'CRITICAL' || log.level === 'ALERT' || log.level === 'EMERGENCY',
                                                              'text-neutral-400': log.level === 'DEBUG'
                                                          }" 
                                                          x-text="log.level"></span>
                                                    <span class="text-neutral-300 break-all whitespace-pre-wrap" x-text="log.message"></span>
                                                </div>
                                                
                                                <template x-if="log.context">
                                                    <div class="mt-1 ml-36 pl-4 border-l-2 border-neutral-700 text-neutral-400 whitespace-pre-wrap">
                                                        <template x-if="!compressStackTraces">
                                                            <div x-text="log.context"></div>
                                                        </template>
                                                        <template x-if="compressStackTraces">
                                                            <div x-text="getCompressedContext(log.context)"></div>
                                                        </template>
                                                    </div>
                                                </template>
                                            </div>
                                        </template>
                                        
                                        <div x-show="filteredLogs.length === 0" class="text-center py-8 text-neutral-500">
                                            No logs match the current filters.
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <script>
                                document.addEventListener('alpine:init', () => {
                                    Alpine.data('logViewer', (logs) => ({
                                        logs: logs,
                                        filters: {
                                            info: true,
                                            warning: true,
                                            error: true,
                                            debug: true
                                        },
                                        compressStackTraces: true,

                                        get filteredLogs() {
                                            return this.logs.filter(log => {
                                                const level = log.level.toLowerCase();
                                                if (level === 'info' && !this.filters.info) return false;
                                                if (level === 'warning' && !this.filters.warning) return false;
                                                if ((level === 'error' || level === 'critical' || level === 'alert' || level === 'emergency') && !this.filters.error) return false;
                                                if (level === 'debug' && !this.filters.debug) return false;
                                                return true;
                                            });
                                        },

                                        getCompressedContext(context) {
                                            const lines = context.trim().split('\n');
                                            if (lines.length <= 6) return context;
                                            
                                            const first3 = lines.slice(0, 3).join('\n');
                                            const last3 = lines.slice(-3).join('\n');
                                            return `${first3}\n\n... ${lines.length - 6} lines omitted ...\n\n${last3}`;
                                        }
                                    }));
                                });
                            </script>
                        @endif

                    @elseif($currentType === 'email')
                        <!-- Email Logs -->
                        <h3 class="text-lg font-semibold mb-4">{{ __('Email Send Logs') }}</h3>
                        
                        @if($sendLogs->isEmpty())
                            <div class="text-center py-12 text-neutral-500">
                                <p>No email logs found</p>
                            </div>
                        @else
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead style="background-color: var(--theme-bg-hover)">
                                        <tr>
                                            <th class="px-6 py-3 text-left text-xs font-medium uppercase" style="color: var(--theme-text-muted)">ID</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium uppercase" style="color: var(--theme-text-muted)">Type</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium uppercase" style="color: var(--theme-text-muted)">Recipient</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium uppercase" style="color: var(--theme-text-muted)">Status</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium uppercase" style="color: var(--theme-text-muted)">Date</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y" style="background-color: var(--theme-bg-card); border-color: var(--theme-border)">
                                        @foreach($sendLogs as $log)
                                            <tr class="hover:bg-opacity-50" style="border-color: var(--theme-border)">
                                                <td class="px-6 py-4 whitespace-nowrap text-sm" style="color: var(--theme-text-main)">{{ $log->id }}</td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                                    @if($log->user_id)
                                                        <span class="px-2 py-1 text-xs rounded" style="background-color: var(--theme-status-info-bg); color: var(--theme-status-info-text)">User</span>
                                                    @else
                                                        <span class="px-2 py-1 text-xs rounded" style="background-color: var(--theme-status-success-bg); color: var(--theme-status-success-text)">Customer</span>
                                                    @endif
                                                </td>
                                                <td class="px-6 py-4 text-sm" style="color: var(--theme-text-muted)">{{ $log->email }}</td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                                    @if($log->status == 1)
                                                        <span class="px-2 py-1 text-xs rounded" style="background-color: var(--theme-status-success-bg); color: var(--theme-status-success-text)">Sent</span>
                                                    @else
                                                        <span class="px-2 py-1 text-xs rounded" style="background-color: var(--theme-status-error-bg); color: var(--theme-status-error-text)">Failed</span>
                                                    @endif
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm" style="color: var(--theme-text-muted)">
                                                    {{ $log->created_at->format('Y-m-d H:i:s') }}
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            
                            <div class="mt-6">
                                {{ $sendLogs->appends(['type' => 'email'])->links() }}
                            </div>
                        @endif

                    @elseif($currentType === 'activity')
                        <!-- Activity Logs -->
                        <h3 class="text-lg font-semibold mb-4">{{ __('Activity Logs') }}</h3>
                        
                        @if($activityLogs->isEmpty())
                            <div class="text-center py-12 text-neutral-500">
                                <p>No activity logs found</p>
                            </div>
                        @else
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead style="background-color: var(--theme-bg-hover)">
                                        <tr>
                                            <th class="px-6 py-3 text-left text-xs font-medium uppercase" style="color: var(--theme-text-muted)">Event</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium uppercase" style="color: var(--theme-text-muted)">User</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium uppercase" style="color: var(--theme-text-muted)">Subject</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium uppercase" style="color: var(--theme-text-muted)">Date</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y" style="background-color: var(--theme-bg-card); border-color: var(--theme-border)">
                                        @foreach($activityLogs as $log)
                                            <tr class="hover:bg-opacity-50" style="border-color: var(--theme-border)">
                                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                    <span class="px-2 py-1 text-xs rounded" style="background-color: var(--theme-bg-hover); color: var(--theme-text-main)">{{ $log->description }}</span>
                                                </td>
                                                <td class="px-6 py-4 text-sm" style="color: var(--theme-text-muted)">
                                                    {{ $log->causer?->getFullName() ?? 'System' }}
                                                </td>
                                                <td class="px-6 py-4 text-sm" style="color: var(--theme-text-muted)">
                                                    @if($log->subject)
                                                        {{ class_basename($log->subject_type) }} #{{ $log->subject_id }}
                                                    @else
                                                        <span style="color: var(--theme-text-muted)">N/A</span>
                                                    @endif
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm" style="color: var(--theme-text-muted)">
                                                    {{ $log->created_at->format('Y-m-d H:i:s') }}
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            
                            <div class="mt-6">
                                {{ $activityLogs->appends(['type' => 'activity'])->links() }}
                            </div>
                        @endif

                    @elseif($currentType === 'login')
                        <!-- Login Logs -->
                        <h3 class="text-lg font-semibold mb-4">{{ __('Login Activity') }}</h3>
                        <p class="text-sm text-neutral-600 mb-4">Note: Failed login attempts are rate-limited to 5 attempts per email+IP combination before lockout.</p>
                        
                        @if($loginLogs->isEmpty())
                            <div class="text-center py-12 text-neutral-500">
                                <p>No login activity found</p>
                            </div>
                        @else
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead style="background-color: var(--theme-bg-hover)">
                                        <tr>
                                            <th class="px-6 py-3 text-left text-xs font-medium uppercase" style="color: var(--theme-text-muted)">Event</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium uppercase" style="color: var(--theme-text-muted)">Email</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium uppercase" style="color: var(--theme-text-muted)">IP Address</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium uppercase" style="color: var(--theme-text-muted)">User</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium uppercase" style="color: var(--theme-text-muted)">Date</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y" style="background-color: var(--theme-bg-card); border-color: var(--theme-border)">
                                        @foreach($loginLogs as $log)
                                            <tr class="hover:bg-opacity-50" style="border-color: var(--theme-border)">
                                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                    @if($log->description === 'login')
                                                        <span class="px-2 py-1 text-xs rounded" style="background-color: var(--theme-status-success-bg); color: var(--theme-status-success-text)">✓ Login Success</span>
                                                    @elseif($log->description === 'login_failed')
                                                        <span class="px-2 py-1 text-xs rounded" style="background-color: var(--theme-status-error-bg); color: var(--theme-status-error-text)">✗ Login Failed</span>
                                                    @elseif($log->description === 'locked')
                                                        <span class="px-2 py-1 text-xs rounded" style="background-color: var(--theme-status-warning-bg); color: var(--theme-status-warning-text)">⚠ Locked Out</span>
                                                    @elseif($log->description === 'logout')
                                                        <span class="px-2 py-1 text-xs rounded" style="background-color: var(--theme-bg-hover); color: var(--theme-text-muted)">← Logout</span>
                                                    @else
                                                        <span class="px-2 py-1 text-xs rounded" style="background-color: var(--theme-bg-hover); color: var(--theme-text-muted)">{{ $log->description }}</span>
                                                    @endif
                                                </td>
                                                <td class="px-6 py-4 text-sm" style="color: var(--theme-text-muted)">{{ $log->properties['email'] ?? 'N/A' }}</td>
                                                <td class="px-6 py-4 text-sm font-mono" style="color: var(--theme-text-muted)">{{ $log->properties['ip'] ?? 'N/A' }}</td>
                                                <td class="px-6 py-4 text-sm" style="color: var(--theme-text-muted)">
                                                    @if($log->causer)
                                                        {{ $log->causer->getFullName() }}
                                                    @else
                                                        <span style="color: var(--theme-text-muted)">—</span>
                                                    @endif
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm" style="color: var(--theme-text-muted)">
                                                    {{ $log->created_at->format('Y-m-d H:i:s') }}
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            
                            <div class="mt-6">
                                {{ $loginLogs->appends(['type' => 'login'])->links() }}
                            </div>
                        @endif
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
