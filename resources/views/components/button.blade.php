@props(['variant' => 'primary', 'type' => 'button']

)

@php
$classes = match($variant) {
    'secondary' => 'bg-white border border-neutral-300 text-neutral-700 hover:bg-neutral-50 focus:ring-neutral-500',
    'success' => 'bg-success-600 border border-transparent text-white hover:bg-success-700 focus:ring-success-500',
    'warning' => 'bg-warning-600 border border-transparent text-white hover:bg-warning-700 focus:ring-warning-500',
    'danger' => 'bg-danger-600 border border-transparent text-white hover:bg-danger-700 focus:ring-danger-500',
    default => 'bg-primary-600 border border-transparent text-white hover:bg-primary-700 focus:ring-primary-500',
};
@endphp

<button {{ $attributes->merge(['type' => $type, 'class' => "inline-flex items-center px-4 py-2 {$classes} rounded-md font-semibold text-xs uppercase tracking-widest shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2 disabled:opacity-25 transition ease-in-out duration-150"]) }}>
    {{ $slot }}
</button>
