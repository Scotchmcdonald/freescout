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
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        /** @var \App\Models\User|null $user */
        $user = $request->user();

        if (! $user || ! $user->isAdmin()) {
            \Illuminate\Support\Facades\Log::warning('EnsureUserIsAdmin: Access denied', [
                'user_id' => $user?->id,
                'role' => $user?->role,
                'is_admin' => $user?->isAdmin(),
                'path' => $request->path(),
            ]);
            abort(403, 'Unauthorized action.');
        }

        return $next($request);
    }
}
