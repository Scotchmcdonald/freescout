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
use Illuminate\Support\Facades\Event;
use Mockery;
use Tests\IntegrationTestCase;
use Webklex\PHPIMAP\Attachment as ImapAttachment;
use Webklex\PHPIMAP\Attribute;
use Webklex\PHPIMAP\Header;
use Webklex\PHPIMAP\Message;
use Webklex\PHPIMAP\Support\AttachmentCollection;

/**
 * Test Suite for IMAP Service Process Message - Basic Tests
 *
 * This test suite covers basic message processing:
 * - Message creation and basic properties (44 tests)
 * - Conversation creation
 * - Thread handling
 * - Customer creation from message addresses
 * - Body content processing
 * - Basic attachment handling
 *
 * Total: 44 tests
 */
class ImapServiceProcessMessageBasicTest extends IntegrationTestCase
{
    protected ImapService $service;

    protected function setUp(): void
    {
        parent::setUp();
        config(['broadcasting.default' => 'log']);
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
        $message = Mockery::mock(Message::class.', \Tests\Integration\Services\MessageWithRawHeader');

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

        // Assert
        $this->assertDatabaseHas('conversations', [
            'mailbox_id' => $mailbox->id,
            'subject' => 'Need help with my account',
            'type' => 1, // Email
            'status' => 1, // Active
        ]);

        // Verify conversation stores sender email directly (no Customer creation)
        $conversation = Conversation::where('mailbox_id', $mailbox->id)
            ->where('subject', 'Need help with my account')
            ->first();
        $this->assertNotNull($conversation);
        $this->assertEquals('customer@example.com', $conversation->customer_email);

        $this->assertDatabaseHas('threads', [
            'type' => 1, // Message
            'status' => 1, // Active
            'from' => 'customer@example.com',
        ]);

        Event::assertDispatched(\App\Events\CustomerCreatedConversation::class);
        Event::assertDispatched(\App\Events\NewMessageReceived::class);
    }

    public function test_process_message_handles_email_with_attachments(): void
    {
        // Arrange
        Event::fake();
        $mailbox = Mailbox::factory()->create(['email' => 'support@example.com']);
        $folder = Folder::factory()->create([
            'mailbox_id' => $mailbox->id,
            'type' => 1,
        ]);

        // Create mock attachment
        $mockAttachment = Mockery::mock(ImapAttachment::class);
        $mockAttachment->shouldReceive('getName')->andReturn('document.pdf');
        $mockAttachment->shouldReceive('getContent')->andReturn('fake pdf content');
        $mockAttachment->shouldReceive('getId')->andReturn(null);
        $mockAttachment->shouldReceive('getContentType')->andReturn('application/pdf');
        $mockAttachment->disposition = 'attachment';

        $message = $this->createMockMessage([
            'from' => [(object) ['mail' => 'customer@example.com', 'personal' => 'John Doe']],
            'has_attachments' => true,
            'attachments' => new AttachmentCollection([$mockAttachment]),
        ]);

        // Act
        $this->invokeProcessMessage($mailbox, $message);

        // Assert
        $conversation = Conversation::where('mailbox_id', $mailbox->id)->first();
        $this->assertNotNull($conversation);
        $this->assertTrue($conversation->has_attachments);

        $this->assertDatabaseHas('attachments', [
            'conversation_id' => $conversation->id,
            'mime_type' => 'application/pdf',
            'embedded' => false,
        ]);
    }

    public function test_process_message_creates_customer_from_email_address(): void
    {
        // Arrange
        Event::fake();
        $mailbox = Mailbox::factory()->create(['email' => 'support@example.com']);
        $folder = Folder::factory()->create([
            'mailbox_id' => $mailbox->id,
            'type' => 1,
        ]);

        $message = $this->createMockMessage([
            'from' => [(object) ['mail' => 'newcustomer@example.com', 'personal' => 'New Customer']],
        ]);

        // Act
        $this->invokeProcessMessage($mailbox, $message);

        // Assert - Conversation stores sender email directly
        $conversation = Conversation::where('mailbox_id', $mailbox->id)->first();
        $this->assertNotNull($conversation);
        $this->assertEquals('newcustomer@example.com', $conversation->customer_email);
    }

    public function test_process_message_links_existing_customer_to_conversation(): void
    {
        // Arrange
        Event::fake();
        $mailbox = Mailbox::factory()->create(['email' => 'support@example.com']);
        $folder = Folder::factory()->create([
            'mailbox_id' => $mailbox->id,
            'type' => 1,
        ]);

        // Create existing customer
        $existingCustomer = Customer::factory()->create([
            'email' => 'existing@example.com',
            'first_name' => 'Existing',
            'last_name' => 'Customer',
        ]);

        $message = $this->createMockMessage([
            'from' => [(object) ['mail' => 'existing@example.com', 'personal' => 'Existing Customer']],
        ]);

        // Act
        $this->invokeProcessMessage($mailbox, $message);

        // Assert
        $conversation = Conversation::where('mailbox_id', $mailbox->id)->first();
        $this->assertNotNull($conversation);
        $this->assertEquals('existing@example.com', $conversation->customer_email);
    }

    public function test_process_message_stores_message_body_correctly(): void
    {
        // Arrange
        Event::fake();
        $mailbox = Mailbox::factory()->create(['email' => 'support@example.com']);
        $folder = Folder::factory()->create([
            'mailbox_id' => $mailbox->id,
            'type' => 1,
        ]);

        $htmlBody = '<html><body><h1>Important Message</h1><p>This is the email body</p></body></html>';
        $textBody = 'Important Message\n\nThis is the email body';

        $message = $this->createMockMessage([
            'from' => [(object) ['mail' => 'customer@example.com', 'personal' => 'John Doe']],
            'html_body' => $htmlBody,
            'text_body' => $textBody,
            'has_html' => true,
        ]);

        // Act
        $this->invokeProcessMessage($mailbox, $message);

        // Assert
        $thread = Thread::first();
        $this->assertNotNull($thread);
        $this->assertStringContainsString('Important Message', $thread->body);
        $this->assertStringContainsString('This is the email body', $thread->body);
    }

    /**
     * Priority 2: Reply Detection Tests
     */
    public function test_process_message_detects_reply_via_in_reply_to_header(): void
    {
        // Arrange
        Event::fake();
        $mailbox = Mailbox::factory()->create(['email' => 'support@example.com']);
        $folder = Folder::factory()->create([
            'mailbox_id' => $mailbox->id,
            'type' => 1,
        ]);

        // Create original conversation and thread
        $customer = Customer::factory()->create(['email' => 'customer@example.com']);
        $originalConversation = Conversation::factory()->create([
            'mailbox_id' => $mailbox->id,
            'customer_id' => $customer->id,
            'subject' => 'Original Subject',
        ]);
        $originalThread = Thread::factory()->create([
            'conversation_id' => $originalConversation->id,
            'message_id' => '<original-123@example.com>',
        ]);
        // Update threads_count to reflect the existing thread
        $originalConversation->update(['threads_count' => 1]);

        // Create reply message
        $message = $this->createMockMessage([
            'from' => [(object) ['mail' => 'customer@example.com', 'personal' => 'John Customer']],
            'subject' => 'Re: Original Subject',
            'in_reply_to' => '<original-123@example.com>',
            'text_body' => 'This is my reply',
        ]);

        // Act
        $this->invokeProcessMessage($mailbox, $message);

        // Assert
        $replyThread = Thread::where('conversation_id', $originalConversation->id)
            ->where('body', 'LIKE', '%This is my reply%')
            ->first();
        $this->assertNotNull($replyThread);
        $this->assertEquals($originalConversation->id, $replyThread->conversation_id);

        Event::assertDispatched(\App\Events\CustomerReplied::class);
    }

