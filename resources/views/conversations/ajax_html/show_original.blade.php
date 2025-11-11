{{-- Show Original Message - Display original raw email message --}}
@php
    $thread_id = $thread_id ?? request('thread_id');
    $thread = $thread ?? (\App\Models\Thread::find($thread_id));
@endphp

<div class="show-original-view p-6">
    <div class="flex items-center justify-between mb-4">
        <h3 class="text-lg font-semibold text-gray-900">{{ __('Original Message') }}</h3>
        
        @if($thread)
            <button type="button"
                    onclick="downloadOriginal()"
                    class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                </svg>
                {{ __('Download') }}
            </button>
        @endif
    </div>

    @if($thread)
        {{-- Email Headers --}}
        <div class="mb-6">
            <h4 class="text-sm font-semibold text-gray-700 mb-2">{{ __('Email Headers') }}</h4>
            <div class="bg-gray-50 rounded-lg p-4 font-mono text-xs text-gray-700 overflow-x-auto">
                @if($thread->headers && is_array($thread->headers))
                    @foreach($thread->headers as $header => $value)
                        <div class="mb-1">
                            <span class="font-semibold">{{ $header }}:</span> {{ is_array($value) ? implode(', ', $value) : $value }}
                        </div>
                    @endforeach
                @else
                    <div class="text-gray-500">{{ __('No headers available') }}</div>
                @endif
            </div>
        </div>

        {{-- Message Source --}}
        <div>
            <h4 class="text-sm font-semibold text-gray-700 mb-2">{{ __('Message Source') }}</h4>
            <div class="bg-gray-900 rounded-lg p-4 overflow-x-auto">
                <pre class="text-xs text-gray-100 whitespace-pre-wrap break-words"><code>{{-- Raw email source would go here --}}
From: {{ $thread->from ?? 'N/A' }}
To: {{ is_array($thread->to) ? implode(', ', $thread->to) : ($thread->to ?? 'N/A') }}
@if($thread->cc)
Cc: {{ is_array($thread->cc) ? implode(', ', $thread->cc) : $thread->cc }}
@endif
Subject: {{ $thread->conversation->subject ?? 'N/A' }}
Date: {{ $thread->created_at->format('r') }}
Message-ID: {{ $thread->message_id ?? 'N/A' }}

{{ $thread->body ?? '' }}</code></pre>
            </div>
        </div>

        {{-- Additional Info --}}
        <div class="mt-6 p-4 bg-blue-50 rounded-lg">
            <div class="text-sm text-blue-700">
                <strong>{{ __('Thread ID') }}:</strong> {{ $thread->id }}<br>
                <strong>{{ __('Created') }}:</strong> {{ $thread->created_at->format('M d, Y g:i:s A') }}<br>
                <strong>{{ __('Type') }}:</strong> {{ $thread->type == 1 ? __('Customer') : ($thread->type == 2 ? __('Note') : __('Message')) }}
            </div>
        </div>
    @else
        <div class="text-center py-12">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
            </svg>
            <h3 class="mt-2 text-sm font-medium text-gray-900">{{ __('Thread not found') }}</h3>
            <p class="mt-1 text-sm text-gray-500">{{ __('Unable to load the original message.') }}</p>
        </div>
    @endif
</div>

<script>
    function downloadOriginal() {
        // Create a downloadable text file with the original message
        const threadId = {{ $thread->id ?? 0 }};
        const content = document.querySelector('pre code').textContent;
        const blob = new Blob([content], { type: 'text/plain' });
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = 'thread-' + threadId + '-original.txt';
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        window.URL.revokeObjectURL(url);
    }
</script>
