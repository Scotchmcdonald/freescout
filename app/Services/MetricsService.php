<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Log;

/**
 * Service for tracking custom business metrics and events.
 * 
 * Use this to log important business events that need to be tracked
 * for analytics, auditing, or monitoring purposes.
 */
class MetricsService
{
    /**
     * Track a business event with context.
     *
     * @param string $event Event name (e.g., 'invoice.generated', 'payment.processed')
     * @param array<string, mixed> $context Additional context data
     * @param string $level Log level (info, warning, error)
     */
    public function trackEvent(string $event, array $context = [], string $level = 'info'): void
    {
        Log::channel('business')->{$level}("Event: {$event}", [
            'event' => $event,
            'timestamp' => now()->toIso8601String(),
            'context' => $context,
        ]);

        // Send to Sentry as breadcrumb if available
        if (function_exists('\\Sentry\\addBreadcrumb')) {
            \Sentry\addBreadcrumb(new \Sentry\Breadcrumb(
                \Sentry\Breadcrumb::LEVEL_INFO,
                \Sentry\Breadcrumb::TYPE_DEFAULT,
                'business.event',
                $event,
                $context
            ));
        }
    }

    /**
     * Track invoice generation metrics.
     *
     * @param int $clientId
     * @param int $invoiceId
     * @param float $durationMs Duration in milliseconds
     */
    public function trackInvoiceGeneration(int $clientId, int $invoiceId, float $durationMs): void
    {
        $this->trackEvent('invoice.generated', [
            'client_id' => $clientId,
            'invoice_id' => $invoiceId,
            'duration_ms' => round($durationMs, 2),
        ]);

        if ($durationMs > 5000) { // Log slow invoice generation
            Log::channel('performance')->warning('Slow invoice generation', [
                'client_id' => $clientId,
                'invoice_id' => $invoiceId,
                'duration_ms' => round($durationMs, 2),
            ]);
        }
    }

    /**
     * Track payment processing metrics.
     *
     * @param int $invoiceId
     * @param int $amountCents
     * @param string $gateway Payment gateway used
     * @param bool $success Whether payment was successful
     */
    public function trackPaymentProcessed(int $invoiceId, int $amountCents, string $gateway, bool $success): void
    {
        $this->trackEvent('payment.processed', [
            'invoice_id' => $invoiceId,
            'amount_cents' => $amountCents,
            'gateway' => $gateway,
            'success' => $success,
        ], $success ? 'info' : 'error');
    }

    /**
     * Track API call performance.
     *
     * @param string $service External service name (e.g., 'action1', 'google', 'helcim')
     * @param string $endpoint API endpoint called
     * @param float $durationMs Duration in milliseconds
     * @param int $statusCode HTTP status code
     */
    public function trackApiCall(string $service, string $endpoint, float $durationMs, int $statusCode): void
    {
        $level = 'info';
        
        // Determine log level based on performance and status
        if ($statusCode >= 500) {
            $level = 'error';
        } elseif ($statusCode >= 400) {
            $level = 'warning';
        } elseif ($durationMs > 3000) {
            $level = 'warning';
        }

        Log::channel('performance')->{$level}("API call to {$service}", [
            'service' => $service,
            'endpoint' => $endpoint,
            'duration_ms' => round($durationMs, 2),
            'status_code' => $statusCode,
        ]);
    }

    /**
     * Track security events (login attempts, permission failures, etc.)
     *
     * @param string $event Security event name
     * @param array<string, mixed> $context Additional context
     */
    public function trackSecurityEvent(string $event, array $context = []): void
    {
        Log::channel('security')->warning("Security event: {$event}", [
            'event' => $event,
            'timestamp' => now()->toIso8601String(),
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'context' => $context,
        ]);

        // Send to Sentry with high priority
        if (function_exists('\\Sentry\\captureMessage')) {
            \Sentry\withScope(function (\Sentry\State\Scope $scope) use ($event, $context): void {
                $scope->setExtras($context);
                \Sentry\captureMessage("Security: {$event}", \Sentry\Severity::warning());
            });
        }
    }

