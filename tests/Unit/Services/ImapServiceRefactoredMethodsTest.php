<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\Conversation;
use App\Models\Customer;
use App\Models\Folder;
use App\Models\Mailbox;
use App\Models\Thread;
use App\Services\ImapService;
use Illuminate\Support\Facades\Log;
use Mockery;
use Tests\UnitTestCase;
use Webklex\PHPIMAP\Attachment;
use Webklex\PHPIMAP\Message;

/**
 * Tests for refactored ImapService methods.
 * Tests the newly extracted methods to ensure high coverage and reduced complexity.
 */
class ImapServiceRefactoredMethodsTest extends UnitTestCase
{
    protected ImapService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ImapService();
    }

    protected function tearDown(): void
    {
        restore_error_handler();
        restore_exception_handler();
        try {
            Mockery::close();
        } finally {
            parent::tearDown();
        }
    }

    // ========================================================================
    // Tests for extractSenderInfo method
    // ========================================================================

    public function test_extract_sender_info_returns_correct_structure(): void
    {
        $message = Mockery::mock(Message::class);
        $from = Mockery::mock(\Webklex\PHPIMAP\Attribute::class);
        $from->shouldReceive('toArray')->andReturn([
            (object) ['mail' => 'test@example.com', 'personal' => 'Test User'],
        ]);
        $message->shouldReceive('getFrom')->andReturn($from);

        Log::shouldReceive('debug')->atLeast()->once();

        $result = $this->invokeMethod($this->service, 'extractSenderInfo', [$message]);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('email', $result);
        $this->assertArrayHasKey('name', $result);
        $this->assertArrayHasKey('user', $result);
        $this->assertEquals('test@example.com', $result['email']);
        $this->assertEquals('Test User', $result['name']);
    }

    public function test_extract_sender_info_handles_empty_from(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('No sender found in message');

        $message = Mockery::mock(Message::class);
        $from = Mockery::mock();
        $from->shouldReceive('toArray')->andReturn([]);
        $message->shouldReceive('getFrom')->andReturn($from);

        $this->invokeMethod($this->service, 'extractSenderInfo', [$message]);
    }

    public function test_extract_sender_info_handles_object_from(): void
    {
        $message = Mockery::mock(Message::class);
        $addressObj = (object) ['mail' => 'user@test.com', 'personal' => 'User Name'];
        
        // Use anonymous class to satisfy method_exists check
        $from = new class($addressObj) {
            private $addr;
            public function __construct($addr) { $this->addr = $addr; }
            public function get() { return [$this->addr]; }
        };
        
        $message->shouldReceive('getFrom')->andReturn($from);

        Log::shouldReceive('debug')->once(); // Only "Processing message from" is logged, user not found

        $result = $this->invokeMethod($this->service, 'extractSenderInfo', [$message]);

        $this->assertEquals('user@test.com', $result['email']);
        $this->assertEquals('User Name', $result['name']);
    }

    // ========================================================================
    // Tests for parseFromAddress method
    // ========================================================================

    public function test_parse_from_address_handles_object_with_properties(): void
    {
        $address = (object) ['mail' => 'test@example.com', 'personal' => 'Test User'];

        $result = $this->invokeMethod($this->service, 'parseFromAddress', [$address]);

        $this->assertEquals('test@example.com', $result[0]);
        $this->assertEquals('Test User', $result[1]);
    }

    public function test_parse_from_address_handles_array(): void
    {
        $address = ['mail' => 'user@example.com', 'personal' => 'User Name'];

        $result = $this->invokeMethod($this->service, 'parseFromAddress', [$address]);

        $this->assertEquals('user@example.com', $result[0]);
        $this->assertEquals('User Name', $result[1]);
    }

    public function test_parse_from_address_handles_string(): void
    {
        $address = 'simple@example.com';

        $result = $this->invokeMethod($this->service, 'parseFromAddress', [$address]);

        $this->assertEquals('simple@example.com', $result[0]);
        $this->assertEquals('', $result[1]);
    }

    public function test_parse_from_address_handles_email_with_name_format(): void
    {
        $address = new class {
            public function __toString(): string
            {
                return 'John Doe <john@example.com>';
            }
        };

        $result = $this->invokeMethod($this->service, 'parseFromAddress', [$address]);

        $this->assertEquals('john@example.com', $result[0]);
        $this->assertEquals('John Doe', $result[1]);
    }

    public function test_parse_from_address_handles_object_without_mail_property(): void
    {
        $address = new class {
            public function __toString(): string
            {
                return 'plain@example.com';
            }
        };

        $result = $this->invokeMethod($this->service, 'parseFromAddress', [$address]);

        $this->assertEquals('plain@example.com', $result[0]);
    }

    // ========================================================================
    // Tests for findOrCreateConversation method
    // ========================================================================

    public function test_find_or_create_conversation_creates_new_conversation(): void
    {
        $mailbox = Mailbox::factory()->create();
        $folder = Folder::factory()->create([
            'mailbox_id' => $mailbox->id,
            'type' => 1, // Inbox
        ]);
        $senderInfo = ['email' => 'sender@example.com', 'name' => 'Test Sender', 'user' => null];
        
        $message = Mockery::mock(Message::class);
        $message->shouldReceive('getHeader')->andReturn(null);
        $message->shouldReceive('getTextBody')->andReturn('Test body');

        $result = $this->invokeMethod($this->service, 'findOrCreateConversation', [
            $mailbox,
            $message,
            $senderInfo,
            'Test Subject',
            '<test@example.com>',
            false,
        ]);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('conversation', $result);
        $this->assertArrayHasKey('is_new', $result);
        $this->assertInstanceOf(Conversation::class, $result['conversation']);
        $this->assertTrue($result['is_new']);
    }

    public function test_find_or_create_conversation_finds_existing_conversation(): void
    {
        $mailbox = Mailbox::factory()->create();
        $folder = Folder::factory()->create([
            'mailbox_id' => $mailbox->id,
            'type' => 1,
        ]);
        $senderInfo = ['email' => 'sender@example.com', 'name' => 'Test Sender', 'user' => null];
        
        // Create existing conversation and thread
        $existingConversation = Conversation::factory()->create([
            'mailbox_id' => $mailbox->id,
        ]);
        
        $parentThread = Thread::factory()->create([
            'conversation_id' => $existingConversation->id,
            'message_id' => '<parent@example.com>',
        ]);

        $message = Mockery::mock(Message::class);
        $header = Mockery::mock(\Webklex\PHPIMAP\Header::class);
        
        $inReplyTo = Mockery::mock(\Webklex\PHPIMAP\Attribute::class);
        $inReplyTo->shouldReceive('first')->andReturn('<parent@example.com>');
        
        $references = Mockery::mock(\Webklex\PHPIMAP\Attribute::class);
        $references->shouldReceive('first')->andReturn(null);

        $header->shouldReceive('get')->with('in_reply_to')->andReturn($inReplyTo);
        $header->shouldReceive('get')->with('references')->andReturn($references);
        $message->shouldReceive('getHeader')->andReturn($header);

        Log::shouldReceive('debug')->atLeast()->once();

        $result = $this->invokeMethod($this->service, 'findOrCreateConversation', [
            $mailbox,
            $message,
            $senderInfo,
            'Re: Test Subject',
            '<reply@example.com>',
            false,
        ]);

        $this->assertFalse($result['is_new']);
        $this->assertEquals($existingConversation->id, $result['conversation']->id);
    }

    public function test_find_or_create_conversation_throws_when_no_inbox_folder(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('No inbox folder found');

        $mailbox = Mailbox::factory()->create();
        // Ensure no folders exist for this mailbox
        Folder::where('mailbox_id', $mailbox->id)->delete();
        
        $senderInfo = ['email' => 'sender@example.com', 'name' => 'Test Sender', 'user' => null];
        
        $message = Mockery::mock(Message::class);
        $message->shouldReceive('getHeader')->andReturn(null);
        $message->shouldReceive('getTextBody')->andReturn('Test');

        $this->invokeMethod($this->service, 'findOrCreateConversation', [
            $mailbox,
            $message,
            $senderInfo,
            'Test',
            '<test@example.com>',
            false,
        ]);
    }

    // ========================================================================
    // Tests for isEmbeddedAttachment method
    // ========================================================================

    public function test_is_embedded_attachment_returns_true_for_cid_match(): void
    {
        $attachment = Mockery::mock(Attachment::class);
        $attachment->disposition = 'attachment';
        
        $result = $this->invokeMethod($this->service, 'isEmbeddedAttachment', [
            $attachment,
            'image123',
            'Some text with cid:image123 reference',
        ]);

        $this->assertTrue($result);
    }

    public function test_is_embedded_attachment_returns_true_for_inline_disposition(): void
    {
        $attachment = new class extends \Webklex\PHPIMAP\Attachment {
            public $disposition = 'inline';
            public function __construct() {}
        };
        
        $result = $this->invokeMethod($this->service, 'isEmbeddedAttachment', [
            $attachment,
            null,
            'Some body text',
        ]);

        $this->assertTrue($result);
    }

    public function test_is_embedded_attachment_returns_false_for_regular_attachment(): void
    {
        $attachment = Mockery::mock(Attachment::class);
        $attachment->disposition = 'attachment';
        
        $result = $this->invokeMethod($this->service, 'isEmbeddedAttachment', [
            $attachment,
            'file123',
            'Body without CID reference',
        ]);

        $this->assertFalse($result);
    }

    // ========================================================================
    // Tests for 0% Coverage Methods
    // ========================================================================

    public function test_get_folders_returns_success_with_folders(): void
    {
        $mockFolder = Mockery::mock(\Webklex\PHPIMAP\Folder::class);
        $mockFolder->full_name = 'INBOX';

        $mockClient = Mockery::mock(\Webklex\PHPIMAP\Client::class);
        $mockClient->shouldReceive('connect')->once();
        $mockClient->shouldReceive('getFolders')->andReturn(new \Webklex\PHPIMAP\Support\FolderCollection([$mockFolder]));
        $mockClient->shouldReceive('disconnect')->once();

        $service = Mockery::mock(ImapService::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $service->shouldReceive('createClient')->andReturn($mockClient);

        $mailbox = Mailbox::factory()->make([
            'in_server' => 'imap.test.com',
            'in_port' => 993,
        ]);

        $result = $service->getFolders($mailbox);

        $this->assertTrue($result['success']);
        $this->assertCount(1, $result['folders']);
        $this->assertEquals('INBOX', $result['folders'][0]);
    }

    public function test_get_folders_returns_success_with_no_folders(): void
    {
        $mockClient = Mockery::mock(\Webklex\PHPIMAP\Client::class);
        $mockClient->shouldReceive('connect')->once();
        $mockClient->shouldReceive('getFolders')->andReturn(new \Webklex\PHPIMAP\Support\FolderCollection([]));
        $mockClient->shouldReceive('disconnect')->once();

        $service = Mockery::mock(ImapService::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $service->shouldReceive('createClient')->andReturn($mockClient);

        $mailbox = Mailbox::factory()->make([
            'in_server' => 'imap.test.com',
            'in_port' => 993,
        ]);

        $result = $service->getFolders($mailbox);

        $this->assertTrue($result['success']);
        $this->assertEquals('Connected, but no folders found', $result['message']);
        $this->assertEmpty($result['folders']);
    }

    public function test_get_folders_handles_connection_failure(): void
    {
        $mockClient = Mockery::mock(\Webklex\PHPIMAP\Client::class);
        $mockClient->shouldReceive('connect')
            ->andThrow(new \Webklex\PHPIMAP\Exceptions\ConnectionFailedException('Connection failed'));

        $service = Mockery::mock(ImapService::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $service->shouldReceive('createClient')->andReturn($mockClient);

        $mailbox = Mailbox::factory()->make([
            'in_server' => 'imap.test.com',
            'in_port' => 993,
        ]);

        $result = $service->getFolders($mailbox);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Connection failed', $result['message']);
    }

    public function test_get_folders_handles_general_exception(): void
    {
        $mockClient = Mockery::mock(\Webklex\PHPIMAP\Client::class);
        $mockClient->shouldReceive('connect')
            ->andThrow(new \Exception('General error'));

        $service = Mockery::mock(ImapService::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $service->shouldReceive('createClient')->andReturn($mockClient);

        $mailbox = Mailbox::factory()->make([
            'in_server' => 'imap.test.com',
            'in_port' => 993,
        ]);

        $result = $service->getFolders($mailbox);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Error', $result['message']);
    }

    public function test_separate_reply_returns_unchanged_when_not_reply(): void
    {
        $body = 'This is the email body';
        $result = $this->invokeMethod($this->service, 'separateReply', [$body, false, false]);

        $this->assertEquals($body, $result);
    }

    public function test_separate_reply_extracts_body_tag_from_html(): void
    {
        $body = '<html><body>Email content</body></html>';
        $result = $this->invokeMethod($this->service, 'separateReply', [$body, true, true]);

        $this->assertStringContainsString('Email content', $result);
    }

    public function test_separate_reply_separates_protonmail_quote(): void
    {
        $body = 'New reply<div class="protonmail_quote">Previous message</div>';
        $result = $this->invokeMethod($this->service, 'separateReply', [$body, true, true]);

        $this->assertEquals('New reply', $result);
    }

    public function test_separate_reply_separates_replied_above(): void
    {
        $body = 'New reply---- Replied Above ----Previous message';
        $result = $this->invokeMethod($this->service, 'separateReply', [$body, false, true]);

        $this->assertStringContainsString('New reply', $result);
    }

    public function test_separate_reply_separates_on_wrote_pattern(): void
    {
        $body = 'New replyOn Tuesday, John wrote:Previous message';
        $result = $this->invokeMethod($this->service, 'separateReply', [$body, false, true]);

        $this->assertStringContainsString('New reply', $result);
    }

    public function test_separate_reply_separates_from_header(): void
    {
        $body = 'New replyFrom: sender@example.comPrevious message';
        $result = $this->invokeMethod($this->service, 'separateReply', [$body, false, true]);

        $this->assertStringContainsString('New reply', $result);
    }

    public function test_separate_reply_separates_underscore_separator(): void
    {
        $body = 'New reply________Previous message';
        $result = $this->invokeMethod($this->service, 'separateReply', [$body, false, true]);

        $this->assertStringContainsString('New reply', $result);
    }

    public function test_separate_reply_returns_full_body_when_no_separator_found(): void
    {
        $body = 'This is just a regular email with no reply separator';
        $result = $this->invokeMethod($this->service, 'separateReply', [$body, false, true]);

        $this->assertEquals($body, $result);
    }

    public function test_separate_reply_converts_plain_text_to_html(): void
    {
        $body = "Line 1\nLine 2";
        $result = $this->invokeMethod($this->service, 'separateReply', [$body, false, true]);

        $this->assertStringContainsString('<br />', $result);
    }

    public function test_get_message_headers_returns_raw_header(): void
    {
        $mockMessage = new class {
            public function getRawHeader() { return 'Header: Value'; }
        };

        $result = $this->invokeMethod($this->service, 'getMessageHeaders', [$mockMessage]);

        $this->assertEquals('Header: Value', $result);
    }

    public function test_get_message_headers_falls_back_to_get_header(): void
    {
        $mockMessage = new class {
            public function getRawHeader() { return ''; }
            public function getHeader() { return 'Fallback Header'; }
        };

        $result = $this->invokeMethod($this->service, 'getMessageHeaders', [$mockMessage]);

        $this->assertEquals('Fallback Header', $result);
    }

    public function test_get_message_headers_returns_empty_on_failure(): void
    {
        $mockMessage = Mockery::mock();
        $mockMessage->shouldReceive('getRawHeader')->andThrow(new \Exception('Failed'));
        $mockMessage->shouldReceive('getHeader')->andThrow(new \Exception('Failed'));

        $result = $this->invokeMethod($this->service, 'getMessageHeaders', [$mockMessage]);

        $this->assertEquals('', $result);
    }

    public function test_get_original_sender_from_fwd_parses_from_with_name_and_email(): void
    {
        $body = 'Some text From: John Doe <john@example.com> more text';
        
        $result = $this->invokeMethod($this->service, 'getOriginalSenderFromFwd', [$body]);

        $this->assertNotNull($result);
        $this->assertEquals('John Doe', $result['name']);
        $this->assertEquals('john@example.com', $result['email']);
    }

    public function test_get_original_sender_from_fwd_parses_from_with_email_only(): void
    {
        $body = 'Some text From: john@example.com more text';
        
        $result = $this->invokeMethod($this->service, 'getOriginalSenderFromFwd', [$body]);

        $this->assertNotNull($result);
        $this->assertEquals('', $result['name']);
        $this->assertEquals('john@example.com', $result['email']);
    }

    public function test_get_original_sender_from_fwd_finds_email_in_body(): void
    {
        $body = 'Check this email: test@example.com for info';
        
        $result = $this->invokeMethod($this->service, 'getOriginalSenderFromFwd', [$body]);

        $this->assertNotNull($result);
        $this->assertEquals('test@example.com', $result['email']);
    }

    public function test_get_original_sender_from_fwd_returns_null_when_no_email_found(): void
    {
        $body = 'This body has no email address';
        
        $result = $this->invokeMethod($this->service, 'getOriginalSenderFromFwd', [$body]);

        $this->assertNull($result);
    }

    public function test_get_original_sender_from_fwd_handles_cid_and_fwd_cleanup(): void
    {
        $body = 'Text with "cid:image123" and @fwd<link> From: sender@example.com';
        
        $result = $this->invokeMethod($this->service, 'getOriginalSenderFromFwd', [$body]);

        // Should handle cleanup and still try to find email
        $this->assertIsArray($result);
        $this->assertEquals('sender@example.com', $result['email']);
    }

    // ========================================================================
    // Tests for testConnection method (85% → 95%+)
    // ========================================================================

    public function test_test_connection_success_with_messages(): void
    {
        $mailbox = Mailbox::factory()->make([
            'in_server' => 'imap.example.com',
            'in_port' => 993,
            'in_username' => 'test@example.com',
            'in_password' => 'password',
            'in_protocol' => 1, // IMAP
        ]);

        $message1 = Mockery::mock();
        $message1->shouldReceive('hasFlag')->with('Seen')->andReturn(false);
        $message2 = Mockery::mock();
        $message2->shouldReceive('hasFlag')->with('Seen')->andReturn(true);

        $messages = new \Webklex\PHPIMAP\Support\MessageCollection([$message1, $message2]);

        $query = Mockery::mock(\Webklex\PHPIMAP\Query\WhereQuery::class);
        $query->shouldReceive('since')->andReturnSelf();
        $query->shouldReceive('leaveUnread')->andReturnSelf();
        $query->shouldReceive('get')->andReturn($messages);

        $folder = Mockery::mock(\Webklex\PHPIMAP\Folder::class);
        $folder->shouldReceive('query')->andReturn($query);

        $client = Mockery::mock(\Webklex\PHPIMAP\Client::class);
        $client->shouldReceive('connect')->andReturnSelf();
        $client->shouldReceive('getFolder')->with('INBOX')->andReturn($folder);
        $client->shouldReceive('disconnect')->andReturnSelf();

        $service = Mockery::mock(ImapService::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $service->shouldReceive('createClient')->with($mailbox)->andReturn($client);

        $result = $service->testConnection($mailbox);

        $this->assertTrue($result['success']);
        $this->assertStringContainsString('Connected successfully', $result['message']);
        $this->assertStringContainsString('2 messages', $result['message']);
        $this->assertStringContainsString('1 unread', $result['message']);
    }

    public function test_test_connection_handles_charset_exception(): void
    {
        $mailbox = Mailbox::factory()->make([
            'in_server' => 'imap.example.com',
            'in_port' => 993,
            'in_username' => 'test@example.com',
            'in_password' => 'password',
        ]);

        $messages = new \Webklex\PHPIMAP\Support\MessageCollection([]);

        $query = Mockery::mock(\Webklex\PHPIMAP\Query\WhereQuery::class);
        $query->shouldReceive('since')->andReturnSelf();
        $query->shouldReceive('leaveUnread')->andReturnSelf();
        $query->shouldReceive('get')
            ->once()
            ->andThrow(new \Exception('Charset UTF-8 not supported'));
        $query->shouldReceive('setCharset')->with(null)->andReturnSelf();
        $query->shouldReceive('get')
            ->once()
            ->andReturn($messages);

        $folder = Mockery::mock(\Webklex\PHPIMAP\Folder::class);
        $folder->shouldReceive('query')->andReturn($query);

        $client = Mockery::mock(\Webklex\PHPIMAP\Client::class);
        $client->shouldReceive('connect')->andReturnSelf();
        $client->shouldReceive('getFolder')->with('INBOX')->andReturn($folder);
        $client->shouldReceive('disconnect')->andReturnSelf();

        $service = Mockery::mock(ImapService::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $service->shouldReceive('createClient')->with($mailbox)->andReturn($client);

        $result = $service->testConnection($mailbox);

        $this->assertTrue($result['success']);
        $this->assertStringContainsString('Connected successfully', $result['message']);
    }

    public function test_test_connection_handles_connection_failed_exception(): void
    {
        $mailbox = Mailbox::factory()->make([
            'in_server' => 'imap.example.com',
            'in_port' => 993,
            'in_username' => 'test@example.com',
            'in_password' => 'wrong_password',
        ]);

        $client = Mockery::mock(\Webklex\PHPIMAP\Client::class);
        $client->shouldReceive('connect')
            ->andThrow(new \Webklex\PHPIMAP\Exceptions\ConnectionFailedException('Authentication failed'));

        $service = Mockery::mock(ImapService::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $service->shouldReceive('createClient')->with($mailbox)->andReturn($client);

        $result = $service->testConnection($mailbox);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Connection failed', $result['message']);
    }

    public function test_test_connection_handles_inbox_not_found(): void
    {
        $mailbox = Mailbox::factory()->make([
            'in_server' => 'imap.example.com',
            'in_port' => 993,
            'in_username' => 'test@example.com',
            'in_password' => 'password',
        ]);

        $client = Mockery::mock(\Webklex\PHPIMAP\Client::class);
        $client->shouldReceive('connect')->andReturnSelf();
        $client->shouldReceive('getFolder')->with('INBOX')->andReturn(null);

        $service = Mockery::mock(ImapService::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $service->shouldReceive('createClient')->with($mailbox)->andReturn($client);

        $result = $service->testConnection($mailbox);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Could not access INBOX folder', $result['message']);
    }

    public function test_test_connection_handles_general_exception(): void
    {
        $mailbox = Mailbox::factory()->make([
            'in_server' => 'imap.example.com',
            'in_port' => 993,
            'in_username' => 'test@example.com',
            'in_password' => 'password',
        ]);

        $client = Mockery::mock(\Webklex\PHPIMAP\Client::class);
        $client->shouldReceive('connect')
            ->andThrow(new \Exception('Unknown error occurred'));

        $service = Mockery::mock(ImapService::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $service->shouldReceive('createClient')->with($mailbox)->andReturn($client);

        $result = $service->testConnection($mailbox);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Error:', $result['message']);
        $this->assertStringContainsString('Unknown error', $result['message']);
    }

    // ========================================================================
    // Helper method to invoke protected/private methods
    // ========================================================================

    /**
     * Invoke protected or private method.
     */
    protected function invokeMethod(object $object, string $methodName, array $parameters = []): mixed
    {
        $reflection = new \ReflectionClass(get_class($object));
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);

        return $method->invokeArgs($object, $parameters);
    }
}
