@props(['variant' => 'primary'])

@php
$classes = match($variant) {
    'success' => 'bg-success-100 text-success-800',
    'warning' => 'bg-warning-100 text-warning-800',
    'danger' => 'bg-danger-100 text-danger-800',
    'info' => 'bg-primary-100 text-primary-800',
    default => 'bg-neutral-100 text-neutral-800',
};
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {$classes}"]) }}>
    {{ $slot }}
</span>
