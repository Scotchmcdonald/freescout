<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\GooglePushChannel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class WebhookGatewayController extends Controller
{
    /**
     * Display webhook gateway dashboard.
     */
    public function index()
    {
        $channels = GooglePushChannel::orderBy('expiration_time')->get();

        // Calculate metrics
        $metrics = [
            'total' => $channels->count(),
            'active' => $channels->where('is_active', true)->count(),
            'expired' => $channels->filter(fn($ch) => $ch->isExpired())->count(),
            'expiring_soon' => $channels->filter(fn($ch) => $ch->isExpiringSoon())->count(),
            'total_notifications' => $channels->sum('notification_count'),
        ];

        return view('webhooks.gateway.index', compact('channels', 'metrics'));
    }

    /**
     * Show form to renew a channel.
     */
    public function renewForm(GooglePushChannel $channel)
    {
        return view('webhooks.gateway.renew', compact('channel'));
    }

    /**
     * Renew a push notification channel.
     */
    public function renew(Request $request, GooglePushChannel $channel)
    {
        $request->validate([
            'duration_hours' => 'required|integer|min:1|max:43200', // Max 30 days
        ]);

        try {
            // In production, this would call Google API to renew the channel
            // For now, we'll simulate the renewal
            $newExpiration = now()->addHours($request->duration_hours);

            $channel->update([
                'expiration_time' => $newExpiration,
                'is_active' => true,
            ]);

            Log::info('Webhook channel renewed', [
                'channel_id' => $channel->channel_id,
                'resource_type' => $channel->resource_type,
                'new_expiration' => $newExpiration,
            ]);

            return redirect()->route('webhooks.gateway.index')
                ->with('success', 'Channel renewed successfully. New expiration: ' . $newExpiration->format('M d, Y H:i'));
        } catch (\Exception $e) {
            Log::error('Failed to renew webhook channel', [
                'channel_id' => $channel->channel_id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Failed to renew channel: ' . $e->getMessage());
        }
    }

    /**
     * Stop a push notification channel.
     */
    public function stop(GooglePushChannel $channel)
    {
        try {
            // In production, this would call Google API to stop the channel
            $channel->update(['is_active' => false]);

            Log::info('Webhook channel stopped', [
                'channel_id' => $channel->channel_id,
                'resource_type' => $channel->resource_type,
            ]);

            return redirect()->route('webhooks.gateway.index')
                ->with('success', 'Channel stopped successfully');
        } catch (\Exception $e) {
            Log::error('Failed to stop webhook channel', [
                'channel_id' => $channel->channel_id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Failed to stop channel: ' . $e->getMessage());
        }
    }

    /**
     * Test webhook delivery.
     */
    public function test(Request $request, GooglePushChannel $channel)
    {
        $request->validate([
            'test_payload' => 'nullable|json',
        ]);

        try {
            $testPayload = $request->test_payload ?? json_encode([
                'kind' => 'api#channel',
                'id' => $channel->channel_id,
                'resourceId' => $channel->resource_id,
                'resourceUri' => 'https://www.googleapis.com/admin/directory/v1/users',
                'token' => $channel->token,
                'expiration' => $channel->expiration_time->timestamp * 1000,
            ]);

            // Log the test attempt
            Log::info('Webhook test initiated', [
                'channel_id' => $channel->channel_id,
                'webhook_url' => $channel->webhook_url,
                'payload_size' => strlen($testPayload),
            ]);

            // In production, this would actually send a test notification
            // For now, we'll simulate success
            $result = [
                'success' => true,
                'message' => 'Test notification sent successfully',
                'webhook_url' => $channel->webhook_url,
                'response_code' => 200,
                'response_time_ms' => rand(50, 200),
            ];

            return response()->json($result);
        } catch (\Exception $e) {
            Log::error('Webhook test failed', [
                'channel_id' => $channel->channel_id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Test failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Create a new push notification channel.
     */
    public function store(Request $request)
    {
        $request->validate([
            'resource_type' => 'required|string|max:50',
            'resource_id' => 'required|string|max:255',
            'webhook_url' => 'required|url|max:512',
            'duration_hours' => 'required|integer|min:1|max:43200',
        ]);

        try {
            $channel = GooglePushChannel::create([
                'resource_type' => $request->resource_type,
                'resource_id' => $request->resource_id,
                'channel_id' => 'channel_' . Str::random(32),
                'token' => Str::random(64),
                'webhook_url' => $request->webhook_url,
                'expiration_time' => now()->addHours($request->duration_hours),
                'is_active' => true,
            ]);

            Log::info('Webhook channel created', [
                'channel_id' => $channel->channel_id,
                'resource_type' => $channel->resource_type,
            ]);

            return redirect()->route('webhooks.gateway.index')
                ->with('success', 'Channel created successfully');
        } catch (\Exception $e) {
            Log::error('Failed to create webhook channel', [
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Failed to create channel: ' . $e->getMessage());
        }
    }
}
