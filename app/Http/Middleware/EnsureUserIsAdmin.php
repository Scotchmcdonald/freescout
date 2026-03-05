<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsAdmin
{
    /**
     * Handle an incoming request.
     *
     * Allows access if the user:
     * - Has a super-admin RBAC role (is_super_admin = true), OR
     * - Has the legacy admin role (transition period fallback), OR
     * - Is internal staff (type = 1)
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        /** @var \App\Models\User|null $user */
        $user = $request->user();

        if (! $user) {
            abort(403, 'Unauthorized action.');
        }

        // RBAC: Check super-admin role (also covers legacy fallback via isAdmin())
        if ($user->isAdmin()) {
            return $next($request);
        }

        // Allow internal staff (type flag — separate from role system)
        if ((int) $user->type === 1) {
            return $next($request);
        }

        // Check for admin panel access permission
        if ($user->hasPermission('access_admin_panel')) {
            return $next($request);
        }

        \Illuminate\Support\Facades\Log::warning('EnsureUserIsAdmin: Access denied', [
            'user_id' => $user->id,
            'role' => $user->role,
            'type' => $user->type,
            'is_admin' => $user->isAdmin(),
            'path' => $request->path(),
        ]);
        abort(403, 'Unauthorized action.');
    }
}
