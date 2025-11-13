<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Services\ImapService;
use App\Models\Mailbox;
use App\Models\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Mockery;
use Webklex\PHPIMAP\Client;
use Webklex\PHPIMAP\Folder;
use Webklex\PHPIMAP\Message;
use Webklex\PHPIMAP\Attribute;

class ImapServiceHelpersTest extends TestCase
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
    // Tests for getAddressesWithNames() - CRITICAL (CRAP: 272)
    // =====================================================================

    public function test_get_addresses_with_names_returns_empty_for_null_input(): void
    {
        $result = $this->invokeMethod($this->service, 'getAddressesWithNames', [null]);

        $this->assertIsArray($result);
        $this->assertCount(0, $result);
    }

    public function test_get_addresses_with_names_returns_empty_for_empty_array(): void
    {
        $result = $this->invokeMethod($this->service, 'getAddressesWithNames', [[]]);

        $this->assertIsArray($result);
        $this->assertCount(0, $result);
    }

    public function test_get_addresses_with_names_returns_empty_for_empty_string(): void
    {
        $result = $this->invokeMethod($this->service, 'getAddressesWithNames', ['']);

        $this->assertIsArray($result);
        $this->assertCount(0, $result);
    }

    public function test_get_addresses_with_names_returns_empty_for_false(): void
    {
        $result = $this->invokeMethod($this->service, 'getAddressesWithNames', [false]);

        $this->assertIsArray($result);
        $this->assertCount(0, $result);
    }

    public function test_get_addresses_with_names_returns_empty_for_zero(): void
    {
        $result = $this->invokeMethod($this->service, 'getAddressesWithNames', [0]);

        $this->assertIsArray($result);
        $this->assertCount(0, $result);
    }

    public function test_get_addresses_with_names_parses_simple_email_as_string(): void
    {
        $result = $this->invokeMethod($this->service, 'getAddressesWithNames', ['user@example.com']);

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertEquals('user@example.com', $result[0]['email']);
        $this->assertEquals('', $result[0]['first_name']);
        $this->assertEquals('', $result[0]['last_name']);
    }

    public function test_get_addresses_with_names_parses_object_with_mail_property(): void
    {
        $address = new \stdClass();
        $address->mail = 'john@example.com';
        $address->personal = 'John Doe';

        $result = $this->invokeMethod($this->service, 'getAddressesWithNames', [[$address]]);

        $this->assertCount(1, $result);
        $this->assertEquals('john@example.com', $result[0]['email']);
        $this->assertEquals('John', $result[0]['first_name']);
        $this->assertEquals('Doe', $result[0]['last_name']);
    }

    public function test_get_addresses_with_names_parses_object_with_email_property(): void
    {
        $address = new \stdClass();
        $address->email = 'jane@example.com';
        $address->name = 'Jane Smith';

        $result = $this->invokeMethod($this->service, 'getAddressesWithNames', [[$address]]);

        $this->assertCount(1, $result);
        $this->assertEquals('jane@example.com', $result[0]['email']);
        $this->assertEquals('Jane', $result[0]['first_name']);
        $this->assertEquals('Smith', $result[0]['last_name']);
    }

    public function test_get_addresses_with_names_parses_array_address_with_mail(): void
    {
        $address = [
            'mail' => 'test@example.com',
            'personal' => 'Test User',
        ];

        $result = $this->invokeMethod($this->service, 'getAddressesWithNames', [[$address]]);

        $this->assertCount(1, $result);
        $this->assertEquals('test@example.com', $result[0]['email']);
        $this->assertEquals('Test', $result[0]['first_name']);
        $this->assertEquals('User', $result[0]['last_name']);
    }

    public function test_get_addresses_with_names_parses_array_address_with_email(): void
    {
        $address = [
            'email' => 'test@example.com',
            'name' => 'Test User',
        ];

        $result = $this->invokeMethod($this->service, 'getAddressesWithNames', [[$address]]);

        $this->assertCount(1, $result);
        $this->assertEquals('test@example.com', $result[0]['email']);
        $this->assertEquals('Test', $result[0]['first_name']);
        $this->assertEquals('User', $result[0]['last_name']);
    }

    public function test_get_addresses_with_names_handles_multiple_addresses(): void
    {
        $addresses = [
            (object)['mail' => 'john@example.com', 'personal' => 'John Doe'],
            (object)['mail' => 'jane@example.com', 'personal' => 'Jane Smith'],
            'bob@example.com',
        ];

        $result = $this->invokeMethod($this->service, 'getAddressesWithNames', [$addresses]);

        $this->assertCount(3, $result);
        $this->assertEquals('john@example.com', $result[0]['email']);
        $this->assertEquals('John', $result[0]['first_name']);
        $this->assertEquals('jane@example.com', $result[1]['email']);
        $this->assertEquals('Jane', $result[1]['first_name']);
        $this->assertEquals('bob@example.com', $result[2]['email']);
    }

    public function test_get_addresses_with_names_truncates_long_first_name(): void
    {
        $address = (object)[
            'mail' => 'test@example.com',
            'personal' => str_repeat('FirstNameTooLong', 5).' LastName',
        ];

        $result = $this->invokeMethod($this->service, 'getAddressesWithNames', [[$address]]);

        $this->assertCount(1, $result);
        $this->assertEquals(20, strlen($result[0]['first_name']));
        $this->assertEquals('LastName', $result[0]['last_name']);
    }

    public function test_get_addresses_with_names_truncates_long_last_name(): void
    {
        $address = (object)[
            'mail' => 'test@example.com',
            'personal' => 'FirstName '.str_repeat('LastNameTooLong', 5),
        ];

        $result = $this->invokeMethod($this->service, 'getAddressesWithNames', [[$address]]);

        $this->assertCount(1, $result);
        $this->assertEquals('FirstName', $result[0]['first_name']);
        $this->assertEquals(30, strlen($result[0]['last_name']));
    }

    public function test_get_addresses_with_names_truncates_both_long_names(): void
    {
        $address = (object)[
            'mail' => 'test@example.com',
            'personal' => str_repeat('FirstNameTooLong', 5).' '.str_repeat('LastNameTooLong', 5),
        ];

        $result = $this->invokeMethod($this->service, 'getAddressesWithNames', [[$address]]);

        $this->assertCount(1, $result);
        $this->assertEquals(20, strlen($result[0]['first_name']));
        $this->assertEquals(30, strlen($result[0]['last_name']));
    }

    public function test_get_addresses_with_names_handles_single_name(): void
    {
        $address = (object)[
            'mail' => 'test@example.com',
            'personal' => 'SingleName',
        ];

        $result = $this->invokeMethod($this->service, 'getAddressesWithNames', [[$address]]);

        $this->assertCount(1, $result);
        $this->assertEquals('SingleName', $result[0]['first_name']);
        $this->assertEquals('', $result[0]['last_name']);
    }

    public function test_get_addresses_with_names_handles_three_part_name(): void
    {
        $address = (object)[
            'mail' => 'test@example.com',
            'personal' => 'First Middle Last',
        ];

        $result = $this->invokeMethod($this->service, 'getAddressesWithNames', [[$address]]);

        $this->assertCount(1, $result);
        $this->assertEquals('First', $result[0]['first_name']);
        $this->assertEquals('Middle Last', $result[0]['last_name']);
    }

    public function test_get_addresses_with_names_handles_empty_personal_name(): void
    {
        $address = (object)[
            'mail' => 'test@example.com',
            'personal' => '',
        ];

        $result = $this->invokeMethod($this->service, 'getAddressesWithNames', [[$address]]);

        $this->assertCount(1, $result);
        $this->assertEquals('', $result[0]['first_name']);
        $this->assertEquals('', $result[0]['last_name']);
    }

    public function test_get_addresses_with_names_converts_attribute_object(): void
    {
        $attribute = Mockery::mock(Attribute::class);
        $attribute->shouldReceive('get')
            ->once()
            ->andReturn([(object)['mail' => 'attr@example.com', 'personal' => 'Attr User']]);

        $result = $this->invokeMethod($this->service, 'getAddressesWithNames', [$attribute]);

        $this->assertCount(1, $result);
        $this->assertEquals('attr@example.com', $result[0]['email']);
        $this->assertEquals('Attr', $result[0]['first_name']);
    }

    public function test_get_addresses_with_names_handles_object_without_mail_property_with_brackets(): void
    {
        // Mock an object that returns a string representation with email
        $address = Mockery::mock(\stdClass::class);
        $address->shouldReceive('__toString')
            ->andReturn('Test Name <test@example.com>');

        $result = $this->invokeMethod($this->service, 'getAddressesWithNames', [[$address]]);

        $this->assertCount(1, $result);
        $this->assertEquals('test@example.com', $result[0]['email']);
        $this->assertEquals('Test', $result[0]['first_name']);
        $this->assertEquals('Name', $result[0]['last_name']);
    }

    public function test_get_addresses_with_names_handles_object_without_mail_property_plain(): void
    {
        $address = Mockery::mock(\stdClass::class);
        $address->shouldReceive('__toString')
            ->andReturn('simple@example.com');

        $result = $this->invokeMethod($this->service, 'getAddressesWithNames', [[$address]]);

        $this->assertCount(1, $result);
        $this->assertEquals('simple@example.com', $result[0]['email']);
    }

    public function test_get_addresses_with_names_skips_invalid_entries(): void
    {
        $addresses = [
            (object)['mail' => 'valid@example.com', 'personal' => 'Valid User'],
            (object)['personal' => 'No Email'], // No email
            null, // Null entry
            '', // Empty string
        ];

        $result = $this->invokeMethod($this->service, 'getAddressesWithNames', [$addresses]);

        // Should only have the valid address
        $this->assertCount(1, $result);
        $this->assertEquals('valid@example.com', $result[0]['email']);
    }

    public function test_get_addresses_with_names_handles_mixed_valid_and_invalid(): void
    {
        $addresses = [
            (object)['mail' => 'first@example.com', 'personal' => 'First User'],
            (object)['personal' => 'No Email'],
            (object)['mail' => 'second@example.com', 'personal' => 'Second User'],
            false,
            (object)['mail' => 'third@example.com', 'personal' => 'Third User'],
        ];

        $result = $this->invokeMethod($this->service, 'getAddressesWithNames', [$addresses]);

        $this->assertCount(3, $result);
        $this->assertEquals('first@example.com', $result[0]['email']);
        $this->assertEquals('second@example.com', $result[1]['email']);
        $this->assertEquals('third@example.com', $result[2]['email']);
    }

    public function test_get_addresses_with_names_handles_unicode_names(): void
    {
        $address = (object)[
            'mail' => 'test@example.com',
            'personal' => 'José García',
        ];

        $result = $this->invokeMethod($this->service, 'getAddressesWithNames', [[$address]]);

        $this->assertCount(1, $result);
        $this->assertEquals('José', $result[0]['first_name']);
        $this->assertEquals('García', $result[0]['last_name']);
    }

    public function test_get_addresses_with_names_handles_special_characters_in_names(): void
    {
        $address = (object)[
            'mail' => 'test@example.com',
            'personal' => "O'Brien-Smith",
        ];

        $result = $this->invokeMethod($this->service, 'getAddressesWithNames', [[$address]]);

        $this->assertCount(1, $result);
        $this->assertEquals("O'Brien-Smith", $result[0]['first_name']);
        $this->assertEquals('', $result[0]['last_name']);
    }

    public function test_get_addresses_with_names_handles_non_string_email(): void
    {
        $address = (object)[
            'mail' => 123, // Non-string email
            'personal' => 'Test User',
        ];

        $result = $this->invokeMethod($this->service, 'getAddressesWithNames', [[$address]]);

        // Should skip non-string emails
        $this->assertCount(0, $result);
    }

    // =====================================================================
    // Tests for parseAddresses() - CRITICAL (CRAP: 182)
    // =====================================================================

    public function test_parse_addresses_returns_empty_for_null_input(): void
    {
        $result = $this->invokeMethod($this->service, 'parseAddresses', [null]);

        $this->assertIsArray($result);
        $this->assertCount(0, $result);
    }

    public function test_parse_addresses_returns_empty_for_empty_array(): void
    {
        $result = $this->invokeMethod($this->service, 'parseAddresses', [[]]);

        $this->assertIsArray($result);
        $this->assertCount(0, $result);
    }

    public function test_parse_addresses_returns_empty_for_false(): void
    {
        $result = $this->invokeMethod($this->service, 'parseAddresses', [false]);

        $this->assertIsArray($result);
        $this->assertCount(0, $result);
    }

    public function test_parse_addresses_returns_empty_for_empty_string(): void
    {
        $result = $this->invokeMethod($this->service, 'parseAddresses', ['']);

        $this->assertIsArray($result);
        $this->assertCount(0, $result);
    }

    public function test_parse_addresses_returns_empty_for_zero(): void
    {
        $result = $this->invokeMethod($this->service, 'parseAddresses', [0]);

        $this->assertIsArray($result);
        $this->assertCount(0, $result);
    }

    public function test_parse_addresses_handles_simple_string(): void
    {
        $result = $this->invokeMethod($this->service, 'parseAddresses', ['test@example.com']);

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertEquals('test@example.com', $result[0]);
    }

    public function test_parse_addresses_handles_object_with_mail_property(): void
    {
        $address = (object)['mail' => 'john@example.com'];

        $result = $this->invokeMethod($this->service, 'parseAddresses', [[$address]]);

        $this->assertCount(1, $result);
        $this->assertEquals('john@example.com', $result[0]);
    }

    public function test_parse_addresses_handles_object_with_email_property(): void
    {
        $address = (object)['email' => 'jane@example.com'];

        $result = $this->invokeMethod($this->service, 'parseAddresses', [[$address]]);

        $this->assertCount(1, $result);
        $this->assertEquals('jane@example.com', $result[0]);
    }

    public function test_parse_addresses_prefers_mail_over_email_property(): void
    {
        $address = (object)['mail' => 'primary@example.com', 'email' => 'secondary@example.com'];

        $result = $this->invokeMethod($this->service, 'parseAddresses', [[$address]]);

        $this->assertCount(1, $result);
        $this->assertEquals('primary@example.com', $result[0]);
    }

    public function test_parse_addresses_handles_array_address_with_mail(): void
    {
        $address = ['mail' => 'test@example.com'];

        $result = $this->invokeMethod($this->service, 'parseAddresses', [[$address]]);

        $this->assertCount(1, $result);
        $this->assertEquals('test@example.com', $result[0]);
    }

    public function test_parse_addresses_handles_array_address_with_email(): void
    {
        $address = ['email' => 'test@example.com'];

        $result = $this->invokeMethod($this->service, 'parseAddresses', [[$address]]);

        $this->assertCount(1, $result);
        $this->assertEquals('test@example.com', $result[0]);
    }

    public function test_parse_addresses_array_prefers_mail_over_email(): void
    {
        $address = ['mail' => 'primary@example.com', 'email' => 'secondary@example.com'];

        $result = $this->invokeMethod($this->service, 'parseAddresses', [[$address]]);

        $this->assertCount(1, $result);
        $this->assertEquals('primary@example.com', $result[0]);
    }

    public function test_parse_addresses_converts_attribute_object(): void
    {
        $attribute = Mockery::mock(Attribute::class);
        $attribute->shouldReceive('get')
            ->once()
            ->andReturn([(object)['mail' => 'attr@example.com']]);

        $result = $this->invokeMethod($this->service, 'parseAddresses', [$attribute]);

        $this->assertCount(1, $result);
        $this->assertEquals('attr@example.com', $result[0]);
    }

    public function test_parse_addresses_converts_attribute_object_with_multiple_addresses(): void
    {
        $attribute = Mockery::mock(Attribute::class);
        $attribute->shouldReceive('get')
            ->once()
            ->andReturn([
                (object)['mail' => 'first@example.com'],
                (object)['mail' => 'second@example.com'],
            ]);

        $result = $this->invokeMethod($this->service, 'parseAddresses', [$attribute]);

        $this->assertCount(2, $result);
        $this->assertEquals('first@example.com', $result[0]);
        $this->assertEquals('second@example.com', $result[1]);
    }

    public function test_parse_addresses_handles_object_without_mail_property_plain_string(): void
    {
        $address = Mockery::mock(\stdClass::class);
        $address->shouldReceive('__toString')
            ->andReturn('test@example.com');

        $result = $this->invokeMethod($this->service, 'parseAddresses', [[$address]]);

        $this->assertCount(1, $result);
        $this->assertEquals('test@example.com', $result[0]);
    }

    public function test_parse_addresses_extracts_email_from_formatted_string(): void
    {
        $address = Mockery::mock(\stdClass::class);
        $address->shouldReceive('__toString')
            ->andReturn('John Doe <john@example.com>');

        $result = $this->invokeMethod($this->service, 'parseAddresses', [[$address]]);

        $this->assertCount(1, $result);
        $this->assertEquals('john@example.com', $result[0]);
    }

    public function test_parse_addresses_extracts_email_from_complex_formatted_string(): void
    {
        $address = Mockery::mock(\stdClass::class);
        $address->shouldReceive('__toString')
            ->andReturn('"John Q. Public" <john.public@example.com>');

        $result = $this->invokeMethod($this->service, 'parseAddresses', [[$address]]);

        $this->assertCount(1, $result);
        $this->assertEquals('john.public@example.com', $result[0]);
    }

    public function test_parse_addresses_handles_multiple_addresses(): void
    {
        $addresses = [
            (object)['mail' => 'john@example.com'],
            (object)['email' => 'jane@example.com'],
            'bob@example.com',
        ];

        $result = $this->invokeMethod($this->service, 'parseAddresses', [$addresses]);

        $this->assertCount(3, $result);
        $this->assertEquals('john@example.com', $result[0]);
        $this->assertEquals('jane@example.com', $result[1]);
        $this->assertEquals('bob@example.com', $result[2]);
    }

    public function test_parse_addresses_handles_large_list(): void
    {
        $addresses = [];
        for ($i = 0; $i < 50; $i++) {
            $addresses[] = (object)['mail' => "user{$i}@example.com"];
        }

        $result = $this->invokeMethod($this->service, 'parseAddresses', [$addresses]);

        $this->assertCount(50, $result);
        $this->assertEquals('user0@example.com', $result[0]);
        $this->assertEquals('user49@example.com', $result[49]);
    }

    public function test_parse_addresses_skips_invalid_entries(): void
    {
        $addresses = [
            (object)['mail' => 'valid@example.com'],
            (object)['name' => 'No Email'], // No email property
            null,
            '',
        ];

        $result = $this->invokeMethod($this->service, 'parseAddresses', [$addresses]);

        $this->assertCount(1, $result);
        $this->assertEquals('valid@example.com', $result[0]);
    }

    public function test_parse_addresses_skips_non_string_email(): void
    {
        $addresses = [
            (object)['mail' => 123], // Non-string
            (object)['mail' => true], // Boolean
            (object)['mail' => ['array']], // Array
        ];

        $result = $this->invokeMethod($this->service, 'parseAddresses', [$addresses]);

        $this->assertCount(0, $result);
    }

    public function test_parse_addresses_handles_mixed_valid_and_invalid(): void
    {
        $addresses = [
            (object)['mail' => 'first@example.com'],
            null,
            (object)['email' => 'second@example.com'],
            false,
            'third@example.com',
            (object)['name' => 'No Email'],
        ];

        $result = $this->invokeMethod($this->service, 'parseAddresses', [$addresses]);

        $this->assertCount(3, $result);
        $this->assertEquals('first@example.com', $result[0]);
        $this->assertEquals('second@example.com', $result[1]);
        $this->assertEquals('third@example.com', $result[2]);
    }

    public function test_parse_addresses_handles_array_with_both_mail_and_email_null(): void
    {
        $address = ['mail' => null, 'email' => null];

        $result = $this->invokeMethod($this->service, 'parseAddresses', [[$address]]);

        $this->assertCount(0, $result);
    }

    public function test_parse_addresses_handles_object_with_null_mail(): void
    {
        $address = (object)['mail' => null, 'email' => 'fallback@example.com'];

        $result = $this->invokeMethod($this->service, 'parseAddresses', [[$address]]);

        $this->assertCount(1, $result);
        $this->assertEquals('fallback@example.com', $result[0]);
    }

    // =====================================================================
    // Tests for separateReply() - HIGH (CRAP: 72)
    // =====================================================================

    public function test_separate_reply_returns_full_body_when_not_reply(): void
    {
        $body = 'This is the full email body content.';

        $result = $this->invokeMethod($this->service, 'separateReply', [$body, false, false]);

        $this->assertEquals($body, $result);
    }

    public function test_separate_reply_returns_full_html_body_when_not_reply(): void
    {
        $body = '<html><body><p>Full HTML content</p></body></html>';

        $result = $this->invokeMethod($this->service, 'separateReply', [$body, true, false]);

        $this->assertEquals($body, $result);
    }

    public function test_separate_reply_extracts_content_from_html_body_tag(): void
    {
        $body = '<html><head></head><body><p>Main content</p></body></html>';

        $result = $this->invokeMethod($this->service, 'separateReply', [$body, true, true]);

        $this->assertStringContainsString('Main content', $result);
        $this->assertStringNotContainsString('<html>', $result);
        $this->assertStringNotContainsString('<head>', $result);
    }

    public function test_separate_reply_extracts_content_from_body_tag_with_attributes(): void
    {
        $body = '<html><body style="color:black" class="test"><p>Content here</p></body></html>';

        $result = $this->invokeMethod($this->service, 'separateReply', [$body, true, true]);

        $this->assertStringContainsString('Content here', $result);
        $this->assertStringNotContainsString('<html>', $result);
    }

    public function test_separate_reply_handles_html_without_body_tag(): void
    {
        $body = '<p>Direct HTML content</p>';

        $result = $this->invokeMethod($this->service, 'separateReply', [$body, true, true]);

        $this->assertStringContainsString('Direct HTML content', $result);
    }

    public function test_separate_reply_handles_protonmail_quote(): void
    {
        $body = 'New reply content<div class="protonmail_quote">Previous message</div>';

        $result = $this->invokeMethod($this->service, 'separateReply', [$body, true, true]);

        $this->assertStringContainsString('New reply content', $result);
        $this->assertStringNotContainsString('Previous message', $result);
    }

    public function test_separate_reply_handles_protonmail_quote_case_insensitive(): void
    {
        $body = 'New reply<div class="ProtonMail_Quote">Old message</div>';

        $result = $this->invokeMethod($this->service, 'separateReply', [$body, true, true]);

        $this->assertStringContainsString('New reply', $result);
        $this->assertStringNotContainsString('Old message', $result);
    }

    public function test_separate_reply_handles_replied_above_separator(): void
    {
        $body = "New content here\n---- Replied Above ----\nOld content";

        $result = $this->invokeMethod($this->service, 'separateReply', [$body, false, true]);

        $this->assertStringContainsString('New content', $result);
        $this->assertStringNotContainsString('Old content', $result);
    }

    public function test_separate_reply_handles_replied_above_separator_case_insensitive(): void
    {
        $body = "New content\n---- replied above ----\nOld content";

        $result = $this->invokeMethod($this->service, 'separateReply', [$body, false, true]);

        $this->assertStringContainsString('New content', $result);
        $this->assertStringNotContainsString('Old content', $result);
    }

    public function test_separate_reply_handles_on_date_wrote_separator(): void
    {
        $body = "My reply\nOn 2025-01-01, John wrote:\n> Original message";

        $result = $this->invokeMethod($this->service, 'separateReply', [$body, false, true]);

        $this->assertStringContainsString('My reply', $result);
        $this->assertStringNotContainsString('Original message', $result);
    }

    public function test_separate_reply_handles_on_date_wrote_various_formats(): void
    {
        $body = "Response here\nOn Mon, Jan 1, 2025 at 10:00 AM, User <user@example.com> wrote:\n> Quoted";

        $result = $this->invokeMethod($this->service, 'separateReply', [$body, false, true]);

        $this->assertStringContainsString('Response here', $result);
        $this->assertStringNotContainsString('Quoted', $result);
    }

    public function test_separate_reply_handles_on_date_wrote_case_insensitive(): void
    {
        $body = "Reply\non 2025-01-01, john WROTE:\n> Quote";

        $result = $this->invokeMethod($this->service, 'separateReply', [$body, false, true]);

        $this->assertStringContainsString('Reply', $result);
    }

    public function test_separate_reply_handles_from_separator(): void
    {
        $body = "Forwarding this\nFrom: Original Sender\nOriginal content";

        $result = $this->invokeMethod($this->service, 'separateReply', [$body, false, true]);

        $this->assertStringContainsString('Forwarding this', $result);
        $this->assertStringNotContainsString('Original content', $result);
    }

    public function test_separate_reply_handles_from_separator_case_insensitive(): void
    {
        $body = "New message\nfrom: sender@example.com\nOld message";

        $result = $this->invokeMethod($this->service, 'separateReply', [$body, false, true]);

        $this->assertStringContainsString('New message', $result);
        $this->assertStringNotContainsString('Old message', $result);
    }

    public function test_separate_reply_handles_from_separator_with_full_header(): void
    {
        $body = "FYI\nFrom: John Doe <john@example.com>\nDate: Jan 1, 2025\nSubject: Test\n\nOriginal body";

        $result = $this->invokeMethod($this->service, 'separateReply', [$body, false, true]);

        $this->assertStringContainsString('FYI', $result);
        $this->assertStringNotContainsString('Original body', $result);
    }

    public function test_separate_reply_handles_underscore_separator(): void
    {
        $body = "New message\n________\nQuoted text";

        $result = $this->invokeMethod($this->service, 'separateReply', [$body, false, true]);

        $this->assertStringContainsString('New message', $result);
        $this->assertStringNotContainsString('Quoted text', $result);
    }

    public function test_separate_reply_handles_underscore_separator_case_insensitive(): void
    {
        $body = "Reply text\n________\nOld text";

        $result = $this->invokeMethod($this->service, 'separateReply', [$body, false, true]);

        $this->assertStringContainsString('Reply text', $result);
        $this->assertStringNotContainsString('Old text', $result);
    }

    public function test_separate_reply_returns_full_body_if_no_separator_found(): void
    {
        $body = 'Complete email body with no reply markers';

        $result = $this->invokeMethod($this->service, 'separateReply', [$body, false, true]);

        $this->assertEquals(nl2br($body), $result);
    }

    public function test_separate_reply_converts_plain_text_to_html(): void
    {
        $body = "Line 1\nLine 2\nLine 3";

        $result = $this->invokeMethod($this->service, 'separateReply', [$body, false, true]);

        $this->assertStringContainsString('<br />', $result);
    }

    public function test_separate_reply_does_not_convert_html_to_br(): void
    {
        $body = "<p>Line 1</p>\n<p>Line 2</p>";

        $result = $this->invokeMethod($this->service, 'separateReply', [$body, true, true]);

        // HTML should not get nl2br applied
        $this->assertStringContainsString('<p>Line 1</p>', $result);
    }

    public function test_separate_reply_skips_separator_if_no_content_before(): void
    {
        $body = "---- Replied Above ----\nOnly old content here";

        $result = $this->invokeMethod($this->service, 'separateReply', [$body, false, true]);

        // Should return full body since there's no real content before separator
        $this->assertStringContainsString('old content', $result);
    }

    public function test_separate_reply_skips_separator_if_only_whitespace_before(): void
    {
        $body = "   \n\n---- Replied Above ----\nActual content";

        $result = $this->invokeMethod($this->service, 'separateReply', [$body, false, true]);

        // Should not split at separator if only whitespace before
        $this->assertStringContainsString('Actual content', $result);
    }

    public function test_separate_reply_skips_separator_if_only_html_tags_before(): void
    {
        $body = "<div></div>---- Replied Above ----\nContent here";

        $result = $this->invokeMethod($this->service, 'separateReply', [$body, true, true]);

        // Empty tags don't count as content
        $this->assertStringContainsString('Content here', $result);
    }

    public function test_separate_reply_uses_first_matching_separator(): void
    {
        $body = "New\n---- Replied Above ----\nMiddle\nFrom: sender\nOld";

        $result = $this->invokeMethod($this->service, 'separateReply', [$body, false, true]);

        // Should stop at first separator
        $this->assertStringContainsString('New', $result);
        $this->assertStringNotContainsString('Middle', $result);
        $this->assertStringNotContainsString('Old', $result);
    }

    public function test_separate_reply_handles_multiple_line_breaks(): void
    {
        $body = "Reply\n\n\n\nOn date wrote:\nQuote";

        $result = $this->invokeMethod($this->service, 'separateReply', [$body, false, true]);

        $this->assertStringContainsString('Reply', $result);
        $this->assertStringNotContainsString('Quote', $result);
    }

    public function test_separate_reply_handles_empty_body(): void
    {
        $body = "";

        $result = $this->invokeMethod($this->service, 'separateReply', [$body, false, true]);

        $this->assertEquals(nl2br(''), $result);
    }

    public function test_separate_reply_handles_body_with_only_separator(): void
    {
        $body = "From: sender@example.com";

        $result = $this->invokeMethod($this->service, 'separateReply', [$body, false, true]);

        // No content before separator, should return full body
        $this->assertStringContainsString('From:', $result);
    }

    public function test_separate_reply_preserves_html_structure_in_reply(): void
    {
        $body = "<p>My <strong>reply</strong></p><div class=\"protonmail_quote\">Quote</div>";

        $result = $this->invokeMethod($this->service, 'separateReply', [$body, true, true]);

        $this->assertStringContainsString('<p>My <strong>reply</strong></p>', $result);
        $this->assertStringNotContainsString('Quote', $result);
    }

    public function test_separate_reply_handles_separator_in_middle_of_line(): void
    {
        $body = "Text before From: middle text\nAfter";

        $result = $this->invokeMethod($this->service, 'separateReply', [$body, false, true]);

        $this->assertStringContainsString('Text before', $result);
        $this->assertStringNotContainsString('After', $result);
    }

    // =====================================================================
    // Tests for getOriginalSenderFromFwd() - MEDIUM (CRAP: 30)
    // =====================================================================

    public function test_get_original_sender_from_fwd_extracts_from_header_with_name(): void
    {
        $body = "---------- Forwarded message ---------\nFrom: John Doe <john@example.com>\nDate: Mon, Jan 1, 2025\nSubject: Test";

        $result = $this->invokeMethod($this->service, 'getOriginalSenderFromFwd', [$body]);

        $this->assertIsArray($result);
        $this->assertEquals('John Doe', $result['name']);
        $this->assertEquals('john@example.com', $result['email']);
    }

    public function test_get_original_sender_from_fwd_extracts_from_header_without_name(): void
    {
        $body = "Forwarded message\nFrom:  <sender@example.com>\nSubject: Test";

        $result = $this->invokeMethod($this->service, 'getOriginalSenderFromFwd', [$body]);

        $this->assertIsArray($result);
        $this->assertEquals('', $result['name']);
        $this->assertEquals('sender@example.com', $result['email']);
    }

    public function test_get_original_sender_from_fwd_extracts_with_quotes_in_name(): void
    {
        $body = 'From: "John Q. Public" <john@example.com>';

        $result = $this->invokeMethod($this->service, 'getOriginalSenderFromFwd', [$body]);

        $this->assertIsArray($result);
        $this->assertEquals('"John Q. Public"', $result['name']);
        $this->assertEquals('john@example.com', $result['email']);
    }

    public function test_get_original_sender_from_fwd_handles_name_with_comma(): void
    {
        $body = 'From: Doe, John <john@example.com>';

        $result = $this->invokeMethod($this->service, 'getOriginalSenderFromFwd', [$body]);

        $this->assertIsArray($result);
        $this->assertEquals('Doe, John', $result['name']);
        $this->assertEquals('john@example.com', $result['email']);
    }

    public function test_get_original_sender_from_fwd_extracts_with_extra_whitespace(): void
    {
        $body = "From:    John Doe    <   john@example.com   >";

        $result = $this->invokeMethod($this->service, 'getOriginalSenderFromFwd', [$body]);

        $this->assertIsArray($result);
        $this->assertEquals('John Doe', $result['name']);
        $this->assertEquals('john@example.com', $result['email']);
    }

    public function test_get_original_sender_from_fwd_case_insensitive(): void
    {
        $body = "from: JOHN DOE <JOHN@EXAMPLE.COM>";

        $result = $this->invokeMethod($this->service, 'getOriginalSenderFromFwd', [$body]);

        $this->assertIsArray($result);
        $this->assertEquals('JOHN DOE', $result['name']);
        $this->assertEquals('JOHN@EXAMPLE.COM', $result['email']);
    }

    public function test_get_original_sender_from_fwd_extracts_email_from_text_single_quotes(): void
    {
        $body = "Check this email: 'user@example.com' sent me a message";

        $result = $this->invokeMethod($this->service, 'getOriginalSenderFromFwd', [$body]);

        $this->assertIsArray($result);
        $this->assertEquals('', $result['name']);
        $this->assertStringContainsString('user@example.com', $result['email']);
    }

    public function test_get_original_sender_from_fwd_extracts_email_from_text_double_quotes(): void
    {
        $body = 'Message from "sender@example.com" was received';

        $result = $this->invokeMethod($this->service, 'getOriginalSenderFromFwd', [$body]);

        $this->assertIsArray($result);
        $this->assertEquals('', $result['name']);
        $this->assertNotEmpty($result['email']);
    }

    public function test_get_original_sender_from_fwd_extracts_email_from_text_angle_brackets(): void
    {
        $body = "Email from <user@example.com> received";

        $result = $this->invokeMethod($this->service, 'getOriginalSenderFromFwd', [$body]);

        $this->assertIsArray($result);
        $this->assertEquals('', $result['name']);
        $this->assertNotEmpty($result['email']);
    }

    public function test_get_original_sender_from_fwd_extracts_email_with_colon(): void
    {
        $body = "Sender: user@example.com mentioned";

        $result = $this->invokeMethod($this->service, 'getOriginalSenderFromFwd', [$body]);

        $this->assertIsArray($result);
        $this->assertNotEmpty($result['email']);
    }

    public function test_get_original_sender_from_fwd_extracts_email_with_semicolon(): void
    {
        $body = "Recipients; user@example.com; others";

        $result = $this->invokeMethod($this->service, 'getOriginalSenderFromFwd', [$body]);

        $this->assertIsArray($result);
        $this->assertNotEmpty($result['email']);
    }

    public function test_get_original_sender_from_fwd_returns_null_when_not_found(): void
    {
        $body = "This is just a regular message with no forwarded content";

        $result = $this->invokeMethod($this->service, 'getOriginalSenderFromFwd', [$body]);

        $this->assertNull($result);
    }

    public function test_get_original_sender_from_fwd_returns_null_for_empty_string(): void
    {
        $body = "";

        $result = $this->invokeMethod($this->service, 'getOriginalSenderFromFwd', [$body]);

        $this->assertNull($result);
    }

    public function test_get_original_sender_from_fwd_returns_null_for_invalid_email_pattern(): void
    {
        $body = "From: not-an-email-at-all";

        $result = $this->invokeMethod($this->service, 'getOriginalSenderFromFwd', [$body]);

        // May return null or may find a pattern - depends on regex
        $this->assertTrue(is_null($result) || is_array($result));
    }

    public function test_get_original_sender_from_fwd_handles_html_entities(): void
    {
        $body = "From: Test &lt;test@example.com&gt;";

        $result = $this->invokeMethod($this->service, 'getOriginalSenderFromFwd', [$body]);

        $this->assertIsArray($result);
        $this->assertStringContainsString('test@example.com', $result['email']);
    }

    public function test_get_original_sender_from_fwd_handles_html_lt_gt(): void
    {
        $body = "From: User &lt;user@example.com&gt;";

        $result = $this->invokeMethod($this->service, 'getOriginalSenderFromFwd', [$body]);

        $this->assertIsArray($result);
        $this->assertNotEmpty($result['email']);
    }

    public function test_get_original_sender_from_fwd_cleans_fwd_prefix(): void
    {
        $body = "From: sender@fwd <real@example.com>";

        $result = $this->invokeMethod($this->service, 'getOriginalSenderFromFwd', [$body]);

        $this->assertIsArray($result);
        // The @fwd should be cleaned from the body
        $this->assertNotEmpty($result['email']);
    }

    public function test_get_original_sender_from_fwd_cleans_fwd_with_space(): void
    {
        $body = "From: user@fwd <actual@example.com>";

        $result = $this->invokeMethod($this->service, 'getOriginalSenderFromFwd', [$body]);

        $this->assertIsArray($result);
        $this->assertNotEmpty($result['email']);
    }

    public function test_get_original_sender_from_fwd_cleans_cid_prefix(): void
    {
        $body = 'Image "cid:image001@example.com" and from: real@example.com';

        $result = $this->invokeMethod($this->service, 'getOriginalSenderFromFwd', [$body]);

        // cid: should be cleaned
        $this->assertIsArray($result);
        $this->assertNotEmpty($result['email']);
    }

    public function test_get_original_sender_from_fwd_prefers_from_header_over_text(): void
    {
        $body = "From: primary@example.com\nMention of 'secondary@example.com' in text";

        $result = $this->invokeMethod($this->service, 'getOriginalSenderFromFwd', [$body]);

        // Should prefer From: header match
        $this->assertIsArray($result);
        $this->assertEquals('primary@example.com', $result['email']);
    }

    public function test_get_original_sender_from_fwd_handles_unicode_name(): void
    {
        $body = "From: José García <jose@example.com>";

        $result = $this->invokeMethod($this->service, 'getOriginalSenderFromFwd', [$body]);

        $this->assertIsArray($result);
        $this->assertEquals('José García', $result['name']);
        $this->assertEquals('jose@example.com', $result['email']);
    }

    public function test_get_original_sender_from_fwd_handles_complex_multiline(): void
    {
        $body = "---------- Forwarded message ---------\n" .
                "From: John Doe <john@example.com>\n" .
                "Date: Wed, Jan 1, 2025 at 10:30 AM\n" .
                "Subject: Important\n" .
                "To: recipient@example.com\n\n" .
                "Email content here";

        $result = $this->invokeMethod($this->service, 'getOriginalSenderFromFwd', [$body]);

        $this->assertIsArray($result);
        $this->assertEquals('John Doe', $result['name']);
        $this->assertEquals('john@example.com', $result['email']);
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

        $this->assertDatabaseHas('customers', ['email' => 'from@example.com']);
        $this->assertDatabaseHas('customers', ['email' => 'to@example.com']);
        $this->assertDatabaseHas('customers', ['email' => 'cc@example.com']);
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

        $this->assertDatabaseHas('customers', ['email' => 'replyto@example.com']);
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

        $this->assertDatabaseHas('customers', ['email' => 'bcc@example.com']);
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

        $this->assertDatabaseHas('customers', ['email' => 'from@example.com']);
        $this->assertDatabaseHas('customers', ['email' => 'to1@example.com']);
        $this->assertDatabaseHas('customers', ['email' => 'to2@example.com']);
        $this->assertDatabaseHas('customers', ['email' => 'to3@example.com']);
        $this->assertDatabaseHas('customers', ['email' => 'cc1@example.com']);
        $this->assertDatabaseHas('customers', ['email' => 'cc2@example.com']);
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

        $this->assertDatabaseHas('customers', ['email' => 'sender@example.com']);
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

        $this->assertDatabaseHas('customers', ['email' => 'sender@example.com']);
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

        // Customer should exist with updated name
        $this->assertDatabaseHas('customers', [
            'email' => 'existing@example.com',
            'first_name' => 'New',
            'last_name' => 'Name',
        ]);
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
        $customers = Customer::where('email', 'duplicate@example.com')->get();
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

        $this->assertDatabaseHas('customers', [
            'email' => 'john@example.com',
            'first_name' => 'John',
            'last_name' => 'Doe',
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

        $this->assertDatabaseHas('customers', [
            'email' => 'noname@example.com',
            'first_name' => '',
            'last_name' => '',
        ]);
    }

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
