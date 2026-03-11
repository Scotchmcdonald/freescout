<?php

namespace Modules\TreeScoutDeploymentManager\Services;

use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\TreeScoutDeploymentManager\Models\DeploymentActivation;
use Modules\TreeScoutDeploymentManager\Models\DeploymentRecord;
use RuntimeException;

/**
 * Handles the full OTAC lifecycle:
 *  1. Generating a new activation code for a deployment.
 *  2. Validating an inbound code from a client server.
 *  3. Redeeming it — calling GitProviderService and marking the code as used.
 *
 * This service is the single source of truth for activation logic.
 * Controllers are thin; all business logic lives here.
 */
class ActivationService
{
    public function __construct(
        private readonly GitProviderService $gitProvider
    ) {}

    // ------------------------------------------------------------------
    // Issuance
    // ------------------------------------------------------------------

    /**
     * Create a new OTAC for a given deployment.
     *
     * @param  DeploymentRecord $deployment
     * @param  int              $ttlHours
     * @param  string|null      $label
     * @param  int|null         $issuedByUserId
     * @return DeploymentActivation
     */
    public function issue(
        DeploymentRecord $deployment,
        int $ttlHours = 24,
        ?string $label = null,
        ?int $issuedByUserId = null
    ): DeploymentActivation {
        $code = DeploymentActivation::generateCode();

        // Ensure uniqueness (practically impossible collision, but be safe)
        while (DeploymentActivation::where('activation_code', $code)->exists()) {
            $code = DeploymentActivation::generateCode();
        }

        return DeploymentActivation::create([
            'activation_code'       => $code,
            'deployment_record_id'  => $deployment->id,
            'issued_by_user_id'     => $issuedByUserId,
            'requested_scopes'      => config('tsdm.activation.default_scopes', ['read_repository']),
            'expires_at'            => now()->addHours($ttlHours),
            'label'                 => $label ?? "Deploy-{$deployment->name}",
        ]);
    }

    // ------------------------------------------------------------------
    // Redemption
    // ------------------------------------------------------------------

    /**
     * Validate and redeem an OTAC. This is called by the public API endpoint.
     *
     * Steps:
     *  1. Find the activation by code. Fail with audit if not found.
     *  2. Check it is not expired or already used.
     *  3. Optional IP pinning: record or verify the origin IP.
     *  4. Call GitProviderService to obtain a scoped token.
     *  5. Encrypt and store the token, mark used_at.
     *  6. Mark the deployment as active and record last_seen_at.
     *
     * @param  string      $code         The OTAC submitted by the client
     * @param  string|null $clientIp     The originating IP address
     * @return array{token: string, git_host: string, expires_at: string}
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     * @throws RuntimeException
     */
    public function redeem(string $code, ?string $clientIp = null): array
    {
        return DB::transaction(function () use ($code, $clientIp) {
            /** @var DeploymentActivation|null $activation */
            $activation = DeploymentActivation::where('activation_code', $code)
                ->lockForUpdate()
                ->first();

            $outcome = 'success';

            try {
                if (!$activation) {
                    $outcome = 'invalid_code';
                    throw new RuntimeException('Activation code not found.');
                }

                if ($activation->isUsed()) {
                    $outcome = 'already_used';
                    throw new RuntimeException('Activation code has already been used.');
                }

                if ($activation->isExpired()) {
                    $outcome = 'expired';
                    throw new RuntimeException('Activation code has expired.');
                }

                // IP pinning: if enabled and already recorded, enforce match
                if (config('tsdm.security.enable_ip_pinning') && $activation->redeemed_from_ip) {
                    if ($activation->redeemed_from_ip !== $clientIp) {
                        $outcome = 'invalid_code';
                        throw new RuntimeException('IP address mismatch. This code was already reserved for a different IP.');
                    }
                }

                $deployment = $activation->deploymentRecord;

                if (!$deployment) {
                    $outcome = 'invalid_code';
                    throw new RuntimeException('Associated deployment record not found.');
                }

                // Call the Git provider
                $tokenData = $this->gitProvider->issueToken(
                    $deployment,
                    $activation->requested_scopes ?? ['read_repository'],
                    $activation->label
                );

                // Persist: mark code as used, store encrypted token
                $activation->update([
                    'used_at'                => now(),
                    'redeemed_from_ip'       => $clientIp,
                    'issued_token_encrypted' => config('tsdm.security.encrypt_issued_tokens')
                        ? Crypt::encryptString($tokenData['token'])
                        : null,
                ]);

                // Update deployment: mark active, record IP fingerprint
                $deployment->update([
                    'status'       => 'active',
                    'last_seen_at' => now(),
                    'server_ip'    => $clientIp,
                ]);

                $this->writeAuditLog($code, $clientIp, 'success');

                return $tokenData;

            } catch (RuntimeException $e) {
                $this->writeAuditLog($code, $clientIp, $outcome, $e->getMessage());
                throw $e;
            }
        });
    }

    // ------------------------------------------------------------------
    // Revocation
    // ------------------------------------------------------------------

    /**
     * Revoke all pending activations for a deployment and mark it suspended.
     */
    public function revokeDeployment(DeploymentRecord $deployment): void
    {
        $deployment->activations()
            ->whereNull('used_at')
            ->update(['expires_at' => now()]);

        $deployment->update(['status' => 'revoked']);

        Log::info('[TSDM] Deployment revoked', ['deployment_id' => $deployment->id]);
    }

    // ------------------------------------------------------------------
    // Internals
    // ------------------------------------------------------------------

    private function writeAuditLog(
        string $code,
        ?string $ip,
        string $outcome,
        ?string $error = null
    ): void {
        DB::table('tsdm_activation_audit_log')->insert([
            'activation_code' => $code,
            'attempt_ip'      => $ip,
            'outcome'         => $outcome,
            'error_detail'    => $error,
            'attempted_at'    => now(),
        ]);
    }
}
