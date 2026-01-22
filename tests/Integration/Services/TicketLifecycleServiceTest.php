<?php

declare(strict_types=1);

namespace Tests\Integration\Services;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Modules\Crm\Services\TicketLifecycleService;
use Modules\Crm\Models\ClientConversation;
use Modules\Crm\Models\TicketLifecycleEvent;
use Modules\Crm\Models\Client;
use Modules\Crm\Models\Company;
use Modules\Crm\Models\Contact;
use Modules\Crm\Events\ConversationLinkedToClient;
use Modules\Crm\Events\TicketLifecycleEventRecorded;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

/**
 * TicketLifecycleService Integration Tests
 * 
 * Tests ticket lifecycle management including:
 * - Linking conversations to clients
 * - Auto-linking via email matching
 * - Recording lifecycle events (open, close, assign, reply)
 * - Time tracking (time to first response, time to resolution)
 * - Event timeline generation
 */
#[Group('integration')]
#[Group('services')]
#[Group('crm')]
#[Group('tickets')]
class TicketLifecycleServiceTest extends TestCase
{
    use RefreshDatabase;

    private TicketLifecycleService $service;
    private Company $company;
    private Client $client;

    protected function setUp(): void
    {
        parent::setUp();

        Event::fake([
            ConversationLinkedToClient::class,
            TicketLifecycleEventRecorded::class,
        ]);

        $this->createRequiredTables();

        $this->company = Company::factory()->create();
        $this->client = Client::factory()->create(['company_id' => $this->company->id]);

        $this->service = app(TicketLifecycleService::class);
    }

