<?php

namespace Tests\Unit;

use App\DataTransferObjects\GoogleUserSyncedData;
use App\Events\VersionedEvent;
use Tests\UnitTestCase;

/**
 * Concrete implementation for testing
 */
class TestVersionedEvent extends VersionedEvent
{
    const CURRENT_VERSION = 2;

    protected static function migrateUp(mixed $data, int $fromVersion): mixed
    {
        if ($fromVersion === 1 && static::CURRENT_VERSION === 2) {
            // Simulate adding a new field in v2
            $data->newField = 'default_value';
        }

        return $data;
    }
}

class VersionedEventTest extends UnitTestCase
{
    public function test_generates_unique_event_id(): void
    {
        $event1 = new TestVersionedEvent((object) ['test' => 'data']);
        $event2 = new TestVersionedEvent((object) ['test' => 'data']);

        $this->assertNotEquals($event1->eventId, $event2->eventId);
        $this->assertNotEmpty($event1->eventId);
    }

    public function test_accepts_custom_event_id(): void
    {
        $customId = 'test-event-123';
        $event = new TestVersionedEvent((object) ['test' => 'data'], $customId);

        $this->assertEquals($customId, $event->eventId);
    }

    public function test_sets_current_version(): void
    {
        $event = new TestVersionedEvent((object) ['test' => 'data']);

        $this->assertEquals(TestVersionedEvent::CURRENT_VERSION, $event->version);
    }

    public function test_migrates_older_version(): void
    {
        $data = (object) ['test' => 'data'];
        $event = new TestVersionedEvent($data, null, 1); // v1 event

        $this->assertEquals(2, $event->version);
        $this->assertEquals('default_value', $event->data->newField);
    }

    public function test_preserves_data(): void
    {
        $data = (object) ['test' => 'value', 'number' => 42];
        $event = new TestVersionedEvent($data);

        $this->assertEquals('value', $event->data->test);
        $this->assertEquals(42, $event->data->number);
    }

    public function test_works_with_readonly_dto(): void
    {
        $dto = new GoogleUserSyncedData(
            clientId: 1,
            email: 'test@example.com',
            firstName: 'Test',
            lastName: 'User',
            googleId: 'google_123',
            suspended: false,
            orgUnitPath: '/',
            metadata: []
        );

        $event = new TestVersionedEvent($dto);

        $this->assertInstanceOf(GoogleUserSyncedData::class, $event->data);
        $this->assertEquals('test@example.com', $event->data->email);
    }
}
