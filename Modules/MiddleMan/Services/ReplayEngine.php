<?php

declare(strict_types=1);

namespace Modules\MiddleMan\Services;

use Illuminate\Contracts\Events\Dispatcher as DispatcherContract;
use Modules\MiddleMan\Models\MiddleManIntercept;
use Modules\MiddleMan\Models\MiddleManLog;
use ReflectionClass;
use ReflectionNamedType;
use ReflectionParameter;
use Throwable;

/**
 * Replays a previously logged event by rehydrating it from the stored payload
 * and dispatching it through the base dispatcher in bypass mode.
 *
 * Features defensive hydration:
 *   - Catches all hydration failures (missing classes, schema changes, corrupted data)
 *   - Flags records as "corrupted" in the UI rather than throwing 500s
 *   - Handles model references that no longer exist (soft-deletes, purged data)
 *   - Validates constructor signatures against stored payload before attempting hydration
 */
final class ReplayEngine
{
    public function __construct(
        private readonly MiddleManContext $context,
    ) {}

    /**
     * Replay a log entry by its ID.
     *
     * @return array{success: bool, event_class: string, message: string, corrupted: bool}
     */
    public function replay(int $logId): array
    {
        $log = MiddleManLog::find($logId);

        if ($log === null) {
            return [
                'success' => false,
                'event_class' => 'unknown',
                'message' => "Log entry [{$logId}] does not exist.",
                'corrupted' => false,
            ];
        }

        $eventClass = $log->event_class;
        $payload = $log->payload ?? [];

        // Guard: event class must still exist in the codebase
        if (! class_exists($eventClass)) {
            $this->flagCorrupted($log, "Event class [{$eventClass}] no longer exists in the codebase.");

            return [
                'success' => false,
                'event_class' => $eventClass,
                'message' => "Event class [{$eventClass}] no longer exists. Record flagged as corrupted.",
                'corrupted' => true,
            ];
        }

        // Guard: validate constructor compatibility before attempting hydration
        $compatibilityCheck = $this->validateConstructorCompatibility($eventClass, $payload);
        if ($compatibilityCheck !== null) {
            $this->flagCorrupted($log, $compatibilityCheck);

            return [
                'success' => false,
                'event_class' => $eventClass,
                'message' => "Schema mismatch: {$compatibilityCheck}",
                'corrupted' => true,
            ];
        }

        // Attempt defensive hydration
        try {
            $event = $this->rehydrate($eventClass, $payload);
        } catch (Throwable $e) {
            $this->flagCorrupted($log, "Hydration failed: {$e->getMessage()}");

            return [
                'success' => false,
                'event_class' => $eventClass,
                'message' => "Failed to rehydrate event: {$e->getMessage()}",
                'corrupted' => true,
            ];
        }

        // Dispatch via the dispatcher — if MiddleManDispatcher, use bypass mode
        try {
            $dispatcher = app(DispatcherContract::class);

            if ($dispatcher instanceof MiddleManDispatcher) {
                $dispatcher->dispatchBypassing($event);
            } else {
                $dispatcher->dispatch($event);
            }
        } catch (Throwable $e) {
            return [
                'success' => false,
                'event_class' => $eventClass,
                'message' => "Event hydrated but dispatch failed: {$e->getMessage()}",
                'corrupted' => false,
            ];
        }

        return [
            'success' => true,
            'event_class' => $eventClass,
            'message' => "Event [{$eventClass}] replayed successfully.",
            'corrupted' => false,
        ];
    }

