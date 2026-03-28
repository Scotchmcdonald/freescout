<?php

declare(strict_types=1);

namespace Modules\MiddleMan\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;
use Modules\MiddleMan\Models\MiddleManAuditEntry;
use Modules\MiddleMan\Services\EventDiscoveryService;
use Modules\MiddleMan\Services\RuleEngine;

class MutingController extends Controller
{
    public function index(RuleEngine $ruleEngine, EventDiscoveryService $discovery): View|JsonResponse
    {
        $listenerMap = $discovery->getListenerMap();

        $listenerCandidates = collect($listenerMap)
            ->flatten()
            ->filter(fn (mixed $listener): bool => is_string($listener))
            ->unique()
            ->sort()
            ->values()
            ->all();

        if (request()->expectsJson()) {
            return response()->json([
                'muted_listeners' => $ruleEngine->getMutedListeners(),
                'listener_candidates' => $listenerCandidates,
            ]);
        }

        return view('middleman::muting.index', [
            'mutedListeners' => $ruleEngine->getMutedListeners(),
            'listenerCandidates' => $listenerCandidates,
        ]);
    }

    public function data(RuleEngine $ruleEngine): JsonResponse
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
            (int) $request->user()?->id,
            'listener_muted',
            null,
            null,
            ['listener_class' => $validated['listener_class']],
        );

        return response()->json([
            'success' => true,
            'muted_listeners' => $ruleEngine->getMutedListeners(),
        ]);
    }

    public function removeMute(Request $request, RuleEngine $ruleEngine): JsonResponse
    {
        $validated = $request->validate([
            'listener_class' => 'required|string|max:255',
        ]);

        $ruleEngine->removeMutedListener($validated['listener_class']);

        MiddleManAuditEntry::record(
            (int) $request->user()?->id,
            'listener_unmuted',
            null,
            null,
            ['listener_class' => $validated['listener_class']],
        );

        return response()->json([
            'success' => true,
            'muted_listeners' => $ruleEngine->getMutedListeners(),
        ]);
    }

    public function clearAll(Request $request, RuleEngine $ruleEngine): JsonResponse
    {
        $count = count($ruleEngine->getMutedListeners());
        $ruleEngine->setMutedListeners([]);

        MiddleManAuditEntry::record(
            (int) $request->user()?->id,
            'muted_listeners_cleared',
            null,
            null,
            ['count' => $count],
        );

        return response()->json([
            'success' => true,
            'cleared' => $count,
            'muted_listeners' => [],
        ]);
    }
}
