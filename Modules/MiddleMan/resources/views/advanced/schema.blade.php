@extends('middleman::layouts.master')

@section('module-content')
    <div class="space-y-6">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-neutral-800 leading-tight">Schema Drift</h2>
            <span
                class="inline-flex items-center rounded-full bg-warning-100 px-3 py-1 text-xs font-medium text-warning-800">
                {{ number_format($driftCount) }} Drift Flags
            </span>
        </div>

        <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
            <div class="bg-white shadow-sm rounded-lg border border-neutral-200 overflow-hidden">
                <div class="px-5 py-4 border-b border-neutral-200 bg-neutral-50">
                    <h3 class="text-sm font-semibold text-neutral-800">Baseline Schemas</h3>
                </div>
                <div class="divide-y divide-neutral-100">
                    @forelse ($schemas as $schema)
                        <div class="p-4">
                            <p class="text-sm font-medium text-neutral-900">{{ $schema->event_class }}</p>
                            <p class="text-xs text-neutral-500 mt-1">Version {{ $schema->version }} · Updated
                                {{ optional($schema->updated_at)->diffForHumans() }}</p>
                            <pre class="mt-2 text-xs font-mono bg-neutral-50 rounded border border-neutral-200 p-2 overflow-x-auto">{{ json_encode($schema->schema, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                        </div>
                    @empty
                        <div class="p-6 text-sm text-neutral-500 italic">No baseline schemas recorded yet.</div>
                    @endforelse
                </div>
                @if ($schemas->hasPages())
                    <div class="px-5 py-3 border-t border-neutral-200">{{ $schemas->links() }}</div>
                @endif
            </div>

            <div class="bg-white shadow-sm rounded-lg border border-neutral-200 overflow-hidden">
                <div class="px-5 py-4 border-b border-neutral-200 bg-neutral-50">
                    <h3 class="text-sm font-semibold text-neutral-800">Recent Drifted Logs</h3>
                </div>
                <div class="divide-y divide-neutral-100 max-h-[48rem] overflow-auto">
                    @forelse ($driftedLogs as $log)
                        <div class="p-4">
                            <p class="text-sm font-medium text-neutral-900">{{ $log->event_name }}</p>
                            <p class="text-xs text-neutral-500">{{ $log->event_class }}</p>
                            <p class="text-xs text-neutral-500 mt-1">{{ optional($log->fired_at)->diffForHumans() }}</p>
                            @if (!empty($log->metadata['schema_drift']))
                                <pre class="mt-2 text-xs font-mono bg-warning-50 rounded border border-warning-200 p-2 overflow-x-auto">{{ json_encode($log->metadata['schema_drift'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                            @endif
                        </div>
                    @empty
                        <div class="p-6 text-sm text-neutral-500 italic">No drifted logs yet.</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
@endsection
