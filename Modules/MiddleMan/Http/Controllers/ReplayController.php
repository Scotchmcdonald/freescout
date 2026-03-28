<?php

declare(strict_types=1);

namespace Modules\MiddleMan\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\MiddleMan\Models\MiddleManAuditEntry;
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
                $request->user()->id,
                'event_replayed',
                'middleman_log',
                $logId,
                ['event_class' => $result['event_class']],
            );
        }

        $statusCode = $result['success'] ? 200 : 422;

        return response()->json($result, $statusCode);
    }
}
