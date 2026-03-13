<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Eventy;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Custom request handling middleware.
 *
 * Handles chat mode toggle and provides hooks for modules.
 */
class CustomHandle
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Enable/disable chat mode based on request parameter (0 or 1)
        if ($request->exists('chat_mode')) {
            $chatModeInput = $request->input('chat_mode');
            $chatMode = is_numeric($chatModeInput) ? intval($chatModeInput) : 0;
            // Validate to ensure it's a boolean-like value
            if ($chatMode === 0 || $chatMode === 1) {
                session()->put('chat_mode', $chatMode);
            }
        }

        // Hook for modules to customize request handling
        Eventy::action('middleware.web.custom_handle', $request);

        // Allow modules to filter the response
        $response = $next($request);

        Eventy::filter('middleware.web.custom_handle.response', $response, $request, $next);

        return $response;
    }
}
