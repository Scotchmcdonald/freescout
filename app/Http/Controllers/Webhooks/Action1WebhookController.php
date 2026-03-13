<?php

declare(strict_types=1);

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Services\MetricsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;

/**
 * Action1WebhookController
 *
 * Handles incoming webhook notifications from Action1 RMM API.
 *
 * Action1 Webhooks (if supported):
 * - Device status changes (online/offline)
 * - Policy compliance updates
 * - Software installation events
 * - Security alerts
 *
 * Webhook Flow:
 * 1. Action1 sends notification to our endpoint
 * 2. Middleware verifies HMAC signature (X-Action1-Signature)
 * 3. Controller processes notification
 * 4. Dispatches appropriate events
 * 5. Idempotent listeners handle business logic
 *
 * Action1 Headers:
 * - X-Action1-Signature: HMAC-SHA256 signature
 * - X-Action1-Timestamp: Unix timestamp
 * - X-Action1-Event: Event type (device.status, device.created, etc.)
 */
class Action1WebhookController extends Controller
{
    public function __construct(
        private MetricsService $metrics
    ) {}

    /**
     * Handle Action1 device notifications
     *
     * Event types:
     * - device.created: New device discovered
     * - device.updated: Device information changed
     * - device.status_changed: Online/offline status changed
     * - device.deleted: Device removed
     */
    public function devices(Request $request): JsonResponse
    {
        $startTime = microtime(true);

        try {
            $eventType = $request->header('X-Action1-Event');
            $timestamp = $request->header('X-Action1-Timestamp');

            $payload = $request->all();

            Log::info('Action1 device webhook received', [
                'event_type' => $eventType,
                'timestamp' => $timestamp,
                'device_id' => $payload['device_id'] ?? null,
            ]);

            $this->metrics->trackWebhookReceived('action1', 'devices');

            // Validate payload structure
            if (! isset($payload['device_id'])) {
                Log::warning('Action1 webhook missing device_id', [
                    'payload' => $payload,
                ]);

                $this->metrics->trackWebhookFailed('action1', 'devices', 'Missing device_id');

                return response()->json(['error' => 'Invalid payload'], 400);
            }

            // Dispatch appropriate event based on event type
            $this->dispatchDeviceEvent($eventType ?? '', $payload);

            $processingTime = microtime(true) - $startTime;
            $this->metrics->trackWebhookProcessed('action1', 'devices', $processingTime);

            Log::info('Action1 device webhook processed', [
                'event_type' => $eventType,
                'device_id' => $payload['device_id'],
                'processing_time_ms' => round($processingTime * 1000, 2),
            ]);

            return response()->json(['status' => 'processed']);
        } catch (\Exception $e) {
            $this->metrics->trackWebhookFailed('action1', 'devices', $e->getMessage());

            Log::error('Failed to process Action1 device webhook', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json(['error' => 'Processing failed'], 500);
        }
    }

    /**
     * Handle Action1 policy compliance notifications
     */
    public function policies(Request $request): JsonResponse
    {
        $startTime = microtime(true);

        try {
            $eventType = $request->header('X-Action1-Event');
            $payload = $request->all();

            Log::info('Action1 policy webhook received', [
                'event_type' => $eventType,
                'device_id' => $payload['device_id'] ?? null,
                'policy_id' => $payload['policy_id'] ?? null,
            ]);

            $this->metrics->trackWebhookReceived('action1', 'policies');

            // Dispatch policy compliance event
            if (class_exists('Modules\\Action1\\Events\\PolicyComplianceChanged')) {
                Event::dispatch(new \Modules\Action1\Events\PolicyComplianceChanged([
                    'device_id' => $payload['device_id'] ?? null,
                    'policy_id' => $payload['policy_id'] ?? null,
                    'status' => $payload['status'] ?? null,
                    'timestamp' => now(),
                    'raw_payload' => $payload,
                ]));
            }

            $processingTime = microtime(true) - $startTime;
            $this->metrics->trackWebhookProcessed('action1', 'policies', $processingTime);

            return response()->json(['status' => 'processed']);
        } catch (\Exception $e) {
            $this->metrics->trackWebhookFailed('action1', 'policies', $e->getMessage());

            Log::error('Failed to process Action1 policy webhook', [
                'error' => $e->getMessage(),
            ]);

            return response()->json(['error' => 'Processing failed'], 500);
        }
    }

    /**
     * Handle Action1 security alert notifications
     */
    public function alerts(Request $request): JsonResponse
    {
        $startTime = microtime(true);

        try {
            $eventType = $request->header('X-Action1-Event');
            $payload = $request->all();

            Log::channel('security')->warning('Action1 security alert received', [
                'event_type' => $eventType,
                'alert_type' => $payload['alert_type'] ?? null,
                'severity' => $payload['severity'] ?? null,
                'device_id' => $payload['device_id'] ?? null,
            ]);

            $this->metrics->trackWebhookReceived('action1', 'alerts');

            // Dispatch security alert event
            if (class_exists('Modules\\Action1\\Events\\SecurityAlertReceived')) {
                Event::dispatch(new \Modules\Action1\Events\SecurityAlertReceived([
                    'alert_type' => $payload['alert_type'] ?? null,
                    'severity' => $payload['severity'] ?? 'medium',
                    'device_id' => $payload['device_id'] ?? null,
                    'timestamp' => now(),
                    'raw_payload' => $payload,
                ]));
            }

            $processingTime = microtime(true) - $startTime;
            $this->metrics->trackWebhookProcessed('action1', 'alerts', $processingTime);

            return response()->json(['status' => 'processed']);
        } catch (\Exception $e) {
            $this->metrics->trackWebhookFailed('action1', 'alerts', $e->getMessage());

            Log::error('Failed to process Action1 alert webhook', [
                'error' => $e->getMessage(),
            ]);

            return response()->json(['error' => 'Processing failed'], 500);
        }
    }

    /**
     * Dispatch appropriate event based on device event type
     *
     * @param  array<string, mixed>  $payload
     */
    private function dispatchDeviceEvent(string $eventType, array $payload): void
    {
        $eventClass = match ($eventType) {
            'device.created' => 'Modules\\Action1\\Events\\Action1DeviceCreated',
            'device.updated' => 'Modules\\Action1\\Events\\Action1DeviceUpdated',
            'device.status_changed' => 'Modules\\Action1\\Events\\Action1DeviceStatusChanged',
            'device.deleted' => 'Modules\\Action1\\Events\\Action1DeviceDeleted',
            default => null,
        };

        if ($eventClass && class_exists($eventClass)) {
            Event::dispatch(new $eventClass([
                'device_id' => $payload['device_id'],
                'timestamp' => now(),
                'raw_payload' => $payload,
            ]));

            Log::info('Action1 webhook event dispatched', [
                'event' => $eventClass,
                'device_id' => $payload['device_id'],
            ]);
        } else {
            Log::warning('Action1 webhook event not handled', [
                'event_type' => $eventType,
                'device_id' => $payload['device_id'] ?? null,
            ]);
        }
    }
}
