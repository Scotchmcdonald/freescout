<?php

declare(strict_types=1);

namespace Modules\MiddleMan\Queue;

use Closure;
use Modules\MiddleMan\Services\MiddleManContext;

/**
 * Queue middleware that propagates MiddleMan tracing context across job boundaries.
 *
 * Attach to any job via the `middleware()` method:
 *
 *   public function middleware(): array
 *   {
 *       return [new PropagateContextMiddleware($this->correlationId, $this->causationId)];
 *   }
 *
 * The WriteLogEntryJob and WriteInterceptEntryJob use this automatically.
 */
class PropagateContextMiddleware
{
    public function __construct(
        private readonly string $correlationId,
        private readonly ?string $causationId = null,
    ) {}

    public function handle(object $job, Closure $next): void
    {
        $context = app(MiddleManContext::class);

        // Hydrate the context with the values from the dispatching process
        $context->setCorrelationId($this->correlationId);
        $context->setCausationId($this->causationId);

        $next($job);
    }
}
