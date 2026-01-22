<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ImpersonationController extends Controller
{
    /**
     * Start impersonating a user
     */
    public function impersonate(Request $request, User $user)
    {
        // Authorization check
        if (!$request->user()->can('impersonate', $user)) {
            return back()->with('error', '⚠️ You do not have permission to impersonate this user.');
        }

        // Audit log
        Log::info('User impersonation started', [
            'admin_id' => auth()->id(),
            'admin_name' => auth()->user()->name,
            'target_user_id' => $user->id,
            'target_user_name' => $user->name,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        // Log activity (if using spatie/laravel-activitylog)
        if (class_exists(\Spatie\Activitylog\Facades\Activity::class)) {
            activity()
                ->causedBy(auth()->user())
                ->performedOn($user)
                ->withProperties([
                    'admin_name' => auth()->user()->name,
                    'target_name' => $user->name,
                ])
                ->log('Started impersonation');
        }

        // Start impersonation
        auth()->user()->impersonate($user);

        return redirect()->route('portal.dashboard')
            ->with('success', "✓ Now viewing portal as {$user->name}. You are in read-only mode.");
    }

    /**
     * Stop impersonating and return to admin account
     */
    public function leave(Request $request)
    {
        if (!auth()->user()->isImpersonated()) {
            return redirect()->route('dashboard');
        }

        $impersonatedUser = auth()->user();
        
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
                $admin = User::find($adminId);
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

        // Leave impersonation
        auth()->user()->leaveImpersonation();

        return redirect()->route('dashboard')
            ->with('success', '✓ Returned to your admin account.');
    }
}