    /**
     * Replay a pending intercept entry with defensive hydration.
     *
     * @return array{success: bool, event_class: string, message: string, corrupted: bool}
     */
    public function replayIntercept(MiddleManIntercept $intercept): array
    {
        $eventClass = $intercept->event_class;
        $payload = $intercept->payload ?? [];

        if (! class_exists($eventClass)) {
            $intercept->update([
                'status' => 'corrupted',
                'metadata' => array_merge($intercept->metadata ?? [], [
                    'corruption_reason' => "Event class [{$eventClass}] no longer exists.",
                    'corrupted_at' => now()->toIso8601String(),
                ]),
            ]);

            return [
                'success' => false,
                'event_class' => $eventClass,
                'message' => "Event class [{$eventClass}] no longer exists. Intercept flagged as corrupted.",
                'corrupted' => true,
            ];
        }

        try {
            $event = $this->rehydrate($eventClass, $payload);
        } catch (Throwable $e) {
            $intercept->update([
                'status' => 'corrupted',
                'metadata' => array_merge($intercept->metadata ?? [], [
                    'corruption_reason' => "Hydration failed: {$e->getMessage()}",
                    'corrupted_at' => now()->toIso8601String(),
                ]),
            ]);

            return [
                'success' => false,
                'event_class' => $eventClass,
                'message' => "Hydration failed: {$e->getMessage()}",
                'corrupted' => true,
            ];
        }

        $dispatcher = app(DispatcherContract::class);

        if ($dispatcher instanceof MiddleManDispatcher) {
            $dispatcher->dispatchBypassing($event);
        } else {
            $dispatcher->dispatch($event);
        }

        return [
            'success' => true,
            'event_class' => $eventClass,
            'message' => "Intercepted event [{$eventClass}] dispatched successfully.",
            'corrupted' => false,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Defensive Hydration
    |--------------------------------------------------------------------------
    */

    /**
     * Rehydrate an event object from its class name and stored payload.
     *
     * Strategy:
     * 1. If constructor parameters can be matched to payload keys → construct normally
     * 2. If no constructor → instantiate and set public properties
     * 3. Fallback → ReflectionClass::newInstanceWithoutConstructor
     *
     * All model references are resolved defensively — a missing model
     * returns null (or throws if the parameter is non-nullable).
     *
     * @param array<string, mixed> $payload
     */
    private function rehydrate(string $eventClass, array $payload): object
    {
        /** @var class-string $eventClass */
        $ref = new ReflectionClass($eventClass);
        $constructor = $ref->getConstructor();

        // Strategy 1: Constructor-based reconstruction
        if ($constructor !== null && $constructor->getNumberOfParameters() > 0) {
            $args = [];
            foreach ($constructor->getParameters() as $param) {
                $name = $param->getName();
                if (array_key_exists($name, $payload)) {
                    $args[] = $this->coerceValue($param, $payload[$name]);
                } elseif ($param->isDefaultValueAvailable()) {
                    $args[] = $param->getDefaultValue();
                } elseif ($param->allowsNull()) {
                    $args[] = null;
                } else {
                    throw new \RuntimeException(
                        "Cannot resolve constructor parameter [{$name}] for [{$eventClass}]."
                    );
                }
            }

            return $ref->newInstanceArgs($args);
        }

        // Strategy 2: No constructor — set public properties
        if ($constructor === null || $constructor->getNumberOfParameters() === 0) {
            $instance = $ref->newInstance();
            foreach ($payload as $key => $value) {
                if (str_starts_with($key, '_')) {
                    continue; // Skip meta keys like _truncated, _type
                }
                if ($ref->hasProperty($key) && $ref->getProperty($key)->isPublic()) {
                    $ref->getProperty($key)->setValue($instance, $value);
                }
            }

            return $instance;
        }

        return $ref->newInstanceWithoutConstructor();
    }

    /**
     * Best-effort type coercion for constructor parameters.
     * Models stored as {_type, _id, _table} are re-fetched from DB.
     * Missing models return null if the parameter allows it.
     */
    private function coerceValue(ReflectionParameter $param, mixed $value): mixed
    {
        // Handle serialized Eloquent model references
        if (is_array($value) && isset($value['_type'], $value['_id'])) {
            $modelClass = $value['_type'];
            if (class_exists($modelClass) && is_subclass_of($modelClass, \Illuminate\Database\Eloquent\Model::class)) {
                $model = $modelClass::find($value['_id']);

                // Defensive: model may have been deleted since serialization
                if ($model === null) {
                    if ($param->allowsNull()) {
                        return null;
                    }

                    throw new \RuntimeException(
                        "Model [{$modelClass}] with ID [" . (string) ($value['_id'] ?? '') . "] no longer exists and parameter [{$param->getName()}] is non-nullable." // @phpstan-ignore cast.string
                    );
                }

                return $model;
            }
        }

        // Scalar/direct coercion by type hint
        $type = $param->getType();
        if ($type instanceof ReflectionNamedType) {
            $typeName = $type->getName();

            // Backed enum coercion
            if (class_exists($typeName) && is_subclass_of($typeName, \BackedEnum::class)) {
                try {
                    /** @var int|string $enumValue */
                    $enumValue = $value;
                    return $typeName::from($enumValue);
                } catch (Throwable) {
                    if ($param->allowsNull()) {
                        return null;
                    }
                    throw new \RuntimeException(
                        "Cannot coerce value '" . (is_scalar($value) ? (string) $value : gettype($value)) . "' to enum [{$typeName}]."
                    );
                }
            }
        }

        return $value;
    }

    /*
    |--------------------------------------------------------------------------
    | Validation & Corruption Flagging
    |--------------------------------------------------------------------------
    */

    /**
     * Pre-validate that the stored payload is compatible with the current constructor.
     * Returns null if compatible, or an error message if not.
     *
     * @param array<string, mixed> $payload
     */
    private function validateConstructorCompatibility(string $eventClass, array $payload): ?string
    {
        try {
            /** @var class-string $eventClass */
            $ref = new ReflectionClass($eventClass);
            $constructor = $ref->getConstructor();

            if ($constructor === null) {
                return null; // No constructor = always compatible
            }

            foreach ($constructor->getParameters() as $param) {
                $name = $param->getName();

                if (! array_key_exists($name, $payload) && ! $param->isOptional() && ! $param->allowsNull()) {
                    return "Required parameter [{$name}] is missing from stored payload.";
                }
            }
        } catch (Throwable $e) {
            return "Reflection failed: {$e->getMessage()}";
        }

        return null;
    }

    /**
     * Flag a log entry as corrupted so the UI can display it appropriately.
     */
    private function flagCorrupted(MiddleManLog $log, string $reason): void
    {
        try {
            $metadata = $log->metadata ?? [];
            $metadata['corruption_reason'] = $reason;
            $metadata['corrupted_at'] = now()->toIso8601String();

            $log->update([
                'has_schema_drift' => true,
                'metadata' => $metadata,
            ]);
        } catch (Throwable) {
            // Non-critical — absorb
        }
    }
}
