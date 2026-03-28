<?php

declare(strict_types=1);

namespace Modules\MiddleMan\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\MiddleMan\Models\MiddleManAuditEntry;
use Modules\MiddleMan\Models\MiddleManIntercept;
use Modules\MiddleMan\Services\EventScanner;
use Modules\MiddleMan\Services\MiddleManDispatcher;
use Modules\MiddleMan\Services\RuleEngine;

class InterceptController extends Controller
{
    public function index(RuleEngine $ruleEngine, EventScanner $scanner)
    {
        $interceptActive = $ruleEngine->isInterceptActive();
        $rules = $ruleEngine->getRules();
        $interceptRules = $rules['intercept'] ?? [];

        $pending = MiddleManIntercept::pending()
            ->ordered()
            ->paginate(50);

        $history = MiddleManIntercept::whereIn('status', [
            MiddleManIntercept::STATUS_FIRED,
            MiddleManIntercept::STATUS_DISCARDED,
        ])->orderByDesc('fired_at')->limit(50)->get();

        $availableEvents = $scanner->discover();

        return view('middleman::intercept.index', compact(
            'interceptActive',
            'interceptRules',
            'pending',
            'history',
            'availableEvents',
        ));
    }

    public function toggle(Request $request, RuleEngine $ruleEngine): JsonResponse
    {
        $active = (bool) $request->input('active');
        $ruleEngine->setInterceptActive($active);

        MiddleManAuditEntry::record(
            $request->user()->id,
            MiddleManAuditEntry::ACTION_INTERCEPT_TOGGLED,
            null,
            null,
            ['active' => $active],
        );

        return response()->json(['active' => $active]);
    }

    public function addRule(Request $request, RuleEngine $ruleEngine): JsonResponse
    {
        $validated = $request->validate([
            'event_class' => 'required|string|max:255',
        ]);

        $ruleEngine->addInterceptRule($validated['event_class']);

        MiddleManAuditEntry::record(
            $request->user()->id,
            MiddleManAuditEntry::ACTION_RULE_CREATED,
            null,
            null,
            ['type' => 'intercept', 'event_class' => $validated['event_class']],
        );

        return response()->json(['success' => true, 'rules' => $ruleEngine->getRules()['intercept']]);
    }

    public function removeRule(Request $request, RuleEngine $ruleEngine): JsonResponse
    {
        $validated = $request->validate([
            'event_class' => 'required|string|max:255',
        ]);

        $ruleEngine->removeInterceptRule($validated['event_class']);

        MiddleManAuditEntry::record(
            $request->user()->id,
            MiddleManAuditEntry::ACTION_RULE_DELETED,
            null,
            null,
            ['type' => 'intercept', 'event_class' => $validated['event_class']],
        );

        return response()->json(['success' => true, 'rules' => $ruleEngine->getRules()['intercept']]);
    }

    public function show(int $id): JsonResponse
    {
        $intercept = MiddleManIntercept::findOrFail($id);

        return response()->json($intercept);
    }

    /**
     * Update the payload of a pending intercepted event.
     */
    public function updatePayload(Request $request, int $id): JsonResponse
    {
        $intercept = MiddleManIntercept::findOrFail($id);

        if (! $intercept->isPending()) {
            return response()->json(['error' => 'Only pending intercepts can be edited.'], 422);
        }

        $validated = $request->validate([
            'payload' => 'required|array',
        ]);

        $oldPayload = $intercept->payload;
        $intercept->update(['payload' => $validated['payload']]);

        MiddleManAuditEntry::record(
            $request->user()->id,
            MiddleManAuditEntry::ACTION_PAYLOAD_EDITED,
            MiddleManIntercept::class,
            $id,
            ['event_class' => $intercept->event_class, 'changed_keys' => array_keys(array_diff_key($validated['payload'], $oldPayload))],
        );

        return response()->json(['success' => true, 'intercept' => $intercept->fresh()]);
    }

    /**
     * Fire a single intercepted event.
     */
    public function fire(Request $request, int $id): JsonResponse
    {
        $intercept = MiddleManIntercept::findOrFail($id);

        if (! $intercept->isPending()) {
            return response()->json(['error' => 'Only pending intercepts can be fired.'], 422);
        }

        // Dispatch the event through the base dispatcher (bypassing MiddleMan)
        $this->dispatchInterceptedEvent($intercept);

        $intercept->markFired($request->user()->id);

        MiddleManAuditEntry::record(
            $request->user()->id,
            MiddleManAuditEntry::ACTION_INTERCEPT_FIRED,
            MiddleManIntercept::class,
            $id,
            ['event_class' => $intercept->event_class],
        );

        return response()->json(['success' => true]);
    }

