@props(['title', 'body' => null, 'actionText' => null, 'actionUrl' => '#', 'code' => null, 'type' => 'warning', 'alpineBody' => null])

@php
$colors = [
    'warning' => [
        'bg' => 'var(--theme-status-warning-bg)',
        'border' => 'var(--theme-status-warning-bg)',
        'text' => 'var(--theme-status-warning-text)',
        'icon' => 'var(--theme-status-warning-text)',
    ],
    'error' => [
        'bg' => 'var(--theme-status-error-bg)',
        'border' => 'var(--theme-status-error-bg)',
        'text' => 'var(--theme-status-error-text)',
        'icon' => 'var(--theme-status-error-text)',
    ],
    'info' => [
        'bg' => 'var(--theme-status-info-bg)',
        'border' => 'var(--theme-status-info-bg)',
        'text' => 'var(--theme-status-info-text)',
        'icon' => 'var(--theme-status-info-text)',
    ],
    'success' => [
        'bg' => 'var(--theme-status-success-bg)',
        'border' => 'var(--theme-status-success-bg)',
        'text' => 'var(--theme-status-success-text)',
        'icon' => 'var(--theme-status-success-text)',
    ]
];
$theme = $colors[$type] ?? $colors['warning'];
@endphp

<div class="rounded-md p-4 border shadow-sm my-4" style="background-color: {{ $theme['bg'] }}; border-color: {{ $theme['border'] }};">
    <div class="flex">
        <div class="flex-shrink-0">
            @if($type === 'error')
                <svg class="h-5 w-5" style="color: {{ $theme['icon'] }}" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                </svg>
            @else
                <svg class="h-5 w-5" style="color: {{ $theme['icon'] }}" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                </svg>
            @endif
        </div>
        <div class="ml-3 flex-1 md:flex md:justify-between">
            <div class="text-sm" style="color: {{ $theme['text'] }}">
                <h3 class="font-bold block mb-1">{{ $title }}</h3>
                <p @if($alpineBody) x-text="{{ $alpineBody }}" @endif>{{ $body }}</p>
                @if($code)
                    <div class="mt-2 bg-white p-2 rounded border font-mono text-xs select-all cursor-pointer" style="border-color: {{ $theme['border'] }}" onclick="navigator.clipboard.writeText(this.innerText); showToast('Copied to clipboard', 'success')">
                        {{ $code }}
                    </div>
                @endif
            </div>
            @if($actionText)
                <p class="mt-3 text-sm md:mt-0 md:ml-6">
                    <a href="{{ $actionUrl }}" class="whitespace-nowrap font-medium flex items-center hover:opacity-75" style="color: {{ $theme['text'] }}">
                        {{ $actionText }}
                        <span aria-hidden="true" class="ml-1">&rarr;</span>
                    </a>
                </p>
            @endif
        </div>
    </div>
</div>
