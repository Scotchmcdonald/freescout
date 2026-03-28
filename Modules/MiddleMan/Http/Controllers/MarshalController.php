<?php

declare(strict_types=1);

namespace Modules\MiddleMan\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;
use Modules\MiddleMan\Contracts\MiddleManSearchable;
use Modules\MiddleMan\Models\MiddleManAuditEntry;
use Modules\MiddleMan\Models\MiddleManIntercept;
use Modules\MiddleMan\Models\MiddleManPreset;
use Modules\MiddleMan\Services\EventDiscoveryService;
use Modules\MiddleMan\Services\MiddleManDispatcher;

class MarshalController extends Controller
{
    public function index(EventDiscoveryService $discovery): View
    {
        $availableEvents = $discovery->discover();

        return view('middleman::marshal.index', compact('availableEvents'));
    }

    /**
     * Return constructor parameters for a specific event class.
     */
    public function parameters(Request $request, EventDiscoveryService $discovery): JsonResponse
    {
        $validated = $request->validate([
            'event_class' => 'required|string|max:255',
        ]);

        $params = $discovery->getParameters($validated['event_class']);

        // Also return any saved presets for this event
        $presets = MiddleManPreset::forEvent($validated['event_class'])
            ->orderBy('name')
            ->get(['id', 'name', 'payload'])
            ->toArray();

        return response()->json([
            'parameters' => $params,
            'presets' => $presets,
        ]);
    }

