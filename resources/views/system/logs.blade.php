<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('System Logs') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Tab Navigation -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="border-b border-gray-200">
                    <nav class="flex -mb-px">
                        <a href="{{ route('system.logs', ['type' => 'application']) }}" 
                           class="px-6 py-3 border-b-2 font-medium text-sm {{ $currentType === 'application' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                            Application Logs
                        </a>
                        <a href="{{ route('system.logs', ['type' => 'email']) }}" 
                           class="px-6 py-3 border-b-2 font-medium text-sm {{ $currentType === 'email' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                            Email Logs
                        </a>
                        <a href="{{ route('system.logs', ['type' => 'activity']) }}" 
                           class="px-6 py-3 border-b-2 font-medium text-sm {{ $currentType === 'activity' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                            Activity Logs
                        </a>
                        <a href="{{ route('system.logs', ['type' => 'login']) }}" 
                           class="px-6 py-3 border-b-2 font-medium text-sm {{ $currentType === 'login' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                            Login Logs
                        </a>
                    </nav>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    @if($currentType === 'application')
                        <!-- Application Logs -->
                        <div class="mb-4 flex justify-between items-center">
                            <h3 class="text-lg font-semibold">{{ __('Recent Log Entries') }}</h3>
                            <span class="text-sm text-gray-600">Showing last 100 lines</span>
                        </div>
                        
                        @if(empty($lines) || count($lines) === 0)
                            <div class="text-center py-12 text-gray-500">
                                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                <p class="mt-2">No log entries found</p>
                            </div>
                        @else
                            <div class="bg-gray-900 rounded-lg p-4 overflow-x-auto">
                                <pre class="text-xs text-gray-100 font-mono whitespace-pre-wrap">@foreach($lines as $line){{ $line }}
@endforeach</pre>
                            </div>
                        @endif

                    @elseif($currentType === 'email')
                        <!-- Email Logs -->
                        <h3 class="text-lg font-semibold mb-4">{{ __('Email Send Logs') }}</h3>
                        
                        @if($sendLogs->isEmpty())
                            <div class="text-center py-12 text-gray-500">
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
                            <div class="text-center py-12 text-gray-500">
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
                        <p class="text-sm text-gray-600 mb-4">Note: Failed login attempts are rate-limited to 5 attempts per email+IP combination before lockout.</p>
                        
                        @if($loginLogs->isEmpty())
                            <div class="text-center py-12 text-gray-500">
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
