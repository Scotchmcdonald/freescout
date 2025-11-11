{{-- Send Log View - Display email send log for debugging --}}
@php
    $thread_id = $thread_id ?? request('thread_id');
    $thread = $thread ?? (\App\Models\Thread::find($thread_id));
    
    // In production, this would fetch actual send logs
    $send_logs = [];
    if ($thread) {
        // Example: $send_logs = $thread->sendLogs()->orderBy('created_at', 'desc')->get();
    }
@endphp

<div class="send-log-view p-6">
    <h3 class="text-lg font-semibold text-gray-900 mb-4">{{ __('Outgoing Emails') }}</h3>

    @if($thread)
        {{-- Thread Info --}}
        <div class="bg-gray-50 rounded-lg p-4 mb-6">
            <div class="text-sm text-gray-600 mb-2">{{ __('Thread') }} #{{ $thread->id }}</div>
            <div class="text-sm">
                <div><strong>{{ __('To') }}:</strong> {{ is_array($thread->to) ? implode(', ', $thread->to) : $thread->to }}</div>
                @if($thread->cc)
                    <div><strong>{{ __('Cc') }}:</strong> {{ is_array($thread->cc) ? implode(', ', $thread->cc) : $thread->cc }}</div>
                @endif
                <div><strong>{{ __('Created') }}:</strong> {{ $thread->created_at->format('M d, Y g:i A') }}</div>
            </div>
        </div>

        {{-- Send Logs --}}
        @if(count($send_logs) > 0)
            <div class="space-y-4">
                @foreach($send_logs as $log)
                    <div class="border border-gray-200 rounded-lg p-4">
                        <div class="flex items-start justify-between mb-2">
                            <div>
                                <div class="font-medium text-gray-900">{{ $log->recipient }}</div>
                                <div class="text-xs text-gray-500">{{ $log->created_at->format('M d, Y g:i:s A') }}</div>
                            </div>
                            @if($log->status == 'sent')
                                <span class="px-2 py-1 text-xs font-medium bg-green-100 text-green-800 rounded">
                                    {{ __('Sent') }}
                                </span>
                            @elseif($log->status == 'failed')
                                <span class="px-2 py-1 text-xs font-medium bg-red-100 text-red-800 rounded">
                                    {{ __('Failed') }}
                                </span>
                            @elseif($log->status == 'bounced')
                                <span class="px-2 py-1 text-xs font-medium bg-yellow-100 text-yellow-800 rounded">
                                    {{ __('Bounced') }}
                                </span>
                            @else
                                <span class="px-2 py-1 text-xs font-medium bg-gray-100 text-gray-800 rounded">
                                    {{ ucfirst($log->status) }}
                                </span>
                            @endif
                        </div>
                        
                        @if($log->message)
                            <div class="text-sm text-gray-600 mt-2">
                                {{ $log->message }}
                            </div>
                        @endif

                        @if($log->error)
                            <div class="mt-2 p-2 bg-red-50 border border-red-200 rounded text-sm text-red-700">
                                <strong>{{ __('Error') }}:</strong> {{ $log->error }}
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        @else
            <div class="text-center py-12">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900">{{ __('No send logs') }}</h3>
                <p class="mt-1 text-sm text-gray-500">{{ __('No outgoing email logs found for this thread.') }}</p>
            </div>
        @endif
    @else
        <div class="text-center py-12">
            <p class="text-sm text-gray-500">{{ __('Thread not found') }}</p>
        </div>
    @endif
</div>
