<?php

declare(strict_types=1);

namespace Modules\MiddleMan\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\MiddleMan\Models\MiddleManAuditEntry;
use Modules\MiddleMan\Services\RuleEngine;

class MutingController extends Controller
{
    public function index(RuleEngine $ruleEngine): JsonResponse
    {
        return response()->json([
            'muted_listeners' => $ruleEngine->getMutedListeners(),
        ]);
    }

    public function addMute(Request $request, RuleEngine $ruleEngine): JsonResponse
    {
        $validated = $request->validate([
            'listener_class' => 'required|string|max:255',
        ]);

        $ruleEngine->addMutedListener($validated['listener_class']);

        MiddleManAuditEntry::record(
            $request->user()->id,
            'listener_muted',
            null,
            null,
            ['listener_class' => $validated['listener_class']],
        );

        return response()->json([
            'success'          => true,
            'muted_listeners'  => $ruleEngine->getMutedListeners(),
        ]);
    }

    public function removeMute(Request $request, RuleEngine $ruleEngine): JsonResponse
    {
        $validated = $request->validate([
            'listener_class' => 'required|string|max:255',
        ]);

        $ruleEngine->removeMutedListener($validated['listener_class']);

        MiddleManAuditEntry::record(
            $request->user()->id,
            'listener_unmuted',
            null,
            null,
            ['listener_class' => $validated['listener_class']],
        );

        return response()->json([
            'success'          => true,
            'muted_listeners'  => $ruleEngine->getMutedListeners(),
        ]);
    }

    public function clearAll(Request $request, RuleEngine $ruleEngine): JsonResponse
    {
        $count = count($ruleEngine->getMutedListeners());
        $ruleEngine->setMutedListeners([]);

        MiddleManAuditEntry::record(
            $request->user()->id,
            'muted_listeners_cleared',
            null,
            null,
            ['count' => $count],
        );

        return response()->json([
            'success'          => true,
            'cleared'          => $count,
            'muted_listeners'  => [],
        ]);
    }
}
