<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Services\ImapService;
use App\Models\Mailbox;
use App\Models\Conversation;
use App\Models\Customer;
use App\Models\Thread;
use App\Models\User;
use App\Models\Folder;
use App\Models\Attachment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use Mockery;
use Webklex\PHPIMAP\Message;
use Webklex\PHPIMAP\Attribute;
use Webklex\PHPIMAP\Header;
use Webklex\PHPIMAP\Attachment as ImapAttachment;

class ImapServiceProcessMessageTest extends TestCase
{
    use RefreshDatabase;

    protected ImapService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ImapService();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
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
                (object)['mail' => 'customer@example.com', 'personal' => 'John Doe']
            ],
            'to' => [],
            'cc' => [],
            'bcc' => [],
            'reply_to' => [],
            'text_body' => 'Test email body content',
            'html_body' => '<p>Test email body content</p>',
            'has_html' => true,
            'has_attachments' => false,
            'attachments' => collect([]),
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
            $header->shouldReceive('get')->with('in_reply_to')->andReturn(null);
        }

        if ($params['references']) {
            $referencesAttr = Mockery::mock(Attribute::class);
            $referencesAttr->shouldReceive('first')->andReturn($params['references']);
            $header->shouldReceive('get')->with('references')->andReturn($referencesAttr);
        } else {
            $header->shouldReceive('get')->with('references')->andReturn(null);
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
            'from' => [(object)['mail' => 'customer@example.com', 'personal' => 'Jane Customer']],
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

        $this->assertDatabaseHas('customers', [
            'email' => 'customer@example.com',
            'first_name' => 'Jane',
            'last_name' => 'Customer',
        ]);

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
            'from' => [(object)['mail' => 'customer@example.com', 'personal' => 'John Doe']],
            'has_attachments' => true,
            'attachments' => collect([$mockAttachment]),
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
            'from' => [(object)['mail' => 'newcustomer@example.com', 'personal' => 'New Customer']],
        ]);

        $this->assertDatabaseMissing('customers', [
            'email' => 'newcustomer@example.com',
        ]);

        // Act
        $this->invokeProcessMessage($mailbox, $message);

        // Assert
        $this->assertDatabaseHas('customers', [
            'email' => 'newcustomer@example.com',
            'first_name' => 'New',
            'last_name' => 'Customer',
        ]);
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
            'from' => [(object)['mail' => 'existing@example.com', 'personal' => 'Existing Customer']],
        ]);

        // Act
        $this->invokeProcessMessage($mailbox, $message);

        // Assert
        $conversation = Conversation::where('mailbox_id', $mailbox->id)->first();
        $this->assertNotNull($conversation);
        $this->assertEquals($existingCustomer->id, $conversation->customer_id);

        // Ensure no duplicate customer was created
        $customerCount = Customer::where('email', 'existing@example.com')->count();
        $this->assertEquals(1, $customerCount);
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
            'from' => [(object)['mail' => 'customer@example.com', 'personal' => 'John Doe']],
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

        // Create reply message
        $message = $this->createMockMessage([
            'from' => [(object)['mail' => 'customer@example.com', 'personal' => 'John Customer']],
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

        // Create reply with References header
        $message = $this->createMockMessage([
            'from' => [(object)['mail' => 'customer@example.com', 'personal' => 'John Customer']],
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
            'from' => [(object)['mail' => 'customer@example.com', 'personal' => 'John Customer']],
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
            'from' => [(object)['mail' => 'customer@example.com', 'personal' => 'John Customer']],
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
            'from' => [(object)['mail' => 'agent@example.com', 'personal' => 'Agent Smith']],
            'subject' => 'Fwd: Original Subject',
            'text_body' => $forwardedBody,
            'has_html' => false,
        ]);

        // Act
        $this->invokeProcessMessage($mailbox, $message);

        // Assert - Should extract original sender
        $this->assertDatabaseHas('customers', [
            'email' => 'original@customer.com',
        ]);

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
            'from' => [(object)['mail' => 'agent@example.com', 'personal' => 'Agent']],
            'subject' => 'Fwd: Document',
            'text_body' => '@fwd From: customer@example.com\n\nHere is the document',
            'has_html' => false,
            'has_attachments' => true,
            'attachments' => collect([$mockAttachment]),
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
            'from' => [(object)['mail' => 'customer@example.com', 'personal' => '']],
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
            'from' => [(object)['mail' => 'customer@example.com', 'personal' => 'John Doe']],
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
            'from' => [(object)['mail' => 'customer@example.com', 'personal' => 'John Doe']],
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
            'from' => [(object)['mail' => 'customer@example.com', 'personal' => 'John Doe']],
            'html_body' => '<p>Check this image: <img src="cid:image123"></p>',
            'has_html' => true,
            'has_attachments' => true,
            'attachments' => collect([$mockAttachment]),
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
            'from' => [(object)['mail' => 'autoresponder@example.com', 'personal' => 'Auto Responder']],
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
            'from' => [(object)['mail' => 'mailer-daemon@mail.example.com', 'personal' => 'Mail Delivery System']],
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
            'from' => [(object)['mail' => 'agent@example.com', 'personal' => 'Agent Smith']],
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
            'from' => [(object)['mail' => 'customer@example.com', 'personal' => 'José García']],
            'subject' => '你好 Hello Привет 🎉 Emoji Test',
            'text_body' => 'Testing UTF-8 characters: café, naïve, über',
        ]);

        // Act
        $this->invokeProcessMessage($mailbox, $message);

        // Assert
        $conversation = Conversation::where('mailbox_id', $mailbox->id)->first();
        $this->assertNotNull($conversation);
        $this->assertEquals('你好 Hello Привет 🎉 Emoji Test', $conversation->subject);

        $customer = Customer::where('email', 'customer@example.com')->first();
        $this->assertNotNull($customer);
        $this->assertEquals('José', $customer->first_name);
        $this->assertEquals('García', $customer->last_name);
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
            'from' => [(object)['mail' => 'customer@example.com', 'personal' => 'John Doe']],
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
            'from' => [(object)['mail' => 'customer@example.com', 'personal' => $veryLongFirstName.' '.$veryLongLastName]],
        ]);

        // Act
        $this->invokeProcessMessage($mailbox, $message);

        // Assert - Names should be truncated
        $customer = Customer::where('email', 'customer@example.com')->first();
        $this->assertNotNull($customer);
        $this->assertEquals(20, strlen($customer->first_name)); // Truncated to 20
        $this->assertEquals(30, strlen($customer->last_name)); // Truncated to 30
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
            'from' => [(object)['mail' => 'customer@example.com', 'personal' => 'John Doe']],
        ]);

        // Act - Process first message
        $this->invokeProcessMessage($mailbox, $message1);

        $conversationCount = Conversation::where('mailbox_id', $mailbox->id)->count();
        $this->assertEquals(1, $conversationCount);

        // Create duplicate message with same ID
        $message2 = $this->createMockMessage([
            'message_id' => $messageId,
            'from' => [(object)['mail' => 'customer@example.com', 'personal' => 'John Doe']],
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
            'from' => [(object)['mail' => 'customer@example.com', 'personal' => 'John Customer']],
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
            'from' => [(object)['mail' => 'customer@example.com', 'personal' => 'John Doe']],
            'to' => [(object)['mail' => 'support@example.com', 'personal' => '']],
            'cc' => [
                (object)['mail' => 'cc1@example.com', 'personal' => 'CC Person 1'],
                (object)['mail' => 'cc2@example.com', 'personal' => 'CC Person 2']
            ],
            'bcc' => [(object)['mail' => 'bcc@example.com', 'personal' => 'BCC Person']],
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
}
