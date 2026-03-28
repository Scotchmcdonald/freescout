<?php

declare(strict_types=1);

namespace Modules\MiddleMan\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\MiddleMan\Models\MiddleManLog;
use Modules\MiddleMan\Models\MiddleManSchema;

/**
 * Compares a log entry's payload schema against the stored baseline.
 * If drift is detected, flags the log entry with `has_schema_drift = true`
 * and stores drift details in the metadata.
 *
 * Always runs asynchronously on the middleman queue.
 */
class DetectSchemaDriftJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;
    public int $backoff = 3;

    public function __construct(
        private readonly int $logId,
    ) {}

    public function handle(): void
    {
        $log = MiddleManLog::find($this->logId);

        if ($log === null) {
            return;
        }

        $payload = $log->payload ?? [];
        if ($payload === []) {
            return;
        }

        // Resolve or create the baseline for this event class
        $result = MiddleManSchema::resolveBaseline($log->event_class, $payload);
        $baseline = $result['baseline'];

        // If the baseline was just created from this payload, there can be no drift
        if ($result['is_new']) {
            return;
        }

        // Compare current payload against baseline
        $drift = $baseline->detectDrift($payload);

        if ($drift['has_drift']) {
            $metadata = $log->metadata ?? [];
            $metadata['schema_drift'] = $drift;

            $log->update([
                'has_schema_drift' => true,
                'metadata' => $metadata,
            ]);
        }
    }
}