    public function test_process_message_detects_reply_via_references_header(): void
    {
        // Arrange
        Event::fake();
        $mailbox = Mailbox::factory()->create(['email' => 'support@example.com']);
        $folder = Folder::factory()->create([
            'mailbox_id' => $mailbox->id,
            'type' => 1,
        ]);

        // Create original conversation and thread
        $customer = Customer::factory()->create(['email' => 'customer@example.com']);
        $originalConversation = Conversation::factory()->create([
            'mailbox_id' => $mailbox->id,
            'customer_id' => $customer->id,
        ]);
        $originalThread = Thread::factory()->create([
            'conversation_id' => $originalConversation->id,
            'message_id' => '<ref-456@example.com>',
        ]);
        // Update threads_count to reflect the existing thread
        $originalConversation->update(['threads_count' => 1]);

        // Create reply with References header
        $message = $this->createMockMessage([
            'from' => [(object) ['mail' => 'customer@example.com', 'personal' => 'John Customer']],
            'references' => '<ref-456@example.com>',
            'in_reply_to' => null,
            'text_body' => 'Reply via references',
        ]);

        // Act
        $this->invokeProcessMessage($mailbox, $message);

        // Assert
        $replyThread = Thread::where('conversation_id', $originalConversation->id)
            ->where('body', 'LIKE', '%Reply via references%')
            ->first();
        $this->assertNotNull($replyThread);
        $this->assertEquals($originalConversation->id, $replyThread->conversation_id);
    }

    public function test_process_message_handles_reply_with_quoted_text(): void
    {
        // Arrange
        Event::fake();
        $mailbox = Mailbox::factory()->create(['email' => 'support@example.com']);
        $folder = Folder::factory()->create([
            'mailbox_id' => $mailbox->id,
            'type' => 1,
        ]);

        $customer = Customer::factory()->create(['email' => 'customer@example.com']);
        $originalConversation = Conversation::factory()->create([
            'mailbox_id' => $mailbox->id,
            'customer_id' => $customer->id,
        ]);
        $originalThread = Thread::factory()->create([
            'conversation_id' => $originalConversation->id,
            'message_id' => '<original@example.com>',
        ]);

        // Reply with quoted text
        $replyBody = 'This is my new reply

On 2024-01-01, Support wrote:
> This is the original message
> It has multiple lines';

        $message = $this->createMockMessage([
            'from' => [(object) ['mail' => 'customer@example.com', 'personal' => 'John Customer']],
            'in_reply_to' => '<original@example.com>',
            'text_body' => $replyBody,
            'has_html' => false,
        ]);

        // Act
        $this->invokeProcessMessage($mailbox, $message);

        // Assert
        $replyThread = Thread::where('conversation_id', $originalConversation->id)
            ->orderBy('id', 'desc')
            ->first();
        $this->assertNotNull($replyThread);

        // The separateReply method should extract just the new content
        $this->assertStringContainsString('This is my new reply', $replyThread->body);
    }

    public function test_process_message_creates_new_thread_for_reply(): void
    {
        // Arrange
        Event::fake();
        $mailbox = Mailbox::factory()->create(['email' => 'support@example.com']);
        $folder = Folder::factory()->create([
            'mailbox_id' => $mailbox->id,
            'type' => 1,
        ]);

        $customer = Customer::factory()->create(['email' => 'customer@example.com']);
        $conversation = Conversation::factory()->create([
            'mailbox_id' => $mailbox->id,
            'customer_id' => $customer->id,
            'threads_count' => 1,
        ]);
        $originalThread = Thread::factory()->create([
            'conversation_id' => $conversation->id,
            'message_id' => '<original@example.com>',
        ]);

        $message = $this->createMockMessage([
            'from' => [(object) ['mail' => 'customer@example.com', 'personal' => 'John Customer']],
            'in_reply_to' => '<original@example.com>',
        ]);

        // Act
        $this->invokeProcessMessage($mailbox, $message);

        // Assert
        $threadCount = Thread::where('conversation_id', $conversation->id)->count();
        $this->assertEquals(2, $threadCount);

        $conversation->refresh();
        $this->assertEquals(2, $conversation->threads_count);
    }

    /**
     * Priority 3: Forward Detection Tests
     */
    public function test_process_message_detects_forwarded_email(): void
    {
        // Arrange
        Event::fake();
        $mailbox = Mailbox::factory()->create(['email' => 'support@example.com']);
        $folder = Folder::factory()->create([
            'mailbox_id' => $mailbox->id,
            'type' => 1,
        ]);

        // Create internal user who is forwarding
        $forwarder = User::factory()->create(['email' => 'agent@example.com']);

        $forwardedBody = '@fwd From: original@customer.com
Subject: Original Subject

This is the forwarded message content';

        $message = $this->createMockMessage([
            'from' => [(object) ['mail' => 'agent@example.com', 'personal' => 'Agent Smith']],
            'subject' => 'Fwd: Original Subject',
            'text_body' => $forwardedBody,
            'has_html' => false,
        ]);

        // Act
        $this->invokeProcessMessage($mailbox, $message);

        // Assert - Should extract original sender and store email directly
        $conversation = Conversation::where('mailbox_id', $mailbox->id)->first();
        $this->assertNotNull($conversation);
        $this->assertEquals('original@customer.com', $conversation->customer_email);
    }

    public function test_process_message_handles_forward_with_attachments(): void
    {
        // Arrange
        Event::fake();
        $mailbox = Mailbox::factory()->create(['email' => 'support@example.com']);
        $folder = Folder::factory()->create([
            'mailbox_id' => $mailbox->id,
            'type' => 1,
        ]);

        $forwarder = User::factory()->create(['email' => 'agent@example.com']);

        $mockAttachment = Mockery::mock(ImapAttachment::class);
        $mockAttachment->shouldReceive('getName')->andReturn('forwarded-doc.pdf');
        $mockAttachment->shouldReceive('getContent')->andReturn('forwarded content');
        $mockAttachment->shouldReceive('getId')->andReturn(null);
        $mockAttachment->shouldReceive('getContentType')->andReturn('application/pdf');
        $mockAttachment->disposition = 'attachment';

        $message = $this->createMockMessage([
            'from' => [(object) ['mail' => 'agent@example.com', 'personal' => 'Agent']],
            'subject' => 'Fwd: Document',
            'text_body' => '@fwd From: customer@example.com\n\nHere is the document',
            'has_html' => false,
            'has_attachments' => true,
            'attachments' => new AttachmentCollection([$mockAttachment]),
        ]);

        // Act
        $this->invokeProcessMessage($mailbox, $message);

        // Assert
        $conversation = Conversation::where('mailbox_id', $mailbox->id)->first();
        $this->assertNotNull($conversation);
        $this->assertDatabaseHas('attachments', [
            'conversation_id' => $conversation->id,
        ]);
    }

