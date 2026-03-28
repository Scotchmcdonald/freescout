@extends('middleman::layouts.master')

@section('module-content')
    <div x-data="{
        loggingActive: @json($loggingActive),
        selectedEvent: null,
        selectedIds: [],
        logRules: @json($logRules),
        newRule: '',
        filterClass: '',
        filterSearch: '',
        submitting: false,
        async toggleLogging() {
            this.submitting = true;
            try {
                const res = await fetch('{{ route('middleman.logging.toggle') }}', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                    body: JSON.stringify({ active: !this.loggingActive })
                });
                const data = await res.json();
                this.loggingActive = data.active;
            } finally { this.submitting = false; }
        },
        async addRule() {
            if (!this.newRule.trim()) return;
            this.submitting = true;
            try {
                const res = await fetch('{{ route('middleman.logging.rules.add') }}', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                    body: JSON.stringify({ event_class: this.newRule })
                });
                const data = await res.json();
                if (data.success) {
                    this.logRules = data.rules;
                    this.newRule = '';
                }
            } finally { this.submitting = false; }
        },
        async removeRule(eventClass) {
            const res = await fetch('{{ route('middleman.logging.rules.remove') }}', {
                method: 'DELETE',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                body: JSON.stringify({ event_class: eventClass })
            });
            const data = await res.json();
            if (data.success) { this.logRules = data.rules; }
        },
        async loadDetail(id) {
            const res = await fetch(`/middleman/logging/${id}`);
            this.selectedEvent = await res.json();
        },
        toggleSelect(id) {
            const idx = this.selectedIds.indexOf(id);
            if (idx === -1) {
                this.selectedIds.push(id);
            } else {
                this.selectedIds.splice(idx, 1);
            }
        },
        isSelected(id) {
            return this.selectedIds.includes(id);
        },
        async replaySelectedSequence() {
            if (this.selectedIds.length === 0) return;
            if (!confirm(`Replay ${this.selectedIds.length} selected event(s) in recorded sequence?`)) return;

            this.submitting = true;
            try {
                const res = await fetch('{{ route('middleman.replay.sequence') }}', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                    body: JSON.stringify({ source: 'logs', ids: this.selectedIds })
                });
                const data = await res.json();
                const status = `${data.succeeded || 0} succeeded, ${data.failed || 0} failed`;
                alert(`Replay sequence complete: ${status}`);
            } finally {
                this.submitting = false;
            }
        }
    }">
        <div class="flex justify-between items-center mb-6">
            <h2 class="font-semibold text-xl text-neutral-800 leading-tight">Event Logging</h2>
            <div class="flex items-center gap-2">
                @can('manage_middleman')
                    <button @click="replaySelectedSequence()" :disabled="submitting || selectedIds.length === 0"
                        class="inline-flex items-center px-4 py-2 border border-transparent rounded-md font-semibold text-xs text-white bg-primary-600 hover:bg-primary-700 uppercase tracking-widest focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 transition ease-in-out duration-150 disabled:opacity-50">
                        Replay Selected (<span class="ml-1" x-text="selectedIds.length"></span>)
                    </button>
                @endcan
                <button @click="toggleLogging()" :disabled="submitting"
                    :class="loggingActive ? 'bg-danger-600 hover:bg-danger-700' : 'bg-success-600 hover:bg-success-700'"
                    class="inline-flex items-center px-4 py-2 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest focus:outline-none focus:ring-2 focus:ring-offset-2 transition ease-in-out duration-150 disabled:opacity-50">
                    <svg x-show="submitting" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none"
                        viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                            stroke-width="4">
                        </circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                    </svg>
                    <span x-text="loggingActive ? 'Stop Recording' : 'Start Recording'"></span>
                </button>
            </div>
        </div>

        <!-- Status Banner -->
        <div x-show="loggingActive" class="mb-6 rounded-lg bg-success-50 border border-success-200 p-4">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-success-500 animate-pulse" fill="currentColor" viewBox="0 0 8 8">
                        <circle cx="4" cy="4" r="3" />
                    </svg>
                </div>
                <p class="ml-3 text-sm font-medium text-success-800">
                    Recording active — monitoring <span x-text="logRules.length" class="font-bold"></span> event pattern(s).
                </p>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
            <!-- Sidebar: Rules Configuration -->
            <div class="lg:col-span-1 space-y-4">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-4">
                    <h3 class="text-sm font-semibold text-neutral-900 mb-3 uppercase tracking-wide">Log Rules</h3>

                    <!-- Add Rule -->
                    <div class="mb-4">
                        <label for="new-log-rule" class="block text-xs font-medium text-neutral-600 mb-1">Add Event
                            Pattern</label>
                        <div class="flex space-x-2">
                            <input x-model="newRule" type="text" id="new-log-rule" list="available-events"
                                placeholder="App\Events\* or *"
                                class="flex-1 text-sm rounded-md border-neutral-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                            <button @click="addRule()" :disabled="submitting"
                                class="px-3 py-2 bg-primary-600 text-white text-xs rounded-md hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 disabled:opacity-50">
                                Add
                            </button>
                        </div>
                        <datalist id="available-events">
                            <option value="*">All Events</option>
                            @foreach ($availableEvents as $event)
                                <option value="{{ $event['class'] }}">{{ $event['name'] }}</option>
                            @endforeach
                        </datalist>
                    </div>

                    <!-- Active Rules -->
                    <div class="space-y-2">
                        <template x-for="(rule, idx) in logRules" :key="idx">
                            <div class="flex items-center justify-between py-1.5 px-2 bg-neutral-50 rounded text-xs">
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
                        <p x-show="logRules.length === 0" class="text-xs text-neutral-500 italic">No rules — add a pattern
                            to begin.</p>
                    </div>
                </div>

                <!-- Quick Filters -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-4">
                    <h3 class="text-sm font-semibold text-neutral-900 mb-3 uppercase tracking-wide">Filters</h3>
                    <div class="space-y-3">
                        <div>
                            <label class="block text-xs font-medium text-neutral-600 mb-1">Search</label>
                            <input x-model="filterSearch" type="text" placeholder="Event name or class..."
                                class="w-full text-sm rounded-md border-neutral-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-neutral-600 mb-1">Event Class</label>
                            <input x-model="filterClass" type="text" placeholder="Filter by class..."
                                class="w-full text-sm rounded-md border-neutral-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main: Log Table -->
            <div class="lg:col-span-3">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <table class="min-w-full divide-y divide-neutral-200">
                        <thead class="bg-neutral-50">
                            <tr>
                                <th class="w-10 px-2 py-3">
                                    <span class="sr-only">Select</span>
                                </th>
                                <th
                                    class="px-4 py-3 text-left text-xs font-medium text-neutral-500 uppercase tracking-wider">
                                    Time</th>
                                <th
                                    class="px-4 py-3 text-left text-xs font-medium text-neutral-500 uppercase tracking-wider">
                                    Event</th>
                                <th
                                    class="px-4 py-3 text-left text-xs font-medium text-neutral-500 uppercase tracking-wider">
                                    Class</th>
                                <th
                                    class="px-4 py-3 text-right text-xs font-medium text-neutral-500 uppercase tracking-wider">
                                    Action</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-neutral-200">
                            @forelse($logs as $log)
                                <tr class="hover:bg-primary-50 cursor-pointer transition-colors"
                                    @click="loadDetail({{ $log->id }})"
                                    @mouseenter="loadDetail({{ $log->id }})">
                                    <td class="px-2 py-3 text-center" @click.stop>
                                        <input type="checkbox" :checked="isSelected({{ $log->id }})"
                                            @change="toggleSelect({{ $log->id }})"
                                            class="rounded border-neutral-300 text-primary-600 focus:ring-primary-500">
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-sm text-neutral-500">
                                        {{ $log->fired_at->diffForHumans() }}
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <span class="text-sm font-medium text-neutral-900">{{ $log->event_name }}</span>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <code
                                            class="text-xs font-mono text-neutral-500">{{ Str::limit($log->event_class, 50) }}</code>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-right">
                                        <button @click.stop="loadDetail({{ $log->id }})"
                                            class="text-primary-600 hover:text-primary-800 text-xs font-medium">
                                            Inspect
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-4 py-12 text-center text-sm text-neutral-500 italic">
                                        No log entries yet. Start recording to capture events.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>

                    @if ($logs->hasPages())
                        <div class="px-4 py-3 border-t border-neutral-200">
                            {{ $logs->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Detail Slide Panel -->
        @include('middleman::components.event-detail-panel')
    </div>
@endsection
