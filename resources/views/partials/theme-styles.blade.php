@php
    $isDarkMode = auth()->user()?->dark_mode ?? false;
@endphp

<script>
    // Set dark class before first paint to keep Tailwind dark utilities in sync.
    document.documentElement.classList.toggle('dark', @json($isDarkMode));
</script>

<x-theme-styles />
