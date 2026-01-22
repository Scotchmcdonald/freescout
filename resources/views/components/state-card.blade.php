@props([
    'title',
    'value',
    'icon',
    'color' => 'primary',
    'trend' => null,
    'trendDirection' => null
])

@php
    // Use semantic CSS variables that map to active theme
    $colorMap = [
        'primary' => [
            'bg' => 'var(--theme-primary-600, #4f46e5)',
            'text' => 'var(--theme-primary-700, #4338ca)'
        ],
        'success' => [
            'bg' => 'var(--theme-success-600, #10b981)',
            'text' => 'var(--theme-success-700, #059669)'
        ],
        'warning' => [
            'bg' => 'var(--theme-warning-600, #f59e0b)',
            'text' => 'var(--theme-warning-700, #d97706)'
        ],
        'danger' => [
            'bg' => 'var(--theme-danger-600, #ef4444)',
            'text' => 'var(--theme-danger-700, #dc2626)'
        ]
    ];
    $colors = $colorMap[$color] ?? $colorMap['primary'];
@endphp

<div class="bg-white overflow-hidden shadow-sm rounded-lg border border-gray-100 transition-shadow hover:shadow-md">
    <div class="p-5">
        <div class="flex items-center">
            <div class="flex-shrink-0 rounded-md p-3" style="background-color: {{ $colors['bg'] }}">
                @if(isset($icon))
                    {{ $icon }}
                @else
                    {{-- Default Icon --}}
                    <svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                    </svg>
                @endif
            </div>
            <div class="ml-5 w-0 flex-1">
                <dl>
                    <dt class="text-sm font-medium text-gray-500 truncate">{{ $title }}</dt>
                    <dd class="flex items-baseline">
                        <span class="text-2xl font-semibold text-gray-900">{{ $value }}</span>
                        @if($trend)
                            <span class="ml-2 text-sm font-medium" style="color: {{ $trendDirection === 'up' ? $colorMap['success']['text'] : $colorMap['danger']['text'] }}">
                                {{ $trend }}
                            </span>
                        @endif
                    </dd>
                </dl>
            </div>
        </div>
    </div>
</div>
