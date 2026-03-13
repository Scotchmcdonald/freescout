<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ImpersonationController extends Controller
{
    /**
     * Maximum duration (in minutes) an impersonation session can last
     * before being automatically terminated.
     */
    public const IMPERSONATION_TTL_MINUTES = 30;

    /**
     * Session key used to track when impersonation started.
     */
    public const SESSION_STARTED_AT_KEY = 'impersonation_started_at';

    /**
     * Start impersonating a user
     */
    public function impersonate(Request $request, User $user): RedirectResponse
    {
        /** @var \App\Models\User $authUser */
        $authUser = auth()->user();

        // Authorization check
        if (! $authUser->can('impersonate', $user)) {
            return back()->with('error', '⚠️ You do not have permission to impersonate this user.');
        }

        // Audit log
        Log::info('User impersonation started', [
            'admin_id' => auth()->id(),
            'admin_name' => $authUser->name,
            'target_user_id' => $user->id,
            'target_user_name' => $user->name,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        // Log activity (if using spatie/laravel-activitylog)
        if (class_exists(\Spatie\Activitylog\Facades\Activity::class)) {
            activity()
                ->causedBy($authUser)
                ->performedOn($user)
                ->withProperties([
                    'admin_name' => $authUser->name,
                    'target_name' => $user->name,
                ])
                ->log('Started impersonation');
        }

        // Start impersonation and record the start time for TTL enforcement
        $authUser->impersonate($user);
        session()->put(self::SESSION_STARTED_AT_KEY, now()->timestamp);

        return redirect()->route('portal.dashboard')
            ->with('success', "✓ Now viewing portal as {$user->name}. You are in read-only mode.");
    }

    /**
     * Stop impersonating and return to admin account
     */
    public function leave(Request $request): RedirectResponse
    {
        /** @var \App\Models\User $authUser */
        $authUser = auth()->user();

        if (! $authUser->isImpersonated()) {
            return redirect()->route('dashboard');
        }

        $impersonatedUser = $authUser;

        // Audit log before leaving
        Log::info('User impersonation ended', [
            'admin_id' => session('impersonated_by'),
            'impersonated_user_id' => $impersonatedUser->id,
            'impersonated_user_name' => $impersonatedUser->name,
            'ip_address' => $request->ip(),
        ]);

        // Log activity (if using spatie/laravel-activitylog)
        if (class_exists(\Spatie\Activitylog\Facades\Activity::class)) {
            $adminId = session('impersonated_by');
            if ($adminId) {
                /** @var \App\Models\User|null $admin */
                $admin = User::find($adminId);
                if ($admin) {
                    activity()
                        ->causedBy($admin)
                        ->performedOn($impersonatedUser)
                        ->withProperties([
                            'admin_name' => $admin->name ?? 'Unknown',
                            'impersonated_name' => $impersonatedUser->name,
                        ])
                        ->log('Ended impersonation');
                }
            }
        }

        // Leave impersonation and clean up TTL tracking
        session()->forget(self::SESSION_STARTED_AT_KEY);
        $authUser->leaveImpersonation();

        return redirect()->route('dashboard')
            ->with('success', '✓ Returned to your admin account.');
    }
}
