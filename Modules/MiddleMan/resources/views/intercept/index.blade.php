@extends('middleman::layouts.master')

@section('module-content')
    <div x-data="{
        interceptActive: @json($interceptActive),
        selectedEvent: null,
        interceptRules: @json($interceptRules),
        newRule: '',
        editingPayload: null,
        editPayloadJson: '',
        submitting: false,
        selectedIds: [],
        async toggleIntercept() {
            this.submitting = true;
            try {
                const res = await fetch('{{ route('middleman.intercept.toggle') }}', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                    body: JSON.stringify({ active: !this.interceptActive })
                });
                const data = await res.json();
                this.interceptActive = data.active;
            } finally { this.submitting = false; }
        },
        async addRule() {
            if (!this.newRule.trim()) return;
            this.submitting = true;
            try {
                const res = await fetch('{{ route('middleman.intercept.rules.add') }}', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                    body: JSON.stringify({ event_class: this.newRule })
                });
                const data = await res.json();
                if (data.success) {
                    this.interceptRules = data.rules;
                    this.newRule = '';
                }
            } finally { this.submitting = false; }
        },
        async removeRule(eventClass) {
            const res = await fetch('{{ route('middleman.intercept.rules.remove') }}', {
                method: 'DELETE',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                body: JSON.stringify({ event_class: eventClass })
            });
            const data = await res.json();
            if (data.success) { this.interceptRules = data.rules; }
        },
        async loadDetail(id) {
            const res = await fetch(`/middleman/intercept/${id}`);
            this.selectedEvent = await res.json();
        },
        async fireEvent(id) {
            if (!confirm('Fire this intercepted event? This action cannot be undone.')) return;
            this.submitting = true;
            try {
                await fetch(`/middleman/intercept/${id}/fire`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
                });
                this.selectedEvent = null;
                window.location.reload();
            } finally { this.submitting = false; }
        },
        async discardEvent(id) {
            if (!confirm('Discard this event permanently?')) return;
            await fetch(`/middleman/intercept/${id}/discard`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
            });
            this.selectedEvent = null;
            window.location.reload();
        },
        async fireSelected() {
            if (this.selectedIds.length === 0) return;
            if (!confirm(`Fire ${this.selectedIds.length} selected event(s)?`)) return;
            this.submitting = true;
            try {
                await fetch('{{ route('middleman.intercept.fire-selected') }}', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                    body: JSON.stringify({ ids: this.selectedIds })
                });
                window.location.reload();
            } finally { this.submitting = false; }
        },
        async replaySelectedSequence() {
            if (this.selectedIds.length === 0) return;
            if (!confirm(`Replay ${this.selectedIds.length} selected captured event(s) in recorded sequence?`)) return;
            this.submitting = true;
            try {
                const res = await fetch('{{ route('middleman.replay.sequence') }}', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                    body: JSON.stringify({ source: 'intercepts', ids: this.selectedIds })
                });
                const data = await res.json();

                if (res.status === 207) {
                    const failures = (data.errors || []).map(err => `#${err.id} ${err.event_class}: ${err.message}`);
                    const details = failures.length > 0 ? `\n\nFailed items:\n${failures.join('\n')}` : '';
                    alert(`Replay sequence completed with partial failures. ${data.succeeded || 0} succeeded, ${data.failed || 0} failed.${details}`);
                    return;
                }

                if (!res.ok) {
                    alert(data.message || data.error || 'Replay sequence failed.');
                    return;
                }

                alert(`Replay sequence complete: ${data.succeeded || 0} succeeded, ${data.failed || 0} failed`);
            } finally {
                this.submitting = false;
            }
        },
        async fireAll() {
            if (!confirm('Fire ALL pending intercepted events in order? This cannot be undone.')) return;
            this.submitting = true;
            try {
                await fetch('{{ route('middleman.intercept.fire-all') }}', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
                });
                window.location.reload();
            } finally { this.submitting = false; }
        },
        startEditPayload(intercept) {
            this.editingPayload = intercept.id;
            this.editPayloadJson = JSON.stringify(intercept.payload, null, 2);
        },
        async savePayload(id) {
            try {
                const payload = JSON.parse(this.editPayloadJson);
                const res = await fetch(`/middleman/intercept/${id}/payload`, {
                    method: 'PUT',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                    body: JSON.stringify({ payload })
                });
                const data = await res.json();
                if (data.success) {
                    this.editingPayload = null;
                    this.selectedEvent = data.intercept;
                }
            } catch (e) { alert('Invalid JSON: ' + e.message); }
        },
        toggleSelect(id) {
            const idx = this.selectedIds.indexOf(id);
            if (idx === -1) { this.selectedIds.push(id); } else { this.selectedIds.splice(idx, 1); }
        },
        isSelected(id) { return this.selectedIds.includes(id); }
    }">
        <div class="flex justify-between items-center mb-6">
            <h2 class="font-semibold text-xl text-neutral-800 leading-tight">Event Interception</h2>
            <div class="flex items-center space-x-3">
                <button @click="toggleIntercept()" :disabled="submitting"
                    :class="interceptActive ? 'bg-danger-600 hover:bg-danger-700' : 'bg-warning-600 hover:bg-warning-700'"
                    class="inline-flex items-center px-4 py-2 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest focus:outline-none focus:ring-2 focus:ring-offset-2 transition ease-in-out duration-150 disabled:opacity-50">
                    <svg x-show="submitting" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none"
                        viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                            stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                    </svg>
                    <span x-text="interceptActive ? 'Stop Intercepting' : 'Start Intercepting'"></span>
                </button>
            </div>
        </div>

        <!-- Warning Banner -->
        <div x-show="interceptActive" class="mb-6 rounded-lg bg-warning-50 border border-warning-200 p-4">
            <div class="flex items-center">
                <svg class="h-5 w-5 text-warning-500 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                    stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
                <p class="ml-3 text-sm font-medium text-warning-800">
                    Interception active — matched events are <strong>halted</strong> and held. Listeners will NOT fire until
                    you release them.
                </p>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
            <!-- Sidebar: Rules Configuration -->
            <div class="lg:col-span-1 space-y-4">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-4">
                    <h3 class="text-sm font-semibold text-neutral-900 mb-3 uppercase tracking-wide">Intercept Rules</h3>

                    <div class="mb-4">
                        <label for="new-intercept-rule" class="block text-xs font-medium text-neutral-600 mb-1">Add Event
                            Pattern</label>
                        <div class="flex space-x-2">
                            <input x-model="newRule" type="text" id="new-intercept-rule"
                                list="available-intercept-events" placeholder="App\Events\OrderPlaced"
                                class="flex-1 text-sm rounded-md border-neutral-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                            <button @click="addRule()" :disabled="submitting"
                                class="px-3 py-2 bg-primary-600 text-white text-xs rounded-md hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 disabled:opacity-50">
                                Add
                            </button>
                        </div>
                        <datalist id="available-intercept-events">
                            @foreach ($availableEvents as $event)
                                <option value="{{ $event['class'] }}">{{ $event['name'] }}</option>
                            @endforeach
                        </datalist>
                    </div>

                    <div class="space-y-2">
                        <template x-for="(rule, idx) in interceptRules" :key="idx">
                            <div class="flex items-center justify-between py-1.5 px-2 bg-warning-50 rounded text-xs">
                                <code class="font-mono text-neutral-700 truncate mr-2" x-text="rule"></code>
                                <button @click="removeRule(rule)"
                                    class="text-danger-500 hover:text-danger-700 flex-shrink-0" aria-label="Remove rule">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                        stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>
                        </template>
                        <p x-show="interceptRules.length === 0" class="text-xs text-neutral-500 italic">No rules configured.
                        </p>
                    </div>
                </div>

                <!-- Bulk Actions -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-4">
                    <h3 class="text-sm font-semibold text-neutral-900 mb-3 uppercase tracking-wide">Bulk Actions</h3>
                    <div class="space-y-2">
                        <button @click="fireSelected()" :disabled="selectedIds.length === 0 || submitting"
                            class="w-full px-3 py-2 bg-success-600 text-white text-xs rounded-md hover:bg-success-700 disabled:opacity-50 disabled:cursor-not-allowed focus:outline-none focus:ring-2 focus:ring-success-500 font-medium uppercase tracking-wide">
                            Fire Selected (<span x-text="selectedIds.length"></span>)
                        </button>
                        <button @click="replaySelectedSequence()" :disabled="selectedIds.length === 0 || submitting"
                            class="w-full px-3 py-2 bg-primary-600 text-white text-xs rounded-md hover:bg-primary-700 disabled:opacity-50 disabled:cursor-not-allowed focus:outline-none focus:ring-2 focus:ring-primary-500 font-medium uppercase tracking-wide">
                            Replay Selected Sequence
                        </button>
                        <button @click="fireAll()" :disabled="submitting"
                            class="w-full px-3 py-2 bg-warning-600 text-white text-xs rounded-md hover:bg-warning-700 disabled:opacity-50 focus:outline-none focus:ring-2 focus:ring-warning-500 font-medium uppercase tracking-wide">
                            Fire All Pending
                        </button>
                    </div>
                    <p class="mt-2 text-xs text-neutral-500">
                        Events are fired in sort order. Re-ordering events that depend on each other may cause errors.
                    </p>
                </div>
            </div>

            <!-- Main: Pending Queue -->
            <div class="lg:col-span-3 space-y-4">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="px-4 py-3 bg-neutral-50 border-b border-neutral-200 flex items-center justify-between">
                        <h3 class="text-sm font-semibold text-neutral-700 uppercase tracking-wide">Pending Queue</h3>
                        <span class="text-xs text-neutral-500">Drag rows to reorder</span>
                    </div>
                    <div id="intercept-queue">
                        <table class="min-w-full divide-y divide-neutral-200">
                            <thead class="bg-neutral-50">
                                <tr>
                                    <th class="w-10 px-2 py-3"></th>
                                    <th class="w-10 px-2 py-3">
                                        <span class="sr-only">Drag</span>
                                    </th>
                                    <th
                                        class="px-4 py-3 text-left text-xs font-medium text-neutral-500 uppercase tracking-wider">
                                        Order</th>
                                    <th
                                        class="px-4 py-3 text-left text-xs font-medium text-neutral-500 uppercase tracking-wider">
                                        Event</th>
                                    <th
                                        class="px-4 py-3 text-left text-xs font-medium text-neutral-500 uppercase tracking-wider">
                                        Class</th>
                                    <th
                                        class="px-4 py-3 text-left text-xs font-medium text-neutral-500 uppercase tracking-wider">
                                        Intercepted</th>
                                    <th
                                        class="px-4 py-3 text-right text-xs font-medium text-neutral-500 uppercase tracking-wider">
                                        Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-neutral-200" id="sortable-body">
                                @forelse($pending as $intercept)
                                    <tr class="hover:bg-primary-50 transition-colors group" data-id="{{ $intercept->id }}"
                                        @click="loadDetail({{ $intercept->id }})">
                                        <td class="px-2 py-3 text-center" @click.stop>
                                            <input type="checkbox" :checked="isSelected({{ $intercept->id }})"
                                                @change="toggleSelect({{ $intercept->id }})"
                                                class="rounded border-neutral-300 text-primary-600 focus:ring-primary-500">
                                        </td>
                                        <td class="px-2 py-3 cursor-grab text-neutral-400 hover:text-neutral-600"
                                            @click.stop>
                                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                                                stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M4 8h16M4 16h16" />
                                            </svg>
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap text-sm font-mono text-neutral-500">
                                            #{{ $intercept->sort_order }}
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            <span
                                                class="text-sm font-medium text-neutral-900">{{ $intercept->event_name }}</span>
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            <code
                                                class="text-xs font-mono text-neutral-500">{{ Str::limit($intercept->event_class, 40) }}</code>
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap text-sm text-neutral-500">
                                            {{ $intercept->intercepted_at->diffForHumans() }}
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap text-right space-x-2" @click.stop>
                                            <button @click="fireEvent({{ $intercept->id }})"
                                                class="text-success-600 hover:text-success-800 text-xs font-medium"
                                                title="Fire this event">
                                                Fire
                                            </button>
                                            <button @click="discardEvent({{ $intercept->id }})"
                                                class="text-danger-600 hover:text-danger-800 text-xs font-medium"
                                                title="Discard this event">
                                                Discard
                                            </button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="px-4 py-12 text-center text-sm text-neutral-500 italic">
                                            No intercepted events in queue.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if ($pending->hasPages())
                        <div class="px-4 py-3 border-t border-neutral-200">
                            {{ $pending->links() }}
                        </div>
                    @endif
                </div>

                <!-- History -->
                @if ($history->isNotEmpty())
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="px-4 py-3 bg-neutral-50 border-b border-neutral-200">
                            <h3 class="text-sm font-semibold text-neutral-700 uppercase tracking-wide">Recent History</h3>
                        </div>
                        <table class="min-w-full divide-y divide-neutral-200">
                            <thead class="bg-neutral-50">
                                <tr>
                                    <th
                                        class="px-4 py-3 text-left text-xs font-medium text-neutral-500 uppercase tracking-wider">
                                        Event</th>
                                    <th
                                        class="px-4 py-3 text-left text-xs font-medium text-neutral-500 uppercase tracking-wider">
                                        Status</th>
                                    <th
                                        class="px-4 py-3 text-left text-xs font-medium text-neutral-500 uppercase tracking-wider">
                                        Intercepted</th>
                                    <th
                                        class="px-4 py-3 text-left text-xs font-medium text-neutral-500 uppercase tracking-wider">
                                        Resolved</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-neutral-200">
                                @foreach ($history as $item)
                                    <tr class="hover:bg-neutral-50">
                                        <td class="px-4 py-3 whitespace-nowrap text-sm text-neutral-700">
                                            {{ $item->event_name }}</td>
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            @include('middleman::components.status-badge', [
                                                'status' => $item->status,
                                            ])
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap text-sm text-neutral-500">
                                            {{ $item->intercepted_at->diffForHumans() }}</td>
                                        <td class="px-4 py-3 whitespace-nowrap text-sm text-neutral-500">
                                            {{ $item->fired_at?->diffForHumans() ?? '-' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>

        <!-- Detail Panel (with edit & fire actions) -->
        <template x-if="selectedEvent">
            <div x-show="selectedEvent" x-transition:enter="transition ease-in-out duration-200"
                x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0"
                x-transition:leave="transition ease-in-out duration-200" x-transition:leave-start="translate-x-0"
                x-transition:leave-end="translate-x-full"
                class="fixed inset-y-0 right-0 z-50 w-full sm:w-[520px] bg-white shadow-xl border-l border-neutral-200 overflow-y-auto"
                @keydown.escape.window="selectedEvent = null">

                <div
                    class="sticky top-0 z-10 bg-white border-b border-neutral-200 px-6 py-4 flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-neutral-900"
                        x-text="selectedEvent?.event_name || 'Intercept Detail'"></h3>
                    <button @click="selectedEvent = null"
                        class="text-neutral-400 hover:text-neutral-500 focus:outline-none focus:ring-2 focus:ring-primary-500 rounded"
                        aria-label="Close panel">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div class="px-6 py-4 space-y-6">
                    <div>
                        <label class="block text-xs font-medium text-neutral-500 uppercase tracking-wide mb-1">Event
                            Class</label>
                        <p class="text-sm font-mono text-neutral-800 break-all" x-text="selectedEvent?.event_class"></p>
                    </div>
                    <div>
                        <label
                            class="block text-xs font-medium text-neutral-500 uppercase tracking-wide mb-1">Status</label>
                        <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium"
                            :class="selectedEvent?.status === 'pending' ? 'bg-warning-100 text-warning-800' : selectedEvent
                                ?.status === 'fired' ? 'bg-success-100 text-success-800' :
                                'bg-neutral-100 text-neutral-600'"
                            x-text="selectedEvent?.status"></span>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-neutral-500 uppercase tracking-wide mb-1">Intercepted
                            At</label>
                        <p class="text-sm text-neutral-700" x-text="selectedEvent?.intercepted_at"></p>
                    </div>
                    <div>
                        <label
                            class="block text-xs font-medium text-neutral-500 uppercase tracking-wide mb-1">Metadata</label>
                        <pre class="text-xs font-mono bg-neutral-50 rounded-md p-3 overflow-x-auto border border-neutral-200 max-h-48"
                            x-text="JSON.stringify(selectedEvent?.metadata, null, 2)"></pre>
                    </div>

                    {{-- Editable Payload --}}
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <label
                                class="block text-xs font-medium text-neutral-500 uppercase tracking-wide">Payload</label>
                            <template x-if="selectedEvent?.status === 'pending' && editingPayload !== selectedEvent?.id">
                                <button @click="startEditPayload(selectedEvent)"
                                    class="text-xs text-primary-600 hover:text-primary-800 font-medium">
                                    Edit
                                </button>
                            </template>
                        </div>

                        <template x-if="editingPayload === selectedEvent?.id">
                            <div class="space-y-2">
                                <textarea x-model="editPayloadJson" rows="12"
                                    class="w-full text-xs font-mono rounded-md border-neutral-300 focus:border-primary-500 focus:ring-primary-500 bg-neutral-50"></textarea>
                                <div class="flex space-x-2">
                                    <button @click="savePayload(selectedEvent.id)"
                                        class="px-3 py-1.5 bg-success-600 text-white text-xs rounded-md hover:bg-success-700">Save</button>
                                    <button @click="editingPayload = null"
                                        class="px-3 py-1.5 bg-neutral-200 text-neutral-700 text-xs rounded-md hover:bg-neutral-300">Cancel</button>
                                </div>
                            </div>
                        </template>
                        <template x-if="editingPayload !== selectedEvent?.id">
                            <pre class="text-xs font-mono bg-neutral-50 rounded-md p-3 overflow-x-auto border border-neutral-200 max-h-96"
                                x-text="JSON.stringify(selectedEvent?.payload, null, 2)"></pre>
                        </template>
                    </div>

                    {{-- Action Buttons (pending only) --}}
                    <template x-if="selectedEvent?.status === 'pending'">
                        <div class="flex space-x-3 pt-4 border-t border-neutral-200">
                            <button @click="fireEvent(selectedEvent.id)" :disabled="submitting"
                                class="flex-1 px-4 py-2 bg-success-600 text-white text-sm rounded-md hover:bg-success-700 disabled:opacity-50 font-medium focus:outline-none focus:ring-2 focus:ring-success-500">
                                Fire Event
                            </button>
                            <button @click="discardEvent(selectedEvent.id)"
                                class="flex-1 px-4 py-2 bg-danger-600 text-white text-sm rounded-md hover:bg-danger-700 font-medium focus:outline-none focus:ring-2 focus:ring-danger-500">
                                Discard
                            </button>
                        </div>
                    </template>

                    {{-- Stale Data Warning --}}
                    <template x-if="selectedEvent?.status === 'pending'">
                        <div class="rounded-lg bg-warning-50 border border-warning-200 p-3">
                            <p class="text-xs text-warning-700">
                                <strong>Caution:</strong> Payload data may become stale if the underlying models have been
                                modified since interception.
                                Re-ordering events that depend on each other may cause exceptions.
                            </p>
                        </div>
                    </template>
                </div>
            </div>
        </template>

        {{-- Backdrop --}}
        <div x-show="selectedEvent" x-transition.opacity class="fixed inset-0 z-40 bg-black bg-opacity-50"
            @click="selectedEvent = null"></div>
    </div>

    {{-- Drag-and-drop reordering via native HTML5 Drag API --}}
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const tbody = document.getElementById('sortable-body');
            if (!tbody) return;

            let draggedRow = null;

            tbody.querySelectorAll('tr[data-id]').forEach(row => {
                row.setAttribute('draggable', 'true');

                row.addEventListener('dragstart', (e) => {
                    draggedRow = row;
                    row.style.opacity = '0.5';
                    e.dataTransfer.effectAllowed = 'move';
                });

                row.addEventListener('dragend', () => {
                    row.style.opacity = '1';
                    draggedRow = null;
                    tbody.querySelectorAll('tr').forEach(r => r.classList.remove('border-t-2',
                        'border-primary-500'));
                });

                row.addEventListener('dragover', (e) => {
                    e.preventDefault();
                    e.dataTransfer.dropEffect = 'move';
                    row.classList.add('border-t-2', 'border-primary-500');
                });

                row.addEventListener('dragleave', () => {
                    row.classList.remove('border-t-2', 'border-primary-500');
                });

                row.addEventListener('drop', (e) => {
                    e.preventDefault();
                    row.classList.remove('border-t-2', 'border-primary-500');
                    if (draggedRow && draggedRow !== row) {
                        tbody.insertBefore(draggedRow, row);
                        saveOrder();
                    }
                });
            });

            function saveOrder() {
                const rows = tbody.querySelectorAll('tr[data-id]');
                const order = Array.from(rows).map((r, i) => ({
                    id: parseInt(r.dataset.id),
                    sort: i + 1
                }));

                fetch('{{ route('middleman.intercept.reorder') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        order
                    })
                });
            }
        });
    </script>
@endsection