    /**
     * Async searchable endpoint for Eloquent model parameters.
     * Returns matching records by ID, name, email, or other searchable columns.
     *
     * Security: only models that implement MiddleManSearchable OR are explicitly
     * listed in `middleman.searchable_models` config are permitted.  All other
     * model classes — even valid Eloquent subclasses — are rejected with a 403.
     */
    public function searchModel(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'model_class' => 'required|string|max:255',
            'query' => 'required|string|max:100',
        ]);

        $modelClass = $validated['model_class'];
        $searchQuery = $validated['query'];

        // ── Allowlist gate ─────────────────────────────────────────────────────
        // A model is searchable when it EITHER:
        //   (a) implements the MiddleManSearchable interface, OR
        //   (b) its FQCN appears in the config-defined allowlist.
        // Reject anything else with a 403 to prevent sensitive model enumeration.
        if (! $this->isSearchableModel($modelClass)) {
            return response()->json([
                'error' => 'Model is not searchable. Implement MiddleManSearchable or add it to middleman.searchable_models.',
            ], 403);
        }
        // ──────────────────────────────────────────────────────────────────────

        try {
            /** @var \Illuminate\Database\Eloquent\Model $instance */
            $instance = new $modelClass;
            $keyName = $instance->getKeyName();
            $table = $instance->getTable();

            $query = $modelClass::query()->limit(20);

            // Smart search: try numeric ID first, then common string columns
            if (is_numeric($searchQuery)) {
                $query->where($keyName, $searchQuery);
            } else {
                $searchableColumns = $this->getSearchableColumns($instance);

                if ($searchableColumns !== []) {
                    $query->where(function ($q) use ($searchableColumns, $searchQuery): void {
                        foreach ($searchableColumns as $col) {
                            $q->orWhere($col, 'like', '%'.$searchQuery.'%');
                        }
                    });
                } else {
                    // Fallback: search by key if it's string-typed
                    $query->where($keyName, 'like', '%'.$searchQuery.'%');
                }
            }

            $results = $query->get()->map(function ($model) use ($keyName): array {
                return [
                    'id' => $model->getKey(),
                    'label' => $this->buildModelLabel($model, $keyName),
                ];
            })->all();

            return response()->json(['results' => $results]);
        } catch (\Throwable $e) {
            return response()->json(['results' => [], 'error' => $e->getMessage()]);
        }
    }

    /**
     * Save a preset for quick re-use.
     */
    public function savePreset(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'event_class' => 'required|string|max:255',
            'name' => 'required|string|max:255',
            'payload' => 'required|array',
        ]);

        $maxPresets = (int) config('middleman.max_presets_per_event', 25); // @phpstan-ignore cast.int
        $existing = MiddleManPreset::forEvent($validated['event_class'])->count();

        if ($existing >= $maxPresets) {
            return response()->json([
                'error' => "Maximum of {$maxPresets} presets per event class reached.",
            ], 422);
        }

        $preset = MiddleManPreset::create([
            'event_class' => $validated['event_class'],
            'name' => $validated['name'],
            'payload' => $validated['payload'],
            'created_by' => (int) $request->user()?->id,
        ]);

        return response()->json(['success' => true, 'preset' => $preset]);
    }

    /**
     * Delete a preset.
     */
    public function deletePreset(Request $request, int $id): JsonResponse
    {
        $preset = MiddleManPreset::findOrFail($id);
        $preset->delete();

        return response()->json(['success' => true]);
    }

    /**
     * Create and dispatch (or hold) a single event.
     */
    public function fire(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'event_class' => 'required|string|max:255',
            'payload' => 'required|array',
            'hold' => 'boolean',
        ]);

        $eventClass = $validated['event_class'];
        $hold = $validated['hold'] ?? false;

        if (! class_exists($eventClass)) {
            return response()->json(['error' => 'Event class does not exist.'], 422);
        }

        if ($hold) {
            $maxOrder = MiddleManIntercept::pending()->max('sort_order') ?? 0;

            MiddleManIntercept::create([
                'event_class' => $eventClass,
                'event_name' => class_basename($eventClass),
                'payload' => $validated['payload'],
                'metadata' => ['source' => 'marshal', 'created_by' => (int) $request->user()?->id],
                'status' => MiddleManIntercept::STATUS_PENDING,
                'sort_order' => (int) $maxOrder + 1, // @phpstan-ignore cast.int
                'intercepted_at' => now(),
            ]);

            MiddleManAuditEntry::record(
                (int) $request->user()?->id,
                MiddleManAuditEntry::ACTION_EVENT_MARSHALLED,
                null,
                null,
                ['event_class' => $eventClass, 'action' => 'held'],
            );

            return response()->json(['success' => true, 'action' => 'held']);
        }

        try {
            $event = $this->instantiateEvent($eventClass, $validated['payload']);

            $dispatcher = app(\Illuminate\Contracts\Events\Dispatcher::class);

            if ($dispatcher instanceof MiddleManDispatcher) {
                $dispatcher->dispatchBypassing($event);
            } else {
                event($event);
            }

            MiddleManAuditEntry::record(
                (int) $request->user()?->id,
                MiddleManAuditEntry::ACTION_EVENT_MARSHALLED,
                null,
                null,
                ['event_class' => $eventClass, 'action' => 'fired'],
            );

            return response()->json(['success' => true, 'action' => 'fired']);
        } catch (\Throwable $e) {
            return response()->json([
                'error' => 'Failed to instantiate event.',
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
            'items' => 'required|array|min:1|max:100',
            'items.*' => 'array',
            'hold' => 'boolean',
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
                        'event_class' => $eventClass,
                        'event_name' => class_basename($eventClass),
                        'payload' => $payload,
                        'metadata' => ['source' => 'marshal_batch', 'index' => $index, 'created_by' => (int) $request->user()?->id],
                        'status' => MiddleManIntercept::STATUS_PENDING,
                        'sort_order' => (int) $maxOrder + 1, // @phpstan-ignore cast.int
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
            (int) $request->user()?->id,
            MiddleManAuditEntry::ACTION_EVENT_MARSHALLED,
            null,
            null,
            [
                'event_class' => $eventClass,
                'action' => $hold ? 'batch_held' : 'batch_fired',
                'success' => $results['success'],
                'failed' => $results['failed'],
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
     * Determine whether a model class is permitted for async search.
     *
     * Allowed paths:
     *   1. Class exists and is an Eloquent model (baseline safety check).
     *   2. Implements MiddleManSearchable interface (opt-in via code), OR
     *   3. FQCN is in the config allowlist (opt-in via environment).
     */
    private function isSearchableModel(string $modelClass): bool
    {
        if (! class_exists($modelClass)) {
            return false;
        }

        if (! is_subclass_of($modelClass, \Illuminate\Database\Eloquent\Model::class)) {
            return false;
        }

        // Interface opt-in
        if (is_a($modelClass, MiddleManSearchable::class, true)) {
            return true;
        }

        // Config allowlist opt-in
        /** @var string[] $allowlist */
        $allowlist = config('middleman.searchable_models', []);

        return in_array($modelClass, (array) $allowlist, true);
    }

    /**
     * Attempt to instantiate an event class from a payload array.
     * Uses Reflection to map payload keys to constructor parameters,
     * with automatic model resolution, enum coercion, and type casting.
     */
    /** @param array<string, mixed> $payload */
    private function instantiateEvent(string $eventClass, array $payload): object
    {
        /** @var class-string $eventClass */
        $ref = new \ReflectionClass($eventClass);
        $constructor = $ref->getConstructor();

        if ($constructor === null) {
            return $ref->newInstance();
        }

        $args = [];
        foreach ($constructor->getParameters() as $param) {
            $name = $param->getName();

            if (array_key_exists($name, $payload)) {
                $args[] = $this->coerceParameterValue($param, $payload[$name]);
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

    /**
     * Coerce a raw form value into the type expected by the constructor parameter.
     */
    private function coerceParameterValue(\ReflectionParameter $param, mixed $value): mixed
    {
        $type = $param->getType();

        if (! $type instanceof \ReflectionNamedType) {
            return $value;
        }

        $typeName = $type->getName();

        // Eloquent models: resolve by primary key
        if (class_exists($typeName) && is_subclass_of($typeName, \Illuminate\Database\Eloquent\Model::class)) {
            return $typeName::findOrFail($value);
        }

        // PHP 8.1+ backed enums: resolve from value
        if (class_exists($typeName) && is_subclass_of($typeName, \BackedEnum::class)) {
            /** @var int|string $value */
            return $typeName::from($value);
        }

        // PHP 8.1+ unit enums: resolve from name
        if (class_exists($typeName) && is_subclass_of($typeName, \UnitEnum::class)) {
            foreach ($typeName::cases() as $case) {
                if ($case->name === $value) {
                    return $case;
                }
            }
            throw new \InvalidArgumentException("Invalid enum case '" . (is_scalar($value) ? (string) $value : gettype($value)) . "' for {$typeName}");
        }

        // Scalar type coercion
        return match ($typeName) {
            'int', 'integer' => (int) (is_scalar($value) ? $value : 0),
            'float', 'double' => (float) (is_scalar($value) ? $value : 0),
            'bool', 'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'string' => is_scalar($value) ? (string) $value : (is_array($value) ? json_encode($value) : ''),
            'array' => is_array($value) ? $value : json_decode(is_string($value) ? $value : '', true) ?? [$value],
            default => $value,
        };
    }

    /**
     * Determine searchable columns for a model's async search.
     *
     * @return string[]
     */
    private function getSearchableColumns(\Illuminate\Database\Eloquent\Model $model): array
    {
        // Common searchable column names, checked against the model's table
        $candidates = ['name', 'email', 'title', 'label', 'first_name', 'last_name', 'username', 'slug'];
        $columns = [];

        try {
            $schema = $model->getConnection()->getSchemaBuilder();
            $tableColumns = $schema->getColumnListing($model->getTable());

            foreach ($candidates as $col) {
                if (in_array($col, $tableColumns, true)) {
                    $columns[] = $col;
                }
            }
        } catch (\Throwable) {
            // Schema introspection failed — return empty
        }

        return $columns;
    }

    /**
     * Build a human-readable label for a model instance in the search dropdown.
     */
    private function buildModelLabel(\Illuminate\Database\Eloquent\Model $model, string $keyName): string
    {
        $id = (string) $model->getKey(); // @phpstan-ignore cast.string

        // Try common label fields
        foreach (['name', 'email', 'title', 'label'] as $field) {
            if (isset($model->{$field}) && is_string($model->{$field})) {
                return "#{$id} — {$model->{$field}}";
            }
        }

        // Try first_name + last_name combo
        if (isset($model->first_name, $model->last_name)) {
            return "#{$id} — {$model->first_name} {$model->last_name}";
        }

        return "#{$id}";
    }
}
