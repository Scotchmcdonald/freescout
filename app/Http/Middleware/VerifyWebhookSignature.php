<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * VerifyWebhookSignature Middleware
 * 
 * Verifies webhook signatures from external services to ensure authenticity.
 * Prevents unauthorized webhook deliveries and replay attacks.
 * 
 * Supported services:
 * - Google Workspace (X-Goog-Channel-Token header)
 * - Action1 RMM (X-Action1-Signature header)
 * 
 * Security features:
 * - Signature verification
 * - Timestamp validation (prevents replay attacks)
 * - IP whitelist checking
 * - Failed attempt logging to security channel
 */
class VerifyWebhookSignature
{
    /**
     * Google's notification IPs (as of 2026)
     * Production should use dynamic IP range checking
     */
    private const GOOGLE_IP_RANGES = [
        // Google's push notification service uses various IPs
        // In production, check: https://www.gstatic.com/ipranges/goog.json
        '0.0.0.0', // Allow all for development - REMOVE IN PRODUCTION
    ];

    /**
     * Action1 IP ranges (obtain from Action1 support)
     */
    private const ACTION1_IP_RANGES = [
        '0.0.0.0', // Allow all for development - REMOVE IN PRODUCTION
    ];

    /**
     * Maximum age for webhook requests (in seconds)
     * Prevents replay attacks
     */
    private const MAX_WEBHOOK_AGE = 300; // 5 minutes

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, ?string $source = null): Response
    {
        // Only verify on production/staging. In local, we skip unless we want to test it.
        // We removed 'testing' to allow security tests to run.
        if (app()->environment('local')) {
            return $next($request);
        }

        // Enforce HTTPS (skip in testing)
        if (!$request->secure() && !app()->environment('testing')) {
            Log::channel('security')->warning('Webhook attempt over HTTP rejected', [
                'ip' => $request->ip(),
                'url' => $request->fullUrl(),
            ]);
            
            return response()->json(['error' => 'HTTPS required'], 403);
        }

        // Determine source from route or parameter
        $source = $source ?? $this->detectSource($request);

        // Verify IP whitelist
        if (!$this->isIpAllowed($request->ip(), $source)) {
            Log::channel('security')->warning('Webhook from unauthorized IP rejected', [
                'ip' => $request->ip(),
                'source' => $source,
                'url' => $request->fullUrl(),
            ]);
            
            return response()->json(['error' => 'Unauthorized IP'], 403);
        }

        // Verify signature based on source
        $verified = match ($source) {
            'google' => $this->verifyGoogleSignature($request),
            'action1' => $this->verifyAction1Signature($request),
            default => false,
        };

        if (!$verified) {
            Log::channel('security')->warning('Webhook signature verification failed', [
                'ip' => $request->ip(),
                'source' => $source,
                'headers' => $request->headers->all(),
            ]);
            
            return response()->json(['error' => 'Invalid signature'], 403);
        }

        // Verify timestamp to prevent replay attacks
        if (!$this->verifyTimestamp($request, $source)) {
            Log::channel('security')->warning('Webhook replay attack detected', [
                'ip' => $request->ip(),
                'source' => $source,
            ]);
            
            return response()->json(['error' => 'Request too old'], 403);
        }

        // Log successful verification
        Log::channel('security')->info('Webhook verified successfully', [
            'source' => $source,
            'ip' => $request->ip(),
        ]);

        return $next($request);
    }

    /**
     * Detect webhook source from request path
     */
    private function detectSource(Request $request): string
    {
        if (str_contains($request->path(), 'google')) {
            return 'google';
        }
        
        if (str_contains($request->path(), 'action1')) {
            return 'action1';
        }

        return 'unknown';
    }

    /**
     * Verify Google webhook signature
     * 
     * Google Push Notifications use X-Goog-Channel-Token header
     * which contains the token we provided during channel creation.
     */
    private function verifyGoogleSignature(Request $request): bool
    {
        $token = $request->header('X-Goog-Channel-Token');
        $channelId = $request->header('X-Goog-Channel-Id');
        $resourceId = $request->header('X-Goog-Resource-Id');
        $resourceState = $request->header('X-Goog-Resource-State');

        // All headers must be present
        if (!$token || !$channelId || !$resourceId || !$resourceState) {
            Log::warning('Google webhook missing required headers', [
                'has_token' => !empty($token),
                'has_channel_id' => !empty($channelId),
                'has_resource_id' => !empty($resourceId),
                'has_resource_state' => !empty($resourceState),
            ]);
            return false;
        }

        // Verify token against database
        $channel = \App\Models\GooglePushChannel::where('channel_id', $channelId)
            ->where('token', $token)
            ->where('is_active', true)
            ->first();

        if (!$channel) {
            Log::warning('Google webhook channel not found or inactive', [
                'channel_id' => $channelId,
                'token' => substr($token, 0, 8) . '...',
            ]);
            return false;
        }

        // Check if channel is expired
        if ($channel->isExpired()) {
            Log::warning('Google webhook channel expired', [
                'channel_id' => $channelId,
                'expired_at' => $channel->expiration_time,
            ]);
            return false;
        }

        return true;
    }

    /**
     * Verify Action1 webhook signature
     * 
     * Action1 uses HMAC-SHA256 signature in X-Action1-Signature header
     * Format: sha256=<hex_signature>
     */
    private function verifyAction1Signature(Request $request): bool
    {
        $signature = $request->header('X-Action1-Signature');
        $timestamp = $request->header('X-Action1-Timestamp');

        if (!$signature || !$timestamp) {
            Log::warning('Action1 webhook missing signature headers');
            return false;
        }

        // Get webhook secret from config
        $secret = config('action1.webhook_secret');
        
        if (!$secret) {
            Log::error('Action1 webhook secret not configured');
            return false;
        }

        // Construct signed payload: timestamp.body
        $payload = $timestamp . '.' . $request->getContent();

        // Compute expected signature
        $expectedSignature = 'sha256=' . hash_hmac('sha256', $payload, $secret);

        // Constant-time comparison to prevent timing attacks
        if (!hash_equals($expectedSignature, $signature)) {
            Log::warning('Action1 webhook signature mismatch', [
                'expected' => substr($expectedSignature, 0, 20) . '...',
                'received' => substr($signature, 0, 20) . '...',
            ]);
            return false;
        }

        return true;
    }

    /**
     * Verify request timestamp to prevent replay attacks
     */
    private function verifyTimestamp(Request $request, string $source): bool
    {
        $timestamp = match ($source) {
            'google' => $request->header('X-Goog-Message-Number'),
            'action1' => $request->header('X-Action1-Timestamp'),
            default => null,
        };

        // Google doesn't provide timestamp, so skip for Google
        if ($source === 'google') {
            return true;
        }

        if (!$timestamp) {
            return false;
        }

        $age = time() - (int) $timestamp;

        if ($age > self::MAX_WEBHOOK_AGE) {
            Log::warning('Webhook request too old', [
                'source' => $source,
                'age' => $age,
                'max_age' => self::MAX_WEBHOOK_AGE,
            ]);
            return false;
        }

        return true;
    }

    /**
     * Check if IP is allowed for the given source
     */
    private function isIpAllowed(?string $ip, string $source): bool
    {
        if (!$ip) {
            return false;
        }

        // In development, allow all IPs
        if (app()->environment('local', 'testing')) {
            return true;
        }

        $allowedRanges = match ($source) {
            'google' => self::GOOGLE_IP_RANGES,
            'action1' => self::ACTION1_IP_RANGES,
            default => [],
        };

        // Simple check - in production, use CIDR matching
        foreach ($allowedRanges as $range) {
            if ($range === '0.0.0.0' || $ip === $range) {
                return true;
            }
        }

        return false;
    }
}
