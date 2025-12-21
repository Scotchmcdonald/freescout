@props(['percent' => 0, 'label' => null, 'color' => 'primary', 'alpine' => null])

@php
    $colorClasses = [
        'primary' => 'bg-primary-600',
        'success' => 'bg-success-600',
        'warning' => 'bg-warning-600',
        'danger' => 'bg-danger-600',
        'info' => 'bg-info-600',
    ];
    $bgClass = $alpine ? '' : ($colorClasses[$color] ?? 'bg-primary-600');
@endphp

<div class="w-full">
    @if($label)
        <div class="flex justify-between mb-1">
            <span class="text-sm font-medium text-gray-700">{{ $label }}</span>
            <span class="text-sm font-medium text-gray-700">
                @if($alpine)
                    <span x-text="{{ $alpine }}"></span>%
                @else
                    {{ $percent }}%
                @endif
            </span>
        </div>
    @endif
    <div class="w-full bg-gray-200 rounded-full h-2.5 dark:bg-gray-700 overflow-hidden">
        <div class="{{ $bgClass }} h-2.5 rounded-full transition-all duration-700 ease-out" 
             @if($alpine) 
                :style="'width: ' + {{ $alpine }} + '%'"
                :class="{
                    'bg-primary-500': {{ $alpine }} < 40,
                    'bg-primary-600': {{ $alpine }} >= 40 && {{ $alpine }} < 80,
                    'bg-primary-700': {{ $alpine }} >= 80
                }"
             @else 
                style="width: {{ $percent }}%" 
             @endif></div>
    </div>
</div>
