<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use TorMorten\Eventy\Facades\Eventy;

class Localize
{
    /**
     * Handle an incoming request.
     *
     * Set the application locale based on user preferences.
     * Uses the Eventy package to allow modules to filter the locale.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Get user locale from session or user preferences
        $sessionLocale = session('user_locale');
        
        // Allow modules to filter/override via Eventy hook
        // Note: Eventy::filter returns void, so we use sessionLocale directly
        Eventy::filter('locale', $sessionLocale);
        
        if (is_string($sessionLocale)) {
            app()->setLocale($sessionLocale);
        }

        return $next($request);
    }
}
