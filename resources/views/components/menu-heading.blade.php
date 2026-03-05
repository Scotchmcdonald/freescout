@props([
    'bordered' => false,
])

<div
    role="presentation"
    {{ $attributes->merge([
        'class' => 'px-4 pt-2 pb-1 text-[11px] font-semibold uppercase tracking-wider rounded-sm' . ($bordered ? ' mt-1 border-t' : ''),
        'style' => 'color: var(--theme-primary-700, #4338ca); background-color: var(--theme-primary-50, #eef2ff);' . ($bordered ? ' border-color: var(--theme-primary-100, #e0e7ff);' : ''),
    ]) }}
>
    {{ $slot }}
</div>
