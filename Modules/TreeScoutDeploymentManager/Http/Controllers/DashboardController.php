<?php

namespace Modules\TreeScoutDeploymentManager\Http\Controllers;

use Illuminate\Routing\Controller;
use Modules\TreeScoutDeploymentManager\Models\DeploymentRecord;
use Modules\TreeScoutDeploymentManager\Models\DeploymentActivation;

/**
 * Control Tower — the module's Flight Deck / Dashboard.
 *
 * Shows system-wide health: total deployments by status, recent activations,
 * and alerts for expiring/unused codes.
 */
class DashboardController extends Controller
{
    public function index(): \Illuminate\View\View
    {
        $stats = [
            'total'     => DeploymentRecord::count(),
            'active'    => DeploymentRecord::where('status', 'active')->count(),
            'pending'   => DeploymentRecord::where('status', 'pending')->count(),
            'suspended' => DeploymentRecord::whereIn('status', ['suspended', 'revoked'])->count(),
        ];

        // Deployments not seen in the last 30 days — potential health concern
        $staleDeployments = DeploymentRecord::where('status', 'active')
            ->where(function ($q) {
                $q->whereNull('last_seen_at')
                  ->orWhere('last_seen_at', '<', now()->subDays(30));
            })
            ->with('deployedModules')
            ->latest('last_seen_at')
            ->limit(10)
            ->get();

        // Valid activation codes expiring within the next 2 hours (needs attention)
        $expiringActivations = DeploymentActivation::whereNull('used_at')
            ->where('expires_at', '>', now())
            ->where('expires_at', '<', now()->addHours(2))
            ->with('deploymentRecord')
            ->get();

        // Most recently active deployments for the primary operations table
        $recentDeployments = DeploymentRecord::with(['activations' => fn ($q) => $q->latest()->limit(1)])
            ->latest('last_seen_at')
            ->limit(20)
            ->get();

        return view('tsdm::dashboard.index', compact(
            'stats',
            'staleDeployments',
            'expiringActivations',
            'recentDeployments'
        ));
    }
}
