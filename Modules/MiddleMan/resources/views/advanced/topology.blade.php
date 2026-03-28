@extends('middleman::layouts.master')

@section('module-content')
    <div class="space-y-6">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-neutral-800 leading-tight">Event Topology</h2>
            <span class="inline-flex items-center rounded-full bg-info-100 px-3 py-1 text-xs font-medium text-info-800">
                Live Discovery Snapshot
            </span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="bg-white shadow-sm rounded-lg p-5 border border-neutral-200">
                <p class="text-xs font-medium text-neutral-500 uppercase tracking-wide">Events</p>
                <p class="mt-1 text-2xl font-bold text-neutral-900">{{ number_format($metadata['total_events'] ?? 0) }}</p>
            </div>
            <div class="bg-white shadow-sm rounded-lg p-5 border border-neutral-200">
                <p class="text-xs font-medium text-neutral-500 uppercase tracking-wide">Listeners</p>
                <p class="mt-1 text-2xl font-bold text-neutral-900">{{ number_format($metadata['total_listeners'] ?? 0) }}
                </p>
            </div>
            <div class="bg-white shadow-sm rounded-lg p-5 border border-neutral-200">
                <p class="text-xs font-medium text-neutral-500 uppercase tracking-wide">Edges</p>
                <p class="mt-1 text-2xl font-bold text-neutral-900">{{ number_format($metadata['total_edges'] ?? 0) }}</p>
            </div>
        </div>

        <div class="bg-white shadow-sm rounded-lg border border-neutral-200 overflow-hidden">
            <div class="px-5 py-4 border-b border-neutral-200 bg-neutral-50 flex items-center justify-between gap-3">
                <div>
                    <h3 class="text-sm font-semibold text-neutral-800">Topology Diagram</h3>
                    <p class="text-xs text-neutral-500 mt-1">Rendered by Kroki from Graphviz DOT generated from your live
                        event/listener map.</p>
                </div>
                @if ($krokiEnabled)
                    <span
                        class="inline-flex items-center rounded-full bg-success-100 px-2.5 py-0.5 text-xs font-medium text-success-800">Kroki
                        Enabled</span>
                @else
                    <span
                        class="inline-flex items-center rounded-full bg-warning-100 px-2.5 py-0.5 text-xs font-medium text-warning-800">Kroki
                        Disabled</span>
                @endif
            </div>
            @if ($krokiEnabled)
                <div class="p-5 bg-white overflow-auto">
                    <img src="{{ route('middleman.topology.diagram') }}" alt="MiddleMan Topology Diagram"
                        class="min-w-full h-auto border border-neutral-200 rounded-lg" />
                </div>
            @else
                <div class="p-5">
                    <div class="rounded-lg border border-warning-200 bg-warning-50 p-4">
                        <p class="text-sm font-medium text-warning-800">Diagram rendering is currently disabled.</p>
                        <p class="text-xs text-warning-700 mt-1">Set MIDDLEMAN_KROKI_ENABLED=true and configure
                            MIDDLEMAN_KROKI_URL to enable visual rendering.</p>
                    </div>
                </div>
            @endif
        </div>

        <div class="bg-white shadow-sm rounded-lg border border-neutral-200 overflow-hidden">
            <div class="px-5 py-4 border-b border-neutral-200 bg-neutral-50">
                <h3 class="text-sm font-semibold text-neutral-800">Graphviz DOT Source</h3>
                <p class="text-xs text-neutral-500 mt-1">Raw topology source sent to Kroki. Useful for local debugging and
                    export workflows.</p>
            </div>
            <div class="p-5">
                <pre class="text-xs font-mono bg-neutral-50 rounded-lg border border-neutral-200 p-4 overflow-auto max-h-[30rem]">{{ $dot }}</pre>
            </div>
        </div>

        <div class="bg-white shadow-sm rounded-lg border border-neutral-200 overflow-hidden">
            <div class="px-5 py-4 border-b border-neutral-200 bg-neutral-50">
                <h3 class="text-sm font-semibold text-neutral-800">Topology Graph JSON</h3>
                <p class="text-xs text-neutral-500 mt-1">Structured graph payload for custom D3/Cytoscape renderers and data
                    export.</p>
            </div>
            <div class="p-5">
                <pre class="text-xs font-mono bg-neutral-50 rounded-lg border border-neutral-200 p-4 overflow-auto max-h-[30rem]">{{ json_encode($graph, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
            </div>
        </div>
    </div>
@endsection
