<?php

declare(strict_types=1);

namespace Modules\MiddleMan\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\MiddleMan\Models\MiddleManAuditEntry;
use Modules\MiddleMan\Models\MiddleManIntercept;
use Modules\MiddleMan\Models\MiddleManLog;
use Modules\MiddleMan\Services\ReplayEngine;

class ReplayController extends Controller
{
    /**
     * Replay a previously logged event from its stored payload.
     */
    public function replay(Request $request, int $logId, ReplayEngine $engine): JsonResponse
    {
        $result = $engine->replay($logId);

        if ($result['success']) {
            MiddleManAuditEntry::record(
                (int) $request->user()?->id,
                'event_replayed',
                'middleman_log',
                $logId,
                ['event_class' => $result['event_class']],
            );
        }

        $statusCode = $result['success'] ? 200 : 422;

        return response()->json($result, $statusCode);
    }

    /**
     * Replay a selected sequence of events from either logs or intercept captures.
     */
    public function replaySequence(Request $request, ReplayEngine $engine): JsonResponse
    {
        $validated = $request->validate([
            'source' => 'required|string|in:logs,intercepts',
            'ids' => 'required|array|min:1|max:200',
            'ids.*' => 'integer|min:1',
        ]);

        $source = $validated['source'];
        $ids = array_values(array_unique($validated['ids']));

        $results = [
            'source' => $source,
            'requested' => count($ids),
            'processed' => 0,
            'succeeded' => 0,
            'failed' => 0,
            'errors' => [],
        ];

        if ($source === 'logs') {
            $items = MiddleManLog::query()
                ->whereIn('id', $ids)
                ->orderBy('fired_at')
                ->get();

            foreach ($items as $log) {
                $outcome = $engine->replay((int) $log->id);
                $results['processed']++;

                if (($outcome['success'] ?? false) === true) {
                    $results['succeeded']++;
                    continue;
                }

                $results['failed']++;
                $results['errors'][] = [
                    'id' => $log->id,
                    'event_class' => $log->event_class,
                    'message' => $outcome['message'] ?? 'Replay failed.',
                ];
            }
        } else {
            $items = MiddleManIntercept::query()
                ->whereIn('id', $ids)
                ->orderBy('intercepted_at')
                ->get();

            foreach ($items as $intercept) {
                $outcome = $engine->replayIntercept($intercept);
                $results['processed']++;

                if (($outcome['success'] ?? false) === true) {
                    $results['succeeded']++;
                    continue;
                }

                $results['failed']++;
                $results['errors'][] = [
                    'id' => $intercept->id,
                    'event_class' => $intercept->event_class,
                    'message' => $outcome['message'] ?? 'Replay failed.',
                ];
            }
        }

        MiddleManAuditEntry::record(
            (int) $request->user()?->id,
            'sequence_replayed',
            null,
            null,
            [
                'source' => $source,
                'requested' => $results['requested'],
                'processed' => $results['processed'],
                'succeeded' => $results['succeeded'],
                'failed' => $results['failed'],
                'ids' => $ids,
            ],
        );

        $statusCode = $results['failed'] > 0 ? 207 : 200;

        return response()->json($results, $statusCode);
    }
}
