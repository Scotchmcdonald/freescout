@props(['title' => 'Error', 'what' => '', 'why' => '', 'action' => ''])

<div class="rounded-lg border border-danger-200 bg-danger-50 p-4">
    <div class="flex items-start">
        <div class="flex-shrink-0">
            <svg class="h-5 w-5 text-danger-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
        </div>
        <div class="ml-3 flex-1">
            <h3 class="text-sm font-semibold text-danger-800">{{ $title }}</h3>

            @if ($what)
                <div class="mt-2">
                    <p class="text-xs font-medium text-danger-700 uppercase tracking-wide">What happened</p>
                    <p class="text-sm text-danger-700">{{ $what }}</p>
                </div>
            @endif

            @if ($why)
                <div class="mt-2">
                    <p class="text-xs font-medium text-danger-700 uppercase tracking-wide">Why</p>
                    <p class="text-sm text-danger-700">{{ $why }}</p>
                </div>
            @endif

            @if ($action)
                <div class="mt-2">
                    <p class="text-xs font-medium text-danger-700 uppercase tracking-wide">What to do</p>
                    <p class="text-sm text-danger-700">{{ $action }}</p>
                </div>
            @endif

            {{ $slot }}
        </div>
    </div>
</div>
