<?php

declare(strict_types=1);

namespace Modules\MiddleMan\Services;

use Modules\MiddleMan\Contracts\MiddleManLoggable;
use ReflectionClass;
use ReflectionProperty;
use Throwable;

/**
 * Safely serializes event objects into JSON-safe payloads.
 *
 * Priority order:
 *  1. If event implements MiddleManLoggable → use toLogPayload()
 *  2. If event uses SerializesModels → use Laravel serialization
 *  3. Fallback → Reflection-based extraction of public properties,
 *     stripping closures, PDO connections, and heavy objects.
 */
final class EventSerializer
{
    private int $maxBytes;

    public function __construct()
    {
        $this->maxBytes = (int) config('middleman.max_payload_bytes', 65536); // @phpstan-ignore cast.int
    }

    /**
     * @return array{payload: array<string, mixed>, metadata: array<string, mixed>}
     */
    public function serialize(object $event): array
    {
        $payload = $this->extractPayload($event);
        $metadata = $this->buildMetadata($event);

        // Truncate if payload is too large
        $encoded = json_encode($payload, JSON_THROW_ON_ERROR | JSON_PARTIAL_OUTPUT_ON_ERROR);
        if (strlen($encoded) > $this->maxBytes) {
            $payload = [
                '_truncated' => true,
                '_original_size' => strlen($encoded),
                '_summary' => $this->summarize($payload),
            ];
        }

        return [
            'payload' => $payload,
            'metadata' => $metadata,
        ];
    }

    /**
     * Deserialize a stored payload back into a best-effort representation.
     * Returns the raw array — callers reconstruct events as needed.
     */
    /** @param array<string, mixed> $storedPayload */
    public function deserialize(array $storedPayload): mixed
    {
        return $storedPayload;
    }

    /*
    |--------------------------------------------------------------------------
    | Extraction Strategies
    |--------------------------------------------------------------------------
    */

    /** @return array<string, mixed> */
    private function extractPayload(object $event): array
    {
        // Strategy 1: Custom loggable interface
        if ($event instanceof MiddleManLoggable) {
            return $event->toLogPayload();
        }

        // Strategy 2 & 3: Reflection-based extraction
        return $this->extractViaReflection($event);
    }

    /** @return array<string, mixed> */
    private function extractViaReflection(object $event): array
    {
        $data = [];

        try {
            $ref = new ReflectionClass($event);

            foreach ($ref->getProperties() as $prop) {
                $data[$prop->getName()] = $this->sanitizeValue(
                    $prop->getValue($event),
                    $prop->getName(),
                );
            }
        } catch (Throwable) {
            $data['_error'] = 'Failed to reflect event properties';
        }

        return $data;
    }

    /**
     * Recursively sanitize a value, stripping non-serializable types.
     */
    private function sanitizeValue(mixed $value, string $key = '', int $depth = 0): mixed
    {
        // Prevent infinite recursion
        if ($depth > 5) {
            return '[max depth reached]';
        }

        if ($value === null || is_scalar($value)) {
            return $value;
        }

        if (is_array($value)) {
            $clean = [];
            foreach ($value as $k => $v) {
                $clean[$k] = $this->sanitizeValue($v, (string) $k, $depth + 1);
            }

            return $clean;
        }

        if ($value instanceof \Closure) {
            return '[Closure]';
        }

        if ($value instanceof \PDO || $value instanceof \PDOStatement) {
            return '[PDO Connection]';
        }

        // Eloquent models: extract key attributes
        if ($value instanceof \Illuminate\Database\Eloquent\Model) {
            return [
                '_type' => get_class($value),
                '_id' => $value->getKey(),
                '_table' => $value->getTable(),
            ];
        }

        // Generic objects: extract public properties
        if (is_object($value)) {
            try {
                $ref = new ReflectionClass($value);
                $props = [];
                $props['_type'] = get_class($value);

                foreach ($ref->getProperties(ReflectionProperty::IS_PUBLIC) as $prop) {
                    $props[$prop->getName()] = $this->sanitizeValue(
                        $prop->getValue($value),
                        $prop->getName(),
                        $depth + 1,
                    );
                }

                return $props;
            } catch (Throwable) {
                return ['_type' => get_class($value), '_string' => '[unserializable]'];
            }
        }

        if (is_resource($value)) {
            return '[resource: '.get_resource_type($value).']';
        }

        return '[unknown type]';
    }

    /** @return array<string, mixed> */
    private function buildMetadata(object $event): array
    {
        return [
            'class' => get_class($event),
            'memory_peak' => memory_get_peak_usage(true),
            'timestamp' => now()->toIso8601String(),
            'php_sapi' => PHP_SAPI,
        ];
    }

    /**
     * Build a size-limited summary when payload exceeds max bytes.
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function summarize(array $payload): array
    {
        $summary = [];
        foreach ($payload as $key => $value) {
            if (is_array($value)) {
                $summary[$key] = '[array: '.count($value).' items]';
            } elseif (is_string($value) && strlen($value) > 200) {
                $summary[$key] = substr($value, 0, 200).'…';
            } else {
                $summary[$key] = $value;
            }
        }

        return $summary;
    }
}
