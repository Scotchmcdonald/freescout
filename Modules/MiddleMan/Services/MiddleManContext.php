<?php

declare(strict_types=1);

namespace Modules\MiddleMan\Services;

use Illuminate\Support\Str;

/**
 * Request-scoped singleton that tracks distributed tracing IDs.
 *
 * - correlation_id: A single UUID that persists across the entire causal chain
 *   (HTTP request → queued job → nested event). Propagated via queue payload.
 * - causation_id:   The ID of the "parent" event that caused the current event.
 *   Forms a DAG of event causality within a single correlation chain.
 * - depth:          Tracks how many levels deep we are in the event chain.
 *
 * Usage: resolve from container (singleton) — never instantiate directly.
 */
final class MiddleManContext
{
    private string $correlationId;
    private ?string $causationId = null;
    private int $depth = 0;

    /** @var list<string|null> Stack of causation IDs for nested dispatches */
    private array $causationStack = [];

    public function __construct()
    {
        $this->correlationId = Str::uuid()->toString();
    }

    /*
    |--------------------------------------------------------------------------
    | Accessors
    |--------------------------------------------------------------------------
    */

    public function correlationId(): string
    {
        return $this->correlationId;
    }

    public function causationId(): ?string
    {
        return $this->causationId;
    }

    public function depth(): int
    {
        return $this->depth;
    }

    /*
    |--------------------------------------------------------------------------
    | Lifecycle — called by the dispatcher around each event
    |--------------------------------------------------------------------------
    */

    /**
     * Push a new causation frame before dispatching a nested event.
     * Returns the event_id that was assigned as this event's identity.
     */
    public function pushCausation(string $eventId): void
    {
        $this->causationStack[] = $this->causationId;
        $this->causationId = $eventId;
        $this->depth++;
    }

    /**
     * Pop the causation frame after the nested event completes.
     */
    public function popCausation(): void
    {
        $this->causationId = array_pop($this->causationStack);
        $this->depth = max(0, $this->depth - 1);
    }

    /*
    |--------------------------------------------------------------------------
    | Hydration — for queue propagation
    |--------------------------------------------------------------------------
    */

    /**
     * Set a specific correlation_id (used when hydrating from a queue payload).
     */
    public function setCorrelationId(string $correlationId): void
    {
        $this->correlationId = $correlationId;
    }

    /**
     * Set a specific causation_id (used when hydrating from a queue payload).
     */
    public function setCausationId(?string $causationId): void
    {
        $this->causationId = $causationId;
    }

    /*
    |--------------------------------------------------------------------------
    | Envelope — merged into log/intercept metadata
    |--------------------------------------------------------------------------
    */

    /**
     * Returns the tracing envelope to be merged into event metadata.
     *
     * @return array{correlation_id: string, causation_id: string|null, depth: int}
     */
    public function envelope(): array
    {
        return [
            'correlation_id' => $this->correlationId,
            'causation_id' => $this->causationId,
            'depth' => $this->depth,
        ];
    }

    /**
     * Reset context to a clean state (primarily for testing).
     */
    public function reset(): void
    {
        $this->correlationId = Str::uuid()->toString();
        $this->causationId = null;
        $this->depth = 0;
        $this->causationStack = [];
    }
}
