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
        Mockery::close();
        parent::tearDown();
    }

    // ========================================================================
    // Tests for extractSenderInfo method
    // ========================================================================

    public function test_extract_sender_info_returns_correct_structure(): void
    {
        $message = Mockery::mock(Message::class);
        $from = Mockery::mock();
        $from->shouldReceive('toArray')->andReturn([
            (object) ['mail' => 'test@example.com', 'personal' => 'Test User'],
        ]);
        $message->shouldReceive('getFrom')->andReturn($from);

        Log::shouldReceive('debug')->twice();

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
        $from = Mockery::mock();
        $from->shouldReceive('get')->andReturn([$addressObj]);
        $message->shouldReceive('getFrom')->andReturn($from);

        Log::shouldReceive('debug')->twice();

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
    // Tests for createCustomerFromName method
    // ========================================================================

    public function test_create_customer_from_name_creates_customer(): void
    {
        $customer = $this->invokeMethod($this->service, 'createCustomerFromName', [
            'newuser@example.com',
            'John Doe',
        ]);

        $this->assertInstanceOf(Customer::class, $customer);
        $this->assertEquals('newuser@example.com', $customer->emails[0]);
    }

    public function test_create_customer_from_name_limits_first_name_length(): void
    {
        Log::shouldReceive('debug')->once();

        $longName = str_repeat('a', 25).' '.str_repeat('b', 35);
        
        $customer = $this->invokeMethod($this->service, 'createCustomerFromName', [
            'test@example.com',
            $longName,
        ]);

        $this->assertInstanceOf(Customer::class, $customer);
        // First name should be limited to 20 chars
        $this->assertLessThanOrEqual(20, strlen($customer->first_name));
    }

    public function test_create_customer_from_name_handles_empty_name(): void
    {
        Log::shouldReceive('debug')->once();

        $customer = $this->invokeMethod($this->service, 'createCustomerFromName', [
            'noname@example.com',
            '',
        ]);

        $this->assertInstanceOf(Customer::class, $customer);
        $this->assertEquals('', $customer->first_name);
    }

    public function test_create_customer_from_name_handles_single_name(): void
    {
        Log::shouldReceive('debug')->once();

        $customer = $this->invokeMethod($this->service, 'createCustomerFromName', [
            'single@example.com',
            'Madonna',
        ]);

        $this->assertInstanceOf(Customer::class, $customer);
        $this->assertEquals('Madonna', $customer->first_name);
        $this->assertEquals('', $customer->last_name);
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
        $customer = Customer::factory()->create();
        
        $message = Mockery::mock(Message::class);
        $message->shouldReceive('getHeader')->andReturn(null);
        $message->shouldReceive('getTextBody')->andReturn('Test body');

        $result = $this->invokeMethod($this->service, 'findOrCreateConversation', [
            $mailbox,
            $message,
            $customer,
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
        $customer = Customer::factory()->create();
        
        // Create existing conversation and thread
        $existingConversation = Conversation::factory()->create([
            'mailbox_id' => $mailbox->id,
            'customer_id' => $customer->id,
        ]);
        
        $parentThread = Thread::factory()->create([
            'conversation_id' => $existingConversation->id,
            'message_id' => '<parent@example.com>',
        ]);

        $message = Mockery::mock(Message::class);
        $header = Mockery::mock();
        $inReplyTo = Mockery::mock();
        $inReplyTo->shouldReceive('first')->andReturn('<parent@example.com>');
        $header->shouldReceive('get')->with('in_reply_to')->andReturn($inReplyTo);
        $header->shouldReceive('get')->with('references')->andReturn(null);
        $message->shouldReceive('getHeader')->andReturn($header);

        Log::shouldReceive('debug')->atLeast()->once();

        $result = $this->invokeMethod($this->service, 'findOrCreateConversation', [
            $mailbox,
            $message,
            $customer,
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
        $customer = Customer::factory()->create();
        
        $message = Mockery::mock(Message::class);
        $message->shouldReceive('getHeader')->andReturn(null);
        $message->shouldReceive('getTextBody')->andReturn('Test');

        $this->invokeMethod($this->service, 'findOrCreateConversation', [
            $mailbox,
            $message,
            $customer,
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
        $attachment = Mockery::mock(Attachment::class);
        $attachment->disposition = 'inline';
        
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
        $mockFolder = Mockery::mock();
        $mockFolder->full_name = 'INBOX';

        $mockClient = Mockery::mock();
        $mockClient->shouldReceive('connect')->once();
        $mockClient->shouldReceive('getFolders')->andReturn([$mockFolder]);
        $mockClient->shouldReceive('disconnect')->once();

        $service = Mockery::mock(ImapService::class)->makePartial();
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
        $mockClient = Mockery::mock();
        $mockClient->shouldReceive('connect')->once();
        $mockClient->shouldReceive('getFolders')->andReturn([]);
        $mockClient->shouldReceive('disconnect')->once();

        $service = Mockery::mock(ImapService::class)->makePartial();
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
        $mockClient = Mockery::mock();
        $mockClient->shouldReceive('connect')
            ->andThrow(new \Webklex\PHPIMAP\Exceptions\ConnectionFailedException('Connection failed'));

        $service = Mockery::mock(ImapService::class)->makePartial();
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
        $mockClient = Mockery::mock();
        $mockClient->shouldReceive('connect')
            ->andThrow(new \Exception('General error'));

        $service = Mockery::mock(ImapService::class)->makePartial();
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
        $mockMessage = Mockery::mock();
        $mockMessage->shouldReceive('getRawHeader')->andReturn('Header: Value');

        $result = $this->invokeMethod($this->service, 'getMessageHeaders', [$mockMessage]);

        $this->assertEquals('Header: Value', $result);
    }

    public function test_get_message_headers_falls_back_to_get_header(): void
    {
        $mockMessage = Mockery::mock();
        $mockMessage->shouldReceive('getRawHeader')->andReturn('');
        $mockMessage->shouldReceive('getHeader')->andReturn('Fallback Header');

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
        $body = 'Text with "cid:image123" and @fwd<link>';
        
        $result = $this->invokeMethod($this->service, 'getOriginalSenderFromFwd', [$body]);

        // Should handle cleanup and still try to find email
        $this->assertIsArray($result);
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