    /**
     * Fire multiple pending intercepts in sort_order sequence.
     */
    public function fireSelected(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'ids'   => 'required|array',
            'ids.*' => 'integer|exists:middleman_intercepts,id',
        ]);

        $intercepts = MiddleManIntercept::whereIn('id', $validated['ids'])
            ->pending()
            ->ordered()
            ->get();

        $fired = 0;
        foreach ($intercepts as $intercept) {
            $this->dispatchInterceptedEvent($intercept);
            $intercept->markFired($request->user()->id);
            $fired++;
        }

        MiddleManAuditEntry::record(
            $request->user()->id,
            MiddleManAuditEntry::ACTION_BATCH_FIRED,
            null,
            null,
            ['count' => $fired, 'ids' => $validated['ids']],
        );

        return response()->json(['success' => true, 'fired' => $fired]);
    }

    /**
     * Fire ALL pending intercepts in sort_order sequence.
     */
    public function fireAll(Request $request): JsonResponse
    {
        $intercepts = MiddleManIntercept::pending()->ordered()->get();

        $fired = 0;
        foreach ($intercepts as $intercept) {
            $this->dispatchInterceptedEvent($intercept);
            $intercept->markFired($request->user()->id);
            $fired++;
        }

        MiddleManAuditEntry::record(
            $request->user()->id,
            MiddleManAuditEntry::ACTION_BATCH_FIRED,
            null,
            null,
            ['count' => $fired, 'scope' => 'all'],
        );

        return response()->json(['success' => true, 'fired' => $fired]);
    }

    /**
     * Discard a pending intercept (it will never fire).
     */
    public function discard(Request $request, int $id): JsonResponse
    {
        $intercept = MiddleManIntercept::findOrFail($id);

        if (! $intercept->isPending()) {
            return response()->json(['error' => 'Only pending intercepts can be discarded.'], 422);
        }

        $intercept->markDiscarded();

        MiddleManAuditEntry::record(
            $request->user()->id,
            MiddleManAuditEntry::ACTION_INTERCEPT_DISCARDED,
            MiddleManIntercept::class,
            $id,
            ['event_class' => $intercept->event_class],
        );

        return response()->json(['success' => true]);
    }

    /**
     * Update the sort order for drag-and-drop reordering.
     */
    public function reorder(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'order'         => 'required|array',
            'order.*.id'    => 'required|integer|exists:middleman_intercepts,id',
            'order.*.sort'  => 'required|integer|min:0',
        ]);

        foreach ($validated['order'] as $item) {
            MiddleManIntercept::where('id', $item['id'])
                ->pending()
                ->update(['sort_order' => $item['sort']]);
        }

        MiddleManAuditEntry::record(
            $request->user()->id,
            MiddleManAuditEntry::ACTION_ORDER_CHANGED,
            null,
            null,
            ['count' => count($validated['order'])],
        );

        return response()->json(['success' => true]);
    }

    /*
    |--------------------------------------------------------------------------
    | Internal
    |--------------------------------------------------------------------------
    */

    /**
     * Dispatch an intercepted event using the base dispatcher to avoid re-interception.
     */
    private function dispatchInterceptedEvent(MiddleManIntercept $intercept): void
    {
        $dispatcher = app(\Illuminate\Contracts\Events\Dispatcher::class);

        // If the event class still exists, reconstruct and dispatch it
        $eventClass = $intercept->event_class;

        if (class_exists($eventClass)) {
            // We fire a generic event name with the stored payload as context.
            // The dispatcher will use bypass mode to prevent re-interception.
            if ($dispatcher instanceof MiddleManDispatcher) {
                // Use a simple stdClass wrapper carrying the original metadata
                $syntheticEvent = new \stdClass();
                $syntheticEvent->originalClass = $eventClass;
                $syntheticEvent->payload = $intercept->payload;
                $syntheticEvent->metadata = $intercept->metadata;
                $syntheticEvent->interceptId = $intercept->id;

                $dispatcher->dispatchBypassing($syntheticEvent);
            }
        }
    }
}
