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

class MarshalController extends Controller
{
    public function index(EventScanner $scanner)
    {
        $availableEvents = $scanner->discover();

        return view('middleman::marshal.index', compact('availableEvents'));
    }

    /**
     * Return constructor parameters for a specific event class.
     */
    public function parameters(Request $request, EventScanner $scanner): JsonResponse
    {
        $validated = $request->validate([
            'event_class' => 'required|string|max:255',
        ]);

        $params = $scanner->getParameters($validated['event_class']);

        return response()->json(['parameters' => $params]);
    }

    /**
     * Create and dispatch (or hold) a single event.
     */
    public function fire(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'event_class' => 'required|string|max:255',
            'payload'     => 'required|array',
            'hold'        => 'boolean',
        ]);

        $eventClass = $validated['event_class'];
        $hold = $validated['hold'] ?? false;

        if (! class_exists($eventClass)) {
            return response()->json(['error' => 'Event class does not exist.'], 422);
        }

        if ($hold) {
            // Write directly to intercepts table
            $maxOrder = MiddleManIntercept::pending()->max('sort_order') ?? 0;

            MiddleManIntercept::create([
                'event_class'    => $eventClass,
                'event_name'     => class_basename($eventClass),
                'payload'        => $validated['payload'],
                'metadata'       => ['source' => 'marshal', 'created_by' => $request->user()->id],
                'status'         => MiddleManIntercept::STATUS_PENDING,
                'sort_order'     => $maxOrder + 1,
                'intercepted_at' => now(),
            ]);

            MiddleManAuditEntry::record(
                $request->user()->id,
                MiddleManAuditEntry::ACTION_EVENT_MARSHALLED,
                null,
                null,
                ['event_class' => $eventClass, 'action' => 'held'],
            );

            return response()->json(['success' => true, 'action' => 'held']);
        }

        // Attempt to instantiate and dispatch
        try {
            $event = $this->instantiateEvent($eventClass, $validated['payload']);

            $dispatcher = app(\Illuminate\Contracts\Events\Dispatcher::class);

            if ($dispatcher instanceof MiddleManDispatcher) {
                $dispatcher->dispatchBypassing($event);
            } else {
                event($event);
            }

            MiddleManAuditEntry::record(
                $request->user()->id,
                MiddleManAuditEntry::ACTION_EVENT_MARSHALLED,
                null,
                null,
                ['event_class' => $eventClass, 'action' => 'fired'],
            );

            return response()->json(['success' => true, 'action' => 'fired']);
        } catch (\Throwable $e) {
            return response()->json([
                'error'   => 'Failed to instantiate event.',
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Batch-create events from a JSON array.
     */
    public function batch(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'event_class' => 'required|string|max:255',
            'items'       => 'required|array|min:1',
            'items.*'     => 'array',
            'hold'        => 'boolean',
        ]);

        $eventClass = $validated['event_class'];
        $hold = $validated['hold'] ?? false;

        if (! class_exists($eventClass)) {
            return response()->json(['error' => 'Event class does not exist.'], 422);
        }

        $results = ['success' => 0, 'failed' => 0, 'errors' => []];

        foreach ($validated['items'] as $index => $payload) {
            try {
                if ($hold) {
                    $maxOrder = MiddleManIntercept::pending()->max('sort_order') ?? 0;

                    MiddleManIntercept::create([
                        'event_class'    => $eventClass,
                        'event_name'     => class_basename($eventClass),
                        'payload'        => $payload,
                        'metadata'       => ['source' => 'marshal_batch', 'index' => $index, 'created_by' => $request->user()->id],
                        'status'         => MiddleManIntercept::STATUS_PENDING,
                        'sort_order'     => $maxOrder + 1,
                        'intercepted_at' => now(),
                    ]);
                } else {
                    $event = $this->instantiateEvent($eventClass, $payload);

                    $dispatcher = app(\Illuminate\Contracts\Events\Dispatcher::class);
                    if ($dispatcher instanceof MiddleManDispatcher) {
                        $dispatcher->dispatchBypassing($event);
                    } else {
                        event($event);
                    }
                }

                $results['success']++;
            } catch (\Throwable $e) {
                $results['failed']++;
                $results['errors'][] = ['index' => $index, 'message' => $e->getMessage()];
            }
        }

        MiddleManAuditEntry::record(
            $request->user()->id,
            MiddleManAuditEntry::ACTION_EVENT_MARSHALLED,
            null,
            null,
            [
                'event_class' => $eventClass,
                'action'      => $hold ? 'batch_held' : 'batch_fired',
                'success'     => $results['success'],
                'failed'      => $results['failed'],
            ],
        );

        return response()->json($results);
    }

    /*
    |--------------------------------------------------------------------------
    | Internal
    |--------------------------------------------------------------------------
    */

    /**
     * Attempt to instantiate an event class from a payload array.
     * Uses Reflection to map payload keys to constructor parameters.
     */
    private function instantiateEvent(string $eventClass, array $payload): object
    {
        $ref = new \ReflectionClass($eventClass);
        $constructor = $ref->getConstructor();

        if ($constructor === null) {
            return $ref->newInstance();
        }

        $args = [];
        foreach ($constructor->getParameters() as $param) {
            $name = $param->getName();

            if (array_key_exists($name, $payload)) {
                $value = $payload[$name];

                // Auto-resolve Eloquent models by ID
                $type = $param->getType();
                if ($type instanceof \ReflectionNamedType
                    && class_exists($type->getName())
                    && is_subclass_of($type->getName(), \Illuminate\Database\Eloquent\Model::class)
                ) {
                    $modelClass = $type->getName();
                    $value = $modelClass::findOrFail($value);
                }

                $args[] = $value;
            } elseif ($param->isDefaultValueAvailable()) {
                $args[] = $param->getDefaultValue();
            } elseif ($param->allowsNull()) {
                $args[] = null;
            } else {
                throw new \InvalidArgumentException("Missing required parameter: {$name}");
            }
        }

        return $ref->newInstanceArgs($args);
    }
}
