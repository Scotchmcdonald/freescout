<?php

declare(strict_types=1);

namespace Modules\MiddleMan\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\MiddleMan\Models\MiddleManIntercept;

/**
 * Writes an intercepted event to the middleman_intercepts table.
 * Always runs on a background queue — never in the HTTP cycle.
 */
class WriteInterceptEntryJob implements ShouldQueue
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
        private readonly string $interceptedAt,
    ) {}

    public function handle(): void
    {
        $maxOrder = MiddleManIntercept::pending()->max('sort_order');

        MiddleManIntercept::create([
            'event_class' => $this->eventClass,
            'event_name' => $this->eventName,
            'payload' => $this->payload,
            'metadata' => $this->metadata,
            'status' => MiddleManIntercept::STATUS_PENDING,
            'sort_order' => ((int) $maxOrder) + 1, // @phpstan-ignore cast.int
            'intercepted_at' => $this->interceptedAt,
        ]);
    }
}
