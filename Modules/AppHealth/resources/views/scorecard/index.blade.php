@extends('layouts.app')

@section('content')
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 py-8">
        <header class="mb-6">
            <h1 class="text-2xl font-semibold text-neutral-900">AppHealth Operator Scorecard</h1>
            <p class="text-sm text-neutral-500 mt-1">Daily Stage A trigger status for scaling readiness and operational
                follow-up.</p>
        </header>

        <section class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6" aria-label="Scorecard summary">
            <article class="bg-white shadow-sm rounded-lg border border-neutral-200 p-5">
                <p class="text-xs uppercase tracking-wide text-neutral-500">Overall Status</p>
                <p
                    class="mt-2 text-2xl font-semibold {{ ($scorecard['overall_status'] ?? 'ok') === 'warning' ? 'text-warning-700' : 'text-success-700' }}">
                    {{ strtoupper($scorecard['overall_status'] ?? 'ok') }}
                </p>
            </article>

            <article class="bg-white shadow-sm rounded-lg border border-neutral-200 p-5">
                <p class="text-xs uppercase tracking-wide text-neutral-500">Breach Count</p>
                <p class="mt-2 text-2xl font-semibold text-neutral-900">{{ (int) ($scorecard['breach_count'] ?? 0) }}</p>
            </article>

            <article class="bg-white shadow-sm rounded-lg border border-neutral-200 p-5">
                <p class="text-xs uppercase tracking-wide text-neutral-500">Recommendation</p>
                <p
                    class="mt-2 text-sm font-semibold {{ ($scorecard['recommendation'] ?? '') === 'schedule_stage_a_work' ? 'text-warning-700' : 'text-primary-700' }}">
                    {{ str_replace('_', ' ', strtoupper((string) ($scorecard['recommendation'] ?? 'continue_observation'))) }}
                </p>
                <p class="mt-1 text-xs text-neutral-500">
                    Snapshot date:
                    {{ $snapshot?->snapshot_date?->toDateString() ?? ($scorecard['snapshot_date'] ?? now()->toDateString()) }}
                </p>
            </article>

            <article class="bg-white shadow-sm rounded-lg border border-neutral-200 p-5">
                <p class="text-xs uppercase tracking-wide text-neutral-500">Weekly Trend</p>
                @if (isset($trend))
                    <p
                        class="mt-2 text-sm font-semibold
                        {{ $trend['direction'] === 'worsening' ? 'text-danger-700' : ($trend['direction'] === 'improving' ? 'text-success-700' : 'text-neutral-600') }}">
                        {{ strtoupper($trend['direction']) }}
                        @if ($trend['delta_7d'] !== 0)
                            ({{ $trend['delta_7d'] > 0 ? '+' : '' }}{{ $trend['delta_7d'] }} vs last week)
                        @endif
                    </p>
                    <p class="mt-1 text-xs text-neutral-500">
                        {{ $trend['consecutive_breach_weeks'] }} / {{ $trend['consecutive_weeks_required'] }} consecutive
                        breach weeks
                    </p>
                @else
                    <p class="mt-2 text-sm text-neutral-400">No trend data yet</p>
                @endif
            </article>
        </section>

        @if (isset($trend) && $trend['gate_condition_met'])
            <div class="mb-6 rounded-lg border border-warning-300 bg-warning-50 p-4">
                <p class="text-sm font-semibold text-warning-800">
                    &#9888; Stage A Gate Condition Met
                </p>
                <p class="text-sm text-warning-700 mt-1">
                    {{ $trend['consecutive_breach_weeks'] }} consecutive breach weeks have been recorded
                    (threshold: {{ $trend['consecutive_weeks_required'] }}).
                    Review the SCALING_PLAYBOOK and schedule Stage A capacity work.
                </p>
            </div>
        @endif

        <section class="mb-6 bg-white shadow-sm rounded-lg border border-neutral-200" aria-label="Observability console">
            <div class="px-6 py-4 border-b border-neutral-200">
                <h2 class="text-sm font-semibold uppercase tracking-wide text-neutral-800">Observability Console</h2>
                <p class="mt-1 text-xs text-neutral-500">Use the scorecard for gate decisions, then pivot to Resilience,
                    Grafana, or Prometheus for deep diagnostics.</p>
            </div>
            <div class="px-6 py-4">
                @if (!empty($observabilityLinks))
                    <div class="flex flex-wrap gap-3">
                        @foreach ($observabilityLinks as $link)
                            <a href="{{ $link['href'] }}"
                                @if ($link['external']) target="_blank" rel="noopener noreferrer" @endif
                                class="inline-flex items-center rounded-lg border border-neutral-300 bg-neutral-50 px-4 py-2 text-sm font-medium text-neutral-800 hover:bg-neutral-100 focus:outline-none focus:ring-2 focus:ring-primary-500">
                                {{ $link['label'] }}
                                @if ($link['external'])
                                    <span class="ms-2 text-xs text-neutral-500">External</span>
                                @endif
                            </a>
                        @endforeach
                    </div>
                @else
                    <div class="rounded-lg border border-neutral-200 bg-neutral-50 px-4 py-3 text-sm text-neutral-600">
                        No external observability links configured yet. Set
                        <span class="font-semibold">APPHEALTH_GRAFANA_URL</span> and
                        <span class="font-semibold">APPHEALTH_PROMETHEUS_URL</span> to enable direct pivots.
                    </div>
                @endif
            </div>
        </section>

        <section class="bg-white shadow-sm rounded-lg border border-neutral-200 overflow-hidden"
            aria-label="Trigger checks">
            <div class="px-6 py-4 border-b border-neutral-200">
                <h2 class="text-sm font-semibold uppercase tracking-wide text-neutral-800">Stage A Trigger Checks</h2>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-neutral-200">
                    <thead class="bg-neutral-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-neutral-500 uppercase">Trigger</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-neutral-500 uppercase">Actual</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-neutral-500 uppercase">Threshold</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-neutral-500 uppercase">Operator</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-neutral-500 uppercase">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-neutral-100">
                        @forelse ($checks as $check)
                            @php
                                $breached = (bool) ($check['breached'] ?? false);
                            @endphp
                            <tr class="hover:bg-neutral-50">
                                <td class="px-6 py-4 text-sm font-medium text-neutral-900">
                                    {{ (string) ($check['name'] ?? 'unknown') }}</td>
                                <td class="px-6 py-4 text-sm text-neutral-700">{{ (string) ($check['actual'] ?? '0') }}
                                </td>
                                <td class="px-6 py-4 text-sm text-neutral-700">{{ (string) ($check['threshold'] ?? '0') }}
                                </td>
                                <td class="px-6 py-4 text-sm text-neutral-700">{{ (string) ($check['operator'] ?? '>') }}
                                </td>
                                <td class="px-6 py-4">
                                    <span
                                        class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold {{ $breached ? 'bg-warning-100 text-warning-800' : 'bg-success-100 text-success-800' }}">
                                        {{ $breached ? 'BREACHED' : 'OK' }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-10 text-center text-sm text-neutral-500">No checks
                                    available yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
@endsection
