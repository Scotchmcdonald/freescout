<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl" style="color: var(--theme-text-main)">
                {{ __('Resilience Dashboard') }}
            </h2>
            <a href="{{ route('admin.resilience.events-audit') }}"
                class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                {{ __('View Audit Log') }}
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">

            {{-- Circuit Breaker Section --}}
            <section>
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-medium" style="color: var(--theme-text-main)">Circuit Breakers</h3>
                </div>

                {{-- Emergency Alert Zone --}}
                @if ($openCircuits > 0)
                    <div class="border-l-4 p-4 rounded-lg mb-6"
                        style="background-color: var(--theme-status-error-bg); border-color: var(--theme-status-error-text);">
                        <div class="flex items-start">
                            <div class="flex-shrink-0">
                                <svg class="h-6 w-6" style="color: var(--theme-status-error-text)" fill="none"
                                    stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                </svg>
                            </div>
                            <div class="ml-3 flex-1">
                                <h3 class="text-lg font-semibold" style="color: var(--theme-status-error-text)">
                                    ⚠️ {{ $openCircuits }} Service(s) Degraded
                                </h3>
                                <p class="mt-1 text-sm" style="color: var(--theme-status-error-text)">
                                    External integrations are experiencing failures. Manual intervention may be
                                    required.
                                </p>
                            </div>
                        </div>
                    </div>
                @endif

                {{-- Live Status Cards --}}
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    @foreach ($circuitBreakers as $service)
                        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="text-lg font-semibold" style="color: var(--theme-text-main)">
                                    {{ $service['name'] }}
                                </h3>
                                @php
                                    $badgeColor = match ($service['state']) {
                                        'closed' => 'bg-green-100 text-green-800',
                                        'half_open' => 'bg-yellow-100 text-yellow-800',
                                        'open' => 'bg-red-100 text-red-800',
                                        default => 'bg-gray-100 text-gray-800',
                                    };
                                    $badgeIcon = match ($service['state']) {
                                        'closed' => '🟢',
                                        'half_open' => '🟡',
                                        'open' => '🔴',
                                        default => '⚪',
                                    };
                                    $badgeText = ucfirst(str_replace('_', ' ', $service['state']));
                                @endphp
                                <span
                                    class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $badgeColor }}">
                                    {{ $badgeIcon }} {{ $badgeText }}
                                </span>
                            </div>

                            <dl class="space-y-2 text-sm" style="color: var(--theme-text-secondary)">
                                <div class="flex justify-between">
                                    <dt>Failures detected:</dt>
                                    <dd class="font-medium text-red-600">{{ $service['failure_count'] }}</dd>
                                </div>
                                <div class="flex justify-between">
                                    <dt>Last changed:</dt>
                                    <dd>{{ $service['last_checked_human'] }}</dd>
                                </div>
                                @if ($service['state'] !== 'closed')
                                    <div class="flex justify-between">
                                        <dt>Retry enabled:</dt>
                                        <dd>{{ $service['can_retry'] ? 'Yes' : 'Wait...' }}</dd>
                                    </div>
                                @endif
                            </dl>

                            @if ($service['state'] !== 'closed')
                                <div class="mt-4">
                                    <form method="POST"
                                        action="{{ route('admin.resilience.reset-circuit', $service['key']) }}">
                                        @csrf
                                        <button type="submit"
                                            class="w-full inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                            Reset Circuit
                                        </button>
                                    </form>
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </section>

            <hr style="border-color: var(--theme-border)">

            {{-- Rate Limiter Section --}}
            <section>
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-medium" style="color: var(--theme-text-main)">API Rate Limits</h3>
                </div>

                <div class="space-y-4">
                    @foreach ($rateLimits as $service)
                        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="text-lg font-semibold" style="color: var(--theme-text-main)">
                                    {{ $service['name'] }}
                                </h3>
                                <span class="text-sm" style="color: var(--theme-text-muted)">
                                    Resets in: <strong>{{ $service['reset_in_human'] }}</strong>
                                </span>
                            </div>

                            {{-- Progress Bar --}}
                            <div class="w-full mb-4">
                                <div class="flex justify-between mb-1">
                                    <span class="text-sm font-medium" style="color: var(--theme-text-main)">
                                        @if (is_numeric($service['limit']) && $service['limit'] > 0)
                                            {{ number_format($service['used']) }} /
                                            {{ number_format($service['limit']) }} requests
                                        @else
                                            {{ number_format($service['used']) }} requests across
                                            {{ number_format($service['key_count'] ?? 0) }} limiter key(s)
                                        @endif
                                    </span>
                                    <span class="text-sm font-medium" style="color: var(--theme-text-main)">
                                        @if (is_numeric($service['used_percent']))
                                            {{ $service['used_percent'] }}%
                                        @else
                                            N/A
                                        @endif
                                    </span>
                                </div>
                                <div class="w-full bg-gray-200 rounded-full h-3">
                                    @php
                                        $rateBarPercent = is_numeric($service['used_percent'])
                                            ? min(100, (float) $service['used_percent'])
                                            : min(100, (float) (($service['key_count'] ?? 0) * 10));
                                    @endphp
                                    <div class="h-3 rounded-full transition-all duration-300 {{ $service['color'] === 'danger'
                                        ? 'bg-red-600'
                                        : ($service['color'] === 'warning'
                                            ? 'bg-yellow-500'
                                            : 'bg-green-600') }}"
                                        style="width: {{ $rateBarPercent }}%">
                                    </div>
                                </div>
                            </div>

                            {{-- Warning Alerts --}}
                            @if (is_numeric($service['used_percent']) && $service['used_percent'] >= 90)
                                <div class="border-l-4 p-4"
                                    style="background-color: var(--theme-status-error-bg); border-color: var(--theme-status-error-text);">
                                    <div class="flex">
                                        <div class="flex-shrink-0">
                                            <svg class="h-5 w-5" style="color: var(--theme-status-error-text)"
                                                fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd"
                                                    d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                                                    clip-rule="evenodd" />
                                            </svg>
                                        </div>
                                        <div class="ml-3">
                                            <p class="text-sm" style="color: var(--theme-status-error-text)">
                                                Critical quota usage! Consider increasing limits or reducing demand.
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            @elseif(is_numeric($service['used_percent']) && $service['used_percent'] >= 70)
                                <div class="border-l-4 p-4"
                                    style="background-color: var(--theme-status-warning-bg); border-color: var(--theme-status-warning-text);">
                                    <div class="flex">
                                        <div class="flex-shrink-0">
                                            <svg class="h-5 w-5" style="color: var(--theme-status-warning-text)"
                                                fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd"
                                                    d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z"
                                                    clip-rule="evenodd" />
                                            </svg>
                                        </div>
                                        <div class="ml-3">
                                            <p class="text-sm" style="color: var(--theme-status-warning-text)">
                                                High usage detected. Monitor closely.
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </section>

            {{-- API Health (module-aware) --}}
            <section>
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-medium" style="color: var(--theme-text-main)">API Health</h3>
                    <span class="text-xs" style="color: var(--theme-text-secondary)">Installed-module integrations
                        only</span>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    @forelse ($apiHealthChecks as $api)
                        @php
                            $state = $api['circuit']['state'] ?? 'closed';
                            $stateBadge = match ($state) {
                                'open' => 'bg-red-100 text-red-800',
                                'half_open' => 'bg-yellow-100 text-yellow-800',
                                default => 'bg-green-100 text-green-800',
                            };
                            $stateLabel = ucfirst(str_replace('_', ' ', $state));
                        @endphp
                        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6" x-data="apiProbe('{{ $api['probe_url'] }}', '{{ csrf_token() }}')">
                            <div class="flex items-start justify-between gap-4 mb-4">
                                <div>
                                    <h4 class="text-lg font-semibold" style="color: var(--theme-text-main)">
                                        {{ $api['name'] }}
                                    </h4>
                                    <p class="text-xs mt-0.5" style="color: var(--theme-text-muted)">
                                        Module: {{ $api['module'] }}
                                    </p>
                                </div>
                                <span class="px-2 py-1 text-xs rounded-full font-semibold {{ $stateBadge }}">
                                    {{ $stateLabel }}
                                </span>
                            </div>

                            <p class="text-sm mb-4" style="color: var(--theme-text-secondary)">
                                {{ $api['description'] }}</p>

                            <dl class="grid grid-cols-2 gap-x-4 gap-y-2 text-sm mb-4"
                                style="color: var(--theme-text-secondary)">
                                <div>
                                    <dt class="text-xs uppercase tracking-wide"
                                        style="color: var(--theme-text-muted)">Failures</dt>
                                    <dd class="font-semibold" style="color: var(--theme-text-main)">
                                        {{ $api['circuit']['failure_count'] }}</dd>
                                </div>
                                <div>
                                    <dt class="text-xs uppercase tracking-wide"
                                        style="color: var(--theme-text-muted)">Limiter Usage</dt>
                                    <dd class="font-semibold" style="color: var(--theme-text-main)">
                                        @if (is_array($api['rate_limit']) && is_numeric($api['rate_limit']['used_percent']))
                                            {{ $api['rate_limit']['used_percent'] }}%
                                        @elseif(is_array($api['rate_limit']))
                                            {{ number_format($api['rate_limit']['used'] ?? 0) }} calls
                                        @else
                                            N/A
                                        @endif
                                    </dd>
                                </div>
                                <div>
                                    <dt class="text-xs uppercase tracking-wide"
                                        style="color: var(--theme-text-muted)">Limiter Reset</dt>
                                    <dd class="font-semibold" style="color: var(--theme-text-main)">
                                        {{ is_array($api['rate_limit']) ? $api['rate_limit']['reset_in_human'] ?? 'N/A' : 'N/A' }}
                                    </dd>
                                </div>
                                <div>
                                    <dt class="text-xs uppercase tracking-wide"
                                        style="color: var(--theme-text-muted)">Last Breaker Update</dt>
                                    <dd class="font-semibold" style="color: var(--theme-text-main)">
                                        {{ $api['circuit']['last_checked_human'] }}</dd>
                                </div>
                            </dl>

                            <div class="flex items-center gap-3 pt-3 border-t"
                                style="border-color: var(--theme-border)">
                                <button @click="run()" :disabled="running"
                                    class="inline-flex items-center gap-2 px-3 py-2 text-xs font-medium rounded-md disabled:opacity-60 disabled:cursor-not-allowed transition"
                                    style="background-color: var(--theme-primary); color: #fff;">
                                    <span x-show="running"
                                        class="relative flex h-3.5 w-3.5 items-center justify-center animate-pulse">
                                        <svg class="animate-spin h-3.5 w-3.5 text-white" fill="none"
                                            viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10"
                                                stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor"
                                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                            </path>
                                        </svg>
                                    </span>
                                    <span x-text="running ? 'Testing…' : 'Run Connectivity Test'"></span>
                                </button>

                                @if ($api['supports_deep_test'])
                                    <span class="text-xs px-2 py-1 rounded-md"
                                        style="color: var(--theme-status-info-text); background-color: var(--theme-status-info-bg);">
                                        Deep test available below
                                    </span>
                                @endif
                            </div>

                            <div x-show="result !== null" class="mt-3 text-xs rounded-md px-3 py-2"
                                :class="result?.ok ? 'bg-green-50 text-green-800' : 'bg-red-50 text-red-700'">
                                <div class="font-semibold" x-text="result?.ok ? 'Healthy' : 'Issue detected'"></div>
                                <div class="mt-0.5" x-text="result?.message"></div>
                                <div class="mt-0.5 text-[11px] opacity-70" x-show="result?.latency_ms"
                                    x-text="'Latency: ' + result?.latency_ms + 'ms'"></div>

                                <template x-if="Array.isArray(result?.details?.roles) && result.details.roles.length">
                                    <div class="mt-3 space-y-2 border-t pt-3"
                                        style="border-color: rgba(255,255,255,0.3);">
                                        <template x-for="role in result.details.roles" :key="role.role">
                                            <div class="rounded-md px-2 py-2"
                                                :class="role.ok ? 'bg-white/60 text-green-900' : 'bg-white/60 text-red-900'">
                                                <div class="flex items-center justify-between gap-3">
                                                    <span class="font-semibold"
                                                        x-text="role.label || role.role"></span>
                                                    <span class="text-[11px] opacity-70"
                                                        x-text="(role.latency_ms || 0) + 'ms'"></span>
                                                </div>
                                                <div class="mt-1 text-[11px] opacity-90" x-text="role.message"></div>
                                            </div>
                                        </template>
                                    </div>
                                </template>
                            </div>
                        </div>
                    @empty
                        <div class="col-span-2 bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 text-sm"
                            style="color: var(--theme-text-muted)">
                            No installed modules currently expose external API integrations for resilience testing.
                        </div>
                    @endforelse
                </div>
            </section>

            @if ($hasAction1Api)
                {{-- Action1 Deep Health --}}
                <section>
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-medium" style="color: var(--theme-text-main)">Action1 Deep Health</h3>
                        <span class="text-xs" style="color: var(--theme-text-secondary)">End-to-end sequential
                            connectivity
                            test</span>
                    </div>

                    {{-- Test / canary endpoint banner --}}
                    <div
                        class="mb-5 rounded-lg border px-4 py-3 flex items-start gap-3 text-sm {{ $action1TestProbe['configured'] ? 'border-blue-200 bg-blue-50' : 'border-amber-200 bg-amber-50' }}">
                        <div class="mt-0.5 text-base">{{ $action1TestProbe['configured'] ? '🖥' : '⚠️' }}</div>
                        <div>
                            <span
                                class="font-medium {{ $action1TestProbe['configured'] ? 'text-blue-800' : 'text-amber-800' }}">Test
                                endpoint:</span>
                            @if ($action1TestProbe['configured'])
                                <span class="font-mono text-blue-900">{{ $action1TestProbe['endpoint_name'] }}</span>
                                <span class="text-blue-700"> &mdash; {{ $action1TestProbe['group_name'] }}
                                    group</span>
                                <span class="ml-2 text-xs text-blue-600">(org:
                                    {{ $action1TestProbe['org_id'] }})</span>
                                <p class="mt-0.5 text-xs text-blue-600">The test will verify the endpoint,
                                    create/run/delete a canary <code>msp_dx_ApiTest</code> script end-to-end.</p>
                            @else
                                <span class="font-mono text-amber-900">{{ $action1TestProbe['endpoint_name'] }}</span>
                                <span class="text-amber-700"> &mdash; {{ $action1TestProbe['group_name'] }}
                                    group</span>
                                <p class="mt-0.5 text-xs text-amber-700">Set <code>ACTION1_TEST_ORG_ID</code> in
                                    <code>.env</code> to enable the sequential test.
                                </p>
                            @endif
                        </div>
                    </div>

                    {{-- Stepper card --}}
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6" x-data="action1Sequence({
                            sync: '{{ route('admin.resilience.action1-sequence', ['step' => 'sync']) }}',
                            manage_create: '{{ route('admin.resilience.action1-sequence', ['step' => 'manage_create']) }}',
                            run: '{{ route('admin.resilience.action1-sequence', ['step' => 'run']) }}',
                            run_status: '{{ route('admin.resilience.action1-sequence', ['step' => 'run_status']) }}',
                            manage_cleanup: '{{ route('admin.resilience.action1-sequence', ['step' => 'manage_cleanup']) }}'
                        },
                        '{{ csrf_token() }}',
                        {{ $action1TestProbe['configured'] ? 'true' : 'false' }}
                    )">

                        {{-- Smart Stepper (inline Alpine-driven) --}}
                        <div class="w-full py-4 mb-6">
                            <div class="flex items-center px-4">
                                <template x-for="(label, index) in stepNames" :key="index">
                                    <div class="flex items-center flex-1 last:flex-none">
                                        {{-- Node --}}
                                        <div class="relative flex flex-col items-center flex-none">
                                            <div class="rounded-full h-10 w-10 flex items-center justify-center border-2 shadow-sm transition-all duration-500"
                                                :style="stepNodeStyle(index + 1)">
                                                {{-- Checkmark --}}
                                                <template x-if="stepState(index + 1) === 'ok'">
                                                    <svg class="w-5 h-5 text-white" fill="none"
                                                        stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2" d="M5 13l4 4L19 7"></path>
                                                    </svg>
                                                </template>
                                                {{-- Error X --}}
                                                <template x-if="stepState(index + 1) === 'error'">
                                                    <svg class="w-5 h-5 text-white" fill="none"
                                                        stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                                    </svg>
                                                </template>
                                                {{-- Spinner (active) --}}
                                                <template x-if="stepState(index + 1) === 'active'">
                                                    <span
                                                        class="relative flex h-5 w-5 items-center justify-center animate-pulse">
                                                        <svg class="animate-spin h-4 w-4"
                                                            style="color: var(--theme-status-info-text);"
                                                            fill="none" viewBox="0 0 24 24">
                                                            <circle class="opacity-25" cx="12" cy="12"
                                                                r="10" stroke="currentColor" stroke-width="4">
                                                            </circle>
                                                            <path class="opacity-75" fill="currentColor"
                                                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                                            </path>
                                                        </svg>
                                                    </span>
                                                </template>
                                                {{-- Number (idle) --}}
                                                <template x-if="stepState(index + 1) === 'idle'">
                                                    <span class="font-bold text-sm font-mono"
                                                        x-text="index + 1"></span>
                                                </template>
                                            </div>
                                            <div class="absolute top-0 mt-12 w-28 text-center text-xs font-semibold uppercase tracking-wide transition-colors duration-300"
                                                :style="stepLabelStyle(index + 1)" x-text="label">
                                            </div>
                                        </div>
                                        {{-- Connector --}}
                                        <div x-show="index < stepNames.length - 1"
                                            class="flex-auto border-t-2 mx-3 transition-colors duration-500"
                                            :style="connectorStyle(index + 1)">
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>

                        {{-- Step result log --}}
                        <div class="mt-10 space-y-2 min-h-[4rem]">
                            <template x-for="(result, index) in stepResults" :key="index">
                                <div x-show="result !== null" class="text-xs rounded px-3 py-2"
                                    :class="result?.ok ? 'bg-green-50 text-green-800' : 'bg-red-50 text-red-700'">
                                    <div class="flex items-start gap-2">
                                        <span class="font-bold shrink-0" x-text="'Step ' + (index + 1) + ':'"></span>
                                        <span x-text="result?.message"></span>
                                        <span x-show="result?.latency_ms"
                                            class="ml-auto shrink-0 text-gray-400 font-mono"
                                            x-text="result?.latency_ms + 'ms'"></span>
                                    </div>
                                    <template x-if="result?.debug_payload">
                                        <details class="mt-2">
                                            <summary class="cursor-pointer opacity-60 hover:opacity-100 select-none">
                                                Dispatched payload (debug)</summary>
                                            <pre class="mt-1 text-[10px] bg-black/10 rounded p-2 overflow-x-auto whitespace-pre-wrap break-all"
                                                x-text="JSON.stringify(result.debug_payload, null, 2)"></pre>
                                        </details>
                                    </template>
                                    <template x-if="result?.phone_home_payload">
                                        <details class="mt-2" open>
                                            <summary class="cursor-pointer opacity-60 hover:opacity-100 select-none">
                                                Phone-home result</summary>
                                            <pre class="mt-1 text-[10px] bg-black/10 rounded p-2 overflow-x-auto whitespace-pre-wrap break-all"
                                                x-text="JSON.stringify(result.phone_home_payload, null, 2)"></pre>
                                        </details>
                                    </template>
                                </div>
                            </template>
                            <p x-show="currentStep === 0" class="text-sm text-center"
                                style="color: var(--theme-text-muted)">
                                Press <strong>Run Full Test</strong> to execute the end-to-end sequence.
                            </p>
                            <p x-show="allDone && overallOk" class="text-sm font-semibold text-green-700 text-center">
                                ✓
                                All 5 steps passed — Action1 API is fully operational.</p>
                            <p x-show="allDone && !overallOk" class="text-sm font-semibold text-red-600 text-center">✗
                                Test sequence failed — see step results above.</p>
                        </div>

                        {{-- Controls --}}
                        <div class="flex items-center gap-4 mt-6 pt-4 border-t"
                            style="border-color: var(--theme-border)">
                            <button @click="start()" :disabled="running || !configured"
                                class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium rounded-md disabled:opacity-50 disabled:cursor-not-allowed transition"
                                style="background-color: var(--theme-primary); color: #fff;">
                                <span x-show="running"
                                    class="relative flex h-4 w-4 items-center justify-center animate-pulse">
                                    <svg class="animate-spin h-3.5 w-3.5 text-white" fill="none"
                                        viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10"
                                            stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor"
                                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                        </path>
                                    </svg>
                                </span>
                                <span x-text="running ? 'Running…' : 'Run Full Test'"></span>
                            </button>
                            <button x-show="currentStep > 0 && !running" @click="reset()" class="text-sm underline"
                                style="color: var(--theme-text-muted)">Reset</button>
                            @if (!$action1TestProbe['configured'])
                                <span class="text-xs text-amber-600">⚠ Set ACTION1_TEST_ORG_ID in .env to enable</span>
                            @endif
                        </div>
                    </div>
                </section>
            @endif

        </div>
    </div>

    <script>
        function apiProbe(url, csrf) {
            return {
                running: false,
                result: null,

                async run() {
                    if (this.running) return;
                    this.running = true;

                    try {
                        const response = await fetch(url, {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': csrf,
                                'Accept': 'application/json',
                                'Content-Type': 'application/json',
                            },
                            body: JSON.stringify({}),
                        });

                        this.result = await response.json();
                    } catch (error) {
                        this.result = {
                            ok: false,
                            message: 'Network error: ' + (error?.message || 'unknown'),
                            latency_ms: 0,
                        };
                    } finally {
                        this.running = false;
                    }
                },
            };
        }

        function action1Sequence(urls, csrf, configured) {
            return {
                configured,
                running: false,
                currentStep: 0, // 0 = idle, 1–5 = active step, 6 = complete
                stepResults: [null, null, null, null, null],
                context: {}, // endpoint_id, script_id, callback_token, automation_id

                stepNames: [
                    'Verify Endpoint',
                    'Create Script',
                    'Run Script',
                    'Receive Result',
                    'Cleanup',
                ],

                get allDone() {
                    return this.currentStep === 6;
                },
                get overallOk() {
                    return this.stepResults.every(r => r === null || r?.ok);
                },

                stepState(num) {
                    if (this.currentStep === 0) return 'idle';
                    if (this.currentStep > num) return this.stepResults[num - 1]?.ok ? 'ok' : 'error';
                    if (this.currentStep === num) return this.running ? 'active' : (this.stepResults[num - 1]?.ok ? 'ok' :
                        'error');
                    return 'idle';
                },

                stepNodeStyle(num) {
                    const state = this.stepState(num);
                    const p = 'var(--theme-primary-600)';
                    const p50 = 'var(--theme-primary-50)';
                    const border = 'var(--theme-border)';
                    const muted = 'var(--theme-text-muted)';
                    const bg = 'var(--theme-bg-card)';
                    if (state === 'ok') return `background-color:${p};border-color:${p};color:#fff`;
                    if (state === 'error') return `background-color:#dc2626;border-color:#dc2626;color:#fff`;
                    if (state === 'active') return `border-color:${p};color:${p};background-color:${p50}`;
                    return `border-color:${border};color:${muted};background-color:${bg}`;
                },

                stepLabelStyle(num) {
                    const state = this.stepState(num);
                    if (state === 'ok') return 'color:var(--theme-primary-700)';
                    if (state === 'error') return 'color:#dc2626';
                    if (state === 'active') return 'color:var(--theme-primary-600)';
                    return 'color:var(--theme-text-muted)';
                },

                connectorStyle(num) {
                    const done = this.stepState(num) === 'ok';
                    return `border-color:${done ? 'var(--theme-primary-600)' : 'var(--theme-border)'}`;
                },

                async start() {
                    if (!this.configured) return;
                    this.running = true;
                    this.currentStep = 1;
                    this.stepResults = [null, null, null, null, null];
                    this.context = {};

                    // Step 1: Sync — find test endpoint
                    const s1 = await this.post(urls.sync, {});
                    this.stepResults[0] = s1;
                    if (!s1.ok) {
                        this.currentStep = 6;
                        this.running = false;
                        return;
                    }
                    this.context.endpoint_id = s1.endpoint_id;

                    // Step 2: Manage — (re)create msp_dx_ApiTest
                    this.currentStep = 2;
                    const s2 = await this.post(urls.manage_create, {});
                    this.stepResults[1] = s2;
                    if (!s2.ok) {
                        this.currentStep = 6;
                        this.running = false;
                        return;
                    }
                    this.context.script_id = s2.script_id;

                    // Step 3: Run — dispatch
                    this.currentStep = 3;
                    const s3 = await this.post(urls.run, {
                        endpoint_id: this.context.endpoint_id,
                        script_id: this.context.script_id,
                    });
                    this.stepResults[2] = s3;
                    if (!s3.ok) {
                        this.currentStep = 5;
                        this.stepResults[4] = await this.cleanup();
                        this.currentStep = 6;
                        this.running = false;
                        return;
                    }
                    this.context.automation_id = s3.automation_id;
                    this.context.callback_token = s3.callback_token;
                    if (s3.run_script_id) this.context.script_id = s3.run_script_id;

                    // Step 4: poll our cache for the phone-home. Every 10 attempts the
                    // server also checks Action1 endpoint-results to detect hard failures.
                    this.currentStep = 4;
                    const s4 = await this.pollStatus(urls.run_status, {
                        callback_token: this.context.callback_token,
                        automation_id: this.context.automation_id,
                    });
                    this.stepResults[3] = s4;

                    // Step 5: Manage — cleanup always runs, regardless of step 4 outcome.
                    this.currentStep = 5;
                    const s5 = await this.cleanup();
                    this.stepResults[4] = s5;

                    this.currentStep = 6;
                    this.running = false;
                },

                async cleanup() {
                    if (!this.context.script_id) {
                        return {
                            ok: true,
                            message: 'No script to clean up.'
                        };
                    }
                    return await this.post(urls.manage_cleanup, {
                        script_id: this.context.script_id
                    });
                },

                async pollStatus(url, extraBody = {}, maxAttempts = 60, intervalMs = 5000) {
                    for (let i = 0; i < maxAttempts; i++) {
                        if (i > 0) await this.sleep(intervalMs);
                        const result = await this.post(url, {
                            ...extraBody,
                            attempt: i + 1,
                        });
                        this.stepResults[3] = {
                            ...result,
                            message: result.pending ?
                                `Waiting for endpoint to phone home (attempt ${i + 1}/${maxAttempts})` : result
                                .message
                        };
                        if (!result.pending) return result;
                    }
                    return {
                        ok: false,
                        pending: false,
                        message: 'Timeout — endpoint did not phone home within ' + (maxAttempts * intervalMs /
                            1000) + 's.',
                        latency_ms: 0
                    };
                },

                async post(url, body) {
                    try {
                        const r = await fetch(url, {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': csrf,
                                'Accept': 'application/json',
                                'Content-Type': 'application/json',
                            },
                            body: JSON.stringify(body),
                        });
                        return await r.json();
                    } catch (e) {
                        return {
                            ok: false,
                            message: 'Network error: ' + (e.message || 'unknown'),
                            latency_ms: 0
                        };
                    }
                },

                sleep(ms) {
                    return new Promise(resolve => setTimeout(resolve, ms));
                },

                reset() {
                    this.currentStep = 0;
                    this.stepResults = [null, null, null, null, null];
                    this.context = {};
                    this.running = false;
                },
            };
        }
    </script>
</x-app-layout>
