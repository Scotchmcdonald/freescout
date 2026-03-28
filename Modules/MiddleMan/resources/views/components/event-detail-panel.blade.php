{{-- Sliding detail panel for event inspection --}}
@props(['panelId' => 'detail-panel'])

<div x-show="selectedEvent" x-transition:enter="transition ease-in-out duration-200"
    x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0"
    x-transition:leave="transition ease-in-out duration-200" x-transition:leave-start="translate-x-0"
    x-transition:leave-end="translate-x-full"
    class="fixed inset-y-0 right-0 z-50 w-full sm:w-[480px] bg-white shadow-xl border-l border-neutral-200 overflow-y-auto"
    @keydown.escape.window="selectedEvent = null" id="{{ $panelId }}">

    <div class="sticky top-0 z-10 bg-white border-b border-neutral-200 px-6 py-4 flex items-center justify-between">
        <h3 class="text-lg font-semibold text-neutral-900" x-text="selectedEvent?.event_name || 'Event Detail'"></h3>
        <button @click="selectedEvent = null"
            class="text-neutral-400 hover:text-neutral-500 focus:outline-none focus:ring-2 focus:ring-primary-500 rounded"
            aria-label="Close panel">
            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>
    </div>

    <div class="px-6 py-4 space-y-6">
        {{-- Event Class --}}
        <div>
            <label class="block text-xs font-medium text-neutral-500 uppercase tracking-wide mb-1">Event Class</label>
            <p class="text-sm font-mono text-neutral-800 break-all" x-text="selectedEvent?.event_class"></p>
        </div>

        {{-- Timestamp --}}
        <div>
            <label class="block text-xs font-medium text-neutral-500 uppercase tracking-wide mb-1">Fired At</label>
            <p class="text-sm text-neutral-700" x-text="selectedEvent?.fired_at || selectedEvent?.intercepted_at"></p>
        </div>

        {{-- Metadata --}}
        <div>
            <label class="block text-xs font-medium text-neutral-500 uppercase tracking-wide mb-1">Metadata</label>
            <pre class="text-xs font-mono bg-neutral-50 rounded-md p-3 overflow-x-auto border border-neutral-200 max-h-48"
                x-text="JSON.stringify(selectedEvent?.metadata, null, 2)"></pre>
        </div>

        {{-- Payload --}}
        <div>
            <label class="block text-xs font-medium text-neutral-500 uppercase tracking-wide mb-1">Payload</label>
            <pre class="text-xs font-mono bg-neutral-50 rounded-md p-3 overflow-x-auto border border-neutral-200 max-h-96"
                x-text="JSON.stringify(selectedEvent?.payload, null, 2)"></pre>
        </div>

        {{-- Slot for extra actions (edit, fire, discard) --}}
        @isset($slot)
            {{ $slot }}
        @endisset
    </div>
</div>

{{-- Backdrop --}}
<div x-show="selectedEvent" x-transition:enter="transition ease-in-out duration-200"
    x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-50"
    x-transition:leave="transition ease-in-out duration-200" x-transition:leave-start="opacity-50"
    x-transition:leave-end="opacity-0" class="fixed inset-0 z-40 bg-black opacity-50" @click="selectedEvent = null">
</div>
