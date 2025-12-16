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
        // Generate nonce for CSP
        $nonce = base64_encode(random_bytes(16));
        $request->attributes->set('csp_nonce', $nonce);

        $this->removeUnwantedHeaders();

        $response = $next($request);

        // Add security headers
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('X-XSS-Protection', '1; mode=block');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        
        // Content Security Policy - Development-friendly for Alpine.js/Vue + Laravel Reverb
        // In production, tighten this based on your specific needs
        $csp = implode('; ', [
            "default-src 'self'",
            "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://static.cloudflareinsights.com", // Alpine/Vue + Cloudflare
            "style-src 'self' 'unsafe-inline' https://fonts.bunny.net",
            "img-src 'self' data: https:",
            "font-src 'self' data: https://fonts.bunny.net",
            "connect-src 'self' ws: wss: https://cloudflareinsights.com", // WebSocket + Cloudflare analytics
            "frame-ancestors 'none'",
            "base-uri 'self'",
            "form-action 'self'",
        ]);
        
        // Only add upgrade-insecure-requests if we're already on HTTPS
        if ($request->secure()) {
            $csp .= "; upgrade-insecure-requests";
        }
        
        $response->headers->set('Content-Security-Policy', $csp);

        // Permissions Policy (formerly Feature-Policy)
        // Disable dangerous browser features
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), payment=()');

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
