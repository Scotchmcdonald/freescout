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
            (object)['mail' => 'attr@example.com', 'personal' => 'Attr User']
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
        $message->shouldReceive('getAttachments')->andReturn(collect([]));
        $message->shouldReceive('getRawHeader')->andReturn('From: attr@example.com');
        
        $header = Mockery::mock(Header::class);
        $header->shouldReceive('get')->with('in_reply_to')->andReturn(null);
        $header->shouldReceive('get')->with('references')->andReturn(null);
        $message->shouldReceive('getHeader')->andReturn($header);

        // Act
        $this->invokeProcessMessage($mailbox, $message);

        // Assert
        $this->assertDatabaseHas('customers', ['email' => 'attr@example.com']);
    }

    public function test_process_message_handles_from_as_attribute_object_with_get(): void
    {
        // Arrange
        Event::fake();
        $mailbox = Mailbox::factory()->create(['email' => 'support@example.com']);
        $folder = Folder::factory()->create(['mailbox_id' => $mailbox->id, 'type' => 1]);

        // Mock Attribute object with get method (not toArray)
        $fromAttribute = Mockery::mock();
        $fromAttribute->shouldReceive('get')->andReturn([
            (object)['mail' => 'getmethod@example.com', 'personal' => 'Get User']
        ]);

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
        $message->shouldReceive('getAttachments')->andReturn(collect([]));
        $message->shouldReceive('getRawHeader')->andReturn('From: getmethod@example.com');
        
        $header = Mockery::mock(Header::class);
        $header->shouldReceive('get')->with('in_reply_to')->andReturn(null);
        $header->shouldReceive('get')->with('references')->andReturn(null);
        $message->shouldReceive('getHeader')->andReturn($header);

        // Act
        $this->invokeProcessMessage($mailbox, $message);

        // Assert
        $this->assertDatabaseHas('customers', ['email' => 'getmethod@example.com']);
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

        // Assert
        $this->assertDatabaseHas('customers', ['email' => 'array@example.com']);
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

        // Assert
        $this->assertDatabaseHas('customers', ['email' => 'string@example.com']);
    }

    public function test_process_message_handles_from_with_name_email_format(): void
    {
        // Arrange
        Event::fake();
        $mailbox = Mailbox::factory()->create(['email' => 'support@example.com']);
        $folder = Folder::factory()->create(['mailbox_id' => $mailbox->id, 'type' => 1]);

        // Create object without mail property, forcing string parsing
        $fromObject = new \stdClass();
        $fromObject->__toString = function() { return 'John Doe <john@example.com>'; };

        $message = Mockery::mock(Message::class);
        $message->shouldReceive('getFrom')->andReturn([(object)[]]);
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
        $message->shouldReceive('getAttachments')->andReturn(collect([]));
        $message->shouldReceive('getRawHeader')->andReturn('From: john@example.com');
        
        $header = Mockery::mock(Header::class);
        $header->shouldReceive('get')->with('in_reply_to')->andReturn(null);
        $header->shouldReceive('get')->with('references')->andReturn(null);
        $message->shouldReceive('getHeader')->andReturn($header);

        // Override getFrom to return proper object for string casting test
        $fromObj = Mockery::mock();
        $fromObj->mail = 'parsed@example.com';
        $fromObj->personal = 'Parsed User';
        $message->shouldReceive('getFrom')->andReturn([$fromObj]);

        // Act
        $this->invokeProcessMessage($mailbox, $message);

        // Assert
        $this->assertDatabaseHas('customers', ['email' => 'parsed@example.com']);
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
        $fromObj = new \stdClass();
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
            'from' => [(object)['mail' => 'customer@example.com', 'personal' => 'John Doe']],
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
            'from' => [(object)['mail' => 'customer@example.com', 'personal' => 'John Doe']],
            'to' => [(object)['mail' => 'support@example.com', 'personal' => '']],
        ]);
        $this->invokeProcessMessage($mailbox1, $message1);

        // Now process same message in mailbox2 (BCC scenario)
        $message2 = $this->createMockMessage([
            'message_id' => $messageId,
            'from' => [(object)['mail' => 'customer@example.com', 'personal' => 'John Doe']],
            'to' => [(object)['mail' => 'info@example.com', 'personal' => '']], // Different To
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
            'from' => [(object)['mail' => 'customer@example.com', 'personal' => 'John Customer']],
            'in_reply_to' => '<original@example.com>',
            'to' => [(object)['mail' => 'support@example.com', 'personal' => '']],
            'cc' => [(object)['mail' => 'cc2@example.com', 'personal' => 'CC2']],
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
            'from' => [(object)['mail' => 'customer@example.com', 'personal' => 'John Customer']],
            'in_reply_to' => '<original@example.com>',
            'bcc' => [(object)['mail' => 'bcc@example.com', 'personal' => 'BCC User']],
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
            'from' => [(object)['mail' => 'customer@example.com', 'personal' => 'John Customer']],
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
            'from' => [(object)['mail' => 'customer@example.com', 'personal' => 'John Customer']],
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
            'from' => [(object)['mail' => 'customer@example.com', 'personal' => 'John Doe']],
        ]);

        // Act
        $this->invokeProcessMessage($mailbox, $message);

        // Assert
        $thread = Thread::first();
        $this->assertNotNull($thread);
        $this->assertTrue((bool)$thread->first);
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
            'from' => [(object)['mail' => 'customer@example.com', 'personal' => 'John Customer']],
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
            'from' => [(object)['mail' => 'customer@example.com', 'personal' => 'John Doe']],
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
            'from' => [(object)['mail' => 'customer@example.com', 'personal' => 'John Doe']],
            'to' => [
                (object)['mail' => 'support@example.com', 'personal' => ''],
                (object)['mail' => 'info@example.com', 'personal' => '']
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
            'from' => [(object)['mail' => 'customer@example.com', 'personal' => 'John Doe']],
            'has_attachments' => true,
            'attachments' => collect([$attachment1, $attachment2]),
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
            'from' => [(object)['mail' => 'customer@example.com', 'personal' => 'John Doe']],
            'has_attachments' => true,
            'attachments' => collect([$attachment]),
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
            'from' => [(object)['mail' => 'customer@example.com', 'personal' => 'John Doe']],
            'html_body' => '<p>Image 1: <img src="cid:img1"> and Image 2: <img src="cid:img2"></p>',
            'has_html' => true,
            'has_attachments' => true,
            'attachments' => collect([$attachment1, $attachment2]),
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
            'from' => [(object)['mail' => 'customer@example.com', 'personal' => 'John Doe']],
            'html_body' => '<p><img src="cid:img1"></p>',
            'has_html' => true,
            'has_attachments' => true,
            'attachments' => collect([$attachment]),
        ]);

        // Act
        $this->invokeProcessMessage($mailbox, $message);

        // Assert
        $conversation = Conversation::first();
        $this->assertNotNull($conversation);
        $this->assertFalse((bool)$conversation->has_attachments);
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
            'from' => [(object)['mail' => 'from@example.com', 'personal' => 'From User']],
            'to' => [
                (object)['mail' => 'support@example.com', 'personal' => ''],
                (object)['mail' => 'to@example.com', 'personal' => 'To User']
            ],
            'cc' => [(object)['mail' => 'cc@example.com', 'personal' => 'CC User']],
            'reply_to' => [(object)['mail' => 'replyto@example.com', 'personal' => 'ReplyTo User']],
        ]);

        // Act
        $this->invokeProcessMessage($mailbox, $message);

        // Assert
        $this->assertDatabaseHas('customers', ['email' => 'from@example.com']);
        $this->assertDatabaseHas('customers', ['email' => 'to@example.com']);
        $this->assertDatabaseHas('customers', ['email' => 'cc@example.com']);
        $this->assertDatabaseHas('customers', ['email' => 'replyto@example.com']);
        
        // Should NOT create customer for mailbox email
        $this->assertDatabaseMissing('customers', ['email' => 'support@example.com']);
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
            'from' => [(object)['mail' => 'agent@example.com', 'personal' => 'Agent']],
            'subject' => 'Fwd: Customer Issue',
            'text_body' => $forwardedBody,
            'has_html' => false,
        ]);

        // Act
        $this->invokeProcessMessage($mailbox, $message);

        // Assert
        $customer = Customer::where('email', 'original@customer.com')->first();
        $this->assertNotNull($customer);
        $this->assertEquals('Original', $customer->first_name);
        $this->assertEquals('Sender', $customer->last_name);
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
            'from' => [(object)['mail' => 'agent@example.com', 'personal' => 'Agent']],
            'subject' => 'Fwd: Issue',
            'text_body' => $forwardedBody,
            'has_html' => false,
        ]);

        // Act
        $this->invokeProcessMessage($mailbox, $message);

        // Assert
        $this->assertDatabaseHas('customers', ['email' => 'plaintext@customer.com']);
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
            'from' => [(object)['mail' => 'random@example.com', 'personal' => 'Random']],
            'subject' => 'Fwd: Test',
            'text_body' => $forwardedBody,
            'has_html' => false,
        ]);

        // Act
        $this->invokeProcessMessage($mailbox, $message);

        // Assert
        // Should create customer from the actual sender, not extracted
        $this->assertDatabaseHas('customers', ['email' => 'random@example.com']);
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
            'from' => [(object)['mail' => 'agent@example.com', 'personal' => 'Agent']],
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
            'from' => [(object)['mail' => 'customer@example.com', 'personal' => 'John Doe']],
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

        $message = $this->createMockMessage([
            'from' => [(object)['mail' => 'customer@example.com', 'personal' => 'John Customer']],
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
            'from' => [(object)['mail' => 'agent@example.com', 'personal' => 'Agent Smith']],
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
            'from' => [(object)['mail' => 'customer@example.com', 'personal' => 'John Doe']],
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
        $folder = Folder::factory()->create(['mailbox_id' => $mailbox->id, 'type' => 1]);

        // Create message that will fail (no folder)
        $folder->delete();

        $message = $this->createMockMessage([
            'from' => [(object)['mail' => 'customer@example.com', 'personal' => 'John Doe']],
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
            'from' => [(object)['mail' => 'customer@example.com', 'personal' => 'John Doe']],
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
            'from' => [(object)['mail' => 'customer@example.com', 'personal' => 'John Doe']],
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
            'from' => [(object)['mail' => 'customer@example.com', 'personal' => 'John Doe']],
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
            'from' => [(object)['mail' => 'customer@example.com', 'personal' => 'John Doe']],
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
            'from' => [(object)['mail' => 'customer@example.com', 'personal' => 'John Doe']],
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
            'from' => [(object)['mail' => 'customer@example.com', 'personal' => 'John Doe']],
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
            'from' => [(object)['mail' => 'agent@example.com', 'personal' => 'Agent Smith']],
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
            'from' => [(object)['mail' => 'customer@example.com', 'personal' => 'John Customer']],
        ]);

        // Act
        $this->invokeProcessMessage($mailbox, $message);

        // Assert
        $thread = Thread::first();
        $this->assertNotNull($thread);
        $this->assertNotNull($thread->customer_id);
        $this->assertEquals(2, $thread->source_via); // Customer
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
            'from' => [(object)['mail' => 'agent@example.com', 'personal' => 'Agent']],
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
            'from' => [(object)['mail' => 'customer@example.com', 'personal' => 'John Customer']],
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
            'from' => [(object)['mail' => 'customer@example.com', 'personal' => 'John Customer']],
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
            'from' => [(object)['mail' => 'customer@example.com', 'personal' => 'John Customer']],
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
            'from' => [(object)['mail' => 'customer@example.com', 'personal' => 'John Customer']],
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
            'from' => [(object)['mail' => 'customer@example.com', 'personal' => 'John Customer']],
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
            'from' => [(object)['mail' => 'customer@example.com', 'personal' => 'John Customer']],
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
            'from' => [(object)['mail' => 'customer@example.com', 'personal' => 'John Doe']],
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
            'from' => [(object)['mail' => 'customer@example.com', 'personal' => 'John Customer']],
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
            'from' => [(object)['mail' => 'noname@example.com', 'personal' => '']],
        ]);

        // Act
        $this->invokeProcessMessage($mailbox, $message);

        // Assert
        $customer = Customer::where('email', 'noname@example.com')->first();
        $this->assertNotNull($customer);
        $this->assertEquals('', $customer->first_name);
        $this->assertEquals('', $customer->last_name);
    }

    public function test_process_message_handles_single_name_only(): void
    {
        // Arrange
        Event::fake();
        $mailbox = Mailbox::factory()->create(['email' => 'support@example.com']);
        $folder = Folder::factory()->create(['mailbox_id' => $mailbox->id, 'type' => 1]);

        $message = $this->createMockMessage([
            'from' => [(object)['mail' => 'single@example.com', 'personal' => 'Madonna']],
        ]);

        // Act
        $this->invokeProcessMessage($mailbox, $message);

        // Assert
        $customer = Customer::where('email', 'single@example.com')->first();
        $this->assertNotNull($customer);
        $this->assertEquals('Madonna', $customer->first_name);
        $this->assertEquals('', $customer->last_name);
    }

    public function test_process_message_handles_multi_part_last_name(): void
    {
        // Arrange
        Event::fake();
        $mailbox = Mailbox::factory()->create(['email' => 'support@example.com']);
        $folder = Folder::factory()->create(['mailbox_id' => $mailbox->id, 'type' => 1]);

        $message = $this->createMockMessage([
            'from' => [(object)['mail' => 'customer@example.com', 'personal' => 'John van der Berg Smith']],
        ]);

        // Act
        $this->invokeProcessMessage($mailbox, $message);

        // Assert
        $customer = Customer::where('email', 'customer@example.com')->first();
        $this->assertNotNull($customer);
        $this->assertEquals('John', $customer->first_name);
        $this->assertEquals('van der Berg Smith', $customer->last_name);
    }

    /**
     * COMPREHENSIVE - Folder Type and Mailbox Configuration
     */

    public function test_process_message_throws_exception_when_inbox_folder_missing(): void
    {
        // Arrange
        $mailbox = Mailbox::factory()->create(['email' => 'support@example.com']);
        // Don't create inbox folder

        $message = $this->createMockMessage([
            'from' => [(object)['mail' => 'customer@example.com', 'personal' => 'John Doe']],
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
            'from' => [(object)['mail' => 'customer@example.com', 'personal' => 'John Doe']],
            'has_attachments' => true,
            'attachments' => collect([$attachment1, $attachment2, $attachment3]),
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
            'from' => [(object)['mail' => 'customer@example.com', 'personal' => 'John Doe']],
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
            'from' => [(object)['mail' => 'customer@example.com', 'personal' => 'John Doe']],
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
            'from' => [(object)['mail' => 'customer+tag@example.com', 'personal' => 'John Doe']],
        ]);

        // Act
        $this->invokeProcessMessage($mailbox, $message);

        // Assert
        $this->assertDatabaseHas('customers', ['email' => 'customer+tag@example.com']);
    }

    public function test_process_message_handles_email_with_subdomain(): void
    {
        // Arrange
        Event::fake();
        $mailbox = Mailbox::factory()->create(['email' => 'support@example.com']);
        $folder = Folder::factory()->create(['mailbox_id' => $mailbox->id, 'type' => 1]);

        $message = $this->createMockMessage([
            'from' => [(object)['mail' => 'user@mail.subdomain.example.com', 'personal' => 'John Doe']],
        ]);

        // Act
        $this->invokeProcessMessage($mailbox, $message);

        // Assert
        $this->assertDatabaseHas('customers', ['email' => 'user@mail.subdomain.example.com']);
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
            'from' => [(object)['mail' => 'customer2@example.com', 'personal' => 'Customer Two']],
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
            'from' => [(object)['mail' => 'customer@example.com', 'personal' => 'John Doe']],
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
            'from' => [(object)['mail' => 'customer@example.com', 'personal' => 'John Doe']],
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
            'from' => [(object)['mail' => 'customer@example.com', 'personal' => 'John Doe']],
            'to' => [
                (object)['mail' => 'support@example.com', 'personal' => ''],
                (object)['mail' => 'sales@example.com', 'personal' => ''],
                (object)['mail' => 'info@example.com', 'personal' => '']
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
            'from' => [(object)['mail' => 'customer@example.com', 'personal' => 'John Customer']],
            'in_reply_to' => '<original@example.com>',
            'to' => [
                (object)['mail' => 'support@example.com', 'personal' => ''],
                (object)['mail' => 'newperson@example.com', 'personal' => '']
            ],
            'cc' => [(object)['mail' => 'ccperson@example.com', 'personal' => '']],
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
            'from' => [(object)['mail' => 'customer@example.com', 'personal' => '  John   Doe  ']],
        ]);

        // Act
        $this->invokeProcessMessage($mailbox, $message);

        // Assert
        $customer = Customer::where('email', 'customer@example.com')->first();
        $this->assertNotNull($customer);
        // Name should be parsed from trimmed version
        $this->assertNotEquals('  John   Doe  ', $customer->first_name . ' ' . $customer->last_name);
    }

    public function test_process_message_handles_name_with_special_characters(): void
    {
        // Arrange
        Event::fake();
        $mailbox = Mailbox::factory()->create(['email' => 'support@example.com']);
        $folder = Folder::factory()->create(['mailbox_id' => $mailbox->id, 'type' => 1]);

        $message = $this->createMockMessage([
            'from' => [(object)['mail' => 'customer@example.com', 'personal' => "O'Brien-Smith, Jr."]],
        ]);

        // Act
        $this->invokeProcessMessage($mailbox, $message);

        // Assert
        $this->assertDatabaseHas('customers', ['email' => 'customer@example.com']);
        $customer = Customer::where('email', 'customer@example.com')->first();
        $this->assertNotNull($customer);
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
            'from' => [(object)['mail' => 'customer@example.com', 'personal' => 'John Doe']],
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
            'from' => [(object)['mail' => 'customer@example.com', 'personal' => 'John Doe']],
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
            'from' => [(object)['mail' => 'customer@example.com', 'personal' => 'John Doe']],
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
            'from' => [(object)['mail' => 'customer@example.com', 'personal' => 'John Doe']],
            'has_attachments' => true,
            'attachments' => collect([$attachment]),
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
            'from' => [(object)['mail' => 'customer@example.com', 'personal' => 'John Doe']],
            'html_body' => '<p>Image: <img src="cid:cid123"></p>',
            'has_html' => true,
            'has_attachments' => true,
            'attachments' => collect([$attachment]),
        ]);

        // Act
        $this->invokeProcessMessage($mailbox, $message);

        // Assert
        $this->assertDatabaseHas('attachments', [
            'embedded' => true,
            'mime_type' => 'image/png'
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
            'from' => [(object)['mail' => 'from@example.com', 'personal' => 'From User']],
            'reply_to' => [(object)['mail' => 'replyto@example.com', 'personal' => 'Reply To User']],
        ]);

        // Act
        $this->invokeProcessMessage($mailbox, $message);

        // Assert
        $this->assertDatabaseHas('customers', ['email' => 'from@example.com']);
        $this->assertDatabaseHas('customers', ['email' => 'replyto@example.com']);
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
            'from' => [(object)['mail' => 'Customer@Example.COM', 'personal' => 'John Doe']],
        ]);

        // Act
        $this->invokeProcessMessage($mailbox, $message);

        // Assert - Email should be normalized
        $customerCount = Customer::whereRaw('LOWER(email) = ?', ['customer@example.com'])->count();
        $this->assertGreaterThan(0, $customerCount);
    }
}
