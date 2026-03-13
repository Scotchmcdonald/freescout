<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\GooglePushChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

// use Modules\GoogleAdmin\Services\GoogleWorkspaceService; // Core Blindness

/**
 * RenewExpiringWebhooksJob
 *
 * Automatically renews webhook channels that are expiring soon.
 *
 * Google Push Notification channels expire after their TTL (typically 7 days).
 * This job runs daily to renew channels expiring within 48 hours.
 *
 * Schedule this job in routes/console.php:
 * Schedule::job(new RenewExpiringWebhooksJob)->daily();
 */
class RenewExpiringWebhooksJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Number of hours before expiration to trigger renewal
     */
    private const RENEWAL_THRESHOLD_HOURS = 48;

    /**
     * New TTL for renewed channels (7 days)
     */
    private const RENEWAL_TTL_SECONDS = 604800;

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Core Blindness: Dynamically resolve GoogleWorkspaceService
        $serviceClass = '\Modules\GoogleAdmin\Services\GoogleWorkspaceService';
        if (! class_exists($serviceClass)) {
            Log::warning('GoogleAdmin module not available, skipping webhook renewal');

            return;
        }
        $googleService = app($serviceClass);

        Log::info('Starting webhook renewal job');

        $threshold = now()->addHours(self::RENEWAL_THRESHOLD_HOURS);

        // Find channels expiring within threshold
        $expiringChannels = GooglePushChannel::where('is_active', true)
            ->where('expiration_time', '<=', $threshold)
            ->where('expiration_time', '>', now())
            ->get();

        if ($expiringChannels->isEmpty()) {
            Log::info('No expiring webhook channels found');

            return;
        }

        Log::info('Found expiring webhook channels', [
            'count' => $expiringChannels->count(),
            'threshold' => $threshold,
        ]);

        $renewed = 0;
        $failed = 0;

        foreach ($expiringChannels as $channel) {
            try {
                // Attempt to renew the channel
                $newChannel = $googleService->renewWebhook($channel, self::RENEWAL_TTL_SECONDS);

                if ($newChannel) {
                    $renewed++;

                    Log::info('Successfully renewed webhook channel', [
                        'old_channel_id' => $channel->channel_id,
                        'new_channel_id' => $newChannel['channel_id'],
                        'resource_type' => $channel->resource_type,
                        'new_expiration' => $newChannel['expiration_time'],
                    ]);
                } else {
                    $failed++;
                    Log::error('Failed to renew webhook channel', [
                        'channel_id' => $channel->channel_id,
                        'resource_type' => $channel->resource_type,
                    ]);
                }
            } catch (\Exception $e) {
                $failed++;

                Log::error('Exception while renewing webhook channel', [
                    'channel_id' => $channel->channel_id,
                    'resource_type' => $channel->resource_type,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);

                // Mark channel as failed but don't throw to continue processing others
                $channel->update([
                    'metadata' => array_merge($channel->metadata ?? [], [
                        'renewal_failed_at' => now()->toIso8601String(),
                        'renewal_error' => $e->getMessage(),
                    ]),
                ]);
            }
        }

        Log::info('Webhook renewal job completed', [
            'total' => $expiringChannels->count(),
            'renewed' => $renewed,
            'failed' => $failed,
        ]);

        // Send alert if there were failures
        if ($failed > 0) {
            Log::channel('security')->warning('Webhook renewal failures detected', [
                'failed_count' => $failed,
                'total_count' => $expiringChannels->count(),
            ]);

            // You could dispatch a notification here to alert administrators
            // event(new WebhookRenewalFailed($failed, $expiringChannels->count()));
        }
    }

    /**
     * Handle job failure.
     */
    public function failed(?\Throwable $exception): void
    {
        Log::error('Webhook renewal job failed', [
            'error' => $exception?->getMessage(),
            'trace' => $exception?->getTraceAsString(),
        ]);

        // Send critical alert
        Log::channel('security')->critical('Webhook renewal job failed completely', [
            'error' => $exception?->getMessage(),
        ]);
    }
}
