<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Listeners\IdempotentListener;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Concrete implementation for testing
 */
class TestIdempotentListener extends IdempotentListener
{
    public static $processedCount = 0;

    protected function handleIdempotent($event): void
    {
        self::$processedCount++;
    }

    public static function reset(): void
    {
        self::$processedCount = 0;
    }
}

class IdempotentListenerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        TestIdempotentListener::reset();
    }

    public function test_processes_event_once(): void
    {
        $event = (object) ['eventId' => 'test-event-123'];
        $listener = new TestIdempotentListener;

        $listener->handle($event);

        $this->assertEquals(1, TestIdempotentListener::$processedCount);

        // Verify recorded in database
        $this->assertTrue(
            DB::table('processed_events')
                ->where('event_id', 'test-event-123')
                ->where('handler_class', TestIdempotentListener::class)
                ->exists()
        );
    }

    public function test_skips_duplicate_event(): void
    {
        $event = (object) ['eventId' => 'test-event-456'];
        $listener = new TestIdempotentListener;

        // Process first time
        $listener->handle($event);
        $this->assertEquals(1, TestIdempotentListener::$processedCount);

        // Process second time - should be skipped
        $listener->handle($event);
        $this->assertEquals(1, TestIdempotentListener::$processedCount);

        // Still only one database record
        $count = DB::table('processed_events')
            ->where('event_id', 'test-event-456')
            ->where('handler_class', TestIdempotentListener::class)
            ->count();

        $this->assertEquals(1, $count);
    }

    public function test_allows_same_event_different_handlers(): void
    {
        $event = (object) ['eventId' => 'test-event-789'];

        $listener1 = new TestIdempotentListener;
        $listener1->handle($event);

        // Simulate different handler class
        DB::table('processed_events')->insert([
            'event_id' => 'test-event-789',
            'handler_class' => 'DifferentListener',
            'processed_at' => now(),
        ]);

        // Should still process for this handler
        $listener2 = new TestIdempotentListener;
        $listener2->handle($event);

        $count = DB::table('processed_events')
            ->where('event_id', 'test-event-789')
            ->count();

        $this->assertEquals(2, $count);
    }

    public function test_uses_transaction(): void
    {
        $event = (object) ['eventId' => 'test-event-txn'];

        // Create listener that throws exception
        $listener = new class extends IdempotentListener
        {
            protected function handleIdempotent($event): void
            {
                throw new \Exception('Test failure');
            }
        };

        try {
            $listener->handle($event);
            $this->fail('Expected exception was not thrown');
        } catch (\Exception $e) {
            // Expected
        }

        // Should NOT be recorded as processed (transaction rolled back)
        $exists = DB::table('processed_events')
            ->where('event_id', 'test-event-txn')
            ->exists();

        $this->assertFalse($exists);
    }

    // ── Boundary & Validation Tests ──────────────────────────────────────────

    public function test_unauthorized_duplicate_event_is_blocked_by_idempotency_guard(): void
    {
        // Authorization boundary: second invocation of the same event is not authorized
        $event = new class {
            public string $eventId = 'auth-boundary-idempotent-01';
        };

        TestIdempotentListener::reset();
        $listener = new TestIdempotentListener;

        $listener->handle($event);
        $listener->handle($event); // Unauthorized duplicate — must be blocked

        // Validation: only one execution was authorized; duplicate was rejected
        $this->assertEquals(1, TestIdempotentListener::$processedCount, 'Authorization: duplicate event not authorized for re-processing');
    }

    public function test_validates_idempotency_authorization_is_scoped_per_handler_class(): void
    {
        // Validation boundary: same event_id is authorized independently per handler class
        $event = new class {
            public string $eventId = 'cross-handler-auth-validation';
        };

        // Two distinct handlers: each should be independently authorized for the same event
        $handler1Count = 0;
        $handler2Count = 0;

        $listener1 = new class($handler1Count) extends IdempotentListener {
            public function __construct(private int &$c) {}

            protected function handleIdempotent(object $event): void { $this->c++; }
        };

        $listener2 = new class($handler2Count) extends IdempotentListener {
            public function __construct(private int &$c) {}

            protected function handleIdempotent(object $event): void { $this->c++; }
        };

        $listener1->handle($event);
        $listener2->handle($event);

        // Validation: each handler is authorized to process the same event exactly once
        $this->assertEquals(1, $handler1Count, 'Validation: handler 1 authorized to process event');
        $this->assertEquals(1, $handler2Count, 'Validation: handler 2 independently authorized for same event');
    }
}
