@props(['percent' => 0, 'label' => null, 'color' => 'primary', 'alpine' => null])

@php
    $colorClasses = [
        'primary' => 'background-color: var(--theme-primary-600)',
        'success' => 'background-color: var(--theme-status-success-text)',
        'warning' => 'background-color: var(--theme-status-warning-text)',
        'danger' => 'background-color: var(--theme-status-error-text)',
        'info' => 'background-color: var(--theme-status-info-text)',
    ];
    $bgStyle = $alpine ? '' : ($colorClasses[$color] ?? 'background-color: var(--theme-primary-600)');
@endphp

<div class="w-full">
    @if($label)
        <div class="flex justify-between mb-1">
            <span class="text-sm font-medium" style="color: var(--theme-text-main)">{{ $label }}</span>
            <span class="text-sm font-medium" style="color: var(--theme-text-main)">
                @if($alpine)
                    <span x-text="{{ $alpine }}"></span>%
                @else
                    {{ $percent }}%
                @endif
            </span>
        </div>
    @endif
    <div class="w-full rounded-full h-2.5 overflow-hidden" style="background-color: var(--theme-bg-input)">
        <div class="h-2.5 rounded-full transition-all duration-700 ease-out" 
             @if($alpine) 
                :style="'width: ' + {{ $alpine }} + '%; filter: saturate(' + (0.5 + ({{ $alpine }} / 100)) + '); background-color: var(--theme-primary-500)'"
             @else 
                style="width: {{ $percent }}%; {{ $bgStyle }}" 
             @endif></div>
    </div>
</div>
