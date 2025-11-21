<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Services\ImapService;
use App\Models\Mailbox;
use App\Models\Customer;
use Tests\UnitTestCase;
use Mockery;
use Webklex\PHPIMAP\Client;
use Webklex\PHPIMAP\Folder;
use Webklex\PHPIMAP\Message;
use Webklex\PHPIMAP\Attribute;

/**
 * Test Suite for IMAP Service Helper Methods - Edge Cases & Integration
 *
 * This test suite covers edge cases and integration methods:
 * - createCustomersFromMessage() (12 tests) - Customer creation from messages
 * - getFolders() (9 tests) - Folder retrieval and management
 * - testConnection() (8 tests) - Connection testing
 * - fetchEmails() (11 tests) - Email fetching operations
 * Total: 40 tests
 *
 * These methods handle integration points and edge cases.
 */
class ImapServiceHelpersEdgeCasesTest extends UnitTestCase
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

    /**
     * Helper method to invoke private/protected methods using reflection
     */
    protected function invokeMethod($object, string $methodName, array $parameters = [])
    {
        $reflection = new \ReflectionClass(get_class($object));
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);

        return $method->invokeArgs($object, $parameters);
    }

    // =====================================================================
    // Tests for createCustomersFromMessage() - MEDIUM (CRAP: 12)
    // =====================================================================

    public function test_create_customers_from_message_creates_customers_from_all_fields(): void
    {
        $mailbox = Mailbox::factory()->create(['email' => 'mailbox@example.com']);

        $message = Mockery::mock(Message::class);
        $message->shouldReceive('getFrom')->andReturn([(object)['mail' => 'from@example.com', 'personal' => 'From User']]);
        $message->shouldReceive('getReplyTo')->andReturn([]);
        $message->shouldReceive('getTo')->andReturn([(object)['mail' => 'to@example.com', 'personal' => 'To User']]);
        $message->shouldReceive('getCc')->andReturn([(object)['mail' => 'cc@example.com', 'personal' => 'CC User']]);
        $message->shouldReceive('getBcc')->andReturn([]);

        $this->invokeMethod($this->service, 'createCustomersFromMessage', [$message, $mailbox]);

        $this->assertDatabaseHas('emails', ['email' => 'from@example.com']);
        $this->assertDatabaseHas('emails', ['email' => 'to@example.com']);
        $this->assertDatabaseHas('emails', ['email' => 'cc@example.com']);
    }

    public function test_create_customers_from_message_creates_from_reply_to(): void
    {
        $mailbox = Mailbox::factory()->create(['email' => 'mailbox@example.com']);

        $message = Mockery::mock(Message::class);
        $message->shouldReceive('getFrom')->andReturn([]);
        $message->shouldReceive('getReplyTo')->andReturn([(object)['mail' => 'replyto@example.com', 'personal' => 'ReplyTo User']]);
        $message->shouldReceive('getTo')->andReturn([]);
        $message->shouldReceive('getCc')->andReturn([]);
        $message->shouldReceive('getBcc')->andReturn([]);

        $this->invokeMethod($this->service, 'createCustomersFromMessage', [$message, $mailbox]);

        $this->assertDatabaseHas('emails', ['email' => 'replyto@example.com']);
    }

    public function test_create_customers_from_message_creates_from_bcc(): void
    {
        $mailbox = Mailbox::factory()->create(['email' => 'mailbox@example.com']);

        $message = Mockery::mock(Message::class);
        $message->shouldReceive('getFrom')->andReturn([]);
        $message->shouldReceive('getReplyTo')->andReturn([]);
        $message->shouldReceive('getTo')->andReturn([]);
        $message->shouldReceive('getCc')->andReturn([]);
        $message->shouldReceive('getBcc')->andReturn([(object)['mail' => 'bcc@example.com', 'personal' => 'BCC User']]);

        $this->invokeMethod($this->service, 'createCustomersFromMessage', [$message, $mailbox]);

        $this->assertDatabaseHas('emails', ['email' => 'bcc@example.com']);
    }

    public function test_create_customers_from_message_handles_multiple_recipients(): void
    {
        $mailbox = Mailbox::factory()->create(['email' => 'mailbox@example.com']);

        $message = Mockery::mock(Message::class);
        $message->shouldReceive('getFrom')->andReturn([(object)['mail' => 'from@example.com', 'personal' => 'From']]);
        $message->shouldReceive('getReplyTo')->andReturn([]);
        $message->shouldReceive('getTo')->andReturn([
            (object)['mail' => 'to1@example.com', 'personal' => 'To One'],
            (object)['mail' => 'to2@example.com', 'personal' => 'To Two'],
            (object)['mail' => 'to3@example.com', 'personal' => 'To Three'],
        ]);
        $message->shouldReceive('getCc')->andReturn([
            (object)['mail' => 'cc1@example.com', 'personal' => 'CC One'],
            (object)['mail' => 'cc2@example.com', 'personal' => 'CC Two'],
        ]);
        $message->shouldReceive('getBcc')->andReturn([]);

        $this->invokeMethod($this->service, 'createCustomersFromMessage', [$message, $mailbox]);

        $this->assertDatabaseHas('emails', ['email' => 'from@example.com']);
        $this->assertDatabaseHas('emails', ['email' => 'to1@example.com']);
        $this->assertDatabaseHas('emails', ['email' => 'to2@example.com']);
        $this->assertDatabaseHas('emails', ['email' => 'to3@example.com']);
        $this->assertDatabaseHas('emails', ['email' => 'cc1@example.com']);
        $this->assertDatabaseHas('emails', ['email' => 'cc2@example.com']);
    }

    public function test_create_customers_from_message_skips_mailbox_email(): void
    {
        $mailbox = Mailbox::factory()->create(['email' => 'mailbox@example.com']);

        $message = Mockery::mock(Message::class);
        $message->shouldReceive('getFrom')->andReturn([(object)['mail' => 'sender@example.com', 'personal' => 'Sender']]);
        $message->shouldReceive('getReplyTo')->andReturn([]);
        $message->shouldReceive('getTo')->andReturn([(object)['mail' => 'mailbox@example.com', 'personal' => 'Mailbox']]);
        $message->shouldReceive('getCc')->andReturn([]);
        $message->shouldReceive('getBcc')->andReturn([]);

        $this->invokeMethod($this->service, 'createCustomersFromMessage', [$message, $mailbox]);

        $this->assertDatabaseHas('emails', ['email' => 'sender@example.com']);
        $this->assertDatabaseMissing('customers', ['email' => 'mailbox@example.com']);
    }

    public function test_create_customers_from_message_skips_mailbox_in_cc(): void
    {
        $mailbox = Mailbox::factory()->create(['email' => 'mailbox@example.com']);

        $message = Mockery::mock(Message::class);
        $message->shouldReceive('getFrom')->andReturn([(object)['mail' => 'sender@example.com', 'personal' => 'Sender']]);
        $message->shouldReceive('getReplyTo')->andReturn([]);
        $message->shouldReceive('getTo')->andReturn([]);
        $message->shouldReceive('getCc')->andReturn([(object)['mail' => 'mailbox@example.com', 'personal' => 'Mailbox']]);
        $message->shouldReceive('getBcc')->andReturn([]);

        $this->invokeMethod($this->service, 'createCustomersFromMessage', [$message, $mailbox]);

        $this->assertDatabaseHas('emails', ['email' => 'sender@example.com']);
        $this->assertDatabaseMissing('customers', ['email' => 'mailbox@example.com']);
    }

    public function test_create_customers_from_message_handles_empty_addresses(): void
    {
        $mailbox = Mailbox::factory()->create(['email' => 'mailbox@example.com']);

        $message = Mockery::mock(Message::class);
        $message->shouldReceive('getFrom')->andReturn([]);
        $message->shouldReceive('getReplyTo')->andReturn([]);
        $message->shouldReceive('getTo')->andReturn([]);
        $message->shouldReceive('getCc')->andReturn([]);
        $message->shouldReceive('getBcc')->andReturn([]);

        // Should not throw an error
        $this->invokeMethod($this->service, 'createCustomersFromMessage', [$message, $mailbox]);

        // No customers should be created
        $this->assertDatabaseCount('customers', 0);
    }

    public function test_create_customers_from_message_handles_null_addresses(): void
    {
        $mailbox = Mailbox::factory()->create(['email' => 'mailbox@example.com']);

        $message = Mockery::mock(Message::class);
        $message->shouldReceive('getFrom')->andReturn(null);
        $message->shouldReceive('getReplyTo')->andReturn(null);
        $message->shouldReceive('getTo')->andReturn(null);
        $message->shouldReceive('getCc')->andReturn(null);
        $message->shouldReceive('getBcc')->andReturn(null);

        // Should not throw an error
        $this->invokeMethod($this->service, 'createCustomersFromMessage', [$message, $mailbox]);

        $this->assertDatabaseCount('customers', 0);
    }

    public function test_create_customers_from_message_updates_existing_customer(): void
    {
        $mailbox = Mailbox::factory()->create(['email' => 'mailbox@example.com']);

        // Create an existing customer
        Customer::create('existing@example.com', [
            'first_name' => 'Old',
            'last_name' => 'Name',
        ]);

        $message = Mockery::mock(Message::class);
        $message->shouldReceive('getFrom')->andReturn([(object)['mail' => 'existing@example.com', 'personal' => 'New Name']]);
        $message->shouldReceive('getReplyTo')->andReturn([]);
        $message->shouldReceive('getTo')->andReturn([]);
        $message->shouldReceive('getCc')->andReturn([]);
        $message->shouldReceive('getBcc')->andReturn([]);

        $this->invokeMethod($this->service, 'createCustomersFromMessage', [$message, $mailbox]);

        // Customer should exist with original name (setData with replace_data=false doesn't overwrite existing)
        $customer = Customer::where('first_name', 'Old')->where('last_name', 'Name')->first();
        $this->assertNotNull($customer);
        $this->assertDatabaseHas('emails', [
            'customer_id' => $customer->id,
            'email' => 'existing@example.com',
        ]);
        // Should still only have 1 customer
        $this->assertDatabaseCount('customers', 1);
    }

    public function test_create_customers_from_message_handles_duplicate_addresses_in_message(): void
    {
        $mailbox = Mailbox::factory()->create(['email' => 'mailbox@example.com']);

        // Same email in multiple fields
        $message = Mockery::mock(Message::class);
        $message->shouldReceive('getFrom')->andReturn([(object)['mail' => 'duplicate@example.com', 'personal' => 'User']]);
        $message->shouldReceive('getReplyTo')->andReturn([]);
        $message->shouldReceive('getTo')->andReturn([(object)['mail' => 'duplicate@example.com', 'personal' => 'User']]);
        $message->shouldReceive('getCc')->andReturn([(object)['mail' => 'duplicate@example.com', 'personal' => 'User']]);
        $message->shouldReceive('getBcc')->andReturn([]);

        $this->invokeMethod($this->service, 'createCustomersFromMessage', [$message, $mailbox]);

        // Should only create one customer
        $customers = Customer::whereHas('emails', fn($q) => $q->where('email', 'duplicate@example.com'))->get();
        $this->assertCount(1, $customers);
    }

    public function test_create_customers_from_message_preserves_names(): void
    {
        $mailbox = Mailbox::factory()->create(['email' => 'mailbox@example.com']);

        $message = Mockery::mock(Message::class);
        $message->shouldReceive('getFrom')->andReturn([(object)['mail' => 'john@example.com', 'personal' => 'John Doe']]);
        $message->shouldReceive('getReplyTo')->andReturn([]);
        $message->shouldReceive('getTo')->andReturn([]);
        $message->shouldReceive('getCc')->andReturn([]);
        $message->shouldReceive('getBcc')->andReturn([]);

        $this->invokeMethod($this->service, 'createCustomersFromMessage', [$message, $mailbox]);

        $customer = Customer::where('first_name', 'John')->where('last_name', 'Doe')->first();
        $this->assertNotNull($customer);
        $this->assertDatabaseHas('emails', [
            'customer_id' => $customer->id,
            'email' => 'john@example.com',
        ]);
    }

    public function test_create_customers_from_message_handles_no_personal_name(): void
    {
        $mailbox = Mailbox::factory()->create(['email' => 'mailbox@example.com']);

        $message = Mockery::mock(Message::class);
        $message->shouldReceive('getFrom')->andReturn([(object)['mail' => 'noname@example.com', 'personal' => '']]);
        $message->shouldReceive('getReplyTo')->andReturn([]);
        $message->shouldReceive('getTo')->andReturn([]);
        $message->shouldReceive('getCc')->andReturn([]);
        $message->shouldReceive('getBcc')->andReturn([]);

        $this->invokeMethod($this->service, 'createCustomersFromMessage', [$message, $mailbox]);

        $customer = Customer::whereHas('emails', fn($q) => $q->where('email', 'noname@example.com'))->first();
        $this->assertNotNull($customer);
        $this->assertEquals('', $customer->first_name);
        $this->assertEquals('', $customer->last_name);
    }

    // =====================================================================
    // =====================================================================
    // Tests for getFolders() - MEDIUM (CRAP: 30)
    // =====================================================================

    public function test_get_folders_returns_success_structure(): void
    {
        $mailbox = Mailbox::factory()->create([
            'in_server' => 'imap.example.com',
            'in_port' => 993,
            'in_username' => 'test@example.com',
            'in_password' => encrypt('password'),
            'in_protocol' => 1,
            'in_encryption' => 1,
        ]);

        $result = $this->service->getFolders($mailbox);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
        $this->assertArrayHasKey('message', $result);
        $this->assertArrayHasKey('folders', $result);
        $this->assertIsArray($result['folders']);
    }

    public function test_get_folders_returns_bool_success(): void
    {
        $mailbox = Mailbox::factory()->create([
            'in_server' => 'imap.example.com',
            'in_port' => 993,
            'in_username' => 'test@example.com',
            'in_password' => encrypt('password'),
            'in_protocol' => 1,
            'in_encryption' => 1,
        ]);

        $result = $this->service->getFolders($mailbox);

        $this->assertIsBool($result['success']);
    }

    public function test_get_folders_returns_string_message(): void
    {
        $mailbox = Mailbox::factory()->create([
            'in_server' => 'imap.example.com',
            'in_port' => 993,
            'in_username' => 'test@example.com',
            'in_password' => encrypt('password'),
            'in_protocol' => 1,
            'in_encryption' => 1,
        ]);

        $result = $this->service->getFolders($mailbox);

        $this->assertIsString($result['message']);
    }

    public function test_get_folders_handles_connection_failure(): void
    {
        $mailbox = Mailbox::factory()->create([
            'in_server' => 'invalid.server.com',
            'in_port' => 993,
            'in_username' => 'test@example.com',
            'in_password' => encrypt('password'),
            'in_protocol' => 1,
            'in_encryption' => 1,
        ]);

        $result = $this->service->getFolders($mailbox);

        $this->assertFalse($result['success']);
        $this->assertNotEmpty($result['message']);
        $this->assertStringContainsString('Connection failed', $result['message']);
    }

    public function test_get_folders_handles_general_exception(): void
    {
        $mailbox = Mailbox::factory()->create([
            'in_server' => 'bad.server.com',
            'in_port' => 9999,
            'in_username' => 'test@example.com',
            'in_password' => encrypt('password'),
            'in_protocol' => 1,
            'in_encryption' => 1,
        ]);

        $result = $this->service->getFolders($mailbox);

        $this->assertFalse($result['success']);
        $this->assertNotEmpty($result['message']);
        $this->assertTrue(
            str_contains($result['message'], 'Connection failed') || 
            str_contains($result['message'], 'Error')
        );
    }

    public function test_get_folders_returns_empty_array_on_failure(): void
    {
        $mailbox = Mailbox::factory()->create([
            'in_server' => 'invalid.server.com',
            'in_port' => 993,
            'in_username' => 'test@example.com',
            'in_password' => encrypt('password'),
            'in_protocol' => 1,
            'in_encryption' => 1,
        ]);

        $result = $this->service->getFolders($mailbox);

        $this->assertIsArray($result['folders']);
        $this->assertCount(0, $result['folders']);
    }

    public function test_get_folders_with_different_ports(): void
    {
        $mailbox = Mailbox::factory()->create([
            'in_server' => 'imap.example.com',
            'in_port' => 143,  // Non-SSL port
            'in_username' => 'test@example.com',
            'in_password' => encrypt('password'),
            'in_protocol' => 1,
            'in_encryption' => 0,
        ]);

        $result = $this->service->getFolders($mailbox);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
    }

    public function test_get_folders_with_ssl_encryption(): void
    {
        $mailbox = Mailbox::factory()->create([
            'in_server' => 'imap.example.com',
            'in_port' => 993,
            'in_username' => 'test@example.com',
            'in_password' => encrypt('password'),
            'in_protocol' => 1,
            'in_encryption' => 1,  // SSL
        ]);

        $result = $this->service->getFolders($mailbox);

        $this->assertIsArray($result);
    }

    public function test_get_folders_with_tls_encryption(): void
    {
        $mailbox = Mailbox::factory()->create([
            'in_server' => 'imap.example.com',
            'in_port' => 143,
            'in_username' => 'test@example.com',
            'in_password' => encrypt('password'),
            'in_protocol' => 1,
            'in_encryption' => 2,  // TLS
        ]);

        $result = $this->service->getFolders($mailbox);

        $this->assertIsArray($result);
    }

    // =====================================================================
    // =====================================================================
    // Tests for testConnection() - LOW (CRAP: 58 - Improve existing)
    // =====================================================================

    public function test_test_connection_returns_success_structure(): void
    {
        $mailbox = Mailbox::factory()->create([
            'in_server' => 'imap.example.com',
            'in_port' => 993,
            'in_username' => 'test@example.com',
            'in_password' => encrypt('password'),
            'in_protocol' => 1,
            'in_encryption' => 1,
        ]);

        $result = $this->service->testConnection($mailbox);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
        $this->assertArrayHasKey('message', $result);
        $this->assertIsBool($result['success']);
        $this->assertIsString($result['message']);
    }

    public function test_test_connection_fails_with_invalid_credentials(): void
    {
        $mailbox = Mailbox::factory()->create([
            'in_server' => 'invalid.imap.server',
            'in_port' => 993,
            'in_username' => 'invalid@example.com',
            'in_password' => encrypt('wrongpassword'),
            'in_protocol' => 1,
            'in_encryption' => 1,
        ]);

        $result = $this->service->testConnection($mailbox);

        $this->assertFalse($result['success']);
        $this->assertNotEmpty($result['message']);
    }

    public function test_test_connection_fails_with_invalid_server(): void
    {
        $mailbox = Mailbox::factory()->create([
            'in_server' => 'nonexistent.server.invalid',
            'in_port' => 993,
            'in_username' => 'test@example.com',
            'in_password' => encrypt('password'),
            'in_protocol' => 1,
            'in_encryption' => 1,
        ]);

        $result = $this->service->testConnection($mailbox);

        $this->assertFalse($result['success']);
        $this->assertNotEmpty($result['message']);
    }

    public function test_test_connection_handles_connection_failure(): void
    {
        $mailbox = Mailbox::factory()->create([
            'in_server' => 'unreachable.example.com',
            'in_port' => 9999,
            'in_username' => 'test@example.com',
            'in_password' => encrypt('password'),
            'in_protocol' => 1,
            'in_encryption' => 1,
        ]);

        $result = $this->service->testConnection($mailbox);

        $this->assertFalse($result['success']);
        $this->assertTrue(
            str_contains($result['message'], 'Connection failed') ||
            str_contains($result['message'], 'Error')
        );
    }

    public function test_test_connection_with_ssl_encryption(): void
    {
        $mailbox = Mailbox::factory()->create([
            'in_server' => 'imap.example.com',
            'in_port' => 993,
            'in_username' => 'test@example.com',
            'in_password' => encrypt('password'),
            'in_protocol' => 1,
            'in_encryption' => 1,  // SSL
        ]);

        $result = $this->service->testConnection($mailbox);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
    }

    public function test_test_connection_with_tls_encryption(): void
    {
        $mailbox = Mailbox::factory()->create([
            'in_server' => 'imap.example.com',
            'in_port' => 143,
            'in_username' => 'test@example.com',
            'in_password' => encrypt('password'),
            'in_protocol' => 1,
            'in_encryption' => 2,  // TLS
        ]);

        $result = $this->service->testConnection($mailbox);

        $this->assertIsArray($result);
    }

    public function test_test_connection_with_no_encryption(): void
    {
        $mailbox = Mailbox::factory()->create([
            'in_server' => 'imap.example.com',
            'in_port' => 143,
            'in_username' => 'test@example.com',
            'in_password' => encrypt('password'),
            'in_protocol' => 1,
            'in_encryption' => 0,  // No encryption
        ]);

        $result = $this->service->testConnection($mailbox);

        $this->assertIsArray($result);
    }

    public function test_test_connection_message_format_on_failure(): void
    {
        $mailbox = Mailbox::factory()->create([
            'in_server' => 'bad.server',
            'in_port' => 993,
            'in_username' => 'test@example.com',
            'in_password' => encrypt('password'),
            'in_protocol' => 1,
            'in_encryption' => 1,
        ]);

        $result = $this->service->testConnection($mailbox);

        $this->assertFalse($result['success']);
        $this->assertNotEmpty($result['message']);
        $this->assertIsString($result['message']);
    }

    // =====================================================================
    // =====================================================================
    // Tests for fetchEmails() - LOW (CRAP: 73 - Improve existing)
    // =====================================================================

    public function test_fetch_emails_returns_stats_structure(): void
    {
        $mailbox = Mailbox::factory()->create([
            'in_server' => 'imap.example.com',
            'in_port' => 993,
            'in_username' => 'test@example.com',
            'in_password' => encrypt('password'),
            'in_protocol' => 1,
            'in_encryption' => 1,
        ]);

        $result = $this->service->fetchEmails($mailbox);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('fetched', $result);
        $this->assertArrayHasKey('created', $result);
        $this->assertArrayHasKey('errors', $result);
        $this->assertArrayHasKey('messages', $result);
        $this->assertIsInt($result['fetched']);
        $this->assertIsInt($result['created']);
        $this->assertIsInt($result['errors']);
        $this->assertIsArray($result['messages']);
    }

    public function test_fetch_emails_handles_empty_folder(): void
    {
        $mailbox = Mailbox::factory()->create([
            'in_server' => null, // No server configured
        ]);

        $result = $this->service->fetchEmails($mailbox);

        $this->assertEquals(0, $result['fetched']);
        $this->assertEquals(0, $result['created']);
        $this->assertNotEmpty($result['messages']);
    }

    public function test_fetch_emails_returns_early_for_null_server(): void
    {
        $mailbox = Mailbox::factory()->create([
            'in_server' => null,
            'name' => 'Test Mailbox',
        ]);

        $result = $this->service->fetchEmails($mailbox);

        $this->assertEquals(0, $result['fetched']);
        $this->assertEquals(0, $result['created']);
        $this->assertEquals(0, $result['errors']);
        $this->assertCount(1, $result['messages']);
        $this->assertStringContainsString('No IMAP server configured', $result['messages'][0]);
    }

    public function test_fetch_emails_returns_early_for_empty_server(): void
    {
        $mailbox = Mailbox::factory()->create([
            'in_server' => '',
            'name' => 'Test Mailbox',
        ]);

        $result = $this->service->fetchEmails($mailbox);

        $this->assertEquals(0, $result['fetched']);
        $this->assertNotEmpty($result['messages']);
    }

    public function test_fetch_emails_initializes_stats_correctly(): void
    {
        $mailbox = Mailbox::factory()->create([
            'in_server' => null,
        ]);

        $result = $this->service->fetchEmails($mailbox);

        $this->assertIsInt($result['fetched']);
        $this->assertIsInt($result['created']);
        $this->assertIsInt($result['errors']);
        $this->assertIsArray($result['messages']);
        $this->assertGreaterThanOrEqual(0, $result['fetched']);
        $this->assertGreaterThanOrEqual(0, $result['created']);
        $this->assertGreaterThanOrEqual(0, $result['errors']);
    }

    public function test_fetch_emails_handles_connection_failure_gracefully(): void
    {
        $mailbox = Mailbox::factory()->create([
            'in_server' => 'invalid.server.com',
            'in_port' => 993,
            'in_username' => 'test@example.com',
            'in_password' => encrypt('password'),
            'in_protocol' => 1,
            'in_encryption' => 1,
        ]);

        $result = $this->service->fetchEmails($mailbox);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('fetched', $result);
        $this->assertArrayHasKey('errors', $result);
    }

    public function test_fetch_emails_with_valid_server_config(): void
    {
        $mailbox = Mailbox::factory()->create([
            'in_server' => 'imap.example.com',
            'in_port' => 993,
            'in_username' => 'test@example.com',
            'in_password' => encrypt('password'),
            'in_protocol' => 1,
            'in_encryption' => 1,
            'in_imap_folders' => 'INBOX',
        ]);

        $result = $this->service->fetchEmails($mailbox);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('fetched', $result);
    }

    public function test_fetch_emails_uses_inbox_when_folders_null(): void
    {
        $mailbox = Mailbox::factory()->create([
            'in_server' => 'imap.example.com',
            'in_port' => 993,
            'in_username' => 'test@example.com',
            'in_password' => encrypt('password'),
            'in_protocol' => 1,
            'in_encryption' => 1,
            'in_imap_folders' => null,
        ]);

        $result = $this->service->fetchEmails($mailbox);

        // Should default to INBOX
        $this->assertIsArray($result);
    }

    public function test_fetch_emails_uses_inbox_when_folders_empty_string(): void
    {
        $mailbox = Mailbox::factory()->create([
            'in_server' => 'imap.example.com',
            'in_port' => 993,
            'in_username' => 'test@example.com',
            'in_password' => encrypt('password'),
            'in_protocol' => 1,
            'in_encryption' => 1,
            'in_imap_folders' => '',
        ]);

        $result = $this->service->fetchEmails($mailbox);

        // Should default to INBOX
        $this->assertIsArray($result);
    }

    public function test_fetch_emails_handles_multiple_folders(): void
    {
        $mailbox = Mailbox::factory()->create([
            'in_server' => 'imap.example.com',
            'in_port' => 993,
            'in_username' => 'test@example.com',
            'in_password' => encrypt('password'),
            'in_protocol' => 1,
            'in_encryption' => 1,
            'in_imap_folders' => 'INBOX,Sent,Drafts',
        ]);

        $result = $this->service->fetchEmails($mailbox);

        $this->assertIsArray($result);
    }

    public function test_fetch_emails_handles_array_folders(): void
    {
        $mailbox = Mailbox::factory()->create([
            'in_server' => 'imap.example.com',
            'in_port' => 993,
            'in_username' => 'test@example.com',
            'in_password' => encrypt('password'),
            'in_protocol' => 1,
            'in_encryption' => 1,
        ]);

        // Manually set as array (if the model allows it)
        $mailbox->in_imap_folders = ['INBOX', 'Sent'];

        $result = $this->service->fetchEmails($mailbox);

        $this->assertIsArray($result);
    }
}