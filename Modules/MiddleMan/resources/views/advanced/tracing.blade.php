@extends('middleman::layouts.master')

@section('module-content')
    <div class="space-y-6">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-neutral-800 leading-tight">Correlation Tracing</h2>
            <span
                class="inline-flex items-center rounded-full bg-primary-100 px-3 py-1 text-xs font-medium text-primary-800">
                {{ number_format($traceGroups->count()) }} Active Correlations
            </span>
        </div>

        <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
            <div class="xl:col-span-1 bg-white shadow-sm rounded-lg border border-neutral-200 overflow-hidden">
                <div class="px-5 py-4 border-b border-neutral-200 bg-neutral-50">
                    <h3 class="text-sm font-semibold text-neutral-800">Correlation IDs</h3>
                </div>
                <div class="divide-y divide-neutral-100 max-h-[46rem] overflow-auto">
                    @forelse ($traceGroups as $group)
                        <a href="{{ route('middleman.tracing.index', ['correlation_id' => $group->correlation_id]) }}"
                            class="block p-4 hover:bg-primary-50 transition-colors {{ $selectedCorrelationId === $group->correlation_id ? 'bg-primary-50' : '' }}">
                            <p class="text-xs font-mono text-neutral-800 break-all">{{ $group->correlation_id }}</p>
                            <p class="text-xs text-neutral-500 mt-1">{{ $group->event_count }} events · Last seen
                                {{ \Carbon\Carbon::parse($group->last_seen_at)->diffForHumans() }}</p>
                        </a>
                    @empty
                        <div class="p-6 text-sm text-neutral-500 italic">No correlated traces recorded yet.</div>
                    @endforelse
                </div>
            </div>

            <div class="xl:col-span-2 bg-white shadow-sm rounded-lg border border-neutral-200 overflow-hidden">
                <div class="px-5 py-4 border-b border-neutral-200 bg-neutral-50 flex items-center justify-between">
                    <h3 class="text-sm font-semibold text-neutral-800">Trace Events</h3>
                    @if ($selectedCorrelationId !== '')
                        <a href="{{ route('middleman.tracing.index') }}"
                            class="text-xs text-primary-600 hover:text-primary-700">Clear Filter</a>
                    @endif
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-neutral-200">
                        <thead class="bg-neutral-50">
                            <tr>
                                <th
                                    class="px-4 py-3 text-left text-xs font-medium text-neutral-500 uppercase tracking-wider">
                                    Time</th>
                                <th
                                    class="px-4 py-3 text-left text-xs font-medium text-neutral-500 uppercase tracking-wider">
                                    Event</th>
                                <th
                                    class="px-4 py-3 text-left text-xs font-medium text-neutral-500 uppercase tracking-wider">
                                    Correlation</th>
                                <th
                                    class="px-4 py-3 text-left text-xs font-medium text-neutral-500 uppercase tracking-wider">
                                    Causation</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-neutral-100 bg-white">
                            @forelse ($traces as $trace)
                                <tr class="hover:bg-neutral-50">
                                    <td class="px-4 py-3 text-xs text-neutral-600">
                                        {{ optional($trace->fired_at)->diffForHumans() }}</td>
                                    <td class="px-4 py-3">
                                        <p class="text-sm font-medium text-neutral-800">{{ $trace->event_name }}</p>
                                        <p class="text-xs text-neutral-500">{{ $trace->event_class }}</p>
                                    </td>
                                    <td class="px-4 py-3 text-xs font-mono text-neutral-700 break-all">
                                        {{ $trace->correlation_id }}</td>
                                    <td class="px-4 py-3 text-xs font-mono text-neutral-700 break-all">
                                        {{ $trace->causation_id ?: '—' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-4 py-12 text-center text-sm text-neutral-500 italic">No
                                        trace events found for this filter.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if ($traces->hasPages())
                    <div class="px-4 py-3 border-t border-neutral-200">{{ $traces->links() }}</div>
                @endif
            </div>
        </div>
    </div>
@endsection
