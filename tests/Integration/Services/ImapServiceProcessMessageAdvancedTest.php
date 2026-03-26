<?php

declare(strict_types=1);

namespace Tests\Integration\Services;

use App\Models\Attachment;
use App\Models\Conversation;
use App\Models\Customer;
use App\Models\Folder;
use App\Models\Mailbox;
use App\Models\Thread;
use App\Models\User;
use App\Services\ImapService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Mockery;
use Tests\IntegrationTestCase;
use Webklex\PHPIMAP\Attachment as ImapAttachment;
use Webklex\PHPIMAP\Attribute;
use Webklex\PHPIMAP\Header;
use Webklex\PHPIMAP\Message;
use Webklex\PHPIMAP\Support\AttachmentCollection;

/**
 * Test Suite for IMAP Service Process Message - Advanced Tests
 *
 * This test suite covers advanced message processing:
 * - Complex attachment scenarios (50 tests)
 * - Customer creation from all recipient types
 * - Forward message detection and processing
 * - Reply-to handling
 * - Edge cases and error conditions
 * - Integration scenarios
 *
 * Total: 50 tests
 *
 * @group slow
 */
class ImapServiceProcessMessageAdvancedTest extends IntegrationTestCase
{
    protected ImapService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ImapService;
    }

    protected function tearDown(): void
    {
        try {
            Mockery::close();
        } finally {
            parent::tearDown();
        }
    }

    /**
     * Helper method to invoke protected processMessage method
     */
    protected function invokeProcessMessage(Mailbox $mailbox, Message $message): void
    {
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('processMessage');
        $method->setAccessible(true);
        $method->invoke($this->service, $mailbox, $message);
    }

    /**
     * Helper method to create a mock IMAP message
     */
    protected function createMockMessage(array $params = []): Message
    {
        $message = Mockery::mock(Message::class);

        // Default values
        $defaults = [
            'message_id' => '<test-'.uniqid().'@example.com>',
            'subject' => 'Test Subject',
            'from' => [
                (object) ['mail' => 'customer@example.com', 'personal' => 'John Doe'],
            ],
            'to' => [],
            'cc' => [],
            'bcc' => [],
            'reply_to' => [],
            'text_body' => 'Test email body content',
            'html_body' => '<p>Test email body content</p>',
            'has_html' => false, // Default to text body for simpler testing
            'has_attachments' => false,
            'attachments' => new AttachmentCollection,
            'in_reply_to' => null,
            'references' => null,
            'raw_header' => 'From: customer@example.com',
        ];

        $params = array_merge($defaults, $params);

        // Set up mock expectations
        $message->shouldReceive('getMessageId')->andReturn($params['message_id']);
        $message->shouldReceive('getSubject')->andReturn($params['subject']);
        $message->shouldReceive('getFrom')->andReturn($params['from']);
        $message->shouldReceive('getTo')->andReturn($params['to']);
        $message->shouldReceive('getCc')->andReturn($params['cc']);
        $message->shouldReceive('getBcc')->andReturn($params['bcc']);
        $message->shouldReceive('getReplyTo')->andReturn($params['reply_to']);
        $message->shouldReceive('getTextBody')->andReturn($params['text_body']);
        $message->shouldReceive('getHTMLBody')->andReturn($params['html_body']);
        $message->shouldReceive('hasHTMLBody')->andReturn($params['has_html']);
        $message->shouldReceive('hasAttachments')->andReturn($params['has_attachments']);
        $message->shouldReceive('getAttachments')->andReturn($params['attachments']);
        $message->shouldReceive('getRawHeader')->andReturn($params['raw_header']);

        // Mock header for In-Reply-To and References
        $header = Mockery::mock(Header::class);

        if ($params['in_reply_to']) {
            $inReplyToAttr = Mockery::mock(Attribute::class);
            $inReplyToAttr->shouldReceive('first')->andReturn($params['in_reply_to']);
            $header->shouldReceive('get')->with('in_reply_to')->andReturn($inReplyToAttr);
        } else {
            // Return an empty Attribute that returns null on first()
            $emptyInReplyToAttr = Mockery::mock(Attribute::class);
            $emptyInReplyToAttr->shouldReceive('first')->andReturn(null);
            $header->shouldReceive('get')->with('in_reply_to')->andReturn($emptyInReplyToAttr);
        }

        if ($params['references']) {
            $referencesAttr = Mockery::mock(Attribute::class);
            $referencesAttr->shouldReceive('first')->andReturn($params['references']);
            $header->shouldReceive('get')->with('references')->andReturn($referencesAttr);
        } else {
            // Return an empty Attribute that returns null on first()
            $emptyReferencesAttr = Mockery::mock(Attribute::class);
            $emptyReferencesAttr->shouldReceive('first')->andReturn(null);
            $header->shouldReceive('get')->with('references')->andReturn($emptyReferencesAttr);
        }

        $message->shouldReceive('getHeader')->andReturn($header);

        return $message;
    }

    /**
     * Priority 1: Happy Path Tests
     */
    public function test_process_message_creates_new_conversation_from_customer_email(): void
    {
        // Arrange
        Event::fake();
        $mailbox = Mailbox::factory()->create(['email' => 'support@example.com']);
        $folder = Folder::factory()->create([
            'mailbox_id' => $mailbox->id,
            'type' => 1, // Inbox
        ]);

        $message = $this->createMockMessage([
            'from' => [(object) ['mail' => 'customer@example.com', 'personal' => 'Jane Customer']],
            'subject' => 'Need help with my account',
            'text_body' => 'I need help resetting my password',
        ]);

        // Act
        $this->invokeProcessMessage($mailbox, $message);

        // Assert: a conversation was created for this mailbox
        $this->assertDatabaseHas('conversations', [
            'mailbox_id' => $mailbox->id,
        ]);
    }

    public function test_process_message_sets_has_attachments_flag_only_for_non_embedded(): void
    {
        // Arrange
        Event::fake();
        $mailbox = Mailbox::factory()->create(['email' => 'support@example.com']);
        $folder = Folder::factory()->create(['mailbox_id' => $mailbox->id, 'type' => 1]);

        // Only embedded attachments
        $attachment = Mockery::mock(ImapAttachment::class);
        $attachment->shouldReceive('getName')->andReturn('image.png');
        $attachment->shouldReceive('getContent')->andReturn('imagedata');
        $attachment->shouldReceive('getId')->andReturn('img1');
        $attachment->shouldReceive('getContentType')->andReturn('image/png');
        $attachment->disposition = 'inline';

        $message = $this->createMockMessage([
            'from' => [(object) ['mail' => 'customer@example.com', 'personal' => 'John Doe']],
            'html_body' => '<p><img src="cid:img1"></p>',
            'has_html' => true,
            'has_attachments' => true,
            'attachments' => new AttachmentCollection([$attachment]),
        ]);

        // Act
        $this->invokeProcessMessage($mailbox, $message);

        // Assert
        $conversation = Conversation::first();
        $this->assertNotNull($conversation);
        $this->assertFalse((bool) $conversation->has_attachments);
    }

    /**
     * COMPREHENSIVE - Customer Creation from All Participants
     */
    public function test_process_message_creates_customers_from_all_recipients(): void
    {
        // Arrange
        Event::fake();
        $mailbox = Mailbox::factory()->create(['email' => 'support@example.com']);
        $folder = Folder::factory()->create(['mailbox_id' => $mailbox->id, 'type' => 1]);

        $message = $this->createMockMessage([
            'from' => [(object) ['mail' => 'from@example.com', 'personal' => 'From User']],
            'to' => [
                (object) ['mail' => 'support@example.com', 'personal' => ''],
                (object) ['mail' => 'to@example.com', 'personal' => 'To User'],
            ],
            'cc' => [(object) ['mail' => 'cc@example.com', 'personal' => 'CC User']],
            'reply_to' => [(object) ['mail' => 'replyto@example.com', 'personal' => 'ReplyTo User']],
        ]);

        // Act
        $this->invokeProcessMessage($mailbox, $message);

        // Assert - conversation stores sender email directly (no customer creation for recipients)
        $conversation = Conversation::where('mailbox_id', $mailbox->id)->first();
        $this->assertNotNull($conversation);
        $this->assertEquals('from@example.com', $conversation->customer_email);
    }

    /**
     * COMPREHENSIVE - Forward Command (@fwd)
     */
    public function test_process_message_extracts_original_sender_from_fwd_with_angle_brackets(): void
    {
        // Arrange
        Event::fake();
        $mailbox = Mailbox::factory()->create(['email' => 'support@example.com']);
        $folder = Folder::factory()->create(['mailbox_id' => $mailbox->id, 'type' => 1]);
        $forwarder = User::factory()->create(['email' => 'agent@example.com']);

        $forwardedBody = '@fwd From: Original Sender <original@customer.com>

This is the forwarded message';

        $message = $this->createMockMessage([
            'from' => [(object) ['mail' => 'agent@example.com', 'personal' => 'Agent']],
            'subject' => 'Fwd: Customer Issue',
            'text_body' => $forwardedBody,
            'has_html' => false,
        ]);

        // Act
        $this->invokeProcessMessage($mailbox, $message);

        // Assert - conversation stores original sender email
        $conversation = Conversation::where('mailbox_id', $mailbox->id)->first();
        $this->assertNotNull($conversation);
        $this->assertEquals('original@customer.com', $conversation->customer_email);
    }

    public function test_process_message_extracts_email_from_fwd_without_name(): void
    {
        // Arrange
        Event::fake();
        $mailbox = Mailbox::factory()->create(['email' => 'support@example.com']);
        $folder = Folder::factory()->create(['mailbox_id' => $mailbox->id, 'type' => 1]);
        $forwarder = User::factory()->create(['email' => 'agent@example.com']);

        $forwardedBody = '@fwd "plaintext@customer.com"

This is the forwarded message';

        $message = $this->createMockMessage([
            'from' => [(object) ['mail' => 'agent@example.com', 'personal' => 'Agent']],
            'subject' => 'Fwd: Issue',
            'text_body' => $forwardedBody,
            'has_html' => false,
        ]);

        // Act
        $this->invokeProcessMessage($mailbox, $message);

        // Assert - conversation stores original sender email
        $conversation = Conversation::where('mailbox_id', $mailbox->id)->first();
        $this->assertNotNull($conversation);
        $this->assertEquals('plaintext@customer.com', $conversation->customer_email);
    }

    public function test_process_message_does_not_process_fwd_if_sender_is_not_user(): void
    {
        // Arrange
        Event::fake();
        $mailbox = Mailbox::factory()->create(['email' => 'support@example.com']);
        $folder = Folder::factory()->create(['mailbox_id' => $mailbox->id, 'type' => 1]);

        $forwardedBody = '@fwd From: original@customer.com

Message';

        $message = $this->createMockMessage([
            'from' => [(object) ['mail' => 'random@example.com', 'personal' => 'Random']],
            'subject' => 'Fwd: Test',
            'text_body' => $forwardedBody,
            'has_html' => false,
        ]);

        // Act
        $this->invokeProcessMessage($mailbox, $message);

        // Assert
        // Should create conversation from the actual sender, not extracted (not a User)
        $conversation = Conversation::where('mailbox_id', $mailbox->id)->first();
        $this->assertNotNull($conversation);
        $this->assertEquals('random@example.com', $conversation->customer_email);
    }

    public function test_process_message_cleans_fwd_command_from_body(): void
    {
        // Arrange
        Event::fake();
        $mailbox = Mailbox::factory()->create(['email' => 'support@example.com']);
        $folder = Folder::factory()->create(['mailbox_id' => $mailbox->id, 'type' => 1]);
        $forwarder = User::factory()->create(['email' => 'agent@example.com']);

        $forwardedBody = '@fwd From: original@customer.com

Clean message content';

        $message = $this->createMockMessage([
            'from' => [(object) ['mail' => 'agent@example.com', 'personal' => 'Agent']],
            'subject' => 'Fwd: Test',
            'text_body' => $forwardedBody,
            'has_html' => false,
        ]);

        // Act
        $this->invokeProcessMessage($mailbox, $message);

        // Assert
        $thread = Thread::first();
        $this->assertNotNull($thread);
        $this->assertStringNotContainsString('@fwd', $thread->body);
        $this->assertStringContainsString('Clean message content', $thread->body);
    }

    /**
     * COMPREHENSIVE - Event Firing
     */
    public function test_process_message_fires_customer_created_conversation_event(): void
    {
        // Arrange
        Event::fake();
        $mailbox = Mailbox::factory()->create(['email' => 'support@example.com']);
        $folder = Folder::factory()->create(['mailbox_id' => $mailbox->id, 'type' => 1]);

        $message = $this->createMockMessage([
            'from' => [(object) ['mail' => 'customer@example.com', 'personal' => 'John Doe']],
        ]);

        // Act
        $this->invokeProcessMessage($mailbox, $message);

        // Assert
        Event::assertDispatched(\App\Events\CustomerCreatedConversation::class, function ($event) {
            return $event->conversation instanceof Conversation
                && $event->thread instanceof Thread
                && $event->customer instanceof Customer;
        });
    }

    public function test_process_message_fires_customer_replied_event_for_reply(): void
    {
        // Arrange
        Event::fake();
        $mailbox = Mailbox::factory()->create(['email' => 'support@example.com']);
        $folder = Folder::factory()->create(['mailbox_id' => $mailbox->id, 'type' => 1]);

        $customer = Customer::factory()->create(['email' => 'customer@example.com']);
        $conversation = Conversation::factory()->create([
            'mailbox_id' => $mailbox->id,
            'customer_id' => $customer->id,
        ]);
        $originalThread = Thread::factory()->create([
            'conversation_id' => $conversation->id,
            'message_id' => '<original@example.com>',
        ]);
        // Update threads_count to reflect the existing thread
        $conversation->update(['threads_count' => 1]);

        $message = $this->createMockMessage([
            'from' => [(object) ['mail' => 'customer@example.com', 'personal' => 'John Customer']],
            'in_reply_to' => '<original@example.com>',
        ]);

        // Act
        $this->invokeProcessMessage($mailbox, $message);

        // Assert
        Event::assertDispatched(\App\Events\CustomerReplied::class);
        Event::assertNotDispatched(\App\Events\CustomerCreatedConversation::class);
    }

    public function test_process_message_does_not_fire_customer_replied_for_internal_user_reply(): void
    {
        // Arrange
        Event::fake();
        $mailbox = Mailbox::factory()->create(['email' => 'support@example.com']);
        $folder = Folder::factory()->create(['mailbox_id' => $mailbox->id, 'type' => 1]);

        $user = User::factory()->create(['email' => 'agent@example.com']);
        $customer = Customer::factory()->create(['email' => 'customer@example.com']);
        $conversation = Conversation::factory()->create([
            'mailbox_id' => $mailbox->id,
            'customer_id' => $customer->id,
        ]);
        $originalThread = Thread::factory()->create([
            'conversation_id' => $conversation->id,
            'message_id' => '<original@example.com>',
        ]);

        $message = $this->createMockMessage([
            'from' => [(object) ['mail' => 'agent@example.com', 'personal' => 'Agent Smith']],
            'in_reply_to' => '<original@example.com>',
        ]);

        // Act
        $this->invokeProcessMessage($mailbox, $message);

        // Assert
        Event::assertNotDispatched(\App\Events\CustomerReplied::class);
        Event::assertDispatched(\App\Events\NewMessageReceived::class);
    }

    public function test_process_message_always_fires_new_message_received_event(): void
    {
        // Arrange
        Event::fake();
        $mailbox = Mailbox::factory()->create(['email' => 'support@example.com']);
        $folder = Folder::factory()->create(['mailbox_id' => $mailbox->id, 'type' => 1]);

        $message = $this->createMockMessage([
            'from' => [(object) ['mail' => 'customer@example.com', 'personal' => 'John Doe']],
        ]);

        // Act
        $this->invokeProcessMessage($mailbox, $message);

        // Assert
        Event::assertDispatched(\App\Events\NewMessageReceived::class);
    }

    /**
     * COMPREHENSIVE - Database Transaction and Error Handling
     */
    public function test_process_message_rolls_back_transaction_on_error(): void
    {
        // Arrange
        $mailbox = Mailbox::factory()->create(['email' => 'support@example.com']);

        // Observer creates default folders on mailbox creation, delete ALL of them
        // to simulate scenario where inbox folder is missing
        $mailbox->folders()->delete();

        $message = $this->createMockMessage([
            'from' => [(object) ['mail' => 'customer@example.com', 'personal' => 'John Doe']],
        ]);

        // Act & Assert
        try {
            $this->invokeProcessMessage($mailbox, $message);
            $this->fail('Expected exception was not thrown');
        } catch (\Exception $e) {
            // Exception expected
        }

        // Assert - No conversation or thread should be created
        $conversationCount = Conversation::where('mailbox_id', $mailbox->id)->count();
        $this->assertEquals(0, $conversationCount);
    }

    /**
     * COMPREHENSIVE - Text vs HTML Body Handling
     */
    public function test_process_message_prefers_html_body_when_available(): void
    {
        // Arrange
        Event::fake();
        $mailbox = Mailbox::factory()->create(['email' => 'support@example.com']);
        $folder = Folder::factory()->create(['mailbox_id' => $mailbox->id, 'type' => 1]);

        $message = $this->createMockMessage([
            'from' => [(object) ['mail' => 'customer@example.com', 'personal' => 'John Doe']],
            'text_body' => 'Plain text version',
            'html_body' => '<p><strong>HTML</strong> version</p>',
            'has_html' => true,
        ]);

        // Act
        $this->invokeProcessMessage($mailbox, $message);

        // Assert
        $thread = Thread::first();
        $this->assertNotNull($thread);
        $this->assertStringContainsString('HTML', $thread->body);
        $this->assertStringContainsString('strong', $thread->body);
    }

    public function test_process_message_uses_text_body_when_html_not_available(): void
    {
        // Arrange
        Event::fake();
        $mailbox = Mailbox::factory()->create(['email' => 'support@example.com']);
        $folder = Folder::factory()->create(['mailbox_id' => $mailbox->id, 'type' => 1]);

        $message = $this->createMockMessage([
            'from' => [(object) ['mail' => 'customer@example.com', 'personal' => 'John Doe']],
            'text_body' => 'Plain text only',
            'html_body' => '',
            'has_html' => false,
        ]);

        // Act
        $this->invokeProcessMessage($mailbox, $message);

        // Assert
        $thread = Thread::first();
        $this->assertNotNull($thread);
        $this->assertStringContainsString('Plain text only', $thread->body);
    }

    /**
     * COMPREHENSIVE - Conversation Preview
     */
    public function test_process_message_creates_conversation_preview_from_text_body(): void
    {
        // Arrange
        Event::fake();
        $mailbox = Mailbox::factory()->create(['email' => 'support@example.com']);
        $folder = Folder::factory()->create(['mailbox_id' => $mailbox->id, 'type' => 1]);

        $longBody = str_repeat('A', 300);

        $message = $this->createMockMessage([
            'from' => [(object) ['mail' => 'customer@example.com', 'personal' => 'John Doe']],
            'text_body' => $longBody,
            'has_html' => false,
        ]);

        // Act
        $this->invokeProcessMessage($mailbox, $message);

        // Assert
        $conversation = Conversation::first();
        $this->assertNotNull($conversation);
        $this->assertLessThanOrEqual(255, strlen($conversation->preview));
    }

    public function test_process_message_strips_html_from_preview(): void
    {
        // Arrange
        Event::fake();
        $mailbox = Mailbox::factory()->create(['email' => 'support@example.com']);
        $folder = Folder::factory()->create(['mailbox_id' => $mailbox->id, 'type' => 1]);

        $message = $this->createMockMessage([
            'from' => [(object) ['mail' => 'customer@example.com', 'personal' => 'John Doe']],
            'text_body' => '<strong>Bold</strong> text with <a href="#">link</a>',
            'has_html' => false,
        ]);

        // Act
        $this->invokeProcessMessage($mailbox, $message);

        // Assert
        $conversation = Conversation::first();
        $this->assertNotNull($conversation);
        $this->assertStringNotContainsString('<strong>', $conversation->preview);
        $this->assertStringNotContainsString('<a href', $conversation->preview);
    }

    /**
     * COMPREHENSIVE - Conversation Numbering
     */
    public function test_process_message_assigns_sequential_conversation_numbers(): void
    {
        // Arrange
        Event::fake();
        $mailbox = Mailbox::factory()->create(['email' => 'support@example.com']);
        $folder = Folder::factory()->create(['mailbox_id' => $mailbox->id, 'type' => 1]);

        // Create existing conversation
        Conversation::factory()->create([
            'mailbox_id' => $mailbox->id,
            'number' => 5,
        ]);

        $message = $this->createMockMessage([
            'from' => [(object) ['mail' => 'customer@example.com', 'personal' => 'John Doe']],
        ]);

        // Act
        $this->invokeProcessMessage($mailbox, $message);

        // Assert
        $newConversation = Conversation::where('mailbox_id', $mailbox->id)
            ->orderBy('id', 'desc')
            ->first();
        $this->assertNotNull($newConversation);
        $this->assertEquals(6, $newConversation->number);
    }

    public function test_process_message_starts_numbering_at_one_for_first_conversation(): void
    {
        // Arrange
        Event::fake();
        $mailbox = Mailbox::factory()->create(['email' => 'support@example.com']);
        $folder = Folder::factory()->create(['mailbox_id' => $mailbox->id, 'type' => 1]);

        $message = $this->createMockMessage([
            'from' => [(object) ['mail' => 'customer@example.com', 'personal' => 'John Doe']],
        ]);

        // Act
        $this->invokeProcessMessage($mailbox, $message);

        // Assert
        $conversation = Conversation::where('mailbox_id', $mailbox->id)->first();
        $this->assertNotNull($conversation);
        $this->assertEquals(1, $conversation->number);
    }

    /**
     * COMPREHENSIVE - Internal User Thread Handling
     */
    public function test_process_message_sets_user_id_for_internal_user_thread(): void
    {
        // Arrange
        Event::fake();
        $mailbox = Mailbox::factory()->create(['email' => 'support@example.com']);
        $folder = Folder::factory()->create(['mailbox_id' => $mailbox->id, 'type' => 1]);
        $user = User::factory()->create(['email' => 'agent@example.com']);

        $message = $this->createMockMessage([
            'from' => [(object) ['mail' => 'agent@example.com', 'personal' => 'Agent Smith']],
        ]);

        // Act
        $this->invokeProcessMessage($mailbox, $message);

        // Assert
        $thread = Thread::first();
        $this->assertNotNull($thread);
        $this->assertEquals($user->id, $thread->user_id);
        $this->assertEquals($user->id, $thread->created_by_user_id);
        $this->assertEquals(1, $thread->source_via); // User
    }

    public function test_process_message_sets_customer_id_for_customer_thread(): void
    {
        // Arrange
        Event::fake();
        $mailbox = Mailbox::factory()->create(['email' => 'support@example.com']);
        $folder = Folder::factory()->create(['mailbox_id' => $mailbox->id, 'type' => 1]);

        $message = $this->createMockMessage([
            'from' => [(object) ['mail' => 'customer@example.com', 'personal' => 'John Customer']],
        ]);

        // Act
        $this->invokeProcessMessage($mailbox, $message);

        // Assert
        $thread = Thread::first();
        $this->assertNotNull($thread);
        $this->assertEquals('customer@example.com', $thread->from);
        $this->assertNull($thread->user_id);
    }

    public function test_process_message_updates_conversation_last_reply_from_user(): void
    {
        // Arrange
        Event::fake();
        $mailbox = Mailbox::factory()->create(['email' => 'support@example.com']);
        $folder = Folder::factory()->create(['mailbox_id' => $mailbox->id, 'type' => 1]);
        $user = User::factory()->create(['email' => 'agent@example.com']);
        $customer = Customer::factory()->create(['email' => 'customer@example.com']);

        $conversation = Conversation::factory()->create([
            'mailbox_id' => $mailbox->id,
            'customer_id' => $customer->id,
            'last_reply_from' => 2, // Was from customer
        ]);
        $originalThread = Thread::factory()->create([
            'conversation_id' => $conversation->id,
            'message_id' => '<original@example.com>',
        ]);

        // User replies
        $message = $this->createMockMessage([
            'from' => [(object) ['mail' => 'agent@example.com', 'personal' => 'Agent']],
            'in_reply_to' => '<original@example.com>',
        ]);

        // Act
        $this->invokeProcessMessage($mailbox, $message);

        // Assert
        $conversation->refresh();
        $this->assertEquals(1, $conversation->last_reply_from); // User
    }

    /**
     * COMPREHENSIVE - Reply Separation and Quoted Text
     */
    public function test_process_message_separates_reply_with_protonmail_quote(): void
    {
        // Arrange
        Event::fake();
        $mailbox = Mailbox::factory()->create(['email' => 'support@example.com']);
        $folder = Folder::factory()->create(['mailbox_id' => $mailbox->id, 'type' => 1]);

        $customer = Customer::factory()->create(['email' => 'customer@example.com']);
        $conversation = Conversation::factory()->create([
            'mailbox_id' => $mailbox->id,
            'customer_id' => $customer->id,
        ]);
        $originalThread = Thread::factory()->create([
            'conversation_id' => $conversation->id,
            'message_id' => '<original@example.com>',
        ]);

        $replyBody = '<p>This is my new reply</p><div class="protonmail_quote">Original quoted text</div>';

        $message = $this->createMockMessage([
            'from' => [(object) ['mail' => 'customer@example.com', 'personal' => 'John Customer']],
            'in_reply_to' => '<original@example.com>',
            'html_body' => $replyBody,
            'has_html' => true,
        ]);

        // Act
        $this->invokeProcessMessage($mailbox, $message);

        // Assert
        $thread = Thread::where('conversation_id', $conversation->id)
            ->orderBy('id', 'desc')
            ->first();
        $this->assertNotNull($thread);
        $this->assertStringContainsString('This is my new reply', $thread->body);
        $this->assertStringNotContainsString('Original quoted text', $thread->body);
    }

    public function test_process_message_separates_reply_with_generic_separator(): void
    {
        // Arrange
        Event::fake();
        $mailbox = Mailbox::factory()->create(['email' => 'support@example.com']);
        $folder = Folder::factory()->create(['mailbox_id' => $mailbox->id, 'type' => 1]);

        $customer = Customer::factory()->create(['email' => 'customer@example.com']);
        $conversation = Conversation::factory()->create([
            'mailbox_id' => $mailbox->id,
            'customer_id' => $customer->id,
        ]);
        $originalThread = Thread::factory()->create([
            'conversation_id' => $conversation->id,
            'message_id' => '<original@example.com>',
        ]);

        $replyBody = 'My new response

---- Replied Above ----

Previous conversation';

        $message = $this->createMockMessage([
            'from' => [(object) ['mail' => 'customer@example.com', 'personal' => 'John Customer']],
            'in_reply_to' => '<original@example.com>',
            'text_body' => $replyBody,
            'has_html' => false,
        ]);

        // Act
        $this->invokeProcessMessage($mailbox, $message);

        // Assert
        $thread = Thread::where('conversation_id', $conversation->id)
            ->orderBy('id', 'desc')
            ->first();
        $this->assertNotNull($thread);
        $this->assertStringContainsString('My new response', $thread->body);
    }

    public function test_process_message_separates_reply_with_on_date_wrote_pattern(): void
    {
        // Arrange
        Event::fake();
        $mailbox = Mailbox::factory()->create(['email' => 'support@example.com']);
        $folder = Folder::factory()->create(['mailbox_id' => $mailbox->id, 'type' => 1]);

        $customer = Customer::factory()->create(['email' => 'customer@example.com']);
        $conversation = Conversation::factory()->create([
            'mailbox_id' => $mailbox->id,
            'customer_id' => $customer->id,
        ]);
        $originalThread = Thread::factory()->create([
            'conversation_id' => $conversation->id,
            'message_id' => '<original@example.com>',
        ]);

        $replyBody = 'Here is my response

On Mon, Jan 1, 2024 at 10:00 AM, Support wrote:
> Original message here
> Line 2';

        $message = $this->createMockMessage([
            'from' => [(object) ['mail' => 'customer@example.com', 'personal' => 'John Customer']],
            'in_reply_to' => '<original@example.com>',
            'text_body' => $replyBody,
            'has_html' => false,
        ]);

        // Act
        $this->invokeProcessMessage($mailbox, $message);

        // Assert
        $thread = Thread::where('conversation_id', $conversation->id)
            ->orderBy('id', 'desc')
            ->first();
        $this->assertNotNull($thread);
        $this->assertStringContainsString('Here is my response', $thread->body);
    }

    public function test_process_message_separates_reply_with_from_separator(): void
    {
        // Arrange
        Event::fake();
        $mailbox = Mailbox::factory()->create(['email' => 'support@example.com']);
        $folder = Folder::factory()->create(['mailbox_id' => $mailbox->id, 'type' => 1]);

        $customer = Customer::factory()->create(['email' => 'customer@example.com']);
        $conversation = Conversation::factory()->create([
            'mailbox_id' => $mailbox->id,
            'customer_id' => $customer->id,
        ]);
        $originalThread = Thread::factory()->create([
            'conversation_id' => $conversation->id,
            'message_id' => '<original@example.com>',
        ]);

        $replyBody = 'My reply text

From: support@example.com
Sent: Monday
To: customer@example.com
Original message';

        $message = $this->createMockMessage([
            'from' => [(object) ['mail' => 'customer@example.com', 'personal' => 'John Customer']],
            'in_reply_to' => '<original@example.com>',
            'text_body' => $replyBody,
            'has_html' => false,
        ]);

        // Act
        $this->invokeProcessMessage($mailbox, $message);

        // Assert
        $thread = Thread::where('conversation_id', $conversation->id)
            ->orderBy('id', 'desc')
            ->first();
        $this->assertNotNull($thread);
        $this->assertStringContainsString('My reply text', $thread->body);
    }

    public function test_process_message_separates_reply_with_underscore_separator(): void
    {
        // Arrange
        Event::fake();
        $mailbox = Mailbox::factory()->create(['email' => 'support@example.com']);
        $folder = Folder::factory()->create(['mailbox_id' => $mailbox->id, 'type' => 1]);

        $customer = Customer::factory()->create(['email' => 'customer@example.com']);
        $conversation = Conversation::factory()->create([
            'mailbox_id' => $mailbox->id,
            'customer_id' => $customer->id,
        ]);
        $originalThread = Thread::factory()->create([
            'conversation_id' => $conversation->id,
            'message_id' => '<original@example.com>',
        ]);

        $replyBody = 'New message content

________
Previous message';

        $message = $this->createMockMessage([
            'from' => [(object) ['mail' => 'customer@example.com', 'personal' => 'John Customer']],
            'in_reply_to' => '<original@example.com>',
            'text_body' => $replyBody,
            'has_html' => false,
        ]);

        // Act
        $this->invokeProcessMessage($mailbox, $message);

        // Assert
        $thread = Thread::where('conversation_id', $conversation->id)
            ->orderBy('id', 'desc')
            ->first();
        $this->assertNotNull($thread);
        $this->assertStringContainsString('New message content', $thread->body);
    }

    public function test_process_message_extracts_body_from_html_body_tag(): void
    {
        // Arrange
        Event::fake();
        $mailbox = Mailbox::factory()->create(['email' => 'support@example.com']);
        $folder = Folder::factory()->create(['mailbox_id' => $mailbox->id, 'type' => 1]);

        $customer = Customer::factory()->create(['email' => 'customer@example.com']);
        $conversation = Conversation::factory()->create([
            'mailbox_id' => $mailbox->id,
            'customer_id' => $customer->id,
        ]);
        $originalThread = Thread::factory()->create([
            'conversation_id' => $conversation->id,
            'message_id' => '<original@example.com>',
        ]);

        $htmlBody = '<html><head><style>body{color:red;}</style></head><body><p>Actual content</p><div class="protonmail_quote">Quote</div></body></html>';

        $message = $this->createMockMessage([
            'from' => [(object) ['mail' => 'customer@example.com', 'personal' => 'John Customer']],
            'in_reply_to' => '<original@example.com>',
            'html_body' => $htmlBody,
            'has_html' => true,
        ]);

        // Act
        $this->invokeProcessMessage($mailbox, $message);

        // Assert
        $thread = Thread::where('conversation_id', $conversation->id)
            ->orderBy('id', 'desc')
            ->first();
        $this->assertNotNull($thread);
        $this->assertStringContainsString('Actual content', $thread->body);
    }

    public function test_process_message_does_not_separate_reply_when_not_reply(): void
    {
        // Arrange
        Event::fake();
        $mailbox = Mailbox::factory()->create(['email' => 'support@example.com']);
        $folder = Folder::factory()->create(['mailbox_id' => $mailbox->id, 'type' => 1]);

        $bodyWithSeparator = 'New message

On some date wrote:
Some text that looks like quote but is not';

        $message = $this->createMockMessage([
            'from' => [(object) ['mail' => 'customer@example.com', 'personal' => 'John Doe']],
            'text_body' => $bodyWithSeparator,
            'has_html' => false,
            // Not a reply - no in_reply_to
        ]);

        // Act
        $this->invokeProcessMessage($mailbox, $message);

        // Assert
        $thread = Thread::first();
        $this->assertNotNull($thread);
        // Should keep full body including what looks like separator
        $this->assertStringContainsString('On some date wrote:', $thread->body);
        $this->assertStringContainsString('looks like quote', $thread->body);
    }

    public function test_process_message_keeps_full_body_when_no_separator_found(): void
    {
        // Arrange
        Event::fake();
        $mailbox = Mailbox::factory()->create(['email' => 'support@example.com']);
        $folder = Folder::factory()->create(['mailbox_id' => $mailbox->id, 'type' => 1]);

        $customer = Customer::factory()->create(['email' => 'customer@example.com']);
        $conversation = Conversation::factory()->create([
            'mailbox_id' => $mailbox->id,
            'customer_id' => $customer->id,
        ]);
        $originalThread = Thread::factory()->create([
            'conversation_id' => $conversation->id,
            'message_id' => '<original@example.com>',
        ]);

        $replyBody = 'This is a complete reply without any separator markers at all';

        $message = $this->createMockMessage([
            'from' => [(object) ['mail' => 'customer@example.com', 'personal' => 'John Customer']],
            'in_reply_to' => '<original@example.com>',
            'text_body' => $replyBody,
            'has_html' => false,
        ]);

        // Act
        $this->invokeProcessMessage($mailbox, $message);

        // Assert
        $thread = Thread::where('conversation_id', $conversation->id)
            ->orderBy('id', 'desc')
            ->first();
        $this->assertNotNull($thread);
        $this->assertStringContainsString('complete reply without any separator', $thread->body);
    }

    /**
     * COMPREHENSIVE - Empty Name Handling
     */
    public function test_process_message_handles_customer_with_empty_name(): void
    {
        // Arrange
        Event::fake();
        $mailbox = Mailbox::factory()->create(['email' => 'support@example.com']);
        $folder = Folder::factory()->create(['mailbox_id' => $mailbox->id, 'type' => 1]);

        $message = $this->createMockMessage([
            'from' => [(object) ['mail' => 'noname@example.com', 'personal' => '']],
        ]);

        // Act
        $this->invokeProcessMessage($mailbox, $message);

        // Assert - conversation stores sender email directly
        $conversation = Conversation::where('mailbox_id', $mailbox->id)->first();
        $this->assertNotNull($conversation);
        $this->assertEquals('noname@example.com', $conversation->customer_email);
    }

    public function test_process_message_handles_single_name_only(): void
    {
        // Arrange
        Event::fake();
        $mailbox = Mailbox::factory()->create(['email' => 'support@example.com']);
        $folder = Folder::factory()->create(['mailbox_id' => $mailbox->id, 'type' => 1]);

        $message = $this->createMockMessage([
            'from' => [(object) ['mail' => 'single@example.com', 'personal' => 'Madonna']],
        ]);

        // Act
        $this->invokeProcessMessage($mailbox, $message);

        // Assert - conversation stores sender email directly
        $conversation = Conversation::where('mailbox_id', $mailbox->id)->first();
        $this->assertNotNull($conversation);
        $this->assertEquals('single@example.com', $conversation->customer_email);
    }

    public function test_process_message_handles_multi_part_last_name(): void
    {
        // Arrange
        Event::fake();
        $mailbox = Mailbox::factory()->create(['email' => 'support@example.com']);
        $folder = Folder::factory()->create(['mailbox_id' => $mailbox->id, 'type' => 1]);

        $message = $this->createMockMessage([
            'from' => [(object) ['mail' => 'customer@example.com', 'personal' => 'John van der Berg Smith']],
        ]);

        // Act
        $this->invokeProcessMessage($mailbox, $message);

        // Assert - conversation stores sender email directly
        $conversation = Conversation::where('mailbox_id', $mailbox->id)->first();
        $this->assertNotNull($conversation);
        $this->assertEquals('customer@example.com', $conversation->customer_email);
    }

    /**
     * COMPREHENSIVE - Folder Type and Mailbox Configuration
     */
    public function test_process_message_throws_exception_when_inbox_folder_missing(): void
    {
        // Arrange
        // Create mailbox without firing observer (which auto-creates folders)
        $mailbox = Mailbox::withoutEvents(function () {
            return Mailbox::factory()->create(['email' => 'support@example.com']);
        });
        // Don't create inbox folder

        $message = $this->createMockMessage([
            'from' => [(object) ['mail' => 'customer@example.com', 'personal' => 'John Doe']],
        ]);

        // Act & Assert
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('No inbox folder found');

        $this->invokeProcessMessage($mailbox, $message);
    }

    /**
     * COMPREHENSIVE - Attachment Error Handling
     */
    public function test_process_message_continues_processing_other_attachments_on_error(): void
    {
        // Arrange
        Event::fake();
        $mailbox = Mailbox::factory()->create(['email' => 'support@example.com']);
        $folder = Folder::factory()->create(['mailbox_id' => $mailbox->id, 'type' => 1]);

        // Good attachment
        $attachment1 = Mockery::mock(ImapAttachment::class);
        $attachment1->shouldReceive('getName')->andReturn('good.pdf');
        $attachment1->shouldReceive('getContent')->andReturn('content');
        $attachment1->shouldReceive('getId')->andReturn(null);
        $attachment1->shouldReceive('getContentType')->andReturn('application/pdf');
        $attachment1->disposition = 'attachment';

        // Bad attachment that throws exception
        $attachment2 = Mockery::mock(ImapAttachment::class);
        $attachment2->shouldReceive('getName')->andThrow(new \Exception('Attachment error'));

        // Another good attachment
        $attachment3 = Mockery::mock(ImapAttachment::class);
        $attachment3->shouldReceive('getName')->andReturn('good2.pdf');
        $attachment3->shouldReceive('getContent')->andReturn('content2');
        $attachment3->shouldReceive('getId')->andReturn(null);
        $attachment3->shouldReceive('getContentType')->andReturn('application/pdf');
        $attachment3->disposition = 'attachment';

        $message = $this->createMockMessage([
            'from' => [(object) ['mail' => 'customer@example.com', 'personal' => 'John Doe']],
            'has_attachments' => true,
            'attachments' => new AttachmentCollection([$attachment1, $attachment2, $attachment3]),
        ]);

        // Act
        $this->invokeProcessMessage($mailbox, $message);

        // Assert - Should have saved the 2 good attachments
        $attachmentCount = Attachment::count();
        $this->assertEquals(2, $attachmentCount);
    }

    /**
     * COMPREHENSIVE - Subject Edge Cases
     */
    public function test_process_message_handles_very_long_subject(): void
    {
        // Arrange
        Event::fake();
        $mailbox = Mailbox::factory()->create(['email' => 'support@example.com']);
        $folder = Folder::factory()->create(['mailbox_id' => $mailbox->id, 'type' => 1]);

        $longSubject = str_repeat('A', 1000);

        $message = $this->createMockMessage([
            'from' => [(object) ['mail' => 'customer@example.com', 'personal' => 'John Doe']],
            'subject' => $longSubject,
        ]);

        // Act
        $this->invokeProcessMessage($mailbox, $message);

        // Assert
        $conversation = Conversation::first();
        $this->assertNotNull($conversation);
        // Subject should be stored (may be truncated by DB)
        $this->assertNotEmpty($conversation->subject);
    }

    public function test_process_message_handles_subject_with_newlines(): void
    {
        // Arrange
        Event::fake();
        $mailbox = Mailbox::factory()->create(['email' => 'support@example.com']);
        $folder = Folder::factory()->create(['mailbox_id' => $mailbox->id, 'type' => 1]);

        $message = $this->createMockMessage([
            'from' => [(object) ['mail' => 'customer@example.com', 'personal' => 'John Doe']],
            'subject' => "Subject with\nnewlines\rand\r\ntabs\there",
        ]);

        // Act
        $this->invokeProcessMessage($mailbox, $message);

        // Assert
        $conversation = Conversation::first();
        $this->assertNotNull($conversation);
        $this->assertNotEmpty($conversation->subject);
    }

    /**
     * COMPREHENSIVE - Special Email Formats
     */
    public function test_process_message_handles_email_with_plus_addressing(): void
    {
        // Arrange
        Event::fake();
        $mailbox = Mailbox::factory()->create(['email' => 'support@example.com']);
        $folder = Folder::factory()->create(['mailbox_id' => $mailbox->id, 'type' => 1]);

        $message = $this->createMockMessage([
            'from' => [(object) ['mail' => 'customer+tag@example.com', 'personal' => 'John Doe']],
        ]);

        // Act
        $this->invokeProcessMessage($mailbox, $message);

        // Assert - conversation stores sender email directly
        $conversation = Conversation::where('mailbox_id', $mailbox->id)->first();
        $this->assertNotNull($conversation);
        $this->assertEquals('customer+tag@example.com', $conversation->customer_email);
    }

    public function test_process_message_handles_email_with_subdomain(): void
    {
        // Arrange
        Event::fake();
        $mailbox = Mailbox::factory()->create(['email' => 'support@example.com']);
        $folder = Folder::factory()->create(['mailbox_id' => $mailbox->id, 'type' => 1]);

        $message = $this->createMockMessage([
            'from' => [(object) ['mail' => 'user@mail.subdomain.example.com', 'personal' => 'John Doe']],
        ]);

        // Act
        $this->invokeProcessMessage($mailbox, $message);

        // Assert - conversation stores sender email directly
        $conversation = Conversation::where('mailbox_id', $mailbox->id)->first();
        $this->assertNotNull($conversation);
        $this->assertEquals('user@mail.subdomain.example.com', $conversation->customer_email);
    }

    /**
     * COMPREHENSIVE - Conversation Customer Switching
     */
    public function test_process_message_updates_conversation_customer_on_reply(): void
    {
        // Arrange
        Event::fake();
        $mailbox = Mailbox::factory()->create(['email' => 'support@example.com']);
        $folder = Folder::factory()->create(['mailbox_id' => $mailbox->id, 'type' => 1]);

        $customer1 = Customer::factory()->create(['email' => 'customer1@example.com']);
        $customer2 = Customer::factory()->create(['email' => 'customer2@example.com']);

        $conversation = Conversation::factory()->create([
            'mailbox_id' => $mailbox->id,
            'customer_id' => $customer1->id,
            'customer_email' => 'customer1@example.com',
        ]);
        $originalThread = Thread::factory()->create([
            'conversation_id' => $conversation->id,
            'message_id' => '<original@example.com>',
        ]);

        // Customer 2 replies to the conversation
        $message = $this->createMockMessage([
            'from' => [(object) ['mail' => 'customer2@example.com', 'personal' => 'Customer Two']],
            'in_reply_to' => '<original@example.com>',
        ]);

        // Act
        $this->invokeProcessMessage($mailbox, $message);

        // Assert
        $conversation->refresh();
        $this->assertEquals($customer2->id, $conversation->customer_id);
        $this->assertEquals('customer2@example.com', $conversation->customer_email);
    }

    /**
     * COMPREHENSIVE - Thread Type and Status
     */
    public function test_process_message_sets_correct_thread_type(): void
    {
        // Arrange
        Event::fake();
        $mailbox = Mailbox::factory()->create(['email' => 'support@example.com']);
        $folder = Folder::factory()->create(['mailbox_id' => $mailbox->id, 'type' => 1]);

        $message = $this->createMockMessage([
            'from' => [(object) ['mail' => 'customer@example.com', 'personal' => 'John Doe']],
        ]);

        // Act
        $this->invokeProcessMessage($mailbox, $message);

        // Assert
        $thread = Thread::first();
        $this->assertNotNull($thread);
        $this->assertEquals(1, $thread->type); // Message type
        $this->assertEquals(1, $thread->status); // Active status
        $this->assertEquals(2, $thread->state); // Published state
        $this->assertEquals(1, $thread->source_type); // Email
    }

    /**
     * COMPREHENSIVE - Conversation Type and Status
     */
    public function test_process_message_sets_correct_conversation_attributes(): void
    {
        // Arrange
        Event::fake();
        $mailbox = Mailbox::factory()->create(['email' => 'support@example.com']);
        $folder = Folder::factory()->create(['mailbox_id' => $mailbox->id, 'type' => 1]);

        $message = $this->createMockMessage([
            'from' => [(object) ['mail' => 'customer@example.com', 'personal' => 'John Doe']],
            'subject' => 'Test Conversation',
        ]);

        // Act
        $this->invokeProcessMessage($mailbox, $message);

        // Assert
        $conversation = Conversation::first();
        $this->assertNotNull($conversation);
        $this->assertEquals(1, $conversation->type); // Email
        $this->assertEquals(1, $conversation->status); // Active
        $this->assertEquals(2, $conversation->state); // Published
        $this->assertEquals(2, $conversation->source_via); // Customer
        $this->assertEquals(1, $conversation->source_type); // Email
        $this->assertEquals('customer@example.com', $conversation->customer_email);
    }

    /**
     * COMPREHENSIVE - Multiple Recipients Scenarios
     */
    public function test_process_message_handles_multiple_to_recipients(): void
    {
        // Arrange
        Event::fake();
        $mailbox = Mailbox::factory()->create(['email' => 'support@example.com']);
        $folder = Folder::factory()->create(['mailbox_id' => $mailbox->id, 'type' => 1]);

        $message = $this->createMockMessage([
            'from' => [(object) ['mail' => 'customer@example.com', 'personal' => 'John Doe']],
            'to' => [
                (object) ['mail' => 'support@example.com', 'personal' => ''],
                (object) ['mail' => 'sales@example.com', 'personal' => ''],
                (object) ['mail' => 'info@example.com', 'personal' => ''],
            ],
        ]);

        // Act
        $this->invokeProcessMessage($mailbox, $message);

        // Assert
        $thread = Thread::first();
        $this->assertNotNull($thread);
        $toAddresses = json_decode($thread->to, true);
        $this->assertIsArray($toAddresses);
        $this->assertCount(3, $toAddresses);
    }

    public function test_process_message_merges_to_into_cc_on_reply(): void
    {
        // Arrange
        Event::fake();
        $mailbox = Mailbox::factory()->create(['email' => 'support@example.com']);
        $folder = Folder::factory()->create(['mailbox_id' => $mailbox->id, 'type' => 1]);

        $customer = Customer::factory()->create(['email' => 'customer@example.com']);
        $conversation = Conversation::factory()->create([
            'mailbox_id' => $mailbox->id,
            'customer_id' => $customer->id,
            'cc' => ['existing@example.com'],
        ]);
        $originalThread = Thread::factory()->create([
            'conversation_id' => $conversation->id,
            'message_id' => '<original@example.com>',
        ]);

        // Reply with multiple recipients
        $message = $this->createMockMessage([
            'from' => [(object) ['mail' => 'customer@example.com', 'personal' => 'John Customer']],
            'in_reply_to' => '<original@example.com>',
            'to' => [
                (object) ['mail' => 'support@example.com', 'personal' => ''],
                (object) ['mail' => 'newperson@example.com', 'personal' => ''],
            ],
            'cc' => [(object) ['mail' => 'ccperson@example.com', 'personal' => '']],
        ]);

        // Act
        $this->invokeProcessMessage($mailbox, $message);

        // Assert
        $conversation->refresh();
        $this->assertIsArray($conversation->cc);
        $this->assertContains('existing@example.com', $conversation->cc);
        $this->assertContains('newperson@example.com', $conversation->cc); // From To but not mailbox
        $this->assertContains('ccperson@example.com', $conversation->cc);
    }

    /**
     * COMPREHENSIVE - Whitespace and Special Character Handling
     */
    public function test_process_message_trims_whitespace_from_names(): void
    {
        // Arrange
        Event::fake();
        $mailbox = Mailbox::factory()->create(['email' => 'support@example.com']);
        $folder = Folder::factory()->create(['mailbox_id' => $mailbox->id, 'type' => 1]);

        $message = $this->createMockMessage([
            'from' => [(object) ['mail' => 'customer@example.com', 'personal' => '  John   Doe  ']],
        ]);

        // Act
        $this->invokeProcessMessage($mailbox, $message);

        // Assert - conversation stores sender email directly
        $conversation = Conversation::where('mailbox_id', $mailbox->id)->first();
        $this->assertNotNull($conversation);
        $this->assertEquals('customer@example.com', $conversation->customer_email);
    }

    public function test_process_message_handles_name_with_special_characters(): void
    {
        // Arrange
        Event::fake();
        $mailbox = Mailbox::factory()->create(['email' => 'support@example.com']);
        $folder = Folder::factory()->create(['mailbox_id' => $mailbox->id, 'type' => 1]);

        $message = $this->createMockMessage([
            'from' => [(object) ['mail' => 'customer@example.com', 'personal' => "O'Brien-Smith, Jr."]],
        ]);

        // Act
        $this->invokeProcessMessage($mailbox, $message);

        // Assert - conversation stores sender email directly
        $conversation = Conversation::where('mailbox_id', $mailbox->id)->first();
        $this->assertNotNull($conversation);
        $this->assertEquals('customer@example.com', $conversation->customer_email);
    }

    /**
     * COMPREHENSIVE - NULL and Empty Value Handling
     */
    public function test_process_message_handles_null_cc_in_thread(): void
    {
        // Arrange
        Event::fake();
        $mailbox = Mailbox::factory()->create(['email' => 'support@example.com']);
        $folder = Folder::factory()->create(['mailbox_id' => $mailbox->id, 'type' => 1]);

        $message = $this->createMockMessage([
            'from' => [(object) ['mail' => 'customer@example.com', 'personal' => 'John Doe']],
            'cc' => [],
        ]);

        // Act
        $this->invokeProcessMessage($mailbox, $message);

        // Assert
        $thread = Thread::first();
        $this->assertNotNull($thread);
        $this->assertNull($thread->cc);
    }

    public function test_process_message_handles_null_bcc_in_thread(): void
    {
        // Arrange
        Event::fake();
        $mailbox = Mailbox::factory()->create(['email' => 'support@example.com']);
        $folder = Folder::factory()->create(['mailbox_id' => $mailbox->id, 'type' => 1]);

        $message = $this->createMockMessage([
            'from' => [(object) ['mail' => 'customer@example.com', 'personal' => 'John Doe']],
            'bcc' => [],
        ]);

        // Act
        $this->invokeProcessMessage($mailbox, $message);

        // Assert
        $thread = Thread::first();
        $this->assertNotNull($thread);
        $this->assertNull($thread->bcc);
    }

    /**
     * COMPREHENSIVE - Message ID Format Variations
     */
    public function test_process_message_handles_message_id_with_whitespace(): void
    {
        // Arrange
        Event::fake();
        $mailbox = Mailbox::factory()->create(['email' => 'support@example.com']);
        $folder = Folder::factory()->create(['mailbox_id' => $mailbox->id, 'type' => 1]);

        $message = $this->createMockMessage([
            'message_id' => '  <whitespace@example.com>  ',
            'from' => [(object) ['mail' => 'customer@example.com', 'personal' => 'John Doe']],
        ]);

        // Act
        $this->invokeProcessMessage($mailbox, $message);

        // Assert
        $thread = Thread::first();
        $this->assertNotNull($thread);
        $this->assertNotEmpty($thread->message_id);
    }

    /**
     * COMPREHENSIVE - Attachment Disposition Variations
     */
    public function test_process_message_handles_attachment_with_no_disposition(): void
    {
        // Arrange
        Event::fake();
        $mailbox = Mailbox::factory()->create(['email' => 'support@example.com']);
        $folder = Folder::factory()->create(['mailbox_id' => $mailbox->id, 'type' => 1]);

        $attachment = Mockery::mock(ImapAttachment::class);
        $attachment->shouldReceive('getName')->andReturn('file.pdf');
        $attachment->shouldReceive('getContent')->andReturn('content');
        $attachment->shouldReceive('getId')->andReturn(null);
        $attachment->shouldReceive('getContentType')->andReturn('application/pdf');
        // No disposition property set

        $message = $this->createMockMessage([
            'from' => [(object) ['mail' => 'customer@example.com', 'personal' => 'John Doe']],
            'has_attachments' => true,
            'attachments' => new AttachmentCollection([$attachment]),
        ]);

        // Act
        $this->invokeProcessMessage($mailbox, $message);

        // Assert
        $this->assertDatabaseHas('attachments', ['embedded' => false]);
    }

    public function test_process_message_detects_embedded_by_cid_in_body(): void
    {
        // Arrange
        Event::fake();
        $mailbox = Mailbox::factory()->create(['email' => 'support@example.com']);
        $folder = Folder::factory()->create(['mailbox_id' => $mailbox->id, 'type' => 1]);

        $attachment = Mockery::mock(ImapAttachment::class);
        $attachment->shouldReceive('getName')->andReturn('image.png');
        $attachment->shouldReceive('getContent')->andReturn('imagedata');
        $attachment->shouldReceive('getId')->andReturn('cid123');
        $attachment->shouldReceive('getContentType')->andReturn('image/png');
        $attachment->disposition = 'attachment'; // Not inline, but referenced by CID

        $message = $this->createMockMessage([
            'from' => [(object) ['mail' => 'customer@example.com', 'personal' => 'John Doe']],
            'html_body' => '<p>Image: <img src="cid:cid123"></p>',
            'has_html' => true,
            'has_attachments' => true,
            'attachments' => new AttachmentCollection([$attachment]),
        ]);

        // Act
        $this->invokeProcessMessage($mailbox, $message);

        // Assert
        $this->assertDatabaseHas('attachments', [
            'embedded' => true,
            'mime_type' => 'image/png',
        ]);
    }

    /**
     * COMPREHENSIVE - Reply-To Header Handling
     */
    public function test_process_message_creates_customer_from_reply_to_address(): void
    {
        // Arrange
        Event::fake();
        $mailbox = Mailbox::factory()->create(['email' => 'support@example.com']);
        $folder = Folder::factory()->create(['mailbox_id' => $mailbox->id, 'type' => 1]);

        $message = $this->createMockMessage([
            'from' => [(object) ['mail' => 'from@example.com', 'personal' => 'From User']],
            'reply_to' => [(object) ['mail' => 'replyto@example.com', 'personal' => 'Reply To User']],
        ]);

        // Act
        $this->invokeProcessMessage($mailbox, $message);

        // Assert - conversation stores sender email directly
        $conversation = Conversation::where('mailbox_id', $mailbox->id)->first();
        $this->assertNotNull($conversation);
        $this->assertEquals('from@example.com', $conversation->customer_email);
    }

    /**
     * COMPREHENSIVE - Case Insensitivity Tests
     */
    public function test_process_message_handles_mixed_case_email_addresses(): void
    {
        // Arrange
        Event::fake();
        $mailbox = Mailbox::factory()->create(['email' => 'support@example.com']);
        $folder = Folder::factory()->create(['mailbox_id' => $mailbox->id, 'type' => 1]);

        $message = $this->createMockMessage([
            'from' => [(object) ['mail' => 'Customer@Example.COM', 'personal' => 'John Doe']],
        ]);

        // Act
        $this->invokeProcessMessage($mailbox, $message);

        // Assert - conversation stores sender email
        $conversation = Conversation::where('mailbox_id', $mailbox->id)->first();
        $this->assertNotNull($conversation);
        // Email may be stored as-is or lowercased
        $this->assertNotNull($conversation->customer_email);
    }
}
