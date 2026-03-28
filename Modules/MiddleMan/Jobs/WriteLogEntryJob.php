<?php

declare(strict_types=1);

namespace Modules\MiddleMan\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\MiddleMan\Models\MiddleManLog;
use Modules\MiddleMan\Queue\PropagateContextMiddleware;

/**
 * Writes a log entry to the middleman_logs table.
 * Always runs on a background queue — never in the HTTP cycle.
 */
class WriteLogEntryJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 5;

    public function __construct(
        private readonly string $eventClass,
        private readonly string $eventName,
        /** @var array<string, mixed> */
        private readonly array $payload,
        /** @var array<string, mixed> */
        private readonly array $metadata,
        private readonly string $firedAt,
        private readonly ?string $correlationId = null,
        private readonly ?string $causationId = null,
        private readonly bool $isReplay = false,
    ) {}

    /** @return list<object> */
    public function middleware(): array
    {
        if ($this->correlationId !== null) {
            return [new PropagateContextMiddleware($this->correlationId, $this->causationId)];
        }

        return [];
    }

    public function handle(): void
    {
        $log = MiddleManLog::create([
            'event_class' => $this->eventClass,
            'event_name' => $this->eventName,
            'payload' => $this->payload,
            'metadata' => $this->metadata,
            'fired_at' => $this->firedAt,
            'correlation_id' => $this->correlationId ?? ($this->metadata['correlation_id'] ?? null),
            'causation_id' => $this->causationId ?? ($this->metadata['causation_id'] ?? null),
            'is_replay' => $this->isReplay,
            'has_schema_drift' => false,
        ]);

        // Dispatch async schema drift detection
        DetectSchemaDriftJob::dispatch($log->id)
            ->onConnection((string) config('middleman.queue_connection', 'redis')) // @phpstan-ignore cast.string
            ->onQueue((string) config('middleman.queue_name', 'middleman')); // @phpstan-ignore cast.string
    }
}