    /**
     * Track queue job metrics.
     *
     * @param string $jobClass
     * @param float $durationMs
     * @param bool $success
     * @param string|null $errorMessage
     */
    public function trackQueueJob(string $jobClass, float $durationMs, bool $success, ?string $errorMessage = null): void
    {
        $level = $success ? 'info' : 'error';

        Log::channel('queue')->{$level}("Queue job: {$jobClass}", [
            'job' => $jobClass,
            'duration_ms' => round($durationMs, 2),
            'success' => $success,
            'error' => $errorMessage,
        ]);

        if ($durationMs > 30000) { // Log jobs taking more than 30 seconds
            Log::channel('performance')->warning('Slow queue job', [
                'job' => $jobClass,
                'duration_ms' => round($durationMs, 2),
            ]);
        }
    }

    /**
     * Track cache hit/miss metrics.
     *
     * @param string $key Cache key
     * @param bool $hit Whether it was a cache hit
     */
    public function trackCacheAccess(string $key, bool $hit): void
    {
        Log::channel('performance')->debug('Cache access', [
            'key' => $key,
            'hit' => $hit,
        ]);
    }

    /**
     * Track database query performance.
     *
     * @param string $query SQL query (truncated)
     * @param float $durationMs
     */
    public function trackSlowQuery(string $query, float $durationMs): void
    {
        if ($durationMs > 1000) { // Log queries taking more than 1 second
            Log::channel('performance')->warning('Slow database query', [
                'query' => substr($query, 0, 200),
                'duration_ms' => round($durationMs, 2),
            ]);

            if (function_exists('\\Sentry\\captureMessage')) {
                \Sentry\withScope(function (\Sentry\State\Scope $scope) use ($query, $durationMs): void {
                    $scope->setExtras([
                        'query' => substr($query, 0, 500),
                        'duration_ms' => round($durationMs, 2),
                    ]);
                    \Sentry\captureMessage('Slow Query', \Sentry\Severity::warning());
                });
            }
        }
    }
    
    /**
     * Track webhook received.
     *
     * @param string $source Webhook source (google, action1, etc.)
     * @param string $type Webhook type (directory, devices, etc.)
     */
    public function trackWebhookReceived(string $source, string $type): void
    {
        $this->trackEvent('webhook.received', [
            'source' => $source,
            'type' => $type,
        ]);
    }

    /**
     * Track webhook processed successfully.
     *
     * @param string $source Webhook source (google, action1, etc.)
     * @param string $type Webhook type (directory, devices, etc.)
     * @param float $durationSeconds Processing duration in seconds
     */
    public function trackWebhookProcessed(string $source, string $type, float $durationSeconds): void
    {
        $durationMs = $durationSeconds * 1000;
        
        $level = 'info';
        if ($durationMs > 1000) { // Warn if processing takes more than 1 second
            $level = 'warning';
        }

        Log::channel('performance')->{$level}("Webhook processed: {$source}/{$type}", [
            'source' => $source,
            'type' => $type,
            'duration_ms' => round($durationMs, 2),
        ]);

        $this->trackEvent('webhook.processed', [
            'source' => $source,
            'type' => $type,
            'duration_ms' => round($durationMs, 2),
        ]);
    }

    /**
     * Track webhook processing failure.
     *
     * @param string $source Webhook source (google, action1, etc.)
     * @param string $type Webhook type (directory, devices, etc.)
     * @param string $reason Failure reason
     */
    public function trackWebhookFailed(string $source, string $type, string $reason): void
    {
        Log::channel('security')->error("Webhook failed: {$source}/{$type}", [
            'source' => $source,
            'type' => $type,
            'reason' => $reason,
        ]);

        $this->trackEvent('webhook.failed', [
            'source' => $source,
            'type' => $type,
            'reason' => $reason,
        ], 'error');
    }
}
