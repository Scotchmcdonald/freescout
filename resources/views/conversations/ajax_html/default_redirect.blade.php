{{-- Default Redirect - Handle default redirect after actions --}}
@php
    $redirect_url = $redirect_url ?? ($conversation ? route('conversations.show', $conversation) : route('conversations.index'));
    $message = $message ?? __('Action completed successfully');
    $countdown = $countdown ?? 3;
@endphp

<div class="default-redirect-view p-6 text-center">
    <div class="max-w-md mx-auto">
        {{-- Success Icon --}}
        <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-green-100 mb-4">
            <svg class="h-8 w-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
            </svg>
        </div>

        {{-- Message --}}
        <h3 class="text-lg font-semibold text-gray-900 mb-2">{{ $message }}</h3>
        
        {{-- Countdown --}}
        <p class="text-sm text-gray-600 mb-6">
            {{ __('Redirecting in') }} <span id="countdown">{{ $countdown }}</span> {{ __('seconds') }}...
        </p>

        {{-- Manual Redirect Link --}}
        <a href="{{ $redirect_url }}" 
           class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
            {{ __('Continue Now') }}
            <svg class="ml-2 -mr-1 w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
        </a>
    </div>
</div>

<script>
    (function() {
        let countdown = {{ $countdown }};
        const countdownEl = document.getElementById('countdown');
        const redirectUrl = '{{ $redirect_url }}';
        
        const timer = setInterval(function() {
            countdown--;
            if (countdownEl) {
                countdownEl.textContent = countdown;
            }
            
            if (countdown <= 0) {
                clearInterval(timer);
                window.location.href = redirectUrl;
            }
        }, 1000);
    })();
</script>
