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
        $this->removeUnwantedHeaders();

        $response = $next($request);

        // Add security headers
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('X-XSS-Protection', '1; mode=block');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        
        // Content Security Policy - Configure based on your needs
        // Adjust this policy if you use inline scripts/styles or external resources
        $csp = implode('; ', [
            "default-src 'self'",
            "script-src 'self' 'unsafe-inline' 'unsafe-eval'", // Allow inline scripts (required for Laravel/Alpine.js)
            "style-src 'self' 'unsafe-inline'", // Allow inline styles
            "img-src 'self' data: https:",
            "font-src 'self' data:",
            "connect-src 'self'",
            "frame-ancestors 'self'",
        ]);
        $response->headers->set('Content-Security-Policy', $csp);

        // Only disable caching for HTML/dynamic content, not static assets
        $contentType = $response->headers->get('Content-Type', '');
        if (str_contains($contentType, 'text/html') || empty($contentType)) {
            $response->headers->set('Pragma', 'no-cache');
            $response->headers->set('Cache-Control', 'no-cache, max-age=0, must-revalidate, no-store');
        }

        return $response;
    }

    /**
     * Remove unwanted headers from response.
     */
    private function removeUnwantedHeaders(): void
    {
        foreach ($this->unwantedHeaders as $header) {
            header_remove($header);
        }
    }
}
