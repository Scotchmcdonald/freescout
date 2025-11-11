<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * FrameGuard Middleware
 * 
 * Adds X-Frame-Options header to prevent clickjacking attacks.
 * This prevents the application from being embedded in iframes on other domains.
 */
class FrameGuard
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        
        // Set X-Frame-Options to SAMEORIGIN
        // This allows framing from the same origin but blocks cross-origin framing
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN', false);
        
        // Also set Content-Security-Policy frame-ancestors directive for modern browsers
        $csp = $response->headers->get('Content-Security-Policy', '');
        if (empty($csp)) {
            $response->headers->set('Content-Security-Policy', "frame-ancestors 'self'", false);
        } else {
            // Append to existing CSP if present
            if (!str_contains($csp, 'frame-ancestors')) {
                $response->headers->set('Content-Security-Policy', $csp . "; frame-ancestors 'self'", false);
            }
        }
        
        return $response;
    }
}
