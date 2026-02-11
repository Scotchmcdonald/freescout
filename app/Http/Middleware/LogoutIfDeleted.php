<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class LogoutIfDeleted
{
    /**
     * Handle an incoming request.
     *
     * Logs out users who have been deleted or disabled.
     * Requires User model to have an isDeleted() method that returns bool.
     *
     * @see \App\Models\User::isDeleted()
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check web guard (admin users)
        if (Auth::guard('web')->check()) {
            /** @var User|null $user */
            $user = Auth::guard('web')->user();

            if ($user instanceof User && $user->isDeleted()) {
                Auth::guard('web')->logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect()->route('login')
                    ->with('error', 'Your account has been deactivated.');
            }
        }

        // Client portal users are handled by EnsureClientIsActive middleware
        // No need to check client guard here

        return $next($request);
    }
}
