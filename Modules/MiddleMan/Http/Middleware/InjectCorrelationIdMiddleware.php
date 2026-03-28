<?php

declare(strict_types=1);

namespace Modules\MiddleMan\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\MiddleMan\Services\MiddleManContext;
use Symfony\Component\HttpFoundation\Response;

/**
 * HTTP middleware that initialises the MiddleManContext for the current request.
 *
 * - Reads an incoming X-Correlation-ID header (if present) so that traces
 *   can be continued across service boundaries.
 * - Attaches the correlation_id to the outgoing response header so callers
 *   can correlate downstream activity.
 */
class InjectCorrelationIdMiddleware
{
    public function __construct(private readonly MiddleManContext $context) {}

    public function handle(Request $request, Closure $next): Response
    {
        // If the upstream caller provided a correlation ID, adopt it
        $incoming = $request->header('X-Correlation-ID');
        if (is_string($incoming) && $incoming !== '') {
            $this->context->setCorrelationId($incoming);
        }

        // Propagate causation if provided
        $causation = $request->header('X-Causation-ID');
        if (is_string($causation) && $causation !== '') {
            $this->context->setCausationId($causation);
        }

        /** @var Response $response */
        $response = $next($request);

        // Echo the correlation ID on the response so callers can trace
        $response->headers->set('X-Correlation-ID', $this->context->correlationId());

        return $response;
    }
}
