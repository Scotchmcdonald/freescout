<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PreventImpersonatorWrites
{
    /**
     * Handle an incoming request.
     * Prevent write operations while impersonating.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if the current user is impersonating someone
        $user = auth()->user();
        if ($user !== null && method_exists($user, 'isImpersonated') && $user->isImpersonated()) {
            // Allow leaving impersonation and logout
            if ($request->routeIs('impersonate.leave') || $request->routeIs('portal.logout')) {
                return $next($request);
            }
            
            // Block destructive HTTP methods for all other routes
            if (in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'])) {
                // Return with an error message
                return back()->with('error', '⚠️ Read-Only Mode: You cannot make changes while viewing as a customer.');
            }
        }

        return $next($request);
    }
}
