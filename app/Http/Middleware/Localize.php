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
        // Allow modules to filter/override via Eventy hook
        $userLocale = Eventy::filter('locale', session('user_locale'));

        if ($userLocale && is_string($userLocale)) {
            app()->setLocale($userLocale);
        }

        return $next($request);
    }
}