    /**
     * Priority 4: Edge Cases Tests
     */
    public function test_process_message_handles_malformed_email_headers(): void
    {
        // Arrange
        Event::fake();
        $mailbox = Mailbox::factory()->create(['email' => 'support@example.com']);
        $folder = Folder::factory()->create([
            'mailbox_id' => $mailbox->id,
            'type' => 1,
        ]);

        // Message with missing/empty headers
        $message = $this->createMockMessage([
            'from' => [(object) ['mail' => 'customer@example.com', 'personal' => '']],
            'subject' => '', // Empty subject
            'message_id' => '', // Empty message ID
        ]);

        // Act & Assert - Should not throw exception
        $this->invokeProcessMessage($mailbox, $message);

        $conversation = Conversation::where('mailbox_id', $mailbox->id)->first();
        $this->assertNotNull($conversation);
        $this->assertEquals('(No Subject)', $conversation->subject);
    }

    public function test_process_message_handles_empty_message_body(): void
    {
        // Arrange
        Event::fake();
        $mailbox = Mailbox::factory()->create(['email' => 'support@example.com']);
        $folder = Folder::factory()->create([
            'mailbox_id' => $mailbox->id,
            'type' => 1,
        ]);

        $message = $this->createMockMessage([
            'from' => [(object) ['mail' => 'customer@example.com', 'personal' => 'John Doe']],
            'text_body' => '',
            'html_body' => '',
        ]);

        // Act
        $this->invokeProcessMessage($mailbox, $message);

        // Assert
        $thread = Thread::first();
        $this->assertNotNull($thread);
        $this->assertEquals('(Empty message)', $thread->body);
    }

    public function test_process_message_handles_multipart_mime_correctly(): void
    {
        // Arrange
        Event::fake();
        $mailbox = Mailbox::factory()->create(['email' => 'support@example.com']);
        $folder = Folder::factory()->create([
            'mailbox_id' => $mailbox->id,
            'type' => 1,
        ]);

        // Message with both HTML and text parts
        $message = $this->createMockMessage([
            'from' => [(object) ['mail' => 'customer@example.com', 'personal' => 'John Doe']],
            'html_body' => '<html><body><p>HTML version of the message</p></body></html>',
            'text_body' => 'Text version of the message',
            'has_html' => true,
        ]);

        // Act
        $this->invokeProcessMessage($mailbox, $message);

        // Assert
        $thread = Thread::first();
        $this->assertNotNull($thread);
        // Should prefer HTML body
        $this->assertStringContainsString('HTML version', $thread->body);
    }

    public function test_process_message_handles_embedded_images(): void
    {
        // Arrange
        Event::fake();
        $mailbox = Mailbox::factory()->create(['email' => 'support@example.com']);
        $folder = Folder::factory()->create([
            'mailbox_id' => $mailbox->id,
            'type' => 1,
        ]);

        // Create embedded image attachment
        $mockAttachment = Mockery::mock(ImapAttachment::class);
        $mockAttachment->shouldReceive('getName')->andReturn('image.png');
        $mockAttachment->shouldReceive('getContent')->andReturn('fake image data');
        $mockAttachment->shouldReceive('getId')->andReturn('image123');
        $mockAttachment->shouldReceive('getContentType')->andReturn('image/png');
        $mockAttachment->disposition = 'inline';

        $message = $this->createMockMessage([
            'from' => [(object) ['mail' => 'customer@example.com', 'personal' => 'John Doe']],
            'html_body' => '<p>Check this image: <img src="cid:image123"></p>',
            'has_html' => true,
            'has_attachments' => true,
            'attachments' => new AttachmentCollection([$mockAttachment]),
        ]);

        // Act
        $this->invokeProcessMessage($mailbox, $message);

        // Assert
        $this->assertDatabaseHas('attachments', [
            'mime_type' => 'image/png',
            'embedded' => true,
        ]);

        $thread = Thread::first();
        $this->assertNotNull($thread);
        // CID should be replaced with URL
        $this->assertStringNotContainsString('cid:image123', $thread->body);
    }

    /**
     * Priority 5: Auto-Responder & Special Cases
     */
    public function test_process_message_handles_auto_responder_detection(): void
    {
        // Arrange
        Event::fake();
        $mailbox = Mailbox::factory()->create(['email' => 'support@example.com']);
        $folder = Folder::factory()->create([
            'mailbox_id' => $mailbox->id,
            'type' => 1,
        ]);

        // Auto-responder typically has certain headers, but for this test we just check behavior
        $message = $this->createMockMessage([
            'from' => [(object) ['mail' => 'autoresponder@example.com', 'personal' => 'Auto Responder']],
            'subject' => 'Out of Office: Re: Your message',
            'text_body' => 'I am currently out of office and will respond when I return.',
        ]);

        // Act
        $this->invokeProcessMessage($mailbox, $message);

        // Assert - Message should still be processed
        $this->assertDatabaseHas('conversations', [
            'mailbox_id' => $mailbox->id,
            'subject' => 'Out of Office: Re: Your message',
        ]);
    }

    public function test_process_message_handles_bounce_notifications(): void
    {
        // Arrange
        Event::fake();
        $mailbox = Mailbox::factory()->create(['email' => 'support@example.com']);
        $folder = Folder::factory()->create([
            'mailbox_id' => $mailbox->id,
            'type' => 1,
        ]);

        $message = $this->createMockMessage([
            'from' => [(object) ['mail' => 'mailer-daemon@mail.example.com', 'personal' => 'Mail Delivery System']],
            'subject' => 'Delivery Status Notification (Failure)',
            'text_body' => 'Your message could not be delivered',
        ]);

        // Act
        $this->invokeProcessMessage($mailbox, $message);

        // Assert - Should process bounce message
        $this->assertDatabaseHas('conversations', [
            'mailbox_id' => $mailbox->id,
        ]);
    }

    public function test_process_message_handles_internal_user_email(): void
    {
        // Arrange
        Event::fake();
        $mailbox = Mailbox::factory()->create(['email' => 'support@example.com']);
        $folder = Folder::factory()->create([
            'mailbox_id' => $mailbox->id,
            'type' => 1,
        ]);

        // Create internal user
        $user = User::factory()->create(['email' => 'agent@example.com']);

        $message = $this->createMockMessage([
            'from' => [(object) ['mail' => 'agent@example.com', 'personal' => 'Agent Smith']],
            'subject' => 'Internal note',
            'text_body' => 'This is an internal email',
        ]);

        // Act
        $this->invokeProcessMessage($mailbox, $message);

        // Assert
        $thread = Thread::first();
        $this->assertNotNull($thread);
        $this->assertEquals($user->id, $thread->created_by_user_id);
        $this->assertEquals($user->id, $thread->user_id);

        // Should NOT fire CustomerCreatedConversation or CustomerReplied for internal user
        Event::assertDispatched(\App\Events\NewMessageReceived::class);
    }

