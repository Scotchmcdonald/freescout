<?php

declare(strict_types=1);

namespace Modules\MiddleMan\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\MiddleMan\Models\MiddleManAuditEntry;
use Modules\MiddleMan\Models\MiddleManLog;
use Modules\MiddleMan\Services\EventDiscoveryService;
use Modules\MiddleMan\Services\RuleEngine;

class LoggingController extends Controller
{
    public function index(RuleEngine $ruleEngine, EventDiscoveryService $discovery)
    {
        $loggingActive = $ruleEngine->isLoggingActive();
        $rules = $ruleEngine->getRules();
        $logRules = $rules['log'] ?? [];

        $logs = MiddleManLog::orderByDesc('fired_at')
            ->paginate(50);

        $availableEvents = $discovery->discover();

        return view('middleman::logging.index', compact(
            'loggingActive',
            'logRules',
            'logs',
            'availableEvents',
        ));
    }

    public function toggle(Request $request, RuleEngine $ruleEngine): JsonResponse
    {
        $active = (bool) $request->input('active');
        $ruleEngine->setLoggingActive($active);

        MiddleManAuditEntry::record(
            $request->user()->id,
            MiddleManAuditEntry::ACTION_LOGGING_TOGGLED,
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

        $ruleEngine->addLogRule($validated['event_class']);

        MiddleManAuditEntry::record(
            $request->user()->id,
            MiddleManAuditEntry::ACTION_RULE_CREATED,
            null,
            null,
            ['type' => 'log', 'event_class' => $validated['event_class']],
        );

        return response()->json(['success' => true, 'rules' => $ruleEngine->getRules()['log']]);
    }

    public function removeRule(Request $request, RuleEngine $ruleEngine): JsonResponse
    {
        $validated = $request->validate([
            'event_class' => 'required|string|max:255',
        ]);

        $ruleEngine->removeLogRule($validated['event_class']);

        MiddleManAuditEntry::record(
            $request->user()->id,
            MiddleManAuditEntry::ACTION_RULE_DELETED,
            null,
            null,
            ['type' => 'log', 'event_class' => $validated['event_class']],
        );

        return response()->json(['success' => true, 'rules' => $ruleEngine->getRules()['log']]);
    }

    public function show(int $id): JsonResponse
    {
        $log = MiddleManLog::findOrFail($id);

        return response()->json($log);
    }

    public function clear(Request $request): JsonResponse
    {
        $deleted = MiddleManLog::query()->delete();

        MiddleManAuditEntry::record(
            $request->user()->id,
            'logs_cleared',
            null,
            null,
            ['deleted_count' => $deleted],
        );

        return response()->json(['deleted' => $deleted]);
    }

    public function filter(Request $request): JsonResponse
    {
        $query = MiddleManLog::query();

        if ($request->filled('event_class')) {
            $query->where('event_class', $request->input('event_class'));
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('event_name', 'like', "%{$search}%")
                  ->orWhere('event_class', 'like', "%{$search}%");
            });
        }

        if ($request->filled('from')) {
            $query->where('fired_at', '>=', $request->input('from'));
        }

        if ($request->filled('to')) {
            $query->where('fired_at', '<=', $request->input('to'));
        }

        $sortField = $request->input('sort', 'fired_at');
        $sortDir = $request->input('dir', 'desc');
        $allowedSorts = ['fired_at', 'event_class', 'event_name', 'created_at'];
        if (in_array($sortField, $allowedSorts, true)) {
            $query->orderBy($sortField, $sortDir === 'asc' ? 'asc' : 'desc');
        }

        return response()->json($query->paginate(50));
    }
}
