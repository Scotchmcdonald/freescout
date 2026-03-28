@extends('middleman::layouts.master')

@section('module-content')
    <div x-data="marshalApp()" x-init="init()">
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
                    immediately or hold in the intercept queue. Model parameters offer async search; enums render as dropdowns.
                </p>
            </div>
        </div>

        {{-- Step 1: Select Event --}}
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 mb-6">
            <div class="flex items-center mb-4">
                <span class="flex items-center justify-center w-8 h-8 rounded-full bg-primary-100 text-primary-700 text-sm font-bold mr-3">1</span>
                <h3 class="text-lg font-medium text-neutral-900">Select Event Type</h3>
            </div>

            <div class="max-w-lg relative">
                <label for="event-search" class="block text-sm font-medium text-neutral-700 mb-1">Event Class</label>
                <input type="text" id="event-search"
                    x-model="eventSearch"
                    @input="filterEvents()"
                    @focus="showEventDropdown = true"
                    @click.away="showEventDropdown = false"
                    :placeholder="selectedEventClass || 'Search events by name, class, or module...'"
                    class="w-full rounded-md border-neutral-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 text-sm"
                    autocomplete="off">

                {{-- Module filter chips --}}
                <div class="flex flex-wrap gap-1 mt-2" x-show="modules.length > 1">
                    <button type="button"
                        @click="moduleFilter = ''; filterEvents()"
                        :class="moduleFilter === '' ? 'bg-primary-100 text-primary-800 border-primary-300' : 'bg-neutral-50 text-neutral-600 border-neutral-200'"
                        class="px-2 py-0.5 text-xs rounded-full border transition-colors">
                        All
                    </button>
                    <template x-for="mod in modules" :key="mod">
                        <button type="button"
                            @click="moduleFilter = mod; filterEvents()"
                            :class="moduleFilter === mod ? 'bg-primary-100 text-primary-800 border-primary-300' : 'bg-neutral-50 text-neutral-600 border-neutral-200'"
                            class="px-2 py-0.5 text-xs rounded-full border transition-colors"
                            x-text="mod">
                        </button>
                    </template>
                </div>

                {{-- Dropdown --}}
                <div x-show="showEventDropdown && filteredEvents.length > 0" x-transition
                    class="absolute z-20 w-full mt-1 bg-white border border-neutral-200 rounded-lg shadow-lg max-h-64 overflow-y-auto">
                    <template x-for="evt in filteredEvents" :key="evt.class">
                        <button type="button"
                            @click="selectEvent(evt)"
                            class="w-full text-left px-4 py-2 text-sm hover:bg-primary-50 transition-colors border-b border-neutral-50 last:border-0">
                            <div class="flex justify-between items-center">
                                <div>
                                    <span class="font-medium text-neutral-900" x-text="evt.name"></span>
                                    <span class="text-xs text-neutral-400 ml-1" x-text="evt.module || 'App'"></span>
                                </div>
                                <span class="text-xs text-neutral-400" x-text="(evt.listener_count || 0) + ' listeners'"></span>
                            </div>
                            <div class="text-xs text-neutral-500 mt-0.5 font-mono truncate" x-text="evt.class"></div>
                        </button>
                    </template>
                </div>
            </div>

            {{-- Selected event badge --}}
            <div x-show="selectedEventClass" class="mt-3 inline-flex items-center bg-primary-50 text-primary-800 px-3 py-1.5 rounded-lg text-sm">
                <svg class="w-4 h-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z" />
                </svg>
                <span class="font-mono text-xs" x-text="selectedEventClass"></span>
                <button type="button" @click="clearSelection()" class="ml-2 text-primary-400 hover:text-primary-600">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>

        {{-- Step 2: Configure Parameters --}}
        <div x-show="selectedEventClass" x-transition class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 mb-6">
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center">
                    <span class="flex items-center justify-center w-8 h-8 rounded-full bg-primary-100 text-primary-700 text-sm font-bold mr-3">2</span>
                    <h3 class="text-lg font-medium text-neutral-900">Configure Parameters</h3>
                </div>
                {{-- Preset dropdown --}}
                <div x-show="presets.length > 0" class="relative" x-data="{ showPresets: false }">
                    <button type="button" @click="showPresets = !showPresets"
                        class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-neutral-600 bg-neutral-50 border border-neutral-200 rounded-md hover:bg-neutral-100 transition-colors">
                        <svg class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z" />
                        </svg>
                        Load Preset
                    </button>
                    <div x-show="showPresets" @click.away="showPresets = false" x-transition
                        class="absolute right-0 z-10 mt-1 w-56 bg-white border border-neutral-200 rounded-lg shadow-lg">
                        <template x-for="preset in presets" :key="preset.id">
                            <div class="flex items-center justify-between px-3 py-2 hover:bg-neutral-50 transition-colors">
                                <button type="button" @click="loadPreset(preset); showPresets = false"
                                    class="text-sm text-neutral-700 hover:text-primary-600 truncate flex-1 text-left" x-text="preset.name"></button>
                                <button type="button" @click.stop="deletePreset(preset.id)"
                                    class="text-neutral-400 hover:text-danger-500 p-1 ml-2 flex-shrink-0">
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                </button>
                            </div>
                        </template>
                    </div>
                </div>
            </div>

            {{-- Tab Switch: Single / Batch --}}
            <div class="border-b border-neutral-200 mb-6">
                <nav class="-mb-px flex space-x-8" aria-label="Mode">
                    <button type="button" @click="tab = 'single'"
                        :class="tab === 'single' ? 'border-primary-500 text-primary-600' : 'border-transparent text-neutral-500 hover:text-neutral-700 hover:border-neutral-300'"
                        class="whitespace-nowrap py-3 px-1 border-b-2 font-medium text-sm">
                        Single Event
                    </button>
                    <button type="button" @click="tab = 'batch'"
                        :class="tab === 'batch' ? 'border-primary-500 text-primary-600' : 'border-transparent text-neutral-500 hover:text-neutral-700 hover:border-neutral-300'"
                        class="whitespace-nowrap py-3 px-1 border-b-2 font-medium text-sm">
                        Batch (JSON)
                    </button>
                </nav>
            </div>

            {{-- Single Event Form (Dynamic Reflection-Based) --}}
            <div x-show="tab === 'single'" class="space-y-5">
                <template x-if="loadingParams">
                    <div class="flex items-center space-x-2 text-neutral-400 text-sm">
                        <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                        <span>Loading parameters...</span>
                    </div>
                </template>

                <template x-if="!loadingParams && parameters.length === 0">
                    <p class="text-sm text-neutral-500 italic">This event has no constructor parameters.</p>
                </template>

                <template x-for="(param, paramIdx) in parameters" :key="param.name">
                    <div class="max-w-lg">
                        <label class="block text-sm font-medium text-neutral-700 mb-1">
                            <span x-text="param.name"></span>
                            <span class="text-xs text-neutral-400 ml-1" x-text="'(' + param.type + ')'"></span>
                            <span x-show="param.required" class="text-danger-500 ml-0.5">*</span>
                        </label>

                        {{-- Enum: Dropdown with cases --}}
                        <template x-if="param.is_enum">
                            <select x-model="formValues[param.name]"
                                class="w-full rounded-md border-neutral-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 text-sm">
                                <option value="">Select...</option>
                                <template x-for="c in (param.enum_cases || [])" :key="c.value">
                                    <option :value="c.value" x-text="c.name + ' (' + c.value + ')'"></option>
                                </template>
                            </select>
                        </template>

                        {{-- Model: Async-searchable select --}}
                        <template x-if="param.is_model && !param.is_enum">
                            <div class="relative" x-data="modelSearchField()" x-init="initParam(param)">
                                <input type="text" x-model="searchText"
                                    @input.debounce.300ms="doSearch()"
                                    @focus="showResults = true"
                                    @click.away="showResults = false"
                                    :placeholder="selectedLabel || 'Search by ID, name, or email...'"
                                    class="w-full rounded-md border-neutral-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 text-sm"
                                    autocomplete="off">
                                <div class="absolute right-2 top-2">
                                    <svg x-show="searching" class="animate-spin h-4 w-4 text-neutral-400" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                    </svg>
                                </div>
                                <div x-show="showResults && results.length > 0" x-transition
                                    class="absolute z-10 w-full mt-1 bg-white border border-neutral-200 rounded-lg shadow-lg max-h-48 overflow-y-auto">
                                    <template x-for="r in results" :key="r.id">
                                        <button type="button" @click="pickResult(r)"
                                            class="w-full text-left px-3 py-2 text-sm hover:bg-primary-50 transition-colors border-b border-neutral-50 last:border-0"
                                            x-text="r.label"></button>
                                    </template>
                                </div>
                                <div x-show="showResults && !searching && searchText.length > 0 && results.length === 0"
                                    class="absolute z-10 w-full mt-1 bg-white border border-neutral-200 rounded-lg shadow-lg p-3 text-sm text-neutral-500">
                                    No results found
                                </div>
                            </div>
                        </template>

                        {{-- Boolean: Toggle --}}
                        <template x-if="(param.type === 'bool' || param.type === 'boolean') && !param.is_model && !param.is_enum">
                            <label class="relative inline-flex items-center cursor-pointer mt-1">
                                <input type="checkbox" x-model="formValues[param.name]" class="sr-only peer">
                                <div class="w-9 h-5 bg-neutral-200 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-primary-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-neutral-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-primary-600"></div>
                                <span class="ml-2 text-sm text-neutral-600" x-text="formValues[param.name] ? 'true' : 'false'"></span>
                            </label>
                        </template>

                        {{-- Numeric: Number input --}}
                        <template x-if="(param.type === 'int' || param.type === 'integer' || param.type === 'float' || param.type === 'double') && !param.is_model && !param.is_enum">
                            <input type="number" x-model="formValues[param.name]"
                                :step="param.type === 'float' || param.type === 'double' ? '0.01' : '1'"
                                :placeholder="'Enter ' + param.name"
                                class="w-full rounded-md border-neutral-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 text-sm">
                        </template>

                        {{-- String/mixed: Text input --}}
                        <template x-if="!param.is_model && !param.is_enum && param.type !== 'bool' && param.type !== 'boolean' && param.type !== 'int' && param.type !== 'integer' && param.type !== 'float' && param.type !== 'double'">
                            <input type="text" x-model="formValues[param.name]"
                                :placeholder="'Enter ' + param.name"
                                class="w-full rounded-md border-neutral-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 text-sm">
                        </template>
                    </div>
                </template>
            </div>

            {{-- Batch JSON --}}
            <div x-show="tab === 'batch'" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-neutral-700 mb-1">Events JSON Array</label>
                    <textarea x-model="batchJson" rows="12"
                        placeholder='[{"user_id": 1, "amount": 100}, {"user_id": 2, "amount": 200}]'
                        class="w-full text-sm font-mono rounded-md border-neutral-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 bg-neutral-50"></textarea>
                    <p class="mt-1 text-xs text-neutral-500">Each object in the array maps to the event constructor parameters. Max 100 items.</p>
                </div>
            </div>
        </div>

        {{-- Step 3: Dispatch --}}
        <div x-show="selectedEventClass" x-transition class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 mb-6">
            <div class="flex items-center mb-4">
                <span class="flex items-center justify-center w-8 h-8 rounded-full bg-primary-100 text-primary-700 text-sm font-bold mr-3">3</span>
                <h3 class="text-lg font-medium text-neutral-900">Dispatch</h3>
            </div>

            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div class="flex items-center space-x-4">
                    <label class="inline-flex items-center">
                        <input type="checkbox" x-model="holdInQueue"
                            class="rounded border-neutral-300 text-warning-600 focus:ring-warning-500">
                        <span class="ml-2 text-sm text-neutral-700">Hold in Intercept Queue</span>
                    </label>
                </div>

                <div class="flex items-center space-x-3">
                    {{-- Save Preset --}}
                    <div x-show="tab === 'single'" class="relative" x-data="{ showSavePreset: false, presetName: '' }">
                        <button type="button" @click="showSavePreset = !showSavePreset"
                            class="inline-flex items-center px-3 py-2 text-xs font-medium text-neutral-600 bg-neutral-50 border border-neutral-200 rounded-md hover:bg-neutral-100 transition-colors">
                            <svg class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z" />
                            </svg>
                            Save Preset
                        </button>
                        <div x-show="showSavePreset" @click.away="showSavePreset = false" x-transition
                            class="absolute right-0 z-10 mt-1 w-64 bg-white border border-neutral-200 rounded-lg shadow-lg p-3">
                            <label class="block text-xs font-medium text-neutral-700 mb-1">Preset Name</label>
                            <input type="text" x-model="presetName" placeholder="e.g. Happy Path User"
                                class="w-full text-sm rounded-md border-neutral-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 mb-2">
                            <button type="button" @click="$data.savePreset(presetName); showSavePreset = false; presetName = ''"
                                :disabled="!presetName.trim()"
                                class="w-full px-3 py-1.5 text-xs font-medium text-white bg-primary-600 rounded-md hover:bg-primary-700 disabled:opacity-50 transition-colors">
                                Save
                            </button>
                        </div>
                    </div>

                    {{-- Fire / Batch Button --}}
                    <template x-if="tab === 'single'">
                        <button @click="fireSingle()" :disabled="submitting"
                            :class="holdInQueue ? 'bg-warning-600 hover:bg-warning-700 focus:ring-warning-500' : 'bg-success-600 hover:bg-success-700 focus:ring-success-500'"
                            class="inline-flex items-center px-6 py-2.5 border border-transparent rounded-md font-semibold text-sm text-white uppercase tracking-widest focus:outline-none focus:ring-2 focus:ring-offset-2 transition ease-in-out duration-150 disabled:opacity-50">
                            <svg x-show="submitting" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                            </svg>
                            <span x-text="holdInQueue ? 'Hold in Queue' : 'Fire Event'"></span>
                        </button>
                    </template>
                    <template x-if="tab === 'batch'">
                        <button @click="fireBatch()" :disabled="submitting"
                            :class="holdInQueue ? 'bg-warning-600 hover:bg-warning-700 focus:ring-warning-500' : 'bg-success-600 hover:bg-success-700 focus:ring-success-500'"
                            class="inline-flex items-center px-6 py-2.5 border border-transparent rounded-md font-semibold text-sm text-white uppercase tracking-widest focus:outline-none focus:ring-2 focus:ring-offset-2 transition ease-in-out duration-150 disabled:opacity-50">
                            <svg x-show="submitting" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                            </svg>
                            <span x-text="holdInQueue ? 'Hold Batch in Queue' : 'Fire Batch'"></span>
                        </button>
                    </template>
                </div>
            </div>
        </div>

        {{-- Result --}}
        <template x-if="result">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-medium text-neutral-900 mb-3">Result</h3>

                <template x-if="result.error">
                    <div class="rounded-lg bg-danger-50 border border-danger-200 p-4">
                        <p class="text-sm text-danger-700" x-text="result.error"></p>
                        <p x-show="result.message" class="mt-1 text-xs text-danger-600" x-text="result.message"></p>
                    </div>
                </template>

                <template x-if="result.success === true && result.action">
                    <div class="rounded-lg bg-success-50 border border-success-200 p-4">
                        <p class="text-sm text-success-700">
                            Event <span x-text="result.action" class="font-semibold"></span> successfully.
                        </p>
                    </div>
                </template>

                <template x-if="typeof result.success === 'number' && result.failed !== undefined">
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

    <script>
        function marshalApp() {
            return {
                allEvents: @json($availableEvents),
                filteredEvents: [],
                modules: [],
                moduleFilter: '',
                eventSearch: '',
                showEventDropdown: false,
                selectedEventClass: '',
                parameters: [],
                presets: [],
                formValues: {},
                tab: 'single',
                batchJson: '[\n  {}\n]',
                holdInQueue: false,
                submitting: false,
                loadingParams: false,
                result: null,

                init() {
                    const mods = new Set();
                    this.allEvents.forEach(e => { if (e.module) mods.add(e.module); });
                    this.modules = [...mods].sort();
                    this.filteredEvents = [...this.allEvents];
                },

                filterEvents() {
                    const q = this.eventSearch.toLowerCase();
                    this.filteredEvents = this.allEvents.filter(e => {
                        const matchesModule = !this.moduleFilter || e.module === this.moduleFilter;
                        const matchesSearch = !q
                            || e.name.toLowerCase().includes(q)
                            || e.class.toLowerCase().includes(q)
                            || (e.module || '').toLowerCase().includes(q);
                        return matchesModule && matchesSearch;
                    });
                },

                async selectEvent(evt) {
                    this.selectedEventClass = evt.class;
                    this.showEventDropdown = false;
                    this.eventSearch = '';
                    this.result = null;
                    await this.loadParameters();
                },

                clearSelection() {
                    this.selectedEventClass = '';
                    this.parameters = [];
                    this.presets = [];
                    this.formValues = {};
                    this.result = null;
                },

                async loadParameters() {
                    if (!this.selectedEventClass) { this.parameters = []; this.presets = []; return; }
                    this.loadingParams = true;
                    try {
                        const res = await fetch(`{{ route('middleman.marshal.parameters') }}?event_class=${encodeURIComponent(this.selectedEventClass)}`);
                        const data = await res.json();
                        this.parameters = data.parameters || [];
                        this.presets = data.presets || [];
                        this.formValues = {};
                        this.parameters.forEach(p => {
                            this.formValues[p.name] = p.default ?? '';
                        });
                    } catch (e) {
                        this.parameters = [];
                        this.presets = [];
                    } finally {
                        this.loadingParams = false;
                    }
                },

                loadPreset(preset) {
                    if (preset.payload) {
                        this.formValues = { ...this.formValues, ...preset.payload };
                    }
                },

                async savePreset(name) {
                    if (!name || !name.trim() || !this.selectedEventClass) return;
                    try {
                        const res = await fetch('{{ route('middleman.marshal.presets.save') }}', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                            body: JSON.stringify({ event_class: this.selectedEventClass, name: name.trim(), payload: this.formValues })
                        });
                        const data = await res.json();
                        if (data.success) {
                            this.presets.push(data.preset);
                        }
                    } catch (e) { /* absorb */ }
                },

                async deletePreset(id) {
                    try {
                        await fetch(`{{ url('/middleman/marshal/presets') }}/${id}`, {
                            method: 'DELETE',
                            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
                        });
                        this.presets = this.presets.filter(p => p.id !== id);
                    } catch (e) { /* absorb */ }
                },

                async fireSingle() {
                    if (!this.selectedEventClass) return;
                    this.submitting = true;
                    this.result = null;
                    try {
                        const res = await fetch('{{ route('middleman.marshal.fire') }}', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                            body: JSON.stringify({ event_class: this.selectedEventClass, payload: this.formValues, hold: this.holdInQueue })
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
                            body: JSON.stringify({ event_class: this.selectedEventClass, items, hold: this.holdInQueue })
                        });
                        this.result = await res.json();
                    } catch (e) { this.result = { error: 'Invalid JSON: ' + e.message }; } finally { this.submitting = false; }
                }
            };
        }

        function modelSearchField() {
            return {
                paramRef: null,
                searchText: '',
                results: [],
                searching: false,
                showResults: false,
                selectedLabel: '',

                initParam(param) {
                    this.paramRef = param;
                },

                async doSearch() {
                    if (!this.searchText || this.searchText.length < 1 || !this.paramRef) { this.results = []; return; }
                    this.searching = true;
                    try {
                        const url = `{{ route('middleman.marshal.search-model') }}?model_class=${encodeURIComponent(this.paramRef.model_class)}&query=${encodeURIComponent(this.searchText)}`;
                        const res = await fetch(url);
                        const data = await res.json();
                        this.results = data.results || [];
                    } catch (e) {
                        this.results = [];
                    } finally {
                        this.searching = false;
                    }
                },

                pickResult(r) {
                    this.selectedLabel = r.label;
                    this.searchText = r.label;
                    this.showResults = false;

                    // Walk the Alpine data stack to find the parent marshalApp scope
                    let el = this.$el;
                    while (el) {
                        const data = Alpine.$data(el);
                        if (data && data.formValues !== undefined && this.paramRef) {
                            data.formValues[this.paramRef.name] = r.id;
                            break;
                        }
                        el = el.parentElement;
                    }
                }
            };
        }
    </script>
@endsection
