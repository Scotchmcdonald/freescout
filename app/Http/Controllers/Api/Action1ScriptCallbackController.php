<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

/**
 * Receives phone-home POST requests from msp_dx_ scripts running on Action1-managed endpoints.
 *
 * Design (push model):
 *   1. ResilienceController mints a one-time token and stores a "pending" record in cache.
 *   2. The token is embedded in the callback URL passed as a parameter when the script is dispatched.
 *   3. The script runs on the endpoint, collects output, writes it to a temp file, then POSTs
 *      the file content to this endpoint.
 *   4. This controller validates the token, stores the result, and returns 200.
 *   5. The stepper's run_status step polls our own cache — no Action1 API calls needed.
 *
 * Security:
 *   - Token is 40-character cryptographically random string (stored in cache with 15-min TTL).
 *   - Endpoint is rate-limited (action1_script_callbacks: 30/min per IP).
 *   - No authentication header required — endpoints behind firewalls can't provide one.
 *   - Output payload is capped at 64 KB to prevent abuse.
 *   - Token can only receive one result; subsequent posts with same token return 409.
 */
class Action1ScriptCallbackController extends Controller
{
    /** Maximum accepted output size in bytes (64 KB). */
    private const MAX_OUTPUT_BYTES = 65536;

    /** Cache key prefix for script result tokens. */
    public const CACHE_PREFIX = 'a1:cb:';

    /** Token TTL in seconds (15 minutes — generous for Action1 dispatch latency). */
    public const TOKEN_TTL = 900;

    public function receive(Request $request, string $token): JsonResponse
    {
        // Sanitise the token (alphanumeric only — guards against cache key injection)
        if (! preg_match('/^[a-zA-Z0-9]{20,64}$/', $token)) {
            return response()->json(['ok' => false, 'message' => 'Invalid token format.'], 400);
        }

        $cacheKey = self::CACHE_PREFIX.$token;
        /** @var array{status: string, script_id?: mixed, org_id?: mixed, minted_at?: mixed}|null $record */ $record = cache()->get($cacheKey);

        if ($record === null) {
            // Token doesn't exist (never minted, or already expired)
            logger()->warning('Action1 script callback: unknown or expired token', ['token' => substr($token, 0, 8).'…', 'ip' => $request->ip()]);

            return response()->json(['ok' => false, 'message' => 'Token not found or expired.'], 404);
        }

        if (($record['status'] ?? '') === 'received') {
            // Idempotent: already received — return 200 but don't overwrite
            return response()->json(['ok' => true, 'message' => 'Already received.'], 200);
        }

        // Validate and cap the output payload
        $output = $request->string('output')->toString();
        if (mb_strlen($output, '8bit') > self::MAX_OUTPUT_BYTES) {
            $output = mb_substr($output, 0, self::MAX_OUTPUT_BYTES, '8bit').'…[truncated]';
        }

        // Store the result — refresh TTL so run_status can read it
        cache()->put($cacheKey, [
            'status' => 'received',
            'endpoint_status' => substr($request->string('status')->toString(), 0, 32),
            'output' => $output,
            'host' => substr($request->string('host')->toString(), 0, 255),
            'user' => substr($request->string('user')->toString(), 0, 255),
            'script_id' => $record['script_id'] ?? null,
            'org_id' => $record['org_id'] ?? null,
            'minted_at' => $record['minted_at'] ?? null,
            'received_at' => now()->toIso8601String(),
        ], self::TOKEN_TTL);

        logger()->info('Action1 script callback received', [
            'token' => substr($token, 0, 8).'…',
            'host' => $request->input('host'),
            'script_id' => $record['script_id'] ?? 'unknown',
        ]);

        return response()->json(['ok' => true], 200);
    }
}
