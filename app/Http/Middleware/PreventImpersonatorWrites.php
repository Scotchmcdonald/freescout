<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Http\Controllers\ImpersonationController;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class PreventImpersonatorWrites
{
    /**
     * Handle an incoming request.
     *
     * - Auto-expires impersonation sessions that exceed the TTL
     * - Prevents write operations while impersonating
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();

        if ($user === null || ! method_exists($user, 'isImpersonated') || ! $user->isImpersonated()) {
            return $next($request);
        }

        // ── TTL enforcement ─────────────────────────────────────────────
        $startedAt = session(ImpersonationController::SESSION_STARTED_AT_KEY);
        $ttlMinutes = ImpersonationController::IMPERSONATION_TTL_MINUTES;

        if ($startedAt && is_numeric($startedAt) && now()->getTimestamp() - (int) $startedAt > $ttlMinutes * 60) {
            Log::warning('Impersonation auto-expired after TTL', [
                'admin_id' => session()->get('impersonated_by'),
                'impersonated_user_id' => $user->id,
                'ttl_minutes' => $ttlMinutes,
                'elapsed_seconds' => now()->getTimestamp() - (int) $startedAt,
            ]);

            session()->forget(ImpersonationController::SESSION_STARTED_AT_KEY);
            $user->leaveImpersonation();

            return redirect()->route('dashboard')
                ->with('warning', '⏱️ Your impersonation session expired automatically after '.$ttlMinutes.' minutes.');
        }

        // ── Allow exit routes ───────────────────────────────────────────
        if ($request->routeIs('impersonate.leave') || $request->routeIs('impersonate.leave.emergency') || $request->routeIs('portal.logout')) {
            return $next($request);
        }

        // ── Block write operations ──────────────────────────────────────
        if (in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'])) {
            return back()->with('error', '⚠️ Read-Only Mode: You cannot make changes while viewing as a customer.');
        }

        return $next($request);
    }
}
