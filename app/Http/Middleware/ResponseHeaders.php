<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Add security response headers and remove unwanted headers.
 */
class ResponseHeaders
{
    /**
     * Headers to remove from response.
     *
     * @var array<string>
     */
    private array $unwantedHeaders = [
        'X-Powered-By',
        'Server',
    ];

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $this->removeUnwantedHeaders($this->unwantedHeaders);

        $response = $next($request);

        // Add security headers
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        // Disable caching for dynamic content
        $response->headers->set('Pragma', 'no-cache');
        $response->headers->set('Cache-Control', 'no-cache, max-age=0, must-revalidate, no-store');

        return $response;
    }

    /**
     * Remove unwanted headers from response.
     *
     * @param  array<string>  $headerList
     */
    private function removeUnwantedHeaders(array $headerList): void
    {
        foreach ($headerList as $header) {
            header_remove($header);
        }
    }
}
