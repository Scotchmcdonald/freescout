@extends('middleman::layouts.master')

@section('module-content')
    <div x-data="{
        tab: 'single',
        selectedEventClass: '',
        parameters: [],
        formValues: {},
        batchJson: '[\n  {}\n]',
        holdInQueue: false,
        submitting: false,
        result: null,
        async loadParameters() {
            if (!this.selectedEventClass) { this.parameters = []; return; }
            const res = await fetch(`{{ route('middleman.marshal.parameters') }}?event_class=${encodeURIComponent(this.selectedEventClass)}`);
            const data = await res.json();
            this.parameters = data.parameters || [];
            this.formValues = {};
            this.parameters.forEach(p => {
                this.formValues[p.name] = p.default ?? '';
            });
        },
        async fireSingle() {
            if (!this.selectedEventClass) return;
            this.submitting = true;
            this.result = null;
            try {
                const res = await fetch('{{ route('middleman.marshal.fire') }}', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                    body: JSON.stringify({
                        event_class: this.selectedEventClass,
                        payload: this.formValues,
                        hold: this.holdInQueue
                    })
                });
                this.result = await res.json();
            } catch (e) { this.result = { error: e.message }; } finally { this.submitting = false; }
        },
        async fireBatch() {
            if (!this.selectedEventClass) return;
            this.submitting = true;
            this.result = null;
            try {
                const items = JSON.parse(this.batchJson);
                const res = await fetch('{{ route('middleman.marshal.batch') }}', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                    body: JSON.stringify({
                        event_class: this.selectedEventClass,
                        items,
                        hold: this.holdInQueue
                    })
                });
                this.result = await res.json();
            } catch (e) { this.result = { error: 'Invalid JSON: ' + e.message }; } finally { this.submitting = false; }
        },
        getInputType(type) {
            switch (type) {
                case 'int':
                case 'float':
                case 'integer':
                    return 'number';
                case 'bool':
                case 'boolean':
                    return 'checkbox';
                default:
                    return 'text';
            }
        }
    }">
        <div class="flex justify-between items-center mb-6">
            <h2 class="font-semibold text-xl text-neutral-800 leading-tight">Event Marshalling</h2>
        </div>

        <div class="mb-6 rounded-lg bg-info-50 border border-info-200 p-4">
            <div class="flex items-start">
                <svg class="h-5 w-5 text-info-500 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                    stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <p class="ml-3 text-sm text-info-700">
                    Create and dispatch events manually. Select an event type, fill in the constructor parameters, and fire
                    immediately or hold in the intercept queue.
                </p>
            </div>
        </div>

        <!-- Step 1: Select Event -->
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 mb-6">
            <div class="flex items-center mb-4">
                <span
                    class="flex items-center justify-center w-8 h-8 rounded-full bg-primary-100 text-primary-700 text-sm font-bold mr-3">1</span>
                <h3 class="text-lg font-medium text-neutral-900">Select Event Type</h3>
            </div>

            <div class="max-w-lg">
                <label for="event-select" class="block text-sm font-medium text-neutral-700 mb-1">Event Class</label>
                <select x-model="selectedEventClass" @change="loadParameters()" id="event-select"
                    class="w-full rounded-md border-neutral-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 text-sm">
                    <option value="">Choose an event...</option>
                    @foreach ($availableEvents as $event)
                        <option value="{{ $event['class'] }}">{{ $event['name'] }} — {{ $event['class'] }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <!-- Step 2: Configure Parameters -->
        <div x-show="selectedEventClass" x-transition class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 mb-6">
            <div class="flex items-center mb-4">
                <span
                    class="flex items-center justify-center w-8 h-8 rounded-full bg-primary-100 text-primary-700 text-sm font-bold mr-3">2</span>
                <h3 class="text-lg font-medium text-neutral-900">Configure Parameters</h3>
            </div>

            <!-- Tab Switch: Single / Batch -->
            <div class="border-b border-neutral-200 mb-6">
                <nav class="-mb-px flex space-x-8" aria-label="Mode">
                    <button type="button" @click="tab = 'single'"
                        :class="tab === 'single' ? 'border-primary-500 text-primary-600' :
                            'border-transparent text-neutral-500 hover:text-neutral-700 hover:border-neutral-300'"
                        class="whitespace-nowrap py-3 px-1 border-b-2 font-medium text-sm">
                        Single Event
                    </button>
                    <button type="button" @click="tab = 'batch'"
                        :class="tab === 'batch' ? 'border-primary-500 text-primary-600' :
                            'border-transparent text-neutral-500 hover:text-neutral-700 hover:border-neutral-300'"
                        class="whitespace-nowrap py-3 px-1 border-b-2 font-medium text-sm">
                        Batch (JSON)
                    </button>
                </nav>
            </div>

            <!-- Single Event Form -->
            <div x-show="tab === 'single'" class="space-y-4">
                <template x-if="parameters.length === 0">
                    <p class="text-sm text-neutral-500 italic">This event has no constructor parameters.</p>
                </template>
                <template x-for="param in parameters" :key="param.name">
                    <div class="max-w-lg">
                        <label class="block text-sm font-medium text-neutral-700 mb-1">
                            <span x-text="param.name"></span>
                            <span class="text-xs text-neutral-400 ml-1" x-text="'(' + param.type + ')'"></span>
                            <span x-show="param.required" class="text-danger-500 ml-0.5">*</span>
                            <span x-show="param.is_model" class="text-xs text-primary-600 ml-1">(Model ID)</span>
                        </label>
                        <template x-if="param.type === 'bool' || param.type === 'boolean'">
                            <input type="checkbox" x-model="formValues[param.name]"
                                class="rounded border-neutral-300 text-primary-600 focus:ring-primary-500">
                        </template>
                        <template x-if="param.type !== 'bool' && param.type !== 'boolean'">
                            <input :type="getInputType(param.type)" x-model="formValues[param.name]"
                                :placeholder="param.is_model ? 'Enter model ID' : 'Enter ' + param.name"
                                class="w-full rounded-md border-neutral-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 text-sm">
                        </template>
                    </div>
                </template>
            </div>

            <!-- Batch JSON -->
            <div x-show="tab === 'batch'" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-neutral-700 mb-1">Events JSON Array</label>
                    <textarea x-model="batchJson" rows="12"
                        placeholder='[{"user_id": 1, "amount": 100}, {"user_id": 2, "amount": 200}]'
                        class="w-full text-sm font-mono rounded-md border-neutral-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 bg-neutral-50"></textarea>
                    <p class="mt-1 text-xs text-neutral-500">Each object in the array maps to the event constructor
                        parameters.</p>
                </div>
            </div>
        </div>

        <!-- Step 3: Fire -->
        <div x-show="selectedEventClass" x-transition class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 mb-6">
            <div class="flex items-center mb-4">
                <span
                    class="flex items-center justify-center w-8 h-8 rounded-full bg-primary-100 text-primary-700 text-sm font-bold mr-3">3</span>
                <h3 class="text-lg font-medium text-neutral-900">Dispatch</h3>
            </div>

            <div class="flex items-center space-x-4 mb-4">
                <label class="inline-flex items-center">
                    <input type="checkbox" x-model="holdInQueue"
                        class="rounded border-neutral-300 text-warning-600 focus:ring-warning-500">
                    <span class="ml-2 text-sm text-neutral-700">Hold in Intercept Queue (don't fire immediately)</span>
                </label>
            </div>

            <div class="flex space-x-3">
                <template x-if="tab === 'single'">
                    <button @click="fireSingle()" :disabled="submitting"
                        :class="holdInQueue ? 'bg-warning-600 hover:bg-warning-700 focus:ring-warning-500' :
                            'bg-success-600 hover:bg-success-700 focus:ring-success-500'"
                        class="inline-flex items-center px-6 py-2.5 border border-transparent rounded-md font-semibold text-sm text-white uppercase tracking-widest focus:outline-none focus:ring-2 focus:ring-offset-2 transition ease-in-out duration-150 disabled:opacity-50">
                        <svg x-show="submitting" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none"
                            viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z">
                            </path>
                        </svg>
                        <span x-text="holdInQueue ? 'Hold in Queue' : 'Fire Event'"></span>
                    </button>
                </template>
                <template x-if="tab === 'batch'">
                    <button @click="fireBatch()" :disabled="submitting"
                        :class="holdInQueue ? 'bg-warning-600 hover:bg-warning-700 focus:ring-warning-500' :
                            'bg-success-600 hover:bg-success-700 focus:ring-success-500'"
                        class="inline-flex items-center px-6 py-2.5 border border-transparent rounded-md font-semibold text-sm text-white uppercase tracking-widest focus:outline-none focus:ring-2 focus:ring-offset-2 transition ease-in-out duration-150 disabled:opacity-50">
                        <svg x-show="submitting" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none"
                            viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z">
                            </path>
                        </svg>
                        <span x-text="holdInQueue ? 'Hold Batch in Queue' : 'Fire Batch'"></span>
                    </button>
                </template>
            </div>
        </div>

        <!-- Result -->
        <template x-if="result">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-medium text-neutral-900 mb-3">Result</h3>

                <template x-if="result.error">
                    <div class="rounded-lg bg-danger-50 border border-danger-200 p-4">
                        <p class="text-sm text-danger-700" x-text="result.error"></p>
                        <p x-show="result.message" class="mt-1 text-xs text-danger-600" x-text="result.message"></p>
                    </div>
                </template>

                <template x-if="result.success">
                    <div class="rounded-lg bg-success-50 border border-success-200 p-4">
                        <p class="text-sm text-success-700">
                            Event <span x-text="result.action" class="font-semibold"></span> successfully.
                        </p>
                    </div>
                </template>

                <template x-if="result.success !== undefined && result.failed !== undefined">
                    <div class="mt-3 grid grid-cols-2 gap-4">
                        <div class="bg-success-50 rounded-lg p-3 text-center">
                            <div class="text-2xl font-bold text-success-700" x-text="result.success"></div>
                            <div class="text-xs text-success-600">Succeeded</div>
                        </div>
                        <div class="bg-danger-50 rounded-lg p-3 text-center">
                            <div class="text-2xl font-bold text-danger-700" x-text="result.failed"></div>
                            <div class="text-xs text-danger-600">Failed</div>
                        </div>
                    </div>
                </template>

                <template x-if="result.errors && result.errors.length > 0">
                    <div class="mt-3 space-y-2">
                        <h4 class="text-sm font-medium text-danger-700">Errors:</h4>
                        <template x-for="err in result.errors" :key="err.index">
                            <div class="text-xs text-danger-600">
                                Item #<span x-text="err.index"></span>: <span x-text="err.message"></span>
                            </div>
                        </template>
                    </div>
                </template>
            </div>
        </template>
    </div>
@endsection