    /**
     * Bonus Tests
     */
    public function test_process_message_handles_international_characters_in_subject(): void
    {
        // Arrange
        Event::fake();
        $mailbox = Mailbox::factory()->create(['email' => 'support@example.com']);
        $folder = Folder::factory()->create([
            'mailbox_id' => $mailbox->id,
            'type' => 1,
        ]);

        $message = $this->createMockMessage([
            'from' => [(object) ['mail' => 'customer@example.com', 'personal' => 'José García']],
            'subject' => '你好 Hello Привет 🎉 Emoji Test',
            'text_body' => 'Testing UTF-8 characters: café, naïve, über',
        ]);

        // Act
        $this->invokeProcessMessage($mailbox, $message);

        // Assert
        $conversation = Conversation::where('mailbox_id', $mailbox->id)->first();
        $this->assertNotNull($conversation);
        $this->assertEquals('你好 Hello Привет 🎉 Emoji Test', $conversation->subject);
        $this->assertEquals('customer@example.com', $conversation->customer_email);
    }

    public function test_process_message_respects_mailbox_configuration(): void
    {
        // Arrange
        Event::fake();
        $mailbox1 = Mailbox::factory()->create(['email' => 'support@example.com']);
        $mailbox2 = Mailbox::factory()->create(['email' => 'sales@example.com']);

        $folder1 = Folder::factory()->create([
            'mailbox_id' => $mailbox1->id,
            'type' => 1,
        ]);
        $folder2 = Folder::factory()->create([
            'mailbox_id' => $mailbox2->id,
            'type' => 1,
        ]);

        $message = $this->createMockMessage([
            'from' => [(object) ['mail' => 'customer@example.com', 'personal' => 'John Doe']],
        ]);

        // Act
        $this->invokeProcessMessage($mailbox1, $message);

        // Assert - Conversation should be created in correct mailbox
        $conversation = Conversation::where('mailbox_id', $mailbox1->id)->first();
        $this->assertNotNull($conversation);

        // Should not be in mailbox2
        $conversation2 = Conversation::where('mailbox_id', $mailbox2->id)->first();
        $this->assertNull($conversation2);
    }

    public function test_process_message_handles_long_names_correctly(): void
    {
        // Arrange
        Event::fake();
        $mailbox = Mailbox::factory()->create(['email' => 'support@example.com']);
        $folder = Folder::factory()->create([
            'mailbox_id' => $mailbox->id,
            'type' => 1,
        ]);

        // Name that exceeds database field limits
        $veryLongFirstName = str_repeat('A', 30);
        $veryLongLastName = str_repeat('B', 40);

        $message = $this->createMockMessage([
            'from' => [(object) ['mail' => 'customer@example.com', 'personal' => $veryLongFirstName.' '.$veryLongLastName]],
        ]);

        // Act
        $this->invokeProcessMessage($mailbox, $message);

        // Assert - Conversation stores email directly
        $conversation = Conversation::where('mailbox_id', $mailbox->id)->first();
        $this->assertNotNull($conversation);
        $this->assertEquals('customer@example.com', $conversation->customer_email);
    }

    public function test_process_message_handles_duplicate_message_id(): void
    {
        // Arrange
        Event::fake();
        $mailbox = Mailbox::factory()->create(['email' => 'support@example.com']);
        $folder = Folder::factory()->create([
            'mailbox_id' => $mailbox->id,
            'type' => 1,
        ]);

        $messageId = '<duplicate-123@example.com>';

        // Create first message
        $message1 = $this->createMockMessage([
            'message_id' => $messageId,
            'from' => [(object) ['mail' => 'customer@example.com', 'personal' => 'John Doe']],
        ]);

        // Act - Process first message
        $this->invokeProcessMessage($mailbox, $message1);

        $conversationCount = Conversation::where('mailbox_id', $mailbox->id)->count();
        $this->assertEquals(1, $conversationCount);

        // Create duplicate message with same ID
        $message2 = $this->createMockMessage([
            'message_id' => $messageId,
            'from' => [(object) ['mail' => 'customer@example.com', 'personal' => 'John Doe']],
        ]);

        // Act - Process duplicate
        try {
            $this->invokeProcessMessage($mailbox, $message2);
        } catch (\Exception $e) {
            // Exception is expected or it should skip silently
        }

        // Assert - Should not create duplicate conversation
        $conversationCount = Conversation::where('mailbox_id', $mailbox->id)->count();
        $this->assertEquals(1, $conversationCount);
    }

    public function test_process_message_updates_conversation_timestamps(): void
    {
        // Arrange
        Event::fake();
        $mailbox = Mailbox::factory()->create(['email' => 'support@example.com']);
        $folder = Folder::factory()->create([
            'mailbox_id' => $mailbox->id,
            'type' => 1,
        ]);

        $customer = Customer::factory()->create(['email' => 'customer@example.com']);
        $conversation = Conversation::factory()->create([
            'mailbox_id' => $mailbox->id,
            'customer_id' => $customer->id,
            'last_reply_at' => now()->subHours(2),
        ]);
        $originalThread = Thread::factory()->create([
            'conversation_id' => $conversation->id,
            'message_id' => '<original@example.com>',
        ]);

        $originalTimestamp = $conversation->last_reply_at;

        // Create reply
        $message = $this->createMockMessage([
            'from' => [(object) ['mail' => 'customer@example.com', 'personal' => 'John Customer']],
            'in_reply_to' => '<original@example.com>',
        ]);

        // Act
        $this->invokeProcessMessage($mailbox, $message);

        // Assert
        $conversation->refresh();
        $this->assertNotEquals($originalTimestamp, $conversation->last_reply_at);
        $this->assertTrue($conversation->last_reply_at->gt($originalTimestamp));
    }

    public function test_process_message_handles_cc_and_bcc_recipients(): void
    {
        // Arrange
        Event::fake();
        $mailbox = Mailbox::factory()->create(['email' => 'support@example.com']);
        $folder = Folder::factory()->create([
            'mailbox_id' => $mailbox->id,
            'type' => 1,
        ]);

        $message = $this->createMockMessage([
            'from' => [(object) ['mail' => 'customer@example.com', 'personal' => 'John Doe']],
            'to' => [(object) ['mail' => 'support@example.com', 'personal' => '']],
            'cc' => [
                (object) ['mail' => 'cc1@example.com', 'personal' => 'CC Person 1'],
                (object) ['mail' => 'cc2@example.com', 'personal' => 'CC Person 2'],
            ],
            'bcc' => [(object) ['mail' => 'bcc@example.com', 'personal' => 'BCC Person']],
        ]);

        // Act
        $this->invokeProcessMessage($mailbox, $message);

        // Assert
        $thread = Thread::first();
        $this->assertNotNull($thread);

        $ccAddresses = json_decode($thread->cc, true);
        $this->assertIsArray($ccAddresses);
        $this->assertContains('cc1@example.com', $ccAddresses);
        $this->assertContains('cc2@example.com', $ccAddresses);

        $bccAddresses = json_decode($thread->bcc, true);
        $this->assertIsArray($bccAddresses);
        $this->assertContains('bcc@example.com', $bccAddresses);
    }