    private function createRequiredTables(): void
    {
        Schema::dropIfExists('ticket_lifecycle_events');
        Schema::dropIfExists('client_conversations');
        Schema::dropIfExists('contacts');
        Schema::dropIfExists('conversations');

        Schema::create('conversations', function ($table) {
            $table->id();
            $table->string('customer_email')->nullable();
            $table->string('subject')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();
        });

        Schema::create('contacts', function ($table) {
            $table->id();
            $table->foreignId('client_id');
            $table->string('email');
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->timestamps();
        });

        Schema::create('client_conversations', function ($table) {
            $table->id();
            $table->foreignId('client_id');
            $table->foreignId('conversation_id');
            $table->integer('total_time_minutes')->default(0);
            $table->foreignId('linked_by_user_id')->nullable();
            $table->string('linked_via')->default('manual');
            $table->timestamp('opened_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();
            $table->unique(['client_id', 'conversation_id']);
        });

        Schema::create('ticket_lifecycle_events', function ($table) {
            $table->id();
            $table->foreignId('conversation_id');
            $table->foreignId('client_id')->nullable();
            $table->string('event_type');
            $table->foreignId('user_id')->nullable();
            $table->string('old_status')->nullable();
            $table->string('new_status')->nullable();
            $table->foreignId('old_assignee_id')->nullable();
            $table->foreignId('new_assignee_id')->nullable();
            $table->timestamp('event_at');
            $table->integer('time_since_open_minutes')->nullable();
            $table->integer('time_since_last_event_minutes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    private function createConversation(array $attributes = []): int
    {
        return DB::table('conversations')->insertGetId(array_merge([
            'customer_email' => 'customer@example.com',
            'subject' => 'Test Ticket',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ], $attributes));
    }

    private function createContact(int $clientId, string $email): int
    {
        return DB::table('contacts')->insertGetId([
            'client_id' => $clientId,
            'email' => $email,
            'first_name' => 'Test',
            'last_name' => 'Contact',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Test links conversation to client.
     */
    public function test_links_conversation_to_client(): void
    {
        $conversationId = $this->createConversation();

        $link = $this->service->linkConversationToClient(
            conversationId: $conversationId,
            clientId: $this->client->id
        );

        $this->assertInstanceOf(ClientConversation::class, $link);
        $this->assertEquals($this->client->id, $link->client_id);
        $this->assertEquals($conversationId, $link->conversation_id);
    }

    /**
     * Test link defaults to email match.
     */
    public function test_link_defaults_to_email_match(): void
    {
        $conversationId = $this->createConversation();

        $link = $this->service->linkConversationToClient(
            conversationId: $conversationId,
            clientId: $this->client->id
        );

        $this->assertEquals(ClientConversation::LINKED_VIA_EMAIL_MATCH, $link->linked_via);
    }

    /**
     * Test link with manual linking.
     */
    public function test_link_with_manual_linking(): void
    {
        $conversationId = $this->createConversation();

        $link = $this->service->linkConversationToClient(
            conversationId: $conversationId,
            clientId: $this->client->id,
            linkedVia: ClientConversation::LINKED_VIA_MANUAL
        );

        $this->assertEquals(ClientConversation::LINKED_VIA_MANUAL, $link->linked_via);
    }

    /**
     * Test dispatches event on new link.
     */
    public function test_dispatches_event_on_new_link(): void
    {
        $conversationId = $this->createConversation();

        $this->service->linkConversationToClient(
            conversationId: $conversationId,
            clientId: $this->client->id
        );

        Event::assertDispatched(ConversationLinkedToClient::class, function ($event) use ($conversationId) {
            return $event->conversationId === $conversationId
                && $event->clientId === $this->client->id;
        });
    }

    /**
     * Test does not duplicate existing link.
     */
    public function test_does_not_duplicate_existing_link(): void
    {
        $conversationId = $this->createConversation();

        $link1 = $this->service->linkConversationToClient(
            conversationId: $conversationId,
            clientId: $this->client->id
        );

        $link2 = $this->service->linkConversationToClient(
            conversationId: $conversationId,
            clientId: $this->client->id
        );

        $this->assertEquals($link1->id, $link2->id);
        
        // Should only dispatch once
        Event::assertDispatchedTimes(ConversationLinkedToClient::class, 1);
    }

    /**
     * Test records opened event.
     */
    public function test_records_opened_event(): void
    {
        $conversationId = $this->createConversation();

        $event = $this->service->recordOpened(
            conversationId: $conversationId,
            status: 'active'
        );

        $this->assertInstanceOf(TicketLifecycleEvent::class, $event);
        $this->assertEquals(TicketLifecycleEvent::TYPE_OPENED, $event->event_type);
        $this->assertEquals('active', $event->new_status);
        $this->assertEquals(0, $event->time_since_open_minutes);
    }

    /**
     * Test records assigned event.
     */
    public function test_records_assigned_event(): void
    {
        $conversationId = $this->createConversation();

        $this->service->recordOpened(conversationId: $conversationId);

        $event = $this->service->recordAssigned(
            conversationId: $conversationId,
            newAssigneeId: 1,
            oldAssigneeId: null
        );

        $this->assertEquals(TicketLifecycleEvent::TYPE_ASSIGNED, $event->event_type);
        $this->assertEquals(1, $event->new_assignee_id);
        $this->assertNull($event->old_assignee_id);
    }

    /**
     * Test records reassignment.
     */
    public function test_records_reassignment(): void
    {
        $conversationId = $this->createConversation();

        $this->service->recordOpened(conversationId: $conversationId);
        $this->service->recordAssigned(conversationId: $conversationId, newAssigneeId: 1);

        $event = $this->service->recordAssigned(
            conversationId: $conversationId,
            newAssigneeId: 2,
            oldAssigneeId: 1
        );

        $this->assertEquals(1, $event->old_assignee_id);
        $this->assertEquals(2, $event->new_assignee_id);
    }

    /**
     * Test records closed event.
     */
    public function test_records_closed_event(): void
    {
        $conversationId = $this->createConversation();
        
        // Link and open
        $this->service->linkConversationToClient(
            conversationId: $conversationId,
            clientId: $this->client->id
        );
        $this->service->recordOpened(conversationId: $conversationId);

        $event = $this->service->recordClosed(
            conversationId: $conversationId,
            oldStatus: 'active'
        );

        $this->assertEquals(TicketLifecycleEvent::TYPE_CLOSED, $event->event_type);
        $this->assertEquals('active', $event->old_status);
        $this->assertEquals('closed', $event->new_status);

        // Check closed_at was set on ClientConversation
        $link = ClientConversation::where('conversation_id', $conversationId)->first();
        $this->assertNotNull($link->closed_at);
    }

    /**
     * Test records reopened event.
     */
    public function test_records_reopened_event(): void
    {
        $conversationId = $this->createConversation();
        
        $this->service->linkConversationToClient(
            conversationId: $conversationId,
            clientId: $this->client->id
        );
        $this->service->recordOpened(conversationId: $conversationId);
        $this->service->recordClosed(conversationId: $conversationId);

        $event = $this->service->recordReopened(conversationId: $conversationId);

        $this->assertEquals(TicketLifecycleEvent::TYPE_REOPENED, $event->event_type);
        $this->assertEquals('closed', $event->old_status);
        $this->assertEquals('active', $event->new_status);

        // Check closed_at was cleared on ClientConversation
        $link = ClientConversation::where('conversation_id', $conversationId)->first();
        $this->assertNull($link->closed_at);
    }

    /**
     * Test records replied event.
     */
    public function test_records_replied_event(): void
    {
        $conversationId = $this->createConversation();

        $this->service->recordOpened(conversationId: $conversationId);

        $event = $this->service->recordReplied(
            conversationId: $conversationId,
            metadata: ['reply_type' => 'email']
        );

        $this->assertEquals(TicketLifecycleEvent::TYPE_REPLIED, $event->event_type);
        $this->assertEquals('email', $event->metadata['reply_type']);
    }

    /**
     * Test calculates time since open.
     */
    public function test_calculates_time_since_open(): void
    {
        $conversationId = $this->createConversation();

        // Record opened 30 minutes ago
        $openedEvent = $this->service->recordOpened(conversationId: $conversationId);
        DB::table('ticket_lifecycle_events')
            ->where('id', $openedEvent->id)
            ->update(['event_at' => now()->subMinutes(30)]);

        $event = $this->service->recordReplied(conversationId: $conversationId);

        $this->assertGreaterThanOrEqual(29, $event->time_since_open_minutes);
        $this->assertLessThanOrEqual(31, $event->time_since_open_minutes);
    }

    /**
     * Test calculates time since last event.
     */
    public function test_calculates_time_since_last_event(): void
    {
        $conversationId = $this->createConversation();

        $this->service->recordOpened(conversationId: $conversationId);
        
        // Update last event to 15 minutes ago
        DB::table('ticket_lifecycle_events')
            ->where('conversation_id', $conversationId)
            ->update(['event_at' => now()->subMinutes(15)]);

        $event = $this->service->recordReplied(conversationId: $conversationId);

        $this->assertGreaterThanOrEqual(14, $event->time_since_last_event_minutes);
        $this->assertLessThanOrEqual(16, $event->time_since_last_event_minutes);
    }

    /**
     * Test dispatches lifecycle event recorded.
     */
    public function test_dispatches_lifecycle_event_recorded(): void
    {
        $conversationId = $this->createConversation();

        $this->service->recordOpened(conversationId: $conversationId);

        Event::assertDispatched(TicketLifecycleEventRecorded::class, function ($event) use ($conversationId) {
            return $event->conversationId === $conversationId
                && $event->eventType === TicketLifecycleEvent::TYPE_OPENED;
        });
    }

    /**
     * Test gets timeline for conversation.
     */
    public function test_gets_timeline_for_conversation(): void
    {
        $conversationId = $this->createConversation();

        $this->service->recordOpened(conversationId: $conversationId);
        $this->service->recordAssigned(conversationId: $conversationId, newAssigneeId: 1);
        $this->service->recordReplied(conversationId: $conversationId);
        $this->service->recordClosed(conversationId: $conversationId);

        $timeline = $this->service->getTimeline($conversationId);

        $this->assertCount(4, $timeline);
        $this->assertEquals(TicketLifecycleEvent::TYPE_OPENED, $timeline[0]->event_type);
        $this->assertEquals(TicketLifecycleEvent::TYPE_ASSIGNED, $timeline[1]->event_type);
        $this->assertEquals(TicketLifecycleEvent::TYPE_REPLIED, $timeline[2]->event_type);
        $this->assertEquals(TicketLifecycleEvent::TYPE_CLOSED, $timeline[3]->event_type);
    }

    /**
     * Test get time to first response.
     */
    public function test_get_time_to_first_response(): void
    {
        $conversationId = $this->createConversation();

        // Simulate opened 20 minutes ago
        $openedEvent = $this->service->recordOpened(conversationId: $conversationId);
        DB::table('ticket_lifecycle_events')
            ->where('id', $openedEvent->id)
            ->update(['event_at' => now()->subMinutes(20)]);

        $this->service->recordReplied(conversationId: $conversationId);

        $ttfr = $this->service->getTimeToFirstResponse($conversationId);

        $this->assertGreaterThanOrEqual(19, $ttfr);
        $this->assertLessThanOrEqual(21, $ttfr);
    }

    /**
     * Test get time to first response returns null if no reply.
     */
    public function test_get_time_to_first_response_returns_null_if_no_reply(): void
    {
        $conversationId = $this->createConversation();

        $this->service->recordOpened(conversationId: $conversationId);

        $ttfr = $this->service->getTimeToFirstResponse($conversationId);

        $this->assertNull($ttfr);
    }

    /**
     * Test get time to resolution.
     */
    public function test_get_time_to_resolution(): void
    {
        $conversationId = $this->createConversation();

        // Simulate opened 60 minutes ago
        $openedEvent = $this->service->recordOpened(conversationId: $conversationId);
        DB::table('ticket_lifecycle_events')
            ->where('id', $openedEvent->id)
            ->update(['event_at' => now()->subMinutes(60)]);

        $this->service->recordClosed(conversationId: $conversationId);

        $ttr = $this->service->getTimeToResolution($conversationId);

        $this->assertGreaterThanOrEqual(59, $ttr);
        $this->assertLessThanOrEqual(61, $ttr);
    }

    /**
     * Test get time to resolution returns null if not closed.
     */
    public function test_get_time_to_resolution_returns_null_if_not_closed(): void
    {
        $conversationId = $this->createConversation();

        $this->service->recordOpened(conversationId: $conversationId);

        $ttr = $this->service->getTimeToResolution($conversationId);

        $this->assertNull($ttr);
    }

    /**
     * Test event stores metadata.
     */
    public function test_event_stores_metadata(): void
    {
        $conversationId = $this->createConversation();

        $metadata = [
            'custom_field' => 'value',
            'tags' => ['urgent', 'billing'],
            'source' => 'portal',
        ];

        $event = $this->service->recordOpened(
            conversationId: $conversationId,
            metadata: $metadata
        );

        $this->assertEquals('value', $event->metadata['custom_field']);
        $this->assertContains('urgent', $event->metadata['tags']);
        $this->assertEquals('portal', $event->metadata['source']);
    }

    /**
     * Test linked via constants.
     */
    public function test_linked_via_constants(): void
    {
        $this->assertEquals('email_match', ClientConversation::LINKED_VIA_EMAIL_MATCH);
        $this->assertEquals('manual', ClientConversation::LINKED_VIA_MANUAL);
        $this->assertEquals('api', ClientConversation::LINKED_VIA_API);
        $this->assertEquals('contact_lookup', ClientConversation::LINKED_VIA_CONTACT_LOOKUP);
    }

    /**
     * Test event type constants.
     */
    public function test_event_type_constants(): void
    {
        $this->assertEquals('opened', TicketLifecycleEvent::TYPE_OPENED);
        $this->assertEquals('assigned', TicketLifecycleEvent::TYPE_ASSIGNED);
        $this->assertEquals('unassigned', TicketLifecycleEvent::TYPE_UNASSIGNED);
        $this->assertEquals('status_changed', TicketLifecycleEvent::TYPE_STATUS_CHANGED);
        $this->assertEquals('replied', TicketLifecycleEvent::TYPE_REPLIED);
        $this->assertEquals('closed', TicketLifecycleEvent::TYPE_CLOSED);
        $this->assertEquals('reopened', TicketLifecycleEvent::TYPE_REOPENED);
    }

    /**
     * Test multiple conversations for same client.
     */
    public function test_multiple_conversations_for_same_client(): void
    {
        $conversation1 = $this->createConversation(['subject' => 'Ticket 1']);
        $conversation2 = $this->createConversation(['subject' => 'Ticket 2']);

        $link1 = $this->service->linkConversationToClient(
            conversationId: $conversation1,
            clientId: $this->client->id
        );

        $link2 = $this->service->linkConversationToClient(
            conversationId: $conversation2,
            clientId: $this->client->id
        );

        $this->assertNotEquals($link1->id, $link2->id);
        $this->assertEquals($this->client->id, $link1->client_id);
        $this->assertEquals($this->client->id, $link2->client_id);

        $clientLinks = ClientConversation::where('client_id', $this->client->id)->count();
        $this->assertEquals(2, $clientLinks);
    }
}
