@extends('middleman::layouts.master')

@section('module-content')
    <div x-data="{ tab: 'overview' }">
        <div class="flex justify-between items-center mb-6">
            <h2 class="font-semibold text-xl text-neutral-800 leading-tight">MiddleMan — Control Tower</h2>
            @if (!$moduleEnabled)
                <span
                    class="inline-flex items-center rounded-full bg-neutral-100 px-3 py-1 text-xs font-medium text-neutral-600">
                    <svg class="mr-1.5 h-3 w-3 text-neutral-400" fill="currentColor" viewBox="0 0 8 8">
                        <circle cx="4" cy="4" r="3" />
                    </svg>
                    Module Disabled
                </span>
            @else
                <span
                    class="inline-flex items-center rounded-full bg-success-100 px-3 py-1 text-xs font-medium text-success-800">
                    <svg class="mr-1.5 h-3 w-3 text-success-500" fill="currentColor" viewBox="0 0 8 8">
                        <circle cx="4" cy="4" r="3" />
                    </svg>
                    Module Active
                </span>
            @endif
        </div>

        @if (!$moduleEnabled)
            @include('middleman::components.troubleshooting-card', [
                'title' => 'Module Disabled',
                'what' => 'MiddleMan is currently turned off. No events are being logged or intercepted.',
                'why' => 'The MIDDLEMAN_ENABLED environment variable is set to false.',
                'action' =>
                    'Set MIDDLEMAN_ENABLED=true in your .env file and restart the application to activate.',
            ])
        @endif

        <!-- Stats Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            <!-- Logs Last Hour -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 border-l-4 border-primary-500 cursor-pointer hover:bg-primary-50 transition-colors"
                @click="tab = 'overview'">
                <div class="text-2xl font-bold text-neutral-800">{{ number_format($metrics['logs_last_hour']) }}</div>
                <div class="text-sm text-neutral-600">Logs (Last Hour)</div>
            </div>
            <!-- Total Logs -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 border-l-4 border-info-500 cursor-pointer hover:bg-info-50 transition-colors"
                @click="tab = 'overview'">
                <div class="text-2xl font-bold text-neutral-800">{{ number_format($metrics['total_logs']) }}</div>
                <div class="text-sm text-neutral-600">Total Log Entries</div>
            </div>
            <!-- Pending Intercepts -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 border-l-4 border-warning-500 cursor-pointer hover:bg-warning-50 transition-colors"
                @click="tab = 'overview'">
                <div class="text-2xl font-bold text-neutral-800">{{ number_format($metrics['pending_intercepts']) }}</div>
                <div class="text-sm text-neutral-600">Pending Intercepts</div>
            </div>
            <!-- Unique Event Types -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 border-l-4 border-success-500 cursor-pointer hover:bg-success-50 transition-colors"
                @click="tab = 'overview'">
                <div class="text-2xl font-bold text-neutral-800">{{ number_format($metrics['unique_event_types']) }}</div>
                <div class="text-sm text-neutral-600">Unique Event Types</div>
            </div>
        </div>

        {{-- Circuit Breaker Status Panel --}}
        @php
            $cbState = $circuitBreakerStatus['state'] ?? 'CLOSED';
            $cbColors = match($cbState) {
                'CLOSED'    => ['bg' => 'bg-success-50',  'border' => 'border-success-200', 'text' => 'text-success-800', 'badge' => 'bg-success-100 text-success-800', 'dot' => 'text-success-500'],
                'HALF_OPEN' => ['bg' => 'bg-warning-50',  'border' => 'border-warning-200', 'text' => 'text-warning-800', 'badge' => 'bg-warning-100 text-warning-800', 'dot' => 'text-warning-500'],
                'OPEN'      => ['bg' => 'bg-danger-50',   'border' => 'border-danger-200',  'text' => 'text-danger-800',  'badge' => 'bg-danger-100 text-danger-800',   'dot' => 'text-danger-500'],
                default     => ['bg' => 'bg-neutral-50',  'border' => 'border-neutral-200', 'text' => 'text-neutral-800', 'badge' => 'bg-neutral-100 text-neutral-800', 'dot' => 'text-neutral-500'],
            };
        @endphp
        <div class="{{ $cbColors['bg'] }} {{ $cbColors['border'] }} border rounded-lg p-6 mb-6" x-data="{ resetting: false }">
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center">
                    <h3 class="text-lg font-medium {{ $cbColors['text'] }}">Circuit Breaker</h3>
                    <span class="ml-3 inline-flex items-center rounded-full {{ $cbColors['badge'] }} px-2.5 py-0.5 text-xs font-medium">
                        <svg class="mr-1.5 h-2.5 w-2.5 {{ $cbColors['dot'] }}" fill="currentColor" viewBox="0 0 8 8">
                            <circle cx="4" cy="4" r="3" />
                        </svg>
                        {{ $cbState }}
                    </span>
                </div>
                @if ($cbState !== 'CLOSED')
                    <button type="button" @click="resetting = true; fetch('{{ route('middleman.circuit-breaker.reset') }}', { method: 'POST', headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' } }).then(() => location.reload()).catch(() => { resetting = false; })"
                        :disabled="resetting"
                        class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-white bg-primary-600 rounded-md hover:bg-primary-700 disabled:opacity-50 transition-colors">
                        <svg x-show="resetting" class="animate-spin -ml-0.5 mr-1.5 h-3.5 w-3.5" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                        Reset Circuit Breaker
                    </button>
                @endif
            </div>

            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div>
                    <div class="text-xs font-medium text-neutral-500 uppercase tracking-wider">Events in Window</div>
                    <div class="mt-1 text-lg font-semibold text-neutral-800">{{ $circuitBreakerStatus['events_in_window'] ?? 0 }}</div>
                </div>
                <div>
                    <div class="text-xs font-medium text-neutral-500 uppercase tracking-wider">Failure Count</div>
                    <div class="mt-1 text-lg font-semibold text-neutral-800">{{ $circuitBreakerStatus['failure_count'] ?? 0 }} / {{ $circuitBreakerStatus['failure_threshold'] ?? '—' }}</div>
                </div>
                <div>
                    <div class="text-xs font-medium text-neutral-500 uppercase tracking-wider">Storm Threshold</div>
                    <div class="mt-1 text-lg font-semibold text-neutral-800">{{ $circuitBreakerStatus['storm_threshold'] ?? '—' }}/s</div>
                </div>
                <div>
                    <div class="text-xs font-medium text-neutral-500 uppercase tracking-wider">Cooldown</div>
                    <div class="mt-1 text-lg font-semibold text-neutral-800">{{ $circuitBreakerStatus['cooldown_seconds'] ?? '—' }}s</div>
                </div>
            </div>

            @if ($cbState === 'OPEN')
                <div class="mt-4 text-sm {{ $cbColors['text'] }}">
                    MiddleMan processing is temporarily suspended. Events will pass through without logging or interception until the circuit resets.
                </div>
            @elseif ($cbState === 'HALF_OPEN')
                <div class="mt-4 text-sm {{ $cbColors['text'] }}">
                    Testing recovery — a limited number of events are being processed. If successful, the circuit will close automatically.
                </div>
            @endif
        </div>

        <!-- System Status Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
            <!-- Logging Status -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="text-lg font-medium text-neutral-900">Logging</h3>
                    @if ($loggingActive)
                        <span
                            class="inline-flex items-center rounded-full bg-success-100 px-2.5 py-0.5 text-xs font-medium text-success-800">Recording</span>
                    @else
                        <span
                            class="inline-flex items-center rounded-full bg-neutral-100 px-2.5 py-0.5 text-xs font-medium text-neutral-600">Inactive</span>
                    @endif
                </div>
                <p class="text-sm text-neutral-600 mb-3">
                    @if ($loggingActive)
                        Monitoring {{ count($rules['log'] ?? []) }} event pattern(s).
                    @else
                        No events are being recorded.
                    @endif
                </p>
                <a href="{{ route('middleman.logging.index') }}"
                    class="text-sm text-primary-600 hover:text-primary-700 font-medium">
                    Open Logging &rarr;
                </a>
            </div>

            <!-- Intercept Status -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="text-lg font-medium text-neutral-900">Interception</h3>
                    @if ($interceptActive)
                        <span
                            class="inline-flex items-center rounded-full bg-warning-100 px-2.5 py-0.5 text-xs font-medium text-warning-800">Active</span>
                    @else
                        <span
                            class="inline-flex items-center rounded-full bg-neutral-100 px-2.5 py-0.5 text-xs font-medium text-neutral-600">Inactive</span>
                    @endif
                </div>
                <p class="text-sm text-neutral-600 mb-3">
                    @if ($interceptActive)
                        Catching {{ count($rules['intercept'] ?? []) }} event pattern(s).
                        {{ $metrics['pending_intercepts'] }} pending.
                    @else
                        No events are being intercepted.
                    @endif
                </p>
                <a href="{{ route('middleman.intercept.index') }}"
                    class="text-sm text-primary-600 hover:text-primary-700 font-medium">
                    Open Intercept &rarr;
                </a>
            </div>
        </div>

        <!-- Tab Navigation -->
        <div class="border-b border-neutral-200 mb-6">
            <nav class="-mb-px flex space-x-8" aria-label="Tabs">
                <button type="button" @click="tab = 'overview'"
                    :class="tab === 'overview' ? 'border-primary-500 text-primary-600' :
                        'border-transparent text-neutral-500 hover:text-neutral-700 hover:border-neutral-300'"
                    class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                    Rules Overview
                </button>
                <button type="button" @click="tab = 'audit'"
                    :class="tab === 'audit' ? 'border-primary-500 text-primary-600' :
                        'border-transparent text-neutral-500 hover:text-neutral-700 hover:border-neutral-300'"
                    class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                    Audit Trail
                </button>
            </nav>
        </div>

        <!-- RULES OVERVIEW TAB -->
        <div x-show="tab === 'overview'" class="space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Log Rules -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-lg font-medium text-neutral-900 mb-4">Active Log Rules</h3>
                    @forelse($rules['log'] ?? [] as $rule)
                        <div class="flex items-center justify-between py-2 border-b border-neutral-100 last:border-0">
                            <code class="text-sm font-mono text-neutral-700">{{ $rule }}</code>
                            <span
                                class="inline-flex items-center rounded-full bg-primary-100 px-2 py-0.5 text-xs text-primary-700">log</span>
                        </div>
                    @empty
                        <p class="text-sm text-neutral-500 italic">No log rules configured.</p>
                    @endforelse
                </div>

                <!-- Intercept Rules -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-lg font-medium text-neutral-900 mb-4">Active Intercept Rules</h3>
                    @forelse($rules['intercept'] ?? [] as $rule)
                        <div class="flex items-center justify-between py-2 border-b border-neutral-100 last:border-0">
                            <code class="text-sm font-mono text-neutral-700">{{ $rule }}</code>
                            <span
                                class="inline-flex items-center rounded-full bg-warning-100 px-2 py-0.5 text-xs text-warning-700">intercept</span>
                        </div>
                    @empty
                        <p class="text-sm text-neutral-500 italic">No intercept rules configured.</p>
                    @endforelse
                </div>
            </div>
        </div>

        <!-- AUDIT TRAIL TAB -->
        <div x-show="tab === 'audit'" class="space-y-4">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <table class="min-w-full divide-y divide-neutral-200">
                    <thead class="bg-neutral-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-neutral-500 uppercase tracking-wider">
                                Time</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-neutral-500 uppercase tracking-wider">
                                User</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-neutral-500 uppercase tracking-wider">
                                Action</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-neutral-500 uppercase tracking-wider">
                                Details</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-neutral-200">
                        @forelse($recentAudit as $entry)
                            <tr class="hover:bg-neutral-50">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-neutral-500">
                                    {{ $entry->created_at->diffForHumans() }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-neutral-700">
                                    {{ $entry->user?->name ?? 'System' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <code
                                        class="text-xs font-mono bg-neutral-100 px-2 py-1 rounded">{{ $entry->action }}</code>
                                </td>
                                <td class="px-6 py-4 text-sm text-neutral-500 max-w-md truncate"
                                    title="{{ json_encode($entry->details) }}">
                                    {{ Str::limit(json_encode($entry->details), 80) }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-12 text-center text-sm text-neutral-500 italic">
                                    No audit trail entries yet.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