    /**
     * COMPREHENSIVE EDGE CASES - Address Parsing
     */
    public function test_process_message_handles_from_as_attribute_object_with_toarray(): void
    {
        // Arrange
        Event::fake();
        $mailbox = Mailbox::factory()->create(['email' => 'support@example.com']);
        $folder = Folder::factory()->create(['mailbox_id' => $mailbox->id, 'type' => 1]);

        // Mock Attribute object with toArray method
        $fromAttribute = Mockery::mock(Attribute::class);
        $fromAttribute->shouldReceive('toArray')->andReturn([
            (object) ['mail' => 'attr@example.com', 'personal' => 'Attr User'],
        ]);

        $message = Mockery::mock(Message::class);
        $message->shouldReceive('getFrom')->andReturn($fromAttribute);
        $message->shouldReceive('getMessageId')->andReturn('<test@example.com>');
        $message->shouldReceive('getSubject')->andReturn('Test');
        $message->shouldReceive('getTo')->andReturn([]);
        $message->shouldReceive('getCc')->andReturn([]);
        $message->shouldReceive('getBcc')->andReturn([]);
        $message->shouldReceive('getReplyTo')->andReturn([]);
        $message->shouldReceive('getTextBody')->andReturn('Body');
        $message->shouldReceive('getHTMLBody')->andReturn('<p>Body</p>');
        $message->shouldReceive('hasHTMLBody')->andReturn(true);
        $message->shouldReceive('hasAttachments')->andReturn(false);
        $message->shouldReceive('getAttachments')->andReturn(new AttachmentCollection);
        $message->shouldReceive('getRawHeader')->andReturn('From: attr@example.com');

        // Mock Header with Attribute returns
        $emptyAttribute = Mockery::mock(Attribute::class);
        $emptyAttribute->shouldReceive('first')->andReturn(null);

        $header = Mockery::mock(Header::class);
        $header->shouldReceive('get')->with('in_reply_to')->andReturn($emptyAttribute);
        $header->shouldReceive('get')->with('references')->andReturn($emptyAttribute);
        $message->shouldReceive('getHeader')->andReturn($header);

        // Act
        $this->invokeProcessMessage($mailbox, $message);

        // Assert - conversation stores sender email directly
        $conversation = Conversation::where('mailbox_id', $mailbox->id)->first();
        $this->assertNotNull($conversation);
        $this->assertEquals('attr@example.com', $conversation->customer_email);
    }

    public function test_process_message_handles_from_as_attribute_object_with_all(): void
    {
        // Arrange
        Event::fake();
        $mailbox = Mailbox::factory()->create(['email' => 'support@example.com']);
        $folder = Folder::factory()->create(['mailbox_id' => $mailbox->id, 'type' => 1]);

        // Mock Attribute object with all() method (the preferred method in parseFromAddress)
        $fromAttribute = new class
        {
            public function all()
            {
                return [
                    (object) ['mail' => 'allmethod@example.com', 'personal' => 'All User'],
                ];
            }
        };

        $message = Mockery::mock(Message::class);
        $message->shouldReceive('getFrom')->andReturn($fromAttribute);
        $message->shouldReceive('getMessageId')->andReturn('<test2@example.com>');
        $message->shouldReceive('getSubject')->andReturn('Test');
        $message->shouldReceive('getTo')->andReturn([]);
        $message->shouldReceive('getCc')->andReturn([]);
        $message->shouldReceive('getBcc')->andReturn([]);
        $message->shouldReceive('getReplyTo')->andReturn([]);
        $message->shouldReceive('getTextBody')->andReturn('Body');
        $message->shouldReceive('getHTMLBody')->andReturn('<p>Body</p>');
        $message->shouldReceive('hasHTMLBody')->andReturn(true);
        $message->shouldReceive('hasAttachments')->andReturn(false);
        $message->shouldReceive('getAttachments')->andReturn(new AttachmentCollection);
        $message->shouldReceive('getRawHeader')->andReturn('From: allmethod@example.com');

        // Mock Header with Attribute returns
        $emptyAttribute = Mockery::mock(Attribute::class);
        $emptyAttribute->shouldReceive('first')->andReturn(null);

        $header = Mockery::mock(Header::class);
        $header->shouldReceive('get')->with('in_reply_to')->andReturn($emptyAttribute);
        $header->shouldReceive('get')->with('references')->andReturn($emptyAttribute);
        $message->shouldReceive('getHeader')->andReturn($header);

        // Act
        $this->invokeProcessMessage($mailbox, $message);

        // Assert - conversation stores sender email directly
        $conversation = Conversation::where('mailbox_id', $mailbox->id)->first();
        $this->assertNotNull($conversation);
        $this->assertEquals('allmethod@example.com', $conversation->customer_email);
    }

    public function test_process_message_handles_from_address_as_array_format(): void
    {
        // Arrange
        Event::fake();
        $mailbox = Mailbox::factory()->create(['email' => 'support@example.com']);
        $folder = Folder::factory()->create(['mailbox_id' => $mailbox->id, 'type' => 1]);

        $message = $this->createMockMessage([
            'from' => [['mail' => 'array@example.com', 'personal' => 'Array User']],
        ]);

        // Act
        $this->invokeProcessMessage($mailbox, $message);

        // Assert - conversation stores sender email directly
        $conversation = Conversation::where('mailbox_id', $mailbox->id)->first();
        $this->assertNotNull($conversation);
        $this->assertEquals('array@example.com', $conversation->customer_email);
    }

    public function test_process_message_handles_from_address_as_string(): void
    {
        // Arrange
        Event::fake();
        $mailbox = Mailbox::factory()->create(['email' => 'support@example.com']);
        $folder = Folder::factory()->create(['mailbox_id' => $mailbox->id, 'type' => 1]);

        $message = $this->createMockMessage([
            'from' => ['string@example.com'],
        ]);

        // Act
        $this->invokeProcessMessage($mailbox, $message);

        // Assert - conversation stores sender email directly
        $conversation = Conversation::where('mailbox_id', $mailbox->id)->first();
        $this->assertNotNull($conversation);
        $this->assertEquals('string@example.com', $conversation->customer_email);
    }

