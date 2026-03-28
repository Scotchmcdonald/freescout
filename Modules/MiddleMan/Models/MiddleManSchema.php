<?php

declare(strict_types=1);

namespace Modules\MiddleMan\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Stores the "baseline" schema for each event class.
 * Schema is represented as a map of property names to scalar type strings.
 *
 * Example schema:
 *   { "user_id": "integer", "email": "string", "roles": "array" }
 *
 * @property int $id
 * @property string $event_class
 * @property array<string, string>|null $schema
 * @property int $version
 * @property \Illuminate\Support\Carbon|null $locked_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class MiddleManSchema extends Model
{
    protected $table = 'middleman_schemas';

    protected $fillable = [
        'event_class',
        'schema',
        'version',
        'locked_at',
    ];

    protected function casts(): array
    {
        return [
            'schema' => 'array',
            'version' => 'integer',
            'locked_at' => 'datetime',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    /** @param Builder<self> $query
     * @return Builder<self> */
    public function scopeForEvent(Builder $query, string $eventClass): Builder
    {
        return $query->where('event_class', $eventClass);
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    /**
     * Get or create the baseline schema for an event class.
     * If no baseline exists, creates one from the given payload.
     *
     * @param  array<string, mixed>  $payload
     * @return array{baseline: self, is_new: bool}
     */
    public static function resolveBaseline(string $eventClass, array $payload): array
    {
        $existing = static::forEvent($eventClass)->orderByDesc('version')->first();

        if ($existing !== null) {
            return ['baseline' => $existing, 'is_new' => false];
        }

        $schema = static::create([
            'event_class' => $eventClass,
            'schema' => static::extractSchema($payload),
            'version' => 1,
            'locked_at' => now(),
        ]);

        return ['baseline' => $schema, 'is_new' => true];
    }

    /**
     * Extract a type-map schema from a payload array.
     *
     * @return array<string, string>
     */
    /** @param array<string, mixed> $payload
     * @return array<string, string> */
    public static function extractSchema(array $payload): array
    {
        $schema = [];

        foreach ($payload as $key => $value) {
            if (str_starts_with((string) $key, '_')) {
                continue; // Skip internal meta keys
            }

            $schema[$key] = static::detectType($value);
        }

        ksort($schema);

        return $schema;
    }

    /**
     * Compare a payload against this baseline and return drift details.
     *
     * @return array{has_drift: bool, added: string[], removed: string[], type_changed: array<string, array{expected: string, actual: string}>}
     */
    /** @param array<string, mixed> $payload
     * @return array{has_drift: bool, added: string[], removed: string[], type_changed: array<string, array{expected: string, actual: string}>} */
    public function detectDrift(array $payload): array
    {
        $currentSchema = static::extractSchema($payload);
        $baselineSchema = $this->schema ?? [];

        $added = array_diff_key($currentSchema, $baselineSchema);
        $removed = array_diff_key($baselineSchema, $currentSchema);

        $typeChanged = [];
        foreach ($currentSchema as $key => $type) {
            if (isset($baselineSchema[$key]) && $baselineSchema[$key] !== $type) {
                $typeChanged[$key] = [
                    'expected' => $baselineSchema[$key],
                    'actual' => $type,
                ];
            }
        }

        $hasDrift = $added !== [] || $removed !== [] || $typeChanged !== [];

        return [
            'has_drift' => $hasDrift,
            'added' => array_keys($added),
            'removed' => array_keys($removed),
            'type_changed' => $typeChanged,
        ];
    }

    protected static function detectType(mixed $value): string
    {
        if ($value === null) {
            return 'null';
        }
        if (is_bool($value)) {
            return 'boolean';
        }
        if (is_int($value)) {
            return 'integer';
        }
        if (is_float($value)) {
            return 'double';
        }
        if (is_string($value)) {
            return 'string';
        }
        if (is_array($value)) {
            // Distinguish between list and map
            if ($value === [] || array_is_list($value)) {
                return 'array';
            }

            return 'object';
        }

        return 'unknown';
    }
}
