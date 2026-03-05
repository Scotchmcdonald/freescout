<?php

declare(strict_types=1);

namespace App\Traits;

use Illuminate\Support\Facades\Log;
use Modules\Alerts\DataTransferObjects\AlertPayload;
use Modules\Alerts\Services\AlertService;

/**
 * Provides resilient queue behavior for ShouldQueue event listeners.
 *
 * Adds:
 *  - Default retry + backoff configuration
 *  - A generic `failed()` method that logs the failure and dispatches an alert
 *  - Structured logging with listener class + event details
 *
 * Usage:  In any listener that `implements ShouldQueue`, add `use ResilientListener;`
 * and optionally override `resilientListenerAlertTypeCode()`.  To customize
 * retry attempts or backoff, define a `tries()` or `backoff()` method on the
 * using class — Laravel resolves these methods before falling back to properties.
 *
 * The trait declares `$tries` and `$backoff` directly — these are the
 * standard properties that Laravel's `Dispatcher::propagateListenerOptions()`
 * reads when creating a `CallQueuedListener` job.
 *
 * Classes that already declare their own `$tries` / `$backoff` properties
 * should NOT use this trait (PHP requires compatible trait property
 * declarations).  Instead, they should define their own `failed()` method
 * or call the `resilientListenerFailed()` helper directly.
 */
trait ResilientListener
{
    /**
     * Maximum retry attempts before permanent failure.
     *
     * To customize: define a `tries()` method on the using class (takes
     * priority over this property in Laravel's resolution).
     */
    public int $tries = 3;

    /**
     * Backoff in seconds between retries.
     *
     * To customize: define a `backoff()` method on the using class (takes
     * priority over this property in Laravel's resolution).
     */
    public int $backoff = 30;

    /**
     * Handle a definitive failure after all queue retries are exhausted.
     *
     * - Logs the failure with structured context.
     * - Dispatches an alert via the Alerts module (if available).
     *
     * Listeners may override this to add domain-specific cleanup.  Call
     * `$this->resilientListenerFailed($event, $exception)` from the override
     * to retain the base logging + alert behavior.
     */
    public function failed(object $event, \Throwable $exception): void
    {
        $this->resilientListenerFailed($event, $exception);
    }

    /**
     * Core failure‐handling logic — separated so that overrides can call it.
     */
    protected function resilientListenerFailed(object $event, \Throwable $exception): void
    {
        $listenerClass = static::class;
        $eventClass = get_class($event);

        Log::error("[ResilientListener] {$listenerClass} failed permanently.", [
            'listener'  => $listenerClass,
            'event'     => $eventClass,
            'error'     => $exception->getMessage(),
            'trace'     => mb_substr($exception->getTraceAsString(), 0, 2000),
        ]);

        $this->dispatchListenerFailureAlert($listenerClass, $eventClass, $exception);
    }

    /**
     * Dispatch a structured alert through the Alerts module.
     */
    protected function dispatchListenerFailureAlert(
        string $listenerClass,
        string $eventClass,
        \Throwable $exception,
    ): void {
        try {
            if (! class_exists(AlertService::class)) {
                return;
            }

            /** @var AlertService $alertService */
            $alertService = app(AlertService::class);
            $shortListener = class_basename($listenerClass);
            $shortEvent = class_basename($eventClass);

            $alertService->dispatch(new AlertPayload(
                alertTypeCode: $this->resilientListenerAlertTypeCode(),
                title: "Listener Failure: {$shortListener}",
                message: "The queued listener {$shortListener} failed permanently after all retries "
                    . "while processing {$shortEvent}.\n\n"
                    . "Error: {$exception->getMessage()}",
                eventId: 'listener_failure_' . md5($listenerClass . $eventClass . time()),
                metadata: [
                    'listener_class' => $listenerClass,
                    'event_class'    => $eventClass,
                    'error'          => $exception->getMessage(),
                ],
            ));
        } catch (\Throwable $alertError) {
            Log::error('[ResilientListener] Failed to dispatch failure alert.', [
                'listener' => $listenerClass,
                'error'    => $alertError->getMessage(),
            ]);
        }
    }

    /**
     * The alert type code used for listener failure alerts.
     *
     * Override in the using class to use a module-specific alert type.
     */
    protected function resilientListenerAlertTypeCode(): string
    {
        return 'listener.failed';
    }
}
