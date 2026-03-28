@props(['status'])

@php
    $colors = match ($status) {
        'pending' => 'bg-warning-100 text-warning-800',
        'fired' => 'bg-success-100 text-success-800',
        'discarded' => 'bg-neutral-100 text-neutral-600',
        'active' => 'bg-primary-100 text-primary-800',
        'inactive' => 'bg-neutral-100 text-neutral-500',
        'error' => 'bg-danger-100 text-danger-800',
        default => 'bg-neutral-100 text-neutral-600',
    };
@endphp

<span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $colors }}">
    {{ ucfirst($status) }}
</span>