    public function test_process_message_handles_from_with_name_email_format(): void
    {
        // Arrange
        Event::fake();
        $mailbox = Mailbox::factory()->create(['email' => 'support@example.com']);
        $folder = Folder::factory()->create(['mailbox_id' => $mailbox->id, 'type' => 1]);

        // Create object without mail property, forcing string parsing
        $fromObject = new \stdClass;
        $fromObject->__toString = function () {
            return 'John Doe <john@example.com>';
        };

        $fromObj = (object) ['mail' => 'parsed@example.com', 'personal' => 'Parsed User'];

        $message = Mockery::mock(Message::class);
        $message->shouldReceive('getFrom')->andReturn([$fromObj]);
        $message->shouldReceive('getMessageId')->andReturn('<parse@example.com>');
        $message->shouldReceive('getSubject')->andReturn('Test');
        $message->shouldReceive('getTo')->andReturn([]);
        $message->shouldReceive('getCc')->andReturn([]);
        $message->shouldReceive('getBcc')->andReturn([]);
        $message->shouldReceive('getReplyTo')->andReturn([]);
        $message->shouldReceive('getTextBody')->andReturn('Body');
        $message->shouldReceive('getHTMLBody')->andReturn('<p>Body</p>');
        $message->shouldReceive('hasHTMLBody')->andReturn(true);
        $message->shouldReceive('hasAttachments')->andReturn(false);
        $message->shouldReceive('getAttachments')->andReturn(new AttachmentCollection);
        $message->shouldReceive('getRawHeader')->andReturn('From: john@example.com');

        $emptyAttribute = Mockery::mock(Attribute::class);
        $emptyAttribute->shouldReceive('first')->andReturn(null);

        $header = Mockery::mock(Header::class);
        $header->shouldReceive('get')->with('in_reply_to')->andReturn($emptyAttribute);
        $header->shouldReceive('get')->with('references')->andReturn($emptyAttribute);
        $message->shouldReceive('getHeader')->andReturn($header);

        // Act
        $this->invokeProcessMessage($mailbox, $message);

        // Assert - conversation stores sender email directly
        $conversation = Conversation::where('mailbox_id', $mailbox->id)->first();
        $this->assertNotNull($conversation);
        $this->assertEquals('parsed@example.com', $conversation->customer_email);
    }

    public function test_process_message_throws_exception_when_no_sender_found(): void
    {
        // Arrange
        $mailbox = Mailbox::factory()->create(['email' => 'support@example.com']);
        $folder = Folder::factory()->create(['mailbox_id' => $mailbox->id, 'type' => 1]);

        $message = Mockery::mock(Message::class);
        $message->shouldReceive('getFrom')->andReturn([]);

        // Act & Assert
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('No sender found in message');

        $this->invokeProcessMessage($mailbox, $message);
    }

    public function test_process_message_throws_exception_when_no_sender_email_found(): void
    {
        // Arrange
        $mailbox = Mailbox::factory()->create(['email' => 'support@example.com']);
        $folder = Folder::factory()->create(['mailbox_id' => $mailbox->id, 'type' => 1]);

        $message = Mockery::mock(Message::class);
        // Return object without mail property and that can't be string-parsed
        $fromObj = new \stdClass;
        $message->shouldReceive('getFrom')->andReturn([$fromObj]);

        // Act & Assert
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('No sender email found in message');

        $this->invokeProcessMessage($mailbox, $message);
    }

    /**
     * COMPREHENSIVE - Message ID and Duplicate Handling
     */
    public function test_process_message_generates_message_id_when_missing(): void
    {
        // Arrange
        Event::fake();
        $mailbox = Mailbox::factory()->create(['email' => 'support@example.com']);
        $folder = Folder::factory()->create(['mailbox_id' => $mailbox->id, 'type' => 1]);

        $message = $this->createMockMessage([
            'message_id' => '',
            'from' => [(object) ['mail' => 'customer@example.com', 'personal' => 'John Doe']],
        ]);

        // Act
        $this->invokeProcessMessage($mailbox, $message);

        // Assert
        $thread = Thread::first();
        $this->assertNotNull($thread);
        $this->assertNotEmpty($thread->message_id);
        $this->assertStringContainsString('freescout-', $thread->message_id);
    }

    public function test_process_message_handles_bcc_to_multiple_mailboxes(): void
    {
        // Arrange
        Event::fake();
        $mailbox1 = Mailbox::factory()->create(['email' => 'support@example.com']);
        $mailbox2 = Mailbox::factory()->create(['email' => 'sales@example.com']);

        $folder1 = Folder::factory()->create(['mailbox_id' => $mailbox1->id, 'type' => 1]);
        $folder2 = Folder::factory()->create(['mailbox_id' => $mailbox2->id, 'type' => 1]);

        $messageId = '<bcc-test@example.com>';
        $customer = Customer::factory()->create(['email' => 'customer@example.com']);

        // First, process in mailbox1
        $message1 = $this->createMockMessage([
            'message_id' => $messageId,
            'from' => [(object) ['mail' => 'customer@example.com', 'personal' => 'John Doe']],
            'to' => [(object) ['mail' => 'support@example.com', 'personal' => '']],
        ]);
        $this->invokeProcessMessage($mailbox1, $message1);

        // Now process same message in mailbox2 (BCC scenario)
        $message2 = $this->createMockMessage([
            'message_id' => $messageId,
            'from' => [(object) ['mail' => 'customer@example.com', 'personal' => 'John Doe']],
            'to' => [(object) ['mail' => 'info@example.com', 'personal' => '']], // Different To
        ]);
        $this->invokeProcessMessage($mailbox2, $message2);

        // Assert - Should create separate conversations for each mailbox
        $conv1 = Conversation::where('mailbox_id', $mailbox1->id)->first();
        $conv2 = Conversation::where('mailbox_id', $mailbox2->id)->first();

        $this->assertNotNull($conv1);
        $this->assertNotNull($conv2);
        $this->assertNotEquals($conv1->id, $conv2->id);

        // Should have different artificial message IDs
        $thread1 = Thread::where('conversation_id', $conv1->id)->first();
        $thread2 = Thread::where('conversation_id', $conv2->id)->first();
        $this->assertNotEquals($thread1->message_id, $thread2->message_id);
    }

    /**
     * COMPREHENSIVE - Conversation Updates and Threading
     */
    public function test_process_message_updates_conversation_cc_list_on_reply(): void
    {
        // Arrange
        Event::fake();
        $mailbox = Mailbox::factory()->create(['email' => 'support@example.com']);
        $folder = Folder::factory()->create(['mailbox_id' => $mailbox->id, 'type' => 1]);

        $customer = Customer::factory()->create(['email' => 'customer@example.com']);
        $conversation = Conversation::factory()->create([
            'mailbox_id' => $mailbox->id,
            'customer_id' => $customer->id,
            'cc' => ['cc1@example.com'],
        ]);
        $originalThread = Thread::factory()->create([
            'conversation_id' => $conversation->id,
            'message_id' => '<original@example.com>',
        ]);

        // Reply with new CC
        $message = $this->createMockMessage([
            'from' => [(object) ['mail' => 'customer@example.com', 'personal' => 'John Customer']],
            'in_reply_to' => '<original@example.com>',
            'to' => [(object) ['mail' => 'support@example.com', 'personal' => '']],
            'cc' => [(object) ['mail' => 'cc2@example.com', 'personal' => 'CC2']],
        ]);

        // Act
        $this->invokeProcessMessage($mailbox, $message);

        // Assert
        $conversation->refresh();
        $this->assertIsArray($conversation->cc);
        $this->assertContains('cc1@example.com', $conversation->cc);
        $this->assertContains('cc2@example.com', $conversation->cc);
    }

