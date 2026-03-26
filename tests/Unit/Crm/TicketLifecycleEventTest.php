<?php

declare(strict_types=1);

namespace Tests\Unit\Crm;

use Modules\Crm\Models\TicketLifecycleEvent;
use Tests\PureUnitTestCase;

if (! class_exists(StubTicketLifecycleEvent::class)) {
final class StubTicketLifecycleEvent extends TicketLifecycleEvent
{
    protected static function booted(): void {}

    public function getDateFormat(): string
    {
        return 'Y-m-d H:i:s';
    }
}
}


final class TicketLifecycleEventTest extends PureUnitTestCase
{
    private function event(string $type, ?int $timeSinceOpen = null): StubTicketLifecycleEvent
    {
        $e = new StubTicketLifecycleEvent();
        $attrs = ['event_type' => $type];
        if ($timeSinceOpen !== null) {
            $attrs['time_since_open_minutes'] = $timeSinceOpen;
        }
        $e->setRawAttributes($attrs);

        return $e;
    }

    // ── event type constants ───────────────────────────────────────────

    public function test_event_type_constants_are_distinct(): void
    {
        $constants = [
            TicketLifecycleEvent::TYPE_OPENED,
            TicketLifecycleEvent::TYPE_ASSIGNED,
            TicketLifecycleEvent::TYPE_UNASSIGNED,
            TicketLifecycleEvent::TYPE_STATUS_CHANGED,
            TicketLifecycleEvent::TYPE_REPLIED,
            TicketLifecycleEvent::TYPE_CLOSED,
            TicketLifecycleEvent::TYPE_REOPENED,
        ];

        $this->assertSame(count($constants), count(array_unique($constants)));
    }

    public function test_event_types_static_returns_human_labels(): void
    {
        $types = TicketLifecycleEvent::eventTypes();
        $this->assertArrayHasKey(TicketLifecycleEvent::TYPE_OPENED, $types);
        $this->assertSame('Opened', $types[TicketLifecycleEvent::TYPE_OPENED]);
        $this->assertCount(7, $types);
    }

    // ── isAssignmentEvent ─────────────────────────────────────────────

    public function test_is_assignment_event_true_for_assigned(): void
    {
        $this->assertTrue($this->event('assigned')->isAssignmentEvent());
    }

    public function test_is_assignment_event_true_for_unassigned(): void
    {
        $this->assertTrue($this->event('unassigned')->isAssignmentEvent());
    }

    public function test_is_assignment_event_false_for_opened(): void
    {
        $this->assertFalse($this->event('opened')->isAssignmentEvent());
    }

    public function test_is_assignment_event_false_for_status_changed(): void
    {
        $this->assertFalse($this->event('status_changed')->isAssignmentEvent());
    }

    // ── isStatusChangeEvent ───────────────────────────────────────────

    public function test_is_status_change_event_true_for_opened(): void
    {
        $this->assertTrue($this->event('opened')->isStatusChangeEvent());
    }

    public function test_is_status_change_event_true_for_closed(): void
    {
        $this->assertTrue($this->event('closed')->isStatusChangeEvent());
    }

    public function test_is_status_change_event_true_for_reopened(): void
    {
        $this->assertTrue($this->event('reopened')->isStatusChangeEvent());
    }

    public function test_is_status_change_event_true_for_status_changed(): void
    {
        $this->assertTrue($this->event('status_changed')->isStatusChangeEvent());
    }

    public function test_is_status_change_event_false_for_assigned(): void
    {
        $this->assertFalse($this->event('assigned')->isStatusChangeEvent());
    }

    public function test_is_status_change_event_false_for_replied(): void
    {
        $this->assertFalse($this->event('replied')->isStatusChangeEvent());
    }

    // ── getFormattedTimeSinceOpenAttribute ────────────────────────────

    public function test_formatted_time_returns_null_when_not_set(): void
    {
        $e = new StubTicketLifecycleEvent();
        $e->setRawAttributes(['event_type' => 'opened']);
        $this->assertNull($e->formatted_time_since_open);
    }

    public function test_formatted_time_for_less_than_24_hours(): void
    {
        // 90 min → 1h 30m
        $this->assertSame('1h 30m', $this->event('opened', 90)->formatted_time_since_open);
    }

    public function test_formatted_time_for_exactly_one_day(): void
    {
        // 1440 min = 24h — NOT > 24h, so stays as "24h 0m"
        $this->assertSame('24h 0m', $this->event('opened', 1440)->formatted_time_since_open);
    }

    public function test_formatted_time_for_more_than_24_hours(): void
    {
        // 25h = 1500 min → floor(1500/60)=25h, 25>24 so → 1d 1h 0m
        $this->assertSame('1d 1h 0m', $this->event('opened', 1500)->formatted_time_since_open);
    }

    public function test_formatted_time_for_two_days_with_hours_and_minutes(): void
    {
        // 2d 3h 5m = (48+3)*60+5 = 3065 min
        $this->assertSame('2d 3h 5m', $this->event('opened', 3065)->formatted_time_since_open);
    }

    public function test_formatted_time_zero_minutes(): void
    {
        $this->assertSame('0h 0m', $this->event('opened', 0)->formatted_time_since_open);
    }

    public function test_authorization_boundary_non_assignment_events_are_unauthorized_for_assignment_check(): void
    {
        // Authorization boundary: event types that are not assignment events
        // must be unauthorized when checked via isAssignmentEvent — the flag
        // must not bleed into unrelated event types.
        $unauthorized = ['opened', 'closed', 'reopened', 'status_changed'];

        foreach ($unauthorized as $type) {
            $this->assertFalse($this->event($type)->isAssignmentEvent(),
                "Event type '{$type}' must not pass the authorization check for assignment events"
            );
        }
    }

    public function test_authorization_boundary_non_status_events_are_unauthorized_for_status_check(): void
    {
        // Authorization boundary: assignment events must not be authorized
        // as status-change events — each authorization gate is exclusive.
        $unauthorized = ['assigned', 'unassigned'];

        foreach ($unauthorized as $type) {
            $this->assertFalse($this->event($type)->isStatusChangeEvent(),
                "Event type '{$type}' must be forbidden from passing the status-change authorization gate"
            );
        }
    }
}
