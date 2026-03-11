<?php

namespace Modules\TreeScoutDeploymentManager\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\TreeScoutDeploymentManager\Models\DeploymentRecord;
use Modules\TreeScoutDeploymentManager\Services\ActivationService;

class DeploymentController extends Controller
{
    public function __construct(
        private readonly ActivationService $activationService
    ) {}

    public function index(): \Illuminate\View\View
    {
        $deployments = DeploymentRecord::with(['deployedModules'])
            ->withCount('activations')
            ->latest()
            ->paginate(25);

        return view('tsdm::deployments.index', compact('deployments'));
    }

    public function create(): \Illuminate\View\View
    {
        // Fetch CRM clients for the dropdown (if CRM module present)
        $clients = $this->getCrmClients();

        return view('tsdm::deployments.create', compact('clients'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'client_id'      => 'required|integer',
            'name'           => 'required|string|max:255',
            'environment'    => 'required|in:production,staging,development',
            'git_provider'   => 'required|in:gitlab,github',
            'git_project_id' => 'nullable|string|max:255',
            'notes'          => 'nullable|string|max:2000',
        ]);

        $deployment = DeploymentRecord::create($validated + ['status' => 'pending']);

        return redirect()
            ->route('tsdm.deployments.show', $deployment)
            ->with('success', "Deployment \"{$deployment->name}\" created successfully.");
    }

    public function show(DeploymentRecord $deployment): \Illuminate\View\View
    {
        $deployment->load(['deployedModules', 'activations' => fn ($q) => $q->latest()->limit(20)]);

        $auditLog = \Illuminate\Support\Facades\DB::table('tsdm_activation_audit_log')
            ->whereIn(
                'activation_code',
                $deployment->activations->pluck('activation_code')
            )
            ->orderByDesc('attempted_at')
            ->limit(50)
            ->get();

        return view('tsdm::deployments.show', compact('deployment', 'auditLog'));
    }

    public function edit(DeploymentRecord $deployment): \Illuminate\View\View
    {
        $clients = $this->getCrmClients();
        return view('tsdm::deployments.edit', compact('deployment', 'clients'));
    }

    public function update(Request $request, DeploymentRecord $deployment): RedirectResponse
    {
        $validated = $request->validate([
            'name'           => 'required|string|max:255',
            'environment'    => 'required|in:production,staging,development',
            'git_provider'   => 'required|in:gitlab,github',
            'git_project_id' => 'nullable|string|max:255',
            'notes'          => 'nullable|string|max:2000',
        ]);

        $deployment->update($validated);

        return redirect()
            ->route('tsdm.deployments.show', $deployment)
            ->with('success', 'Deployment updated.');
    }

    public function revoke(DeploymentRecord $deployment): RedirectResponse
    {
        $this->activationService->revokeDeployment($deployment);

        return back()->with('success', "Deployment \"{$deployment->name}\" has been revoked.");
    }

    public function reinstate(DeploymentRecord $deployment): RedirectResponse
    {
        $deployment->update(['status' => 'active']);

        return back()->with('success', "Deployment \"{$deployment->name}\" reinstated.");
    }

    // ------------------------------------------------------------------

    /** @return \Illuminate\Support\Collection<int, mixed> */
    private function getCrmClients(): \Illuminate\Support\Collection
    {
        if (class_exists(\Modules\Crm\Models\Client::class)) {
            return \Modules\Crm\Models\Client::orderBy('name')->get(['id', 'name']);
        }
        return collect();
    }
}
