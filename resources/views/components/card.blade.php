@props(['padding' => true])

<div {{ $attributes->merge(['class' => 'bg-white overflow-hidden shadow-sm sm:rounded-lg' . ($padding ? ' p-6' : '')]) }}>
    {{ $slot }}
</div>
