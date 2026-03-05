<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Modules\Alerts\Models\NotificationSubscription;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AlertSubscriptionController extends Controller
{
    public function index(): View
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $definitions = NotificationSubscription::getAlertTypes();
        
        // Get existing subscriptions keyed by alert_type
        $subscriptions = $user->notificationSubscriptions()
            ->get()
            ->keyBy('alert_type');

        return view('alerts.subscriptions.index', compact('definitions', 'subscriptions'));
    }

    public function update(Request $request): JsonResponse|RedirectResponse
    {
        $request->validate([
            'subscriptions' => 'array',
            'subscriptions.*.alert_type' => 'required|string',
            'subscriptions.*.channels' => 'array',
            'subscriptions.*.frequency' => 'required|in:immediate,daily,weekly',
            'subscriptions.*.is_active' => 'boolean',
        ]);

        /** @var \App\Models\User $user */
        $user = Auth::user();
        /** @var array<string, array<string, mixed>> $input */
        $input = $request->input('subscriptions', []);

        foreach ($input as $alertType => $data) {
            // Only process valid alert types
            if (!array_key_exists((string) $alertType, NotificationSubscription::getAlertTypes())) {
                continue;
            }

            $subscription = $user->notificationSubscriptions()->updateOrCreate(
                ['alert_type' => $alertType],
                [
                    'channels' => $data['channels'] ?? [],
                    'frequency' => $data['frequency'] ?? 'immediate',
                    'is_active' => filter_var($data['is_active'] ?? false, FILTER_VALIDATE_BOOLEAN),
                ]
            );
        }

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'Subscriptions updated successfully',
                'status' => 'success'
            ]);
        }

        return back()->with('success', 'Subscriptions updated successfully');
    }
}
