<?php

declare(strict_types=1);

namespace Modules\MiddleMan\Contracts;

/**
 * Events implementing this interface provide a safe, pre-sanitized
 * payload for MiddleMan logging — avoiding runaway serialization of
 * Eloquent models, PDO connections, or closures.
 */
interface MiddleManLoggable
{
    /**
     * Return a JSON-safe array representing this event's payload.
     *
     * @return array<string, mixed>
     */
    public function toLogPayload(): array;
}
