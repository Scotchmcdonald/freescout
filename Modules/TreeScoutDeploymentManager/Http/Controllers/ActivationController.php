<?php

namespace Modules\TreeScoutDeploymentManager\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\TreeScoutDeploymentManager\Models\DeploymentActivation;
use Modules\TreeScoutDeploymentManager\Models\DeploymentRecord;
use Modules\TreeScoutDeploymentManager\Services\ActivationService;

class ActivationController extends Controller
{
    public function __construct(
        private readonly ActivationService $activationService
    ) {}

    public function index(): \Illuminate\View\View
    {
        $activations = DeploymentActivation::with('deploymentRecord')
            ->latest()
            ->paginate(30);

        $deployments = DeploymentRecord::orderBy('name')->get(['id', 'name', 'client_id']);

        return view('tsdm::activations.index', compact('activations', 'deployments'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'deployment_record_id' => 'required|exists:tsdm_deployment_records,id',
            'ttl_hours'            => 'nullable|integer|min:1|max:168',  // max 1 week
            'label'                => 'nullable|string|max:255',
        ]);

        $deployment = DeploymentRecord::findOrFail($validated['deployment_record_id']);
        $ttl        = (int) ($validated['ttl_hours'] ?? config('tsdm.activation.ttl_hours', 24));

        $activation = $this->activationService->issue(
            deployment:      $deployment,
            ttlHours:        $ttl,
            label:           $validated['label'] ?? null,
            issuedByUserId:  auth()->id(),
        );

        return redirect()
            ->route('tsdm.activations.index')
            ->with('new_code', $activation->activation_code)
            ->with('success', 'Activation code generated. Copy it now — it will not be shown again.');
    }

    public function expire(DeploymentActivation $activation): RedirectResponse
    {
        // Immediately expire the code by setting expires_at to now
        $activation->update(['expires_at' => now()]);

        return back()->with('success', "Activation code {$activation->activation_code} has been expired.");
    }
}
