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
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">ID</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Recipient</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        @foreach($sendLogs as $log)
                                            <tr class="hover:bg-gray-50">
                                                <td class="px-6 py-4 whitespace-nowrap text-sm">{{ $log->id }}</td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                                    @if($log->user_id)
                                                        <span class="px-2 py-1 text-xs bg-blue-100 text-blue-800 rounded">User</span>
                                                    @else
                                                        <span class="px-2 py-1 text-xs bg-green-100 text-green-800 rounded">Customer</span>
                                                    @endif
                                                </td>
                                                <td class="px-6 py-4 text-sm text-gray-600">{{ $log->email }}</td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                                    @if($log->status == 1)
                                                        <span class="px-2 py-1 text-xs bg-green-100 text-green-800 rounded">Sent</span>
                                                    @else
                                                        <span class="px-2 py-1 text-xs bg-red-100 text-red-800 rounded">Failed</span>
                                                    @endif
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
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
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Event</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">User</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Subject</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        @foreach($activityLogs as $log)
                                            <tr class="hover:bg-gray-50">
                                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">{{ $log->event }}</td>
                                                <td class="px-6 py-4 text-sm text-gray-600">
                                                    {{ $log->causer?->getFullName() ?? 'System' }}
                                                </td>
                                                <td class="px-6 py-4 text-sm text-gray-600">
                                                    {{ $log->subject_type }} #{{ $log->subject_id }}
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
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
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
