<?php

declare(strict_types=1);

namespace Modules\MiddleMan\Http\Controllers;

use Illuminate\Http\Response;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Http;
use Modules\MiddleMan\Models\MiddleManLog;
use Modules\MiddleMan\Models\MiddleManSchema;
use Modules\MiddleMan\Services\TopologyBuilder;

class AdvancedController extends Controller
{
    public function topology(TopologyBuilder $topologyBuilder)
    {
        $graph = $topologyBuilder->build();
        $dot = $this->toDot($graph);

        return view('middleman::advanced.topology', [
            'graph' => $graph,
            'metadata' => $graph['metadata'] ?? [],
            'dot' => $dot,
            'krokiEnabled' => (bool) config('middleman.kroki.enabled', false),
        ]);
    }

    public function topologyDiagram(TopologyBuilder $topologyBuilder): Response
    {
        if (! (bool) config('middleman.kroki.enabled', false)) {
            return response('Kroki rendering is disabled.', 404);
        }

        $baseUrl = rtrim((string) config('middleman.kroki.base_url', 'http://kroki:8000'), '/');
        $timeout = (int) config('middleman.kroki.timeout_seconds', 10);
        $dot = $this->toDot($topologyBuilder->build());

        try {
            $kroki = Http::timeout(max(1, $timeout))
                ->withHeaders(['Content-Type' => 'text/plain'])
                ->withBody($dot, 'text/plain')
                ->post("{$baseUrl}/graphviz/svg");

            if (! $kroki->successful()) {
                return response('Failed to render topology diagram from Kroki.', 502);
            }

            return response($kroki->body(), 200)
                ->header('Content-Type', 'image/svg+xml')
                ->header('Cache-Control', 'no-store, max-age=0');
        } catch (\Throwable $e) {
            return response('Kroki service unreachable: ' . $e->getMessage(), 502);
        }
    }

    public function schema()
    {
        $schemas = MiddleManSchema::query()
            ->orderByDesc('updated_at')
            ->paginate(25);

        $driftedLogs = MiddleManLog::query()
            ->withDrift()
            ->orderByDesc('fired_at')
            ->limit(50)
            ->get();

        return view('middleman::advanced.schema', [
            'schemas' => $schemas,
            'driftedLogs' => $driftedLogs,
            'driftCount' => MiddleManLog::query()->withDrift()->count(),
        ]);
    }

    public function tracing(Request $request)
    {
        $selectedCorrelationId = (string) $request->query('correlation_id', '');

        $tracesQuery = MiddleManLog::query()->whereNotNull('correlation_id');
        if ($selectedCorrelationId !== '') {
            $tracesQuery->where('correlation_id', $selectedCorrelationId);
        }

        $traces = $tracesQuery
            ->orderByDesc('fired_at')
            ->paginate(50)
            ->withQueryString();

        $traceGroups = MiddleManLog::query()
            ->whereNotNull('correlation_id')
            ->selectRaw('correlation_id, COUNT(*) as event_count, MAX(fired_at) as last_seen_at')
            ->groupBy('correlation_id')
            ->orderByDesc('last_seen_at')
            ->limit(100)
            ->get();

        return view('middleman::advanced.tracing', [
            'traces' => $traces,
            'traceGroups' => $traceGroups,
            'selectedCorrelationId' => $selectedCorrelationId,
        ]);
    }

    public function replay()
    {
        $logs = MiddleManLog::query()
            ->orderByDesc('fired_at')
            ->paginate(50);

        return view('middleman::advanced.replay', [
            'logs' => $logs,
            'replayCount' => MiddleManLog::query()->replays()->count(),
        ]);
    }

    /**
     * @param array{nodes: array<int, array<string, mixed>>, edges: array<int, array<string, mixed>>} $graph
     */
    private function toDot(array $graph): string
    {
        $lines = [
            'digraph MiddleManTopology {',
            '  rankdir=LR;',
            '  graph [pad="0.2", nodesep="0.4", ranksep="0.6"];',
            '  node [shape=box, style="rounded,filled", fontname="Helvetica", fontsize=10];',
            '  edge [color="#64748b", arrowsize=0.6];',
        ];

        $nodeIds = [];
        foreach (($graph['nodes'] ?? []) as $node) {
            if (! is_array($node) || ! isset($node['id'])) {
                continue;
            }

            $rawId = (string) $node['id'];
            $dotId = 'n' . md5($rawId);
            $nodeIds[$rawId] = $dotId;

            $label = isset($node['label']) ? (string) $node['label'] : $rawId;
            $type = isset($node['type']) ? (string) $node['type'] : 'unknown';
            $fill = $type === 'event' ? '#dbeafe' : '#dcfce7';
            $stroke = $type === 'event' ? '#60a5fa' : '#4ade80';

            $lines[] = sprintf(
                '  %s [label="%s", fillcolor="%s", color="%s"];',
                $dotId,
                $this->escapeDot($label),
                $fill,
                $stroke,
            );
        }

        foreach (($graph['edges'] ?? []) as $edge) {
            if (! is_array($edge) || ! isset($edge['source'], $edge['target'])) {
                continue;
            }

            $sourceRaw = (string) $edge['source'];
            $targetRaw = (string) $edge['target'];

            $source = $nodeIds[$sourceRaw] ?? ('n' . md5($sourceRaw));
            $target = $nodeIds[$targetRaw] ?? ('n' . md5($targetRaw));

            $lines[] = sprintf('  %s -> %s;', $source, $target);
        }

        $lines[] = '}';

        return implode("\n", $lines);
    }

    private function escapeDot(string $value): string
    {
        return str_replace(['\\', '"', "\n", "\r"], ['\\\\', '\\"', ' ', ' '], $value);
    }
}
