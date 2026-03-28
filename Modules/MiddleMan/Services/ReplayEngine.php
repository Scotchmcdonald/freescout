<?php

declare(strict_types=1);

namespace Modules\MiddleMan\Services;

use Illuminate\Contracts\Events\Dispatcher as DispatcherContract;
use Modules\MiddleMan\Models\MiddleManLog;
use ReflectionClass;
use ReflectionParameter;
use Throwable;

/**
 * Replays a previously logged event by rehydrating it from the stored payload
 * and dispatching it through the base dispatcher in bypass mode.
 *
 * The replayed event is flagged with `is_replay = true` in its metadata
 * so downstream consumers can distinguish replays from organic events.
 */
class ReplayEngine
{
    public function __construct(
        private readonly MiddleManContext $context,
    ) {}

    /**
     * Replay a log entry by its ID.
     *
     * @return array{success: bool, event_class: string, message: string}
     */
    public function replay(int $logId): array
    {
        $log = MiddleManLog::findOrFail($logId);

        $eventClass = $log->event_class;
        $payload = $log->payload ?? [];

        if (! class_exists($eventClass)) {
            return [
                'success'     => false,
                'event_class' => $eventClass,
                'message'     => "Event class [{$eventClass}] does not exist.",
            ];
        }

        try {
            $event = $this->rehydrate($eventClass, $payload);
        } catch (Throwable $e) {
            return [
                'success'     => false,
                'event_class' => $eventClass,
                'message'     => "Failed to rehydrate event: {$e->getMessage()}",
            ];
        }

        // Dispatch via the dispatcher — if MiddleManDispatcher, use bypass mode
        $dispatcher = app(DispatcherContract::class);

        if ($dispatcher instanceof MiddleManDispatcher) {
            $dispatcher->dispatchBypassing($event);
        } else {
            $dispatcher->dispatch($event);
        }

        return [
            'success'     => true,
            'event_class' => $eventClass,
            'message'     => "Event [{$eventClass}] replayed successfully.",
        ];
    }

    /**
     * Rehydrate an event object from its class name and stored payload.
     *
     * Strategy:
     * 1. If constructor parameters can be matched to payload keys → construct normally
     * 2. If no constructor → instantiate and set public properties
     * 3. Fallback → ReflectionClass::newInstanceWithoutConstructor
     */
    private function rehydrate(string $eventClass, array $payload): object
    {
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
     */
    private function coerceValue(ReflectionParameter $param, mixed $value): mixed
    {
        // Handle serialized Eloquent model references
        if (is_array($value) && isset($value['_type'], $value['_id'])) {
            $modelClass = $value['_type'];
            if (class_exists($modelClass) && is_subclass_of($modelClass, \Illuminate\Database\Eloquent\Model::class)) {
                return $modelClass::find($value['_id']);
            }
        }

        return $value;
    }
}
