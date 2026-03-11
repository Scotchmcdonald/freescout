<?php

namespace Modules\TreeScoutDeploymentManager\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\TreeScoutDeploymentManager\Services\ActivationService;
use RuntimeException;

/**
 * Public-facing endpoint consumed by the client-side deploy.sh script.
 *
 * No authentication — the OTAC itself is the credential.
 * Security layers:
 *  - Throttle middleware (10 req/min per IP) in api.php
 *  - Single-use codes with hard expiry
 *  - Audit log on every attempt (success and failure)
 *  - Optional IP pinning (TSDM_IP_PINNING=true)
 */
class ActivationBrokerController extends Controller
{
    public function __construct(
        private readonly ActivationService $activationService
    ) {}

    /**
     * POST /api/tsdm/activate
     *
     * Body params:
     *   code  (required) — the OTAC, e.g. "TREE-X7K2-9MNQ"
     *
     * Success 200:
     * {
     *   "token":      "glpat-xxxx",
     *   "git_host":   "gitlab.com",
     *   "expires_at": "2026-03-01"
     * }
     */
    public function activate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'code' => 'required|string|max:32',
        ]);

        $clientIp = $request->ip();

        try {
            $tokenData = $this->activationService->redeem(
                code:     strtoupper(trim($validated['code'])),
                clientIp: $clientIp
            );

            return response()->json($tokenData, 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'error' => 'invalid_code',
                'message' => 'Activation code not found, expired, or already used.',
            ], 404);

        } catch (RuntimeException $e) {
            // Surface safe user-facing messages; internal details are already logged
            return response()->json([
                'error'   => 'activation_failed',
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}