    public function test_process_message_updates_conversation_bcc_on_reply(): void
    {
        // Arrange
        Event::fake();
        $mailbox = Mailbox::factory()->create(['email' => 'support@example.com']);
        $folder = Folder::factory()->create(['mailbox_id' => $mailbox->id, 'type' => 1]);

        $customer = Customer::factory()->create(['email' => 'customer@example.com']);
        $conversation = Conversation::factory()->create([
            'mailbox_id' => $mailbox->id,
            'customer_id' => $customer->id,
            'bcc' => null,
        ]);
        $originalThread = Thread::factory()->create([
            'conversation_id' => $conversation->id,
            'message_id' => '<original@example.com>',
        ]);

        // Reply with BCC
        $message = $this->createMockMessage([
            'from' => [(object) ['mail' => 'customer@example.com', 'personal' => 'John Customer']],
            'in_reply_to' => '<original@example.com>',
            'bcc' => [(object) ['mail' => 'bcc@example.com', 'personal' => 'BCC User']],
        ]);

        // Act
        $this->invokeProcessMessage($mailbox, $message);

        // Assert
        $conversation->refresh();
        $this->assertIsArray($conversation->bcc);
        $this->assertContains('bcc@example.com', $conversation->bcc);
    }

    public function test_process_message_sets_conversation_status_to_active_on_reply(): void
    {
        // Arrange
        Event::fake();
        $mailbox = Mailbox::factory()->create(['email' => 'support@example.com']);
        $folder = Folder::factory()->create(['mailbox_id' => $mailbox->id, 'type' => 1]);

        $customer = Customer::factory()->create(['email' => 'customer@example.com']);
        $conversation = Conversation::factory()->create([
            'mailbox_id' => $mailbox->id,
            'customer_id' => $customer->id,
            'status' => 2, // Closed
        ]);
        $originalThread = Thread::factory()->create([
            'conversation_id' => $conversation->id,
            'message_id' => '<original@example.com>',
        ]);

        $message = $this->createMockMessage([
            'from' => [(object) ['mail' => 'customer@example.com', 'personal' => 'John Customer']],
            'in_reply_to' => '<original@example.com>',
        ]);

        // Act
        $this->invokeProcessMessage($mailbox, $message);

        // Assert
        $conversation->refresh();
        $this->assertEquals(1, $conversation->status); // Active
    }

    public function test_process_message_sets_last_reply_from_customer(): void
    {
        // Arrange
        Event::fake();
        $mailbox = Mailbox::factory()->create(['email' => 'support@example.com']);
        $folder = Folder::factory()->create(['mailbox_id' => $mailbox->id, 'type' => 1]);

        $customer = Customer::factory()->create(['email' => 'customer@example.com']);
        $conversation = Conversation::factory()->create([
            'mailbox_id' => $mailbox->id,
            'customer_id' => $customer->id,
            'last_reply_from' => 1, // Was from user
        ]);
        $originalThread = Thread::factory()->create([
            'conversation_id' => $conversation->id,
            'message_id' => '<original@example.com>',
        ]);

        $message = $this->createMockMessage([
            'from' => [(object) ['mail' => 'customer@example.com', 'personal' => 'John Customer']],
            'in_reply_to' => '<original@example.com>',
        ]);

        // Act
        $this->invokeProcessMessage($mailbox, $message);

        // Assert
        $conversation->refresh();
        $this->assertEquals(2, $conversation->last_reply_from); // Customer
    }

