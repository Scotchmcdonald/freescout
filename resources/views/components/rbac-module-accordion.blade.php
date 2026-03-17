{{-- RBAC Module Accordion Component --}}
@props([
    'module' => '',
    'label' => '',
    'permissionCount' => 0,
    'enabledCount' => 0,
    'expanded' => false,
])

<div class="border border-neutral-200 dark:border-neutral-600 rounded-lg mb-3 overflow-hidden"
     x-data="{ open: {{ $expanded ? 'true' : 'false' }} }">
    {{-- Accordion Header --}}
    <button type="button"
            class="w-full flex items-center justify-between px-4 py-3 text-left bg-neutral-50 dark:bg-neutral-700/50 hover:bg-neutral-100 dark:hover:bg-neutral-700 transition-colors duration-150"
            @click="open = !open"
            :aria-expanded="open">
        <div class="flex items-center gap-3">
            {{-- Chevron --}}
            <svg class="w-4 h-4 text-neutral-500 dark:text-neutral-400 transition-transform duration-200"
                 :class="{ 'rotate-90': open }"
                 fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
            </svg>

            {{-- Module Name --}}
            <span class="font-semibold text-sm text-neutral-800 dark:text-neutral-200">
                {{ $label ?: ucfirst($module) }}
            </span>
        </div>

        {{-- Permission summary badge --}}
        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-medium"
              :class="open
                  ? 'bg-neutral-200 dark:bg-neutral-600 text-neutral-600 dark:text-neutral-300'
                  : '{{ $enabledCount > 0 ? "bg-success-100 dark:bg-success-900/30 text-success-700 dark:text-success-400" : "bg-neutral-100 dark:bg-neutral-700 text-neutral-500 dark:text-neutral-400" }}'">
            <template x-if="!open">
                <span>{{ $enabledCount }} / {{ $permissionCount }} enabled</span>
            </template>
            <template x-if="open">
                <span>{{ $permissionCount }} permissions</span>
            </template>
        </span>
    </button>

    {{-- Accordion Body --}}
    <div x-show="open"
         x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0 -translate-y-1"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-100"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         x-cloak>
        <div class="border-t border-neutral-200 dark:border-neutral-600">
            {{ $slot }}
        </div>
    </div>
</div>
