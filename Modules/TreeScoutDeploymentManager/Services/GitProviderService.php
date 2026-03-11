<?php

namespace Modules\TreeScoutDeploymentManager\Services;

use Illuminate\Http\Client\Factory as HttpClient;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\TreeScoutDeploymentManager\Models\DeploymentRecord;
use RuntimeException;

/**
 * Abstracts Git provider API calls.
 *
 * Supported providers: GitLab (default), GitHub (via App Installation Tokens).
 *
 * Tokens issued here should be:
 *  - Scoped to `read_repository` only (minimum privilege).
 *  - Short-lived — expired by the provider within 24 hours maximum.
 *  - Named to include the client identifier for audit trails.
 */
class GitProviderService
{
    /**
     * Generate a short-lived, scoped Git token for the given deployment.
     *
     * @param  DeploymentRecord $deployment
     * @param  array<string>    $scopes      e.g. ['read_repository']
     * @param  string|null      $labelHint   Human label embedded in the token name
     * @return array{token: string, git_host: string, expires_at: string}
     * @throws RuntimeException
     */
    public function issueToken(
        DeploymentRecord $deployment,
        array $scopes = ['read_repository'],
        ?string $labelHint = null
    ): array {
        $provider = config('tsdm.git.provider', 'gitlab');

        return match ($provider) {
            'gitlab' => $this->issueGitLabToken($deployment, $scopes, $labelHint),
            'github' => $this->issueGitHubToken($deployment, $scopes, $labelHint),
            default  => throw new RuntimeException("Unsupported git provider: {$provider}"),
        };
    }

    // ------------------------------------------------------------------
    // GitLab
    // ------------------------------------------------------------------

    /**
     * Creates a GitLab Project Access Token via the API.
     * Requires an admin PAT with `api` scope configured in services.
     *
     * @param  DeploymentRecord $deployment
     * @param  array<string>    $scopes
     * @param  string|null      $labelHint
     * @return array{token: string, git_host: string, expires_at: string}
     */
    private function issueGitLabToken(
        DeploymentRecord $deployment,
        array $scopes,
        ?string $labelHint
    ): array {
        $host        = rtrim((string) config('tsdm.git.gitlab.host', 'https://gitlab.com'), '/');
        $adminToken  = config('tsdm.git.gitlab.admin_token');
        $projectId   = $deployment->git_project_id
                    ?? config('tsdm.git.gitlab.default_project_id');

        if (!$adminToken) {
            throw new RuntimeException('TSDM_GITLAB_ADMIN_TOKEN is not configured.');
        }

        if (!$projectId) {
            throw new RuntimeException('No GitLab project ID configured for this deployment.');
        }

        $accessLevel = (int) config('tsdm.activation.gitlab_access_level', 20);
        $expiresAt   = now()->addDay()->format('Y-m-d');
        $tokenName   = 'Deploy-' . ($labelHint ?? "client-{$deployment->client_id}-deploy-{$deployment->id}");

        // Security: truncate to 64 chars (GitLab limit) and strip special chars
        $tokenName = substr(preg_replace('/[^a-zA-Z0-9_\-]/', '-', $tokenName) ?? $tokenName, 0, 64);

        $response = Http::withToken($adminToken)
            ->post("{$host}/api/v4/projects/{$projectId}/access_tokens", [
                'name'         => $tokenName,
                'scopes'       => $scopes,       // e.g. ['read_repository']
                'expires_at'   => $expiresAt,
                'access_level' => $accessLevel,  // 20 = Reporter (minimum for read)
            ]);

        if (!$response->successful()) {
            Log::error('[TSDM] GitLab token creation failed', [
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);
            throw new RuntimeException('GitLab API error: ' . $response->body());
        }

        $token = $response->json('token');

        if (!$token) {
            throw new RuntimeException('GitLab API returned no token in response.');
        }

        return [
            'token'      => $token,
            'git_host'   => parse_url($host, PHP_URL_HOST) ?? 'gitlab.com',
            'expires_at' => $expiresAt,
        ];
    }

    // ------------------------------------------------------------------
    // GitHub
    // ------------------------------------------------------------------

    /**
     * Generates a GitHub App Installation Access Token.
     * These are always short-lived (≤ 1 hour) and scoped to the installation's repos.
     *
     * @param  DeploymentRecord $deployment
     * @param  array<string>    $scopes      Ignored for GitHub (determined by App installation)
     * @param  string|null      $labelHint
     * @return array{token: string, git_host: string, expires_at: string}
     */
    private function issueGitHubToken(
        DeploymentRecord $deployment,
        array $scopes,
        ?string $labelHint
    ): array {
        // --- STATIC PAT MODE (testing only) ---
        // If TSDM_GITHUB_STATIC_PAT is set, skip App token issuance entirely
        // and return the pre-configured PAT directly. Rotate after testing.
        $staticPat = config('tsdm.git.github.static_pat');
        if ($staticPat) {
            Log::warning('[TSDM] Using TSDM_GITHUB_STATIC_PAT static token — not suitable for production.');
            return [
                'token'      => $staticPat,
                'git_host'   => 'github.com',
                'expires_at' => now()->addYear()->format('Y-m-d'), // PATs don't expire via API
            ];
        }

        $appId          = config('tsdm.git.github.app_id');
        $privateKeyPath = config('tsdm.git.github.private_key_path');
        $installationId = config('tsdm.git.github.installation_id');

        if (!$appId || !$privateKeyPath || !$installationId) {
            throw new RuntimeException('GitHub App credentials (TSDM_GITHUB_APP_ID, PRIVATE_KEY_PATH, INSTALLATION_ID) are not fully configured.');
        }

        $jwt = $this->generateGitHubJwt($appId, $privateKeyPath);

        $response = Http::withToken($jwt, 'Bearer')
            ->withHeaders([
                'Accept'               => 'application/vnd.github+json',
                'X-GitHub-Api-Version' => '2022-11-28',
            ])
            ->post("https://api.github.com/app/installations/{$installationId}/access_tokens");

        if (!$response->successful()) {
            Log::error('[TSDM] GitHub token creation failed', [
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);
            throw new RuntimeException('GitHub API error: ' . $response->body());
        }

        $token     = $response->json('token');
        $expiresAt = $response->json('expires_at'); // ISO 8601 string

        if (!$token) {
            throw new RuntimeException('GitHub API returned no token in response.');
        }

        return [
            'token'      => $token,
            'git_host'   => 'github.com',
            'expires_at' => $expiresAt ?? now()->addHour()->toIso8601String(),
        ];
    }

    /**
     * Build a signed JWT for GitHub App authentication.
     * RS256 signature using the App's private key.
     *
     * @param  string $appId
     * @param  string $privateKeyPath  Absolute path to PEM file
     * @return string  JWT string
     */
    private function generateGitHubJwt(string $appId, string $privateKeyPath): string
    {
        if (!file_exists($privateKeyPath)) {
            throw new RuntimeException("GitHub App private key not found at: {$privateKeyPath}");
        }

        $privateKey = file_get_contents($privateKeyPath);
        $now        = time();

        $header  = base64_encode(json_encode(['alg' => 'RS256', 'typ' => 'JWT']) ?: '');
        $payload = base64_encode(json_encode([
            'iat' => $now - 60,  // issued 60s ago to allow clock skew
            'exp' => $now + 600, // 10 minutes max
            'iss' => $appId,
        ]) ?: '');

        $signingInput = "{$header}.{$payload}";
        $signature    = '';

        openssl_sign($signingInput, $signature, (string) $privateKey, OPENSSL_ALGO_SHA256);

        return $signingInput . '.' . base64_encode($signature);
    }
}