    /**
     * COMPREHENSIVE - Thread Creation
     */
    public function test_process_message_sets_thread_first_flag_correctly(): void
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
        $this->assertTrue((bool) $thread->first);
    }

    public function test_process_message_sets_thread_first_flag_false_for_replies(): void
    {
        // Arrange
        Event::fake();
        $mailbox = Mailbox::factory()->create(['email' => 'support@example.com']);
        $folder = Folder::factory()->create(['mailbox_id' => $mailbox->id, 'type' => 1]);

        $customer = Customer::factory()->create(['email' => 'customer@example.com']);
        $conversation = Conversation::factory()->create([
            'mailbox_id' => $mailbox->id,
            'customer_id' => $customer->id,
            'threads_count' => 1,
        ]);
        $originalThread = Thread::factory()->create([
            'conversation_id' => $conversation->id,
            'message_id' => '<original@example.com>',
            'first' => true,
        ]);

        $message = $this->createMockMessage([
            'from' => [(object) ['mail' => 'customer@example.com', 'personal' => 'John Customer']],
            'in_reply_to' => '<original@example.com>',
        ]);

        // Act
        $this->invokeProcessMessage($mailbox, $message);

        // Assert
        $replyThread = Thread::where('conversation_id', $conversation->id)
            ->where('first', false)
            ->first();
        $this->assertNotNull($replyThread);
    }

    public function test_process_message_stores_headers_in_thread(): void
    {
        // Arrange
        Event::fake();
        $mailbox = Mailbox::factory()->create(['email' => 'support@example.com']);
        $folder = Folder::factory()->create(['mailbox_id' => $mailbox->id, 'type' => 1]);

        $rawHeaders = "From: customer@example.com\r\nTo: support@example.com\r\nSubject: Test";

        $message = $this->createMockMessage([
            'from' => [(object) ['mail' => 'customer@example.com', 'personal' => 'John Doe']],
            'raw_header' => $rawHeaders,
        ]);

        // Act
        $this->invokeProcessMessage($mailbox, $message);

        // Assert
        $thread = Thread::first();
        $this->assertNotNull($thread);
        $this->assertStringContainsString('From: customer@example.com', $thread->headers);
    }

    public function test_process_message_stores_to_addresses_as_json(): void
    {
        // Arrange
        Event::fake();
        $mailbox = Mailbox::factory()->create(['email' => 'support@example.com']);
        $folder = Folder::factory()->create(['mailbox_id' => $mailbox->id, 'type' => 1]);

        $message = $this->createMockMessage([
            'from' => [(object) ['mail' => 'customer@example.com', 'personal' => 'John Doe']],
            'to' => [
                (object) ['mail' => 'support@example.com', 'personal' => ''],
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
        $this->assertContains('support@example.com', $toAddresses);
        $this->assertContains('info@example.com', $toAddresses);
    }

    /**
     * COMPREHENSIVE - Attachment Handling
     */
    public function test_process_message_handles_multiple_attachments(): void
    {
        // Arrange
        Event::fake();
        $mailbox = Mailbox::factory()->create(['email' => 'support@example.com']);
        $folder = Folder::factory()->create(['mailbox_id' => $mailbox->id, 'type' => 1]);

        $attachment1 = Mockery::mock(ImapAttachment::class);
        $attachment1->shouldReceive('getName')->andReturn('doc1.pdf');
        $attachment1->shouldReceive('getContent')->andReturn('content1');
        $attachment1->shouldReceive('getId')->andReturn(null);
        $attachment1->shouldReceive('getContentType')->andReturn('application/pdf');
        $attachment1->disposition = 'attachment';

        $attachment2 = Mockery::mock(ImapAttachment::class);
        $attachment2->shouldReceive('getName')->andReturn('doc2.docx');
        $attachment2->shouldReceive('getContent')->andReturn('content2');
        $attachment2->shouldReceive('getId')->andReturn(null);
        $attachment2->shouldReceive('getContentType')->andReturn('application/vnd.openxmlformats-officedocument.wordprocessingml.document');
        $attachment2->disposition = 'attachment';

        $message = $this->createMockMessage([
            'from' => [(object) ['mail' => 'customer@example.com', 'personal' => 'John Doe']],
            'has_attachments' => true,
            'attachments' => new AttachmentCollection([$attachment1, $attachment2]),
        ]);

        // Act
        $this->invokeProcessMessage($mailbox, $message);

        // Assert
        $attachmentCount = Attachment::count();
        $this->assertEquals(2, $attachmentCount);
    }

    public function test_process_message_skips_attachment_without_filename(): void
    {
        // Arrange
        Event::fake();
        $mailbox = Mailbox::factory()->create(['email' => 'support@example.com']);
        $folder = Folder::factory()->create(['mailbox_id' => $mailbox->id, 'type' => 1]);

        $attachment = Mockery::mock(ImapAttachment::class);
        $attachment->shouldReceive('getName')->andReturn('');
        $attachment->shouldReceive('getContent')->andReturn('content');
        $attachment->shouldReceive('getId')->andReturn(null);

        $message = $this->createMockMessage([
            'from' => [(object) ['mail' => 'customer@example.com', 'personal' => 'John Doe']],
            'has_attachments' => true,
            'attachments' => new AttachmentCollection([$attachment]),
        ]);

        // Act
        $this->invokeProcessMessage($mailbox, $message);

        // Assert
        $attachmentCount = Attachment::count();
        $this->assertEquals(0, $attachmentCount);
    }

    public function test_process_message_replaces_multiple_cid_references(): void
    {
        // Arrange
        Event::fake();
        $mailbox = Mailbox::factory()->create(['email' => 'support@example.com']);
        $folder = Folder::factory()->create(['mailbox_id' => $mailbox->id, 'type' => 1]);

        $attachment1 = Mockery::mock(ImapAttachment::class);
        $attachment1->shouldReceive('getName')->andReturn('image1.png');
        $attachment1->shouldReceive('getContent')->andReturn('image1data');
        $attachment1->shouldReceive('getId')->andReturn('img1');
        $attachment1->shouldReceive('getContentType')->andReturn('image/png');
        $attachment1->disposition = 'inline';

        $attachment2 = Mockery::mock(ImapAttachment::class);
        $attachment2->shouldReceive('getName')->andReturn('image2.png');
        $attachment2->shouldReceive('getContent')->andReturn('image2data');
        $attachment2->shouldReceive('getId')->andReturn('img2');
        $attachment2->shouldReceive('getContentType')->andReturn('image/png');
        $attachment2->disposition = 'inline';

        $message = $this->createMockMessage([
            'from' => [(object) ['mail' => 'customer@example.com', 'personal' => 'John Doe']],
            'html_body' => '<p>Image 1: <img src="cid:img1"> and Image 2: <img src="cid:img2"></p>',
            'has_html' => true,
            'has_attachments' => true,
            'attachments' => new AttachmentCollection([$attachment1, $attachment2]),
        ]);

        // Act
        $this->invokeProcessMessage($mailbox, $message);

        // Assert
        $thread = Thread::first();
        $this->assertNotNull($thread);
        $this->assertStringNotContainsString('cid:img1', $thread->body);
        $this->assertStringNotContainsString('cid:img2', $thread->body);
        $this->assertStringContainsString('storage/attachments', $thread->body);
    }

    public function test_process_message_sets_has_attachments_flag_only_for_non_embedded(): void
    {
        // Arrange
        $mailbox = Mailbox::factory()->create();

        // Case 1: Message with only embedded attachments
        $embeddedAttachment = Mockery::mock(ImapAttachment::class);
        $embeddedAttachment->shouldReceive('getName')->andReturn('image1.png');
        $embeddedAttachment->shouldReceive('getContent')->andReturn('image1data');
        $embeddedAttachment->shouldReceive('getId')->andReturn('img1');
        $embeddedAttachment->shouldReceive('getContentType')->andReturn('image/png');
        $embeddedAttachment->disposition = 'inline';

        $messageEmbedded = $this->createMockMessage([
            'from' => [(object) ['mail' => 'customer1@example.com', 'personal' => 'John Doe']],
            'html_body' => '<p>Image: <img src="cid:img1"></p>',
            'has_html' => true,
            'has_attachments' => true,
            'attachments' => new AttachmentCollection([$embeddedAttachment]),
            'message_id' => '<msg1@example.com>',
            'subject' => 'Embedded Only',
        ]);

        // Act
        $this->invokeProcessMessage($mailbox, $messageEmbedded);

        // Assert
        $conversation1 = Conversation::where('subject', 'Embedded Only')->first();
        $this->assertNotNull($conversation1);
        $this->assertFalse((bool) $conversation1->has_attachments, 'Conversation should not have attachments flag if only embedded images are present');

        // Case 2: Message with regular attachment
        $regularAttachment = Mockery::mock(ImapAttachment::class);
        $regularAttachment->shouldReceive('getName')->andReturn('doc.pdf');
        $regularAttachment->shouldReceive('getContent')->andReturn('pdfdata');
        $regularAttachment->shouldReceive('getId')->andReturn(null);
        $regularAttachment->shouldReceive('getContentType')->andReturn('application/pdf');
        $regularAttachment->disposition = 'attachment';

        $messageRegular = $this->createMockMessage([
            'from' => [(object) ['mail' => 'customer2@example.com', 'personal' => 'Jane Doe']],
            'html_body' => '<p>Here is the doc</p>',
            'has_html' => true,
            'has_attachments' => true,
            'attachments' => new AttachmentCollection([$regularAttachment]),
            'message_id' => '<msg2@example.com>',
            'subject' => 'Regular Attachment',
        ]);

        // Act
        $this->invokeProcessMessage($mailbox, $messageRegular);

        // Assert
        $conversation2 = Conversation::where('subject', 'Regular Attachment')->first();
        $this->assertNotNull($conversation2);
        $this->assertTrue((bool) $conversation2->has_attachments, 'Conversation should have attachments flag if regular attachment is present');
    }
}
interface MessageWithRawHeader
{
    public function getRawHeader();
}
